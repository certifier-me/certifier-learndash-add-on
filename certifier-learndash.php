<?php
/**
 * Certifier for LearnDash
 *
 * @package Certifier_Learndash
 *
 * Plugin Name: Certifier for LearnDash
 * Plugin URI:  https://certifier.io
 * Description: Issue Certifier credentials when learners complete LearnDash courses.
 * Version:     0.1.0
 * Author:      Certifier
 * Author URI:  https://certifier.io
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: certifier-learndash
 * Requires PHP: 8.1
 */

defined( 'ABSPATH' ) || exit;

define( 'CERTIFIER_LEARNDASH_VERSION', '0.1.0' );
define( 'CERTIFIER_LEARNDASH_API_VERSION', '2022-10-26' );
define( 'CERTIFIER_LEARNDASH_PLUGIN_FILE', __FILE__ );
define( 'CERTIFIER_LEARNDASH_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'CERTIFIER_LEARNDASH_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

require_once CERTIFIER_LEARNDASH_PLUGIN_PATH . 'includes/class-certifier-learndash-settings.php';
require_once CERTIFIER_LEARNDASH_PLUGIN_PATH . 'includes/class-certifier-learndash-api-client.php';
require_once CERTIFIER_LEARNDASH_PLUGIN_PATH . 'includes/class-certifier-learndash-issuer.php';
require_once CERTIFIER_LEARNDASH_PLUGIN_PATH . 'includes/class-certifier-learndash-admin.php';
require_once CERTIFIER_LEARNDASH_PLUGIN_PATH . 'includes/class-certifier-learndash-plugin.php';

register_activation_hook( __FILE__, array( 'Certifier_Learndash_Settings', 'activate' ) );

add_action( 'plugins_loaded', array( 'Certifier_Learndash_Plugin', 'init' ), 11 );
