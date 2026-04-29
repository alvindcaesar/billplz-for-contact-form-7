<?php

namespace BillplzCF7\Payment;

use BillplzCF7\Helpers\Functions;
use WP_Error;
use WPCF7_Submission;

class FormSubmission
{
  private $helpers;

  public function __construct()
  {
    $this->helpers = Functions::get_instance();
  }

  public function register()
  {
    add_action('wpcf7_before_send_mail', array($this, 'process_data'), 10, 3);
    add_action('wp_enqueue_scripts', array($this, 'scripts'));
  }

  public function scripts()
  {
    wp_enqueue_script(
      'bcf7-payment-redirect',
      BCF7_ASSETS_URL . 'js/payment-redirect.js',
      array('contact-form-7'),
      filemtime(BCF7_PLUGIN_PATH . 'assets/js/payment-redirect.js'),
      true
    );
  }

  public function process_data($contact_form, &$abort = false, $submission = null)
  {
    $id = $contact_form->id();

    if (! $submission instanceof WPCF7_Submission) {
      $submission = WPCF7_Submission::get_instance();
    }

    if (! $submission) {
      return;
    }

    if (! $this->is_payment_submission($id, $submission)) {
      return;
    }

    $form_id    = $submission->get_contact_form()->id();
    $form_title = $submission->get_contact_form()->title();
    $name       = $this->posted_string($submission, 'bcf7-name');
    $email      = $this->posted_string($submission, 'bcf7-email');
    $amount     = $this->normalize_amount($submission->get_posted_data('bcf7-amount'));
    $phone      = $this->posted_string($submission, 'bcf7-phone');

    if ('' === $name || '' === $email || $amount <= 0) {
      $this->abort_submission(
        $submission,
        $abort,
        __('Payment form fields are incomplete. Please check the payment form setup.', BCF7_TEXT_DOMAIN)
      );
      return;
    }

    $payment_id = $this->record_data($form_id, $form_title, $name, $phone, $email, $amount, '', $this->helpers->get_mode(), 'pending');

    if (! $payment_id) {
      $this->abort_submission(
        $submission,
        $abort,
        __('Unable to record the payment. Please try again later.', BCF7_TEXT_DOMAIN)
      );
      return;
    }

    $description = apply_filters('bcf7_form_description', "Payment for $form_title");
    $bill = $this->process_payment($name, $email, $phone, $amount, $description, $payment_id);

    if (is_wp_error($bill)) {
      $this->mark_payment_failed($payment_id);
      $this->abort_submission($submission, $abort, $bill->get_error_message());
      return;
    }

    $this->update_bill_data($payment_id, $bill['id'], $bill['url']);

    if (! $this->is_rest_request()) {
      wp_redirect($bill['url']);
      exit;
    }

    $submission->add_result_props(array(
      'billplz' => array(
        'payment_id'   => $payment_id,
        'bill_id'      => $bill['id'],
        'redirect_url' => $bill['url'],
      ),
    ));

    $submission->set_status('payment_required');
    $submission->set_response(__('Redirecting to Billplz...', BCF7_TEXT_DOMAIN));
    $abort = true;
  }

  public function record_data($form_id, $form_title, $name, $phone, $email, $amount, $transaction_id, $mode, $status)
  {
    global $wpdb;

    $table_name = $wpdb->prefix . "bcf7_payment";

    $wpdb->insert(
      $table_name,
      array(
        'form_id'        => $form_id,
        'form_title'     => $form_title,
        'name'           => $name,
        'phone'          => $phone,
        'amount'         => $amount,
        'transaction_id' => $transaction_id,
        'email'          => $email,
        'mode'           => $mode,
        'status'         => $status,
        'created_at'     => current_time('mysql'),
        'paid_at'        => '0000-00-00 00:00:00',
      ),
    );

    return $wpdb->insert_id;
  }

  public function update_bill_data($payment_id, $transaction_id, $bill_url)
  {
    global $wpdb;

    $table_name = $wpdb->prefix . "bcf7_payment";

    $wpdb->update(
      $table_name,
      array(
        'transaction_id' => $transaction_id,
        'bill_url' => $bill_url,
      ),
      array('ID' => $payment_id)
    );
  }

  public function mark_payment_failed($payment_id)
  {
    global $wpdb;

    $table_name = $wpdb->prefix . "bcf7_payment";

    $wpdb->update(
      $table_name,
      array('status' => 'failed'),
      array('ID' => $payment_id)
    );
  }

  public function process_payment($name, $email, $phone, $amount, $description, $payment_id)
  {
    $args = array(
      'headers' => array(
        'Authorization' => 'Basic ' . $this->helpers->get_api_key(),
      ),
      'body' => array(
        'collection_id' => $this->helpers->get_collection_id(),
        'email' => $email,
        'name' => $name,
        'amount' => (int) round($amount * 100),
        'mobile' => (isset($phone) ? $phone : ""),
        'redirect_url' => add_query_arg(array('bcf7-listener' => 'billplz', 'payment-id' => $payment_id), site_url("?page_id=" . $this->helpers->general_option('bcf7_redirect_page') . "")),
        'callback_url' => add_query_arg(array('bcf7-listener' => 'billplz', 'payment-id' => $payment_id), site_url('index.php')),
        'description' => $description
      ),
      'timeout' => 30,
    );

    $response = wp_remote_post($this->helpers->get_url() . "/api/v3/bills", $args);

    if (is_wp_error($response)) {
      return new WP_Error(
        'bcf7_billplz_request_failed',
        __('Unable to connect to Billplz. Please try again later.', BCF7_TEXT_DOMAIN)
      );
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $api_body = json_decode(wp_remote_retrieve_body($response), true);

    if ($status_code < 200 || $status_code >= 300 || ! is_array($api_body)) {
      return new WP_Error(
        'bcf7_billplz_invalid_response',
        __('Billplz could not create the payment bill. Please try again later.', BCF7_TEXT_DOMAIN)
      );
    }

    $bill_url = isset($api_body['url']) ? esc_url_raw($api_body['url']) : '';
    $bill_id = isset($api_body['id']) ? sanitize_text_field($api_body['id']) : '';

    if ('' === $bill_id || '' === $bill_url || ! $this->is_valid_billplz_url($bill_url)) {
      return new WP_Error(
        'bcf7_billplz_missing_url',
        __('Billplz returned an invalid payment URL. Please try again later.', BCF7_TEXT_DOMAIN)
      );
    }

    return array(
      'id' => $bill_id,
      'url' => $bill_url,
    );
  }

  private function abort_submission($submission, &$abort, $message)
  {
    $submission->set_status('aborted');
    $submission->set_response($message);
    $abort = true;
  }

  private function posted_string($submission, $name)
  {
    $value = $submission->get_posted_data($name);

    if (is_array($value)) {
      $value = reset($value);
    }

    return is_scalar($value) ? trim((string) $value) : '';
  }

  private function normalize_amount($value)
  {
    if (is_array($value)) {
      $value = reset($value);
    }

    if (! is_scalar($value)) {
      return 0;
    }

    $value = preg_replace('/[^0-9.]/', '', (string) $value);

    return is_numeric($value) ? (float) $value : 0;
  }

  private function is_payment_submission($form_id, $submission)
  {
    $list_of_forms = $this->helpers->general_option('bcf7_form_select', array());
    $list_of_forms = array_filter(array_map('intval', (array) $list_of_forms));

    if (in_array((int) $form_id, $list_of_forms, true)) {
      return true;
    }

    return null !== $submission->get_posted_data('bcf7-amount');
  }

  private function is_valid_billplz_url($url)
  {
    $expected = wp_parse_url($this->helpers->get_url());
    $actual = wp_parse_url($url);

    return (
      isset($expected['host'], $actual['host'], $actual['scheme']) &&
      'https' === $actual['scheme'] &&
      $expected['host'] === $actual['host']
    );
  }

  private function is_rest_request()
  {
    if (method_exists('WPCF7_Submission', 'is_restful')) {
      return WPCF7_Submission::is_restful();
    }

    return defined('REST_REQUEST') && REST_REQUEST;
  }
}
