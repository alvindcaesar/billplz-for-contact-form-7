<?php

class Billplz_CF7_Activator
{
  public static function activate()
  {
    self::db();
    self::create_pages();
  }

  public static function db()
  {
    require_once plugin_dir_path(__FILE__) . "database/class-payment-database.php";
    Billplz_CF7_Payment_DB::up();
  }

  private static function create_pages()
  {
    $page_title = "BCF7 Payment Confirmation";
    $page_content = "<!-- wp:shortcode -->[bcf7_payment_confirmation]<!-- /wp:shortcode -->";

    $page = get_page_by_title( $page_title );

    $instance = new self();

    if (! empty( $page ) ) {
      if ( $page->post_content == $page_content ) {
        return;
      } else {
        $instance->save_id($instance->page_id());
      }
    } else {
      $instance->save_id($instance->page_id());
    }
  }

  private function page_id()
  {
     return wp_insert_post(
      array(
        'post_title'     => __( 'BCF7 Payment Confirmation', 'billplz-for-cf7' ),
        'post_name'      => 'bcf7-payment-confirmation',
        'post_content'   => "<!-- wp:shortcode -->[bcf7_payment_confirmation]<!-- /wp:shortcode -->",
        'post_status'    => 'publish',
        'post_author'    => get_current_user_id(),
        'post_type'      => 'page',
        'comment_status' => 'closed'
      )
    );
  }

  private function save_id( $id )
  {
    $options = get_option("bcf7_general_settings");

    if ( $options ) {
      $options["bcf7_redirect_page"] = $id;
      update_option( "bcf7_general_settings", $options );
    } else {
      $options = array(
        "bcf7_mode" => "",
        "bcf7_form_select" => "",
        "bcf7_redirect_page" => $id
      );
      add_option( "bcf7_general_settings", $options );
    }
  }
}