  /* monitor unsuccessful login attempts and remove adyen cc payment method if max attempts exceeded */
  
  jQuery(document).ready(function ($) {

     /* function which runs on ajax success,
      * checks for checkout event and then
      * sets failed checkout session data
      * via ajax */
     $(document).ajaxSuccess(function (event, request, settings) {

        /* check that we're listening for a valid ajax request,
         * wc-ajax=checkout in this particular case */
        var validUrl = "wc-ajax=checkout";
        var requestUrl = settings.url;
        var validate = requestUrl.indexOf(validUrl);

        /* if our request is valid, send our own
         * ajax to update fraud check status */
        if (validate >= 0) {
           var result = request.responseJSON.result;
           if (result == 'failure') {

              var base_url = window.location.origin;
              var ajax_loc = '/wp-admin/admin-ajax.php';
              var ajax_url = base_url + ajax_loc;

              var af_data = {
                 'action': 'adaf_aj',
                 'adaf_tr_status': result
              };

              $.post(ajax_url, af_data, function (result) {
                 if (result == 5) {
                    $('li.wc_payment_method.payment_method_sb-adyen-cc').remove();
                    window.location.reload();
                 }
              });
           }
        }
     });
  });

