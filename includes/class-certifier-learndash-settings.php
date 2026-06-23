<?php
/**
 * Settings storage for Certifier for LearnDash.
 *
 * @package Certifier_Learndash
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Certifier_Learndash_Settings' ) ) :
	/**
	 * Handles option defaults, reads, and sanitization.
	 */
	final class Certifier_Learndash_Settings {
		public const OPTION_NAME          = 'certifier_learndash_settings';
		public const DEFAULT_API_BASE_URL = 'https://api.certifier.io';
		public const USER_META_PREFIX     = '_certifier_learndash_issue_';

		/**
		 * Create default settings on activation.
		 */
		public static function activate() {
			if ( false === get_option( self::OPTION_NAME, false ) ) {
				add_option( self::OPTION_NAME, self::defaults() );
			}
		}

		/**
		 * Default settings.
		 *
		 * @return array<string, mixed>
		 */
		public static function defaults() {
			return array(
				'api_base_url'    => self::DEFAULT_API_BASE_URL,
				'access_token'    => '',
				'course_mappings' => array(),
				'debug_enabled'   => false,
			);
		}

		/**
		 * Get all settings.
		 *
		 * @return array<string, mixed>
		 */
		public static function get() {
			$settings = get_option( self::OPTION_NAME, array() );
			if ( ! is_array( $settings ) ) {
				$settings = array();
			}

			$settings = wp_parse_args( $settings, self::defaults() );

			if ( ! is_array( $settings['course_mappings'] ) ) {
				$settings['course_mappings'] = array();
			}

			return $settings;
		}

		/**
		 * Get API base URL.
		 */
		public static function get_api_base_url() {
			$settings = self::get();

			return untrailingslashit( (string) $settings['api_base_url'] );
		}

		/**
		 * Get saved access token.
		 */
		public static function get_access_token() {
			$settings = self::get();

			return (string) $settings['access_token'];
		}

		/**
		 * Check debug logging flag.
		 */
		public static function is_debug_enabled() {
			$settings = self::get();

			return (bool) $settings['debug_enabled'];
		}

		/**
		 * Get course-to-group mappings.
		 *
		 * @return array<int, string>
		 */
		public static function get_course_mappings() {
			$settings = self::get();

			return $settings['course_mappings'];
		}

		/**
		 * Get Certifier group ID for a LearnDash course ID.
		 *
		 * @param int|string $course_id LearnDash course post ID.
		 */
		public static function get_group_id_for_course( $course_id ) {
			$mappings  = self::get_course_mappings();
			$course_id = absint( $course_id );

			return $mappings[ $course_id ] ?? '';
		}

		/**
		 * Convert saved mappings into textarea form.
		 *
		 * @param array<int, string> $mappings Saved mappings.
		 */
		public static function mappings_to_text( $mappings ) {
			$lines = array();

			foreach ( $mappings as $course_id => $group_id ) {
				$lines[] = absint( $course_id ) . '=' . $group_id;
			}

			return implode( "\n", $lines );
		}

		/**
		 * Sanitize settings from wp-admin.
		 *
		 * @param array<string, mixed> $input Raw settings.
		 * @return array<string, mixed>
		 */
		public static function sanitize( $input ) {
			$current = self::get();
			$output  = self::defaults();

			if ( array_key_exists( 'api_base_url', $input ) ) {
				$api_base_url = esc_url_raw( trim( (string) $input['api_base_url'] ) );
				if ( '' === $api_base_url ) {
					$api_base_url = self::DEFAULT_API_BASE_URL;
				}
				$output['api_base_url'] = untrailingslashit( $api_base_url );
			} else {
				$output['api_base_url'] = $current['api_base_url'];
			}

			$should_clear_token = ! empty( $input['clear_access_token'] );
			$access_token       = isset( $input['access_token'] ) ? trim( (string) $input['access_token'] ) : '';
			if ( $should_clear_token ) {
				$output['access_token'] = '';
			} elseif ( '' !== $access_token ) {
				$output['access_token'] = sanitize_text_field( $access_token );
			} else {
				$output['access_token'] = $current['access_token'];
			}

			$output['debug_enabled'] = array_key_exists( 'debug_enabled', $input ) ? ! empty( $input['debug_enabled'] ) : $current['debug_enabled'];
			$output['course_mappings'] = self::has_course_mappings_input( $input ) ? self::sanitize_course_mappings( $input ) : $current['course_mappings'];

			return $output;
		}

		/**
		 * Check if settings payload includes course mappings.
		 *
		 * @param array<string, mixed> $input Raw settings.
		 */
		private static function has_course_mappings_input( $input ) {
			return array_key_exists( 'course_mappings', $input ) || array_key_exists( 'course_mappings_text', $input ) || array_key_exists( 'course_mappings_present', $input );
		}

		/**
		 * Sanitize course mappings from either array or textarea input.
		 *
		 * @param array<string, mixed> $input Raw settings.
		 * @return array<int, string>
		 */
		private static function sanitize_course_mappings( $input ) {
			$mappings = array();

			if ( isset( $input['course_mappings'] ) && is_array( $input['course_mappings'] ) ) {
				foreach ( $input['course_mappings'] as $course_id => $mapping ) {
					if ( is_array( $mapping ) ) {
						self::add_mapping(
							$mappings,
							$mapping['course_id'] ?? 0,
							$mapping['group_id'] ?? ''
						);
					} else {
						self::add_mapping( $mappings, $course_id, $mapping );
					}
				}
			}

			if ( isset( $input['course_mappings_text'] ) ) {
				$lines = preg_split( '/\r\n|\r|\n/', (string) $input['course_mappings_text'] );
				if ( is_array( $lines ) ) {
					foreach ( $lines as $line ) {
						$line = trim( $line );
						if ( '' === $line ) {
							continue;
						}

						$parts = array_map( 'trim', explode( '=', $line, 2 ) );
						if ( 2 !== count( $parts ) ) {
							continue;
						}

						self::add_mapping( $mappings, $parts[0], $parts[1] );
					}
				}
			}

			ksort( $mappings );

			return $mappings;
		}

		/**
		 * Add a sanitized mapping.
		 *
		 * @param array<int, string> $mappings Mappings accumulator.
		 * @param mixed              $course_id Raw course ID.
		 * @param mixed              $group_id Raw group ID.
		 */
		private static function add_mapping( &$mappings, $course_id, $group_id ) {
			$course_id = absint( $course_id );
			$group_id  = sanitize_text_field( trim( (string) $group_id ) );

			if ( $course_id <= 0 || '' === $group_id ) {
				return;
			}

			$mappings[ $course_id ] = $group_id;
		}
	}
endif;
