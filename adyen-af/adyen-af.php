<?php
  /**
   * Plugin Name: Silverback Adyen CC Anti-fraud
   * Description: Adds fraud checking functionality to Adyen credit card payment gateway
   * Author: Mr. Bessinger
   * Version: 1.0.0
   */
  /* prevent direct access */
  if (!defined('ABSPATH')):
      exit;
  endif;

  /**
   * >>> REGISTER AJAX SCRIPTS FOR USE DURING FRAUD CHECK OPS
   * 
   * 1. adaf_aj handles updating of session data
   * 2. adaf_set_cookie_session_ban sets cookie for 24 hours,
   *    banning current user from using adyen cc
   *    checkout option. user is banned by user id
   *    if logged in, else is banned by ip address
   * 3. adaf_save_settings - save plugin settings
   */
  add_action('wp_ajax_adaf_aj', 'adaf_aj');
  add_action('wp_ajax_nopriv_adaf_aj', 'adaf_aj');
  add_action('wp_ajax_adaf_set_cookie_session_ban', 'adaf_set_cookie_session_ban');
  add_action('wp_ajax_nopriv_adaf_set_cookie_session_ban', 'adaf_set_cookie_session_ban');
  add_action('wp_ajax_adaf_save_settings', 'adaf_save_settings');
  add_action('wp_ajax_nopriv_adaf_save_settings', 'adaf_save_settings');
  /**
   * >>> FUNCTION WHICH UPDATES/SAVES PLUGIN SETTINGS VIA AJAX
   */
  function adaf_save_settings() {
      if (isset($_POST['save_settings'])):

          /* settings post data */
          $rcSiteKey   = $_POST['adaf_rc_site_key'];
          $rcSecretKey = $_POST['adaf_rc_secret_key'];
          $maxRetries  = $_POST['adaf_max_retries'];

          $setting1 = get_option('adaf_rc_site_key');
          $setting2 = get_option('adaf_rc_secret_key');
          $setting3 = get_option('adaf_max_retries');

          /* update settings */
          if (!$setting1):
              $siteKeyUpdated = add_option('adaf_rc_site_key', $rcSiteKey);
          else:
              $siteKeyUpdated = update_option('adaf_rc_site_key', $rcSiteKey);
          endif;

          if (!$setting2):
              $secretKeyUpdated = add_option('adaf_rc_secret_key', $rcSecretKey);
          else:
              $secretKeyUpdated = update_option('adaf_rc_secret_key', $rcSecretKey);
          endif;

          if (!$setting3):
              $maxTriesUpdated = add_option('adaf_max_retries', $maxRetries);
          else:
              $maxTriesUpdated = update_option('adaf_max_retries', $maxRetries);
          endif;

          if ($siteKeyUpdated || $secretKeyUpdated || $maxTriesUpdated):
              echo __('Settings updated.', 'woocommerce');
          else:
              echo __('Settings not updated. Reason: no change to settings detected.', 'woocommerce');
          endif;

          wp_die();
      endif;
  }

  /**
   * >>> FUNCTION WHICH UPDATES SESSION DATA VIA AJAX
   * 
   * For each failed checkout attempt $_SESSION key
   * adaf_checkout_attempts is incremented by 1
   */
  function adaf_aj() {
      if (isset($_POST)):
          $tr_status = $_POST['adaf_tr_status'];

          if ($tr_status == 'failure'):
              $_SESSION['adaf_checkout_attempts'] += 1;
              print $_SESSION['adaf_checkout_attempts'];
          endif;

          wp_die();
      endif;
  }

  /**
   * >>> FUNCTION WHICH SETS COOKIE AND SESSION BAN DATA IF USER 
   *     CLOSES RECAPTCHA MODAL WITHOUT COMPLETING RECAPTCHA CHALLENGE
   */
  function adaf_set_cookie_session_ban() {

      /* check if user id has been set and insert cookie/session data */
      if (isset($_POST['user_id'])):
          $userId       = $_POST['user_id'];
          $userIdCookie = setcookie('adaf_ban_user_id', $userId, time() + 86400, "/");
          if ($userIdCookie):
              $_SESSION['adaf_ban_user_id'] = $userId;
          endif;
      endif;

      /* check if user ip has been set and insert cookie/session data */
      if (isset($_POST['user_ip'])):
          $userIp                       = $_POST['user_ip'];
          $userIpCookie                 = setcookie('adaf_ban_user_ip', $userIp, time() + 86400, "/");
          $_SESSION['adaf_ban_user_ip'] = $userIp;
      endif;

      /* if insertion successful, return success message to ajax call */
      if ($userIdCookie || $userIpCookie):
          echo 'ban set';
      endif;

      wp_die();
  }

  /**
   * >>> ENQUEUES/REGISTERS SCRIPTS REQUIRED AT THIS STAGE FOR FRONTEND
   */
  function adaf_js_css() {

      /* main ajax */
      wp_enqueue_script('adaf_aj_prim', plugin_dir_url(__FILE__) . 'js/adaf-ajax-primary.js', ['jquery'], '', false);

      /* secondary ajax */
      wp_register_script('adaf_aj_sec', plugin_dir_url(__FILE__) . 'js/adaf-ajax-secondary.js', ['jquery'], '', false);

      /* cc input removal script */
      wp_register_script('adaf_rm_cc', plugin_dir_url(__FILE__) . 'js/adaf-remove-cc-method.js', ['jquery'], '', false);

      /* cc input and recaptcha modal removal/hide script */
      wp_register_script('adaf_rm_cc_rc', plugin_dir_url(__FILE__) . 'js/adaf-hide-modal-cc.js', ['jquery'], '', false);


      /* css front */
      wp_enqueue_style('adaf_css_front', plugin_dir_url(__FILE__) . 'css/adaf-css-front.css');
  }

  add_action('wp_enqueue_scripts', 'adaf_js_css');
  /**
   * >>>ADMIN/SETTINGS JS AND CSS
   */
  function adaf_admin_js_css() {
      wp_register_script('adaf_settings', plugin_dir_url(__FILE__) . 'js/adaf-settings.js', ['jquery'], '', false);
      wp_enqueue_script('adaf_settings');
  }

  add_action('admin_enqueue_scripts', 'adaf_admin_js_css');
  /**
   * >>> REGISTER PLUGIN SETTINGS PAGE
   */
  function adaf_register_settings_page() {
      add_submenu_page(
      'woocommerce',
      __('Adyen Credit Card Anti-Fraud Settings', 'woocommerce'),
         __('Adyen CC Anti-Fraud', 'woocommerce'),
            'manage_options',
            'adaf-settings',
            'adaf_render_settings'
      );
  }

  /**
   * >>> SETTINGS PAGE CALLBACK
   */
  function adaf_render_settings() {
      ?>
      <div id="adaf_settings_wrap" class="wrap">
        <h1><?php _e('Adyen Credit Card Anti-Fraud Settings', 'woocommerce'); ?></h1>

        <p class="adaf-setting">
          <i><b><u>IMPORTANT:</u> Plugin will <u>not</u> work if below settings are not specified!</b></i>
        </p>

        <p class="adaf-setting">
          <label for="adaf_rc_site_key"><?php echo __('Recaptcha v2 Site Key:', 'woocommerce'); ?></label>
          <input id="adaf_rc_site_key" name="adaf_rc_site_key" type="text" value="<?php echo wp_specialchars_decode(get_option('adaf_rc_site_key')); ?>" style="min-width: 500px;">
        </p>

        <p class="adaf-setting">
          <label for="adaf_rc_secret_key"><?php echo __('Recaptcha v2 Secret Key:', 'woocommerce'); ?></label>
          <input id="adaf_rc_secret_key" name="adaf_rc_secret_key" type="text" value="<?php echo wp_specialchars_decode(get_option('adaf_rc_site_key')); ?>" style="min-width: 500px;">
        </p>

        <p class="adaf-setting">
          <label for="adaf_max_retries"><?php echo __('Maximum checkout retries you want to allow for Adyen Credit Card:', 'woocommerce'); ?></label>
          <select id="adaf_max_retries" name="adaf_max_retries" setting="<?php echo get_option('adaf_max_retries'); ?>">
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
            <option value="4">4</option>
            <option value="5">5</option>
            <option value="6">6</option>
            <option value="7">7</option>
            <option value="8">8</option>
            <option value="9">9</option>
            <option value="10">10</option>
          </select>
        </p>

        <p class="adaf-setting">
          <!-- save settings -->
          <button id="adaf_save_settings" class="button button-primary" style="font-size:16px;"><?php echo __('Save Settings', 'woocommerce'); ?></button>
        </p>
      </div>
      <?php
  }

  add_action('admin_menu', 'adaf_register_settings_page', 99);
  /**
   * >>> PERFORMS NECESSARY ANTI-FRAUD CHECKS ON CHECKOUT PAGE
   * 
   * 1.  Each failed checkout attempt sends data to adaf_aj via
   *     AJAX and updates $_SESSION key adaf_checkout_attempts
   *     by 1
   * 2.  If value of adaf_checkout_attempts == 5, checkout page
   *     is reloaded Adyen CC payment option removed (see adyen-af.js)
   * 3.  In conjunction with (2) above, recaptcha modal is shown
   *     to user on page reload with recaptcha to be completed (see adyen-af-rc.js)
   * 4.  Successful completion of recaptcha decrements value of
   *     adaf_checkout_attempts by 1 and thus shows cc payment
   *     option again
   * 5.  Recaptcha failure OR closing of recaptcha modal sets $_COOKIE
   *     adaf_ban_user_id and adaf_ban_user_ip to true, banning
   *     use of Adyen cc payment method for 24 hours
   */
  function adaf_checkout_page() {

      /* check if plugin options are set; if not, return out of function */
      if (!get_option('adaf_rc_site_key') || !get_option('adaf_rc_secret_key') || !get_option('adaf_max_retries')):
          return;
      endif;

      /* get user id if user is logged in */
      if (is_user_logged_in()):
          $userId = get_current_user_id();
      endif;

      /* get user IP */
      $userIP = $_SERVER['REMOTE_ADDR'];

      /* Perform recaptcha validation */
      if (isset($_POST['rcv2submit'])):

          /* recaptcha vars */
          $secretKey   = wp_specialchars_decode(get_option('adaf_rc_secret_key'));
          $responseKey = $_POST['g-recaptcha-response'];

          /* current url */
          $currUrl = $_POST['adaf_curr_url'];

          /* recaptcha request url and fgc */
          $url      = "https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$responseKey&remoteip=$userIP";
          $response = file_get_contents($url);

          /* recaptcha decoded response */
          $responseObj = json_decode($response);

          /* if recaptcha solved successfully, reduce adaf_checkout_attempts key value to 4, 
           * else ban use of CC payment method for 24 hours via cookie */
          if ($responseObj->success == 1):

              $max_retries = get_option('adaf_max_retries');

              $_SESSION['adaf_checkout_attempts'] = $max_retries - 1;
              ?>
              <script type="text/javascript">
                    window.location.replace('<?php echo $currUrl; ?>');
              </script>
              <?php
          else:

              /* set cookies for ban */
              $userIpCookie = setcookie('adaf_ban_user_ip', $userIP, time() + 86400, "/");

              if ($userId):
                  $userIdCookie = setcookie('adaf_ban_user_id', $userId, time() + 86400, "/");
              endif;

              /* set session keys for ban; this is done in case user tries to 
               * access checkout via private/incognito browser window, in which 
               * case no cookies for user will be picked up */
              $_SESSION['adaf_ban_user_ip'] = $userIP;
              $_SESSION['adaf_ban_user_id'] = $userId;

              /* check if ban cookies ban keys are set and reload checkout page on true */
              if ($userIdCookie || $userIpCookie):
                  ?>
                  <script type="text/javascript">
                        window.location.replace('<?php echo $currUrl; ?>');
                  </script>
                  <?php
              endif;
          endif;
      endif;

      /* if is checkout page run our fraud check code */
      if (is_checkout()):

          /* TEST */
//          echo '<pre>';
//          print_r($_COOKIE);
//          print_r($_SESSION);
//          echo '</pre>';
          /* TEST */

          /* get session checkout attempts */
          $checkoutAttempts = $_SESSION['adaf_checkout_attempts'];

          /* check for existence cookie based ban keys */
          $userIpBanned = $_COOKIE['adaf_ban_user_ip'];
          $userIdBanned = $_COOKIE['adaf_ban_user_id'];

          /* check for existence of session based ban keys */
          $userIdSession = $_SESSION['adaf_ban_user_id'];
          $userIpSession = $_SESSION['adaf_ban_user_ip'];

          /* If total failed checkout attempts are equal to or exceed
           * 5, show recaptcha challenge, else if either user id or 
           * user ip is banned via cookie or session, completely remove Adyen
           * cc payment option from checkout page and also hide recaptcha modal */
          if ($checkoutAttempts && $checkoutAttempts >= get_option('adaf_max_retries') && !$userIdBanned && !$userIpBanned && !$userIdSession && !$userIpSession):

              /* cc payment method removal script */
              wp_enqueue_script('adaf_rm_cc');
              ?>

              <div id="adafoverlay"></div>

              <div id="adafrcmodal">
                <span id="adaf_modal_close" title="<?php echo __('Cancel'); ?>">x</span>

                <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                <div id="rcv2checknote" class="alert alert-info text-center"><?php echo __('Please prove that you are human:', 'woocommerce'); ?></div>
                <form id="adyrcv2" method="post" action="">
                  <div class="g-recaptcha" data-sitekey="<?php echo wp_specialchars_decode(get_option('adaf_rc_site_key')); ?>"></div>
                  <input id="adaf_curr_url" name="adaf_curr_url" value="<?php echo get_the_permalink(get_the_ID()); ?>" type="hidden">
                  <input id="adafuid" name="adafuid" value="<?php echo $userId ?>" type="hidden">
                  <input id="adafuip" name="adafuip" value="<?php echo $userIP; ?>" type="hidden">

                  <input id="rcv2submit" class="button button-primary" name="rcv2submit" type="submit" value="<?php echo __('Submit', 'woocommerce'); ?>">
                </form>
              </div>
              <?php
              /* recaptcha modal ajax -> sets temp ban cookie and session data */
              wp_enqueue_script('adaf_aj_sec');

          elseif ($userIdBanned || $userIpBanned || $userIdSession || $userIpSession):
              /* recaptcha and cc input removal script */
              wp_enqueue_script('adaf_rm_cc_rc');
          endif;
      endif;
  }

  add_action('wp_head', 'adaf_checkout_page');
  