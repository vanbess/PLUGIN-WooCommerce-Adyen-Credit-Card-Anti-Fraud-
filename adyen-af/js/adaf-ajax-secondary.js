  /* Secondary ajax for plugin - handles all ajax
   * tied to Recaptcha modal shown on checkout page */

  jQuery(document).ready(function ($) {

     /* get user id/user ip from recaptcha;
      * strip hidden inputs for both to prevent
      * any bypass attempts */
     var uid = $('#adafuid').val();
     var uip = $('#adafuip').val();
     $('#adafuid, #adafuip').remove();

     $('#adaf_modal_close, #adafoverlay').click(function () {

        $('#adafoverlay, #adafrcmodal').hide();

        var base_url = window.location.origin;
        var ajax_loc = '/wp-admin/admin-ajax.php';
        var ajax_url = base_url + ajax_loc;

        var rcc_data = {
           'action': 'adaf_set_cookie_session_ban',
           'user_id': uid,
           'user_ip': uip
        };

        $.post(ajax_url, rcc_data, function (response) {
           if (response == 'ban set') {
              window.location.reload();
           }
        });
     });
  });