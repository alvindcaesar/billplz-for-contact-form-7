<?php

namespace BillplzCF7\Helpers;

class Functions
{
  private static $instance = null;

  public static function get_instance()
  {
    if (self::$instance === null) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  public function general_option($key = '', $default = false)
  {
    $options = get_option('bcf7_general_settings', array());
    $value = (is_array($options) && array_key_exists($key, $options) && '' !== $options[$key]) ? $options[$key] : $default;

    return $value;
  }

  public function api_option($key = '', $default = false)
  {
    $options = get_option('bcf7_api_options', array());
    $value = (is_array($options) && array_key_exists($key, $options) && '' !== $options[$key]) ? $options[$key] : $default;

    return $value;
  }

  public function get_mode()
  {
    $mode = ("1" == $this->general_option("bcf7_mode")) ? "Test" : "Live";
    return $mode;
  }

  public function get_url()
  {
    $live    = "https://www.billplz.com";
    $sandbox = "https://www.billplz-sandbox.com";

    $url = ("Live" == $this->get_mode()) ? $live : $sandbox;

    return $url;
  }

  public function get_api_key()
  {
    $live    = base64_encode($this->api_option("bcf7_live_secret_key") . ':');
    $sandbox = base64_encode($this->api_option("bcf7_sandbox_secret_key") . ':');

    $api_key = ("Live" == $this->get_mode()) ? $live : $sandbox;

    return $api_key;
  }

  public function get_collection_id()
  {
    $live    = $this->api_option("bcf7_live_collection_id");
    $sandbox = $this->api_option("bcf7_sandbox_collection_id");

    $collection_id = ("Live" == $this->get_mode()) ? $live : $sandbox;

    return $collection_id;
  }

  public function get_xsignature()
  {
    $live    = $this->api_option("bcf7_live_xsignature_key");
    $sandbox = $this->api_option("bcf7_sandbox_xsignature_key");

    $xsignature = ("Live" == $this->get_mode()) ? $live : $sandbox;

    return $xsignature;
  }
}
