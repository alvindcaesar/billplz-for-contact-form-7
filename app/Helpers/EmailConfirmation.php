<?php
namespace BillplzCF7\Helpers;

class EmailConfirmation {

  private const EMAIL_SETTINGS_OPTION = 'bcf7_email_settings';
  private const EMAIL_SUBJECT_OPTION = 'bcf7_email_subject';
  private const EMAIL_BODY_OPTION = 'bcf7_email_body';

  private array $options = array();

  /**
   * Email confirmation constructor.
   */
  public function __construct() {
    $stored = get_option( self::EMAIL_SETTINGS_OPTION );
    if ( is_array( $stored ) ) {
      $this->options = $stored;
    }
  }


  /**
   * Sends email confirmation.
   *
   * @param array $transaction Transaction details.
   */
  public function send( array $transaction ): void {
    $to      = isset( $transaction['customer_email'] ) ? $transaction['customer_email'] : '';
    $subject = isset( $this->options[ self::EMAIL_SUBJECT_OPTION ] ) ? $this->options[ self::EMAIL_SUBJECT_OPTION ] : '';
    $body    = isset( $this->options[ self::EMAIL_BODY_OPTION ] ) ? $this->options[ self::EMAIL_BODY_OPTION ] : '';

    if ( '' === $to || '' === $subject || '' === $body ) {
      return;
    }

    $body = $this->replace_variables_in_email_body( $body, $transaction );

    wp_mail( $to, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
  }

  /**
   * Replaces placeholders in email body with transaction details.
   *
   * @param string $body Email body.
   * @param array $transaction Transaction details.
   *
   * @return string Modified email body.
   */
  private function replace_variables_in_email_body( string $body, array $transaction ): string {
    $date = ! empty( $transaction['txn_date'] ) ? wp_date( 'F j, Y', strtotime( $transaction['txn_date'] ) ) : '';
    $body = str_replace( '{customer_name}', esc_html( (string) ( $transaction['customer_name'] ?? '' ) ), $body );
    $body = str_replace( '{transaction_id}', esc_html( (string) ( $transaction['txn_id'] ?? '' ) ), $body );
    $body = str_replace( '{transaction_date}', esc_html( $date ), $body );
    $body = str_replace( '{transaction_amount}', 'RM ' . esc_html( number_format( (float) ( $transaction['txn_amount'] ?? 0 ), 2 ) ), $body );
    return $body;
  }
}