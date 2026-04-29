<?php

namespace BillplzCF7\Payment;

use BillplzCF7\Helpers\EmailConfirmation;

class CallbackHandler
{
  private $helpers;

  public function __construct()
  {
    $this->helpers = \BillplzCF7\Helpers\Functions::get_instance();
  }

  public function register()
  {
    add_action("init", array($this, "redirect"));
    add_action("init", array($this, "callback"));
    add_action('bcf7_payment_success', array($this, "send_email"));
  }

  public function send_email($transactions)
  {
    $option = get_option('bcf7_email_settings');
    if (is_array($option) && isset($option['bcf7_email_permission']) && '1' == $option['bcf7_email_permission']) {
      (new EmailConfirmation())->send( $transactions );
    }
  }
  
  public function redirect()
  {
    if (! $this->is_billplz_listener()) {
      return;
    }

    $url = isset($_SERVER['QUERY_STRING']) ? html_entity_decode((string) $_SERVER['QUERY_STRING']) : '';

    parse_str($url, $query);

    if (! $this->has_redirect_payload($query)) {
      return;
    }

    $x_sign = sanitize_text_field($query['billplz']['x_signature']);
    $hash = $this->redirect_signature($query);

    if (! $this->signatures_match($hash, $x_sign)) {
      return;
    }

    $this->handle_payment_update(
      absint($query['payment-id']),
      sanitize_text_field($query['billplz']['id']),
      sanitize_text_field($query['billplz']['paid']),
      isset($query['billplz']['paid_at']) ? sanitize_text_field($query['billplz']['paid_at']) : ''
    );
  }

  public function callback()
  {
    if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST' || ! $this->is_billplz_listener()) {
      return;
    }

    $query_string = file_get_contents('php://input');

    parse_str($query_string, $query_params);

    if (! $this->has_callback_payload($query_params)) {
      return;
    }

    $payment_id = isset($_GET['payment-id']) ? absint($_GET['payment-id']) : 0;

    if (! $payment_id) {
      return;
    }

    $x_sign = sanitize_text_field($query_params['x_signature']);
    $hash = $this->callback_signature($query_params);

    if (! $this->signatures_match($hash, $x_sign)) {
      return;
    }

    if ('true' === $query_params['paid'] && ! $this->paid_amount_matches($payment_id, $query_params)) {
      return;
    }

    $this->handle_payment_update(
      $payment_id,
      sanitize_text_field($query_params['id']),
      sanitize_text_field($query_params['paid']),
      isset($query_params['paid_at']) ? sanitize_text_field($query_params['paid_at']) : ''
    );
  }

  private function paid_amount_matches($payment_id, $query_params)
  {
    $payment = $this->get_payment($payment_id);

    if (! $payment) {
      return false;
    }

    $expected_cents = (int) round(((float) $payment->amount) * 100);
    $reported_cents = isset($query_params['paid_amount']) ? (int) $query_params['paid_amount'] : 0;

    return $expected_cents > 0 && $expected_cents === $reported_cents;
  }

  private function is_billplz_listener()
  {
    return isset($_GET['bcf7-listener']) && 'billplz' === $_GET['bcf7-listener'];
  }

  private function has_redirect_payload($query)
  {
    return (
      isset($query['payment-id'], $query['billplz']) &&
      is_array($query['billplz']) &&
      ! empty($query['billplz']['id']) &&
      isset($query['billplz']['paid']) &&
      ! empty($query['billplz']['x_signature'])
    );
  }

  private function has_callback_payload($query_params)
  {
    return (
      ! empty($query_params['x_signature']) &&
      ! empty($query_params['id']) &&
      isset($query_params['paid'])
    );
  }

  private function redirect_signature($query)
  {
    unset($query['billplz']['x_signature']);
    unset($query['payment-id']);
    unset($query['bcf7-listener']);
    unset($query['page_id']);

    $parts = array();

    foreach ($query as $key => $value) {
      if (is_array($value)) {
        foreach ($value as $sub_key => $sub_val) {
          $parts[] = $key . $sub_key . $sub_val;
        }
      }
    }

    sort($parts);

    return hash_hmac('sha256', implode('|', $parts), $this->helpers->get_xsignature());
  }

  private function callback_signature($query_params)
  {
    unset($query_params['x_signature']);

    $parts = array();

    foreach ($query_params as $key => $value) {
      if (is_scalar($value)) {
        $parts[] = $key . $value;
      }
    }

    sort($parts);

    return hash_hmac('sha256', implode('|', $parts), $this->helpers->get_xsignature());
  }

  private function signatures_match($hash, $signature)
  {
    return is_string($signature) && hash_equals($hash, $signature);
  }

  private function handle_payment_update($payment_id, $transaction_id, $paid, $paid_at)
  {
    if (! $payment_id || '' === $transaction_id) {
      return false;
    }

    $payment = $this->get_payment($payment_id);

    if (! $payment) {
      return false;
    }

    global $wpdb;

    $table_name = $wpdb->prefix . "bcf7_payment";
    $bill_url = esc_url_raw($this->helpers->get_url() . "/bills/" . rawurlencode($transaction_id));

    if ('true' === $paid) {
      $updated = $wpdb->query(
        $wpdb->prepare(
          "UPDATE {$table_name} SET status = %s, transaction_id = %s, paid_at = %s, bill_url = %s WHERE id = %d AND status != %s",
          'completed',
          $transaction_id,
          $paid_at,
          $bill_url,
          $payment_id,
          'completed'
        )
      );

      if ($updated > 0) {
        do_action('bcf7_payment_success', array(
          'customer_email' => $payment->email,
          'customer_name' => $payment->name,
          'txn_id' => $transaction_id,
          'txn_date' => $paid_at,
          'txn_amount' => $payment->amount
        ));
      }

      return true;
    }

    if ('completed' === $payment->status) {
      return true;
    }

    $wpdb->update(
      $table_name,
      array(
        'transaction_id' => $transaction_id,
        'paid_at' => null,
        'bill_url' => $bill_url
      ),
      array('id' => $payment_id)
    );

    return true;
  }

  private function get_payment($payment_id)
  {
    global $wpdb;

    $table_name = $wpdb->prefix . "bcf7_payment";

    return $wpdb->get_row($wpdb->prepare("SELECT name, email, amount, status FROM {$table_name} WHERE id = %d", $payment_id));
  }
}
