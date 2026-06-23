<?php
/**
 * Main plugin class.
 *
 * @package Certifier_Learndash
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Certifier_Learndash_Plugin' ) ) :
	/**
	 * Coordinates plugin services.
	 */
	final class Certifier_Learndash_Plugin {
		/**
		 * Initialize plugin.
		 */
		public static function init() {
			return new self();
		}

		/**
		 * Constructor.
		 */
		public function __construct() {
			if ( is_admin() ) {
				Certifier_Learndash_Admin::init();
			}

			$issuer = new Certifier_Learndash_Issuer();
			$issuer->register_hooks();
		}
	}
endif;
