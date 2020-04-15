  /*
   * Hides both Recaptcha modal AND Adyen CC payment method on checkout page
   */
  jQuery(document).ready(function ($) {
     $('#adafoverlay, #adafrcmodal').hide();
     $(document).ajaxComplete(function () {
        $('li.wc_payment_method.payment_method_sb-adyen-cc').remove();
     });
  });