<?php
/**
   * >>> REGISTER AJAX SCRIPTS FOR USE DURING FRAUD CHECK OPS
   * 
   * 1. adaf_aj handles updating of session data
   * 2. adaf_set_cookie_session_ban sets cookie for 24 hours,
   *    banning current user from using adyen cc
   *    checkout option. user is banned by user id
   *    if logged in, else is banned by ip address
   */
  add_action('wp_ajax_adaf_aj', 'adaf_aj');
  add_action('wp_ajax_nopriv_adaf_aj', 'adaf_aj');
  add_action('wp_ajax_adaf_set_cookie_session_ban', 'adaf_set_cookie_session_ban');
  add_action('wp_ajax_nopriv_adaf_set_cookie_session_ban', 'adaf_set_cookie_session_ban');
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