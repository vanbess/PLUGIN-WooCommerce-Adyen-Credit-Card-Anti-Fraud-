  /* show recaptcha modal */
  jQuery(document).ready(function ($) {
     $(document).ajaxComplete(function () {
        $('li.wc_payment_method.payment_method_sb-adyen-cc').remove();
     });
  });


