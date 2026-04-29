<?php

namespace BillplzCF7\Payment;

class ProcessRedirect
{

  private $helpers;

  public function __construct()
  {
    $this->helpers = \BillplzCF7\Helpers\Functions::get_instance();
  }
  
  public function register()
  {
    add_shortcode("bcf7_payment_confirmation", array($this, "redirect_callback"));
  }

  public function redirect_callback()
  {
    if (! isset($_GET['bcf7-listener']) || "billplz" !== $_GET['bcf7-listener'] || empty($_GET['payment-id'])) {
      return '';
    }

    $payment_id = absint($_GET['payment-id']);

    if (! $payment_id) {
      return '';
    }

    global $wpdb;

    $table_name = $wpdb->prefix . "bcf7_payment";
    $data = $wpdb->get_row($wpdb->prepare("SELECT name, email, transaction_id, bill_url, status FROM {$table_name} WHERE id = %d", $payment_id), ARRAY_A);

    if (! $data) {
      return '<p>' . esc_html__('Payment record not found.', BCF7_TEXT_DOMAIN) . '</p>';
    }

    $name   = $data['name'];
    $email  = $data['email'];
    $trx_id = $data['transaction_id'];
    $bill   = $data['bill_url'];
    $status = $data['status'];
    $billplz = (isset($_GET['billplz']) && is_array($_GET['billplz'])) ? $_GET['billplz'] : array();
    $is_paid_return = isset($billplz['paid']) && "true" === $billplz['paid'];

    ob_start();

    if ("completed" == $status) {
?>
        <h2>Thank you for your payment!</h2>
        <p>Payment ID: <?php echo esc_html($payment_id); ?></p>
        <p>Name: <?php echo esc_html($name); ?></p>
        <p>Email: <?php echo esc_html($email); ?></p>
        <p>Payment Status: <strong>Completed</strong></p>
        <p>Bill ID: <a href="<?php echo esc_url($bill); ?>" target="_blank"><?php echo esc_html($trx_id); ?></a></p>
      <?php
    } elseif (("pending" == $status) && $is_paid_return) {
        $bill_id = isset($billplz['id']) ? sanitize_text_field($billplz['id']) : '';
        $bill_url = $bill_id ? $this->helpers->get_url() . '/bills/' . rawurlencode($bill_id) : $bill;
      ?>
        <h2>Something wrong. please contact site owner.</h2>
        <p>Payment Status: Unknown</p>
        <p>Please check your bill <a href="<?php echo esc_url($bill_url); ?>" target="_blank">here</a></p>
      <?php

    } else {
      ?>
        <h2>Sorry, your payment was unsuccessful</h2>
        <p>Payment Status: Failed</p>
        <?php if ($bill) : ?>
          <p>Please repay the bill <a href="<?php echo esc_url($bill); ?>" target="_blank">here</a></p>
        <?php endif; ?>
<?php
    }

    return ob_get_clean();
  }
}
