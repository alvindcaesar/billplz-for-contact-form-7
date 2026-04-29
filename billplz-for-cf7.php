<?php

/**
 * Plugin Name:     Billplz for Contact Form 7
 * Plugin URI:      https://github.com/alvindcaesar/billplz-for-cf7
 * Description:     Accept payment in Contact Form 7 by using Billplz
 * Author:          Alvind Caesar
 * Author URI:      https://alvindcaesar.com
 * Text Domain:     billplz-for-cf7
 * Domain Path:     /languages
 * Version:         1.3.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package         Billplz_For_CF7
 */

defined("WPINC") or die;

if (file_exists( dirname(__FILE__) . '/vendor/autoload.php' ) ) {
  require_once dirname(__FILE__) . '/vendor/autoload.php';
}

define("BCF7_PLUGIN_PATH", plugin_dir_path(__FILE__));
define("BCF7_PLUGIN_URL", plugin_dir_url(__FILE__));
define("BCF7_PLUGIN_FILE", plugin_basename(__FILE__));
define("BCF7_ASSETS_URL", plugins_url('/billplz-for-contact-form-7/assets/'));
define("BCF7_TEXT_DOMAIN", "billplz-for-cf7");
define("BCF7_PLUGIN_VERSION", "1.3.0");

if (class_exists('BillplzCF7\\Init')) {
  BillplzCF7\Init::register_services();
}

function bcf7_activation() {
	( new \BillplzCF7\Base\Activate() )->activate();
}

register_activation_hook( __FILE__, 'bcf7_activation' );