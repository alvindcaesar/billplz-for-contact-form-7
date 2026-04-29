<?php

namespace BillplzCF7\Settings;

class Validation
{
  private $helpers;

  public function __construct()
  {
    $this->helpers = \BillplzCF7\Helpers\Functions::get_instance();
  }

  public function register()
  {
    add_action("admin_notices", array($this, "credentials_check"));
  }

  public function credentials_check()
  {
    if (! current_user_can('manage_options')) {
      return;
    }

    $is_test_mode = ('1' === (string) $this->helpers->general_option('bcf7_mode'));

    if ($is_test_mode) {
      $missing = (
        empty($this->helpers->api_option('bcf7_sandbox_secret_key'))
        || empty($this->helpers->api_option('bcf7_sandbox_collection_id'))
        || empty($this->helpers->api_option('bcf7_sandbox_xsignature_key'))
      );
      $message = __('Billplz Sandbox credentials are not set. Enter your Secret Key, Collection ID and X-Signature Key to use the Billplz service.', BCF7_TEXT_DOMAIN);
    } else {
      $missing = (
        empty($this->helpers->api_option('bcf7_live_secret_key'))
        || empty($this->helpers->api_option('bcf7_live_collection_id'))
        || empty($this->helpers->api_option('bcf7_live_xsignature_key'))
      );
      $message = __('Billplz Live credentials are not set. Enter your Secret Key, Collection ID and X-Signature Key to use the Billplz service.', BCF7_TEXT_DOMAIN);
    }

    if (! $missing) {
      return;
    }

    $url        = admin_url('admin.php?page=billplz-cf7&tab=api-settings');
    $link_label = __('Set Credentials', BCF7_TEXT_DOMAIN);

    printf(
      '<div class="notice notice-warning"><p><strong>%1$s</strong> %2$s <a href="%3$s">%4$s</a></p></div>',
      esc_html__('Billplz for Contact Form 7 -', BCF7_TEXT_DOMAIN),
      esc_html($message),
      esc_url($url),
      esc_html($link_label)
    );
  }
}