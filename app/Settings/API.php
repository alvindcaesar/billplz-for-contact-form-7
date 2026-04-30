<?php

namespace BillplzCF7\Settings;

class API {

	public static $options;

	public function __construct() {
		self::$options = get_option( 'bcf7_api_options' );
	}

	public function register() {
		add_action( 'admin_init', array( $this, 'init' ) );
	}

	public function init() {
		register_setting( 'bcf7_api', 'bcf7_api_options', array(
			'sanitize_callback' => array( $this, 'sanitize_options' ),
		) );

		add_settings_section(
			'bcf7_live_section',
			"<h3>Live Credentials</h3>
        <p class='description'>Go to <a href='https://dashboard.billplz.com/' target='_blank'><code>https://dashboard.billplz.com/</code></a> to get your live credentials.</p>",
			null,
			'bcf7_live_settings'
		);

		add_settings_field(
			'bcf7_live_secret_key',
			'Secret Key',
			array( $this, 'secret_key_callback' ),
			'bcf7_live_settings',
			'bcf7_live_section'
		);

		add_settings_field(
			'bcf7_live_collection_id',
			'Collection ID',
			array( $this, 'collection_id_callback' ),
			'bcf7_live_settings',
			'bcf7_live_section',
		);

		add_settings_field(
			'bcf7_live_xsignature_key',
			'X-Signature Key',
			array( $this, 'xsignature_callback' ),
			'bcf7_live_settings',
			'bcf7_live_section',
		);

		add_settings_section(
			'bcf7_sandbox_section',
			"<h3>Test Credentials</h3>
        <p class='description'>Go to <a href='https://dashboard.billplz-sandbox.com/' target='_blank'><code>https://dashboard.billplz-sandbox.com/</code></a> to get your test credentials.</p>",
			null,
			'bcf7_sandbox_settings'
		);

		add_settings_field(
			'bcf7_sandbox_secret_key',
			'Test Secret Key',
			array( $this, 'sandbox_secret_key_callback' ),
			'bcf7_sandbox_settings',
			'bcf7_sandbox_section',
		);

		add_settings_field(
			'bcf7_sandbox_collection_id',
			'Test Collection ID',
			array( $this, 'sandbox_collection_id_callback' ),
			'bcf7_sandbox_settings',
			'bcf7_sandbox_section',
		);

		add_settings_field(
			'bcf7_sandbox_xsignature_key',
			'Test X-Signature Key',
			array( $this, 'sandbox_xsignature_callback' ),
			'bcf7_sandbox_settings',
			'bcf7_sandbox_section',
		);
	}

	public function secret_key_callback() {
		?>
	<input class="regular-text" type="text" name="bcf7_api_options[bcf7_live_secret_key]" id="bcf7_live_secret_key" value="<?php echo esc_attr( isset( self::$options['bcf7_live_secret_key'] ) ? self::$options['bcf7_live_secret_key'] : '' ); ?>">
		<?php
	}

	public function collection_id_callback() {
		?>
	<input class="regular-text" type="text" name="bcf7_api_options[bcf7_live_collection_id]" id="bcf7_live_collection_id" value="<?php echo esc_attr( isset( self::$options['bcf7_live_collection_id'] ) ? self::$options['bcf7_live_collection_id'] : '' ); ?>">
		<?php
	}

	public function xsignature_callback() {
		?>
	<input class="regular-text" type="text" name="bcf7_api_options[bcf7_live_xsignature_key]" id="bcf7_live_xsignature_key" value="<?php echo esc_attr( isset( self::$options['bcf7_live_xsignature_key'] ) ? self::$options['bcf7_live_xsignature_key'] : '' ); ?>">
		<?php
	}

	public function sandbox_secret_key_callback() {
		?>
	<input class="regular-text" type="text" name="bcf7_api_options[bcf7_sandbox_secret_key]" id="bcf7_sandbox_secret_key" value="<?php echo esc_attr( isset( self::$options['bcf7_sandbox_secret_key'] ) ? self::$options['bcf7_sandbox_secret_key'] : '' ); ?>">
		<?php
	}

	public function sandbox_collection_id_callback() {
		?>
	<input class="regular-text" type="text" name="bcf7_api_options[bcf7_sandbox_collection_id]" id="bcf7_sandbox_collection_id" value="<?php echo esc_attr( isset( self::$options['bcf7_sandbox_collection_id'] ) ? self::$options['bcf7_sandbox_collection_id'] : '' ); ?>">
		<?php
	}

	public function sanitize_options( $input ) {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$keys = array(
			'bcf7_live_secret_key',
			'bcf7_live_collection_id',
			'bcf7_live_xsignature_key',
			'bcf7_sandbox_secret_key',
			'bcf7_sandbox_collection_id',
			'bcf7_sandbox_xsignature_key',
		);

		$clean = array();
		foreach ( $keys as $key ) {
			$clean[ $key ] = isset( $input[ $key ] ) ? sanitize_text_field( $input[ $key ] ) : '';
		}

		return $clean;
	}

	public function sandbox_xsignature_callback() {
		?>
	<input class="regular-text" type="text" name="bcf7_api_options[bcf7_sandbox_xsignature_key]" id="bcf7_sandbox_xsignature_key" value="<?php echo esc_attr( isset( self::$options['bcf7_sandbox_xsignature_key'] ) ? self::$options['bcf7_sandbox_xsignature_key'] : '' ); ?>">
		<?php
	}
}
