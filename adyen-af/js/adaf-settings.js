  /* Saves/updates plugin settings via Ajax */
  jQuery(document).ready(function ($) {

     $('#adaf_save_settings').click(function (e) {
        e.preventDefault();

        var adaf = {
           'action': 'adaf_save_settings',
           'save_settings': 'yes',
           'adaf_rc_site_key': $('#adaf_rc_site_key').val(),
           'adaf_rc_secret_key': $('#adaf_rc_secret_key').val(),
           'adaf_max_retries': $('#adaf_max_retries').val()
        };

        $.post(ajaxurl, adaf, function (response) {
           window.alert(response);
           window.location.reload();
        });
     });

     /* set currently specified retries value */
     var maxRetries = $('#adaf_max_retries').attr('setting');
//     console.log(maxRetries);
     $('#adaf_max_retries').val(maxRetries);

  });