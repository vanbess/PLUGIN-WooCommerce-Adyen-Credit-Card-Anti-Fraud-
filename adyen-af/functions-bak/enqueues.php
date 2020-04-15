<?php

  /**
   * >>> ENQUEUES/REGISTERS SCRIPTS REQUIRED AT THIS STAGE
   */
  function adaf_js_css() {
      
      /* main ajax */
      wp_enqueue_script('adaf_aj_prim', plugin_dir_url(__FILE__) . 'js/adaf-ajax-primary.js', ['jquery'], '', false);
      
      /* secondary ajax */
      wp_register_script('adaf_aj_sec', plugin_dir_url(__FILE__).'js/adaf-ajax-secondary.js', ['jquery'], '', false);
      
      /* cc input removal script */
      wp_register_script('adaf_rm_cc', plugin_dir_url(__FILE__).'js/adaf-remove-cc-method.js', ['jquery'], '', false);
      
      /* cc input and recaptcha modal removal/hide script */
      wp_register_script('adaf_rm_cc_rc', plugin_dir_url(__FILE__).'js/adaf-hide-modal-cc.js', ['jquery'], '', false);
      
      /* css front */
      wp_enqueue_style('adaf_css_front', plugin_dir_url(__FILE__) . 'css/adaf-css-front.css');
  }

  add_action('wp_enqueue_scripts', 'adaf_js_css');