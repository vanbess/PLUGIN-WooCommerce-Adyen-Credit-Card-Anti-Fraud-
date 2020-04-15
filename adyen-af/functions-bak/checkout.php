<?php

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

      /* get user id if user is logged in */
      if (is_user_logged_in()):
          $userId = get_current_user_id();
      endif;

      /* get user IP */
      $userIP = $_SERVER['REMOTE_ADDR'];

      /* Perform recaptcha validation */
      if (isset($_POST['rcv2submit'])):

          /* recaptcha vars */
          $secretKey   = '6Lcy3-cUAAAAAEWOUkCIotXLqARURPyXcyX_k5xI';
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
              $_SESSION['adaf_checkout_attempts'] = 4;
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
           * cc payment option from checkout page */
          if ($checkoutAttempts && $checkoutAttempts >= 5 && !$userIdBanned && !$userIpBanned && !$userIdSession && !$userIpSession):
              
              /* cc payment method removal script */
              wp_enqueue_script('adaf_rm_cc');
          
              ?>
 
              <div id="adafoverlay"></div>

              <div id="adafrcmodal">
                <span id="adaf_modal_close" title="<?php echo __('Cancel'); ?>">x</span>

                <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                <div id="rcv2checknote" class="alert alert-info text-center"><?php echo __('Please prove that you are human:', 'woocommerce'); ?></div>
                <form id="adyrcv2" method="post" action="">
                  <div class="g-recaptcha" data-sitekey="6Lcy3-cUAAAAAL7Jo8mgABWtlR7I0TWJsGAtuKvU"></div>
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