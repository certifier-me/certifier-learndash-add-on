<?php
/**
 * LearnDash completion listener.
 *
 * @package Certifier_Learndash
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Certifier_Learndash_Issuer' ) ) :
	/**
	 * Handles credential issuance from LearnDash events.
	 */
	final class Certifier_Learndash_Issuer {
		/**
		 * Register event hooks.
		 */
		public function register_hooks() {
			add_action( 'learndash_course_completed', array( $this, 'handle_course_completed' ), 20, 1 );
		}

		/**
		 * Issue a Certifier credential when a LearnDash course is completed.
		 *
		 * @param array<string, mixed> $data LearnDash course completion data.
		 */
		public function handle_course_completed( $data ) {
			$course = $this->extract_course( $data );
			$user   = $this->extract_user( $data );

			if ( ! $course || ! $user ) {
				$this->debug_log( 'Course completion ignored: missing course or user.' );
				return;
			}

			$course_id = absint( $course->ID );
			$group_id  = Certifier_Learndash_Settings::get_group_id_for_course( $course_id );
			if ( '' === $group_id ) {
				$this->debug_log( sprintf( 'Course completion ignored: no Certifier group mapped for course %d.', $course_id ) );
				return;
			}

			if ( '' === trim( (string) $user->user_email ) ) {
				$this->debug_log( sprintf( 'Course completion ignored: user %d has no email address.', $user->ID ) );
				return;
			}

			$idempotency_key = $this->get_idempotency_key( $user->ID, $course_id, $group_id );
			$existing        = get_user_meta( $user->ID, $idempotency_key, true );
			if ( is_array( $existing ) && ! empty( $existing['credential_id'] ) ) {
				$this->debug_log( sprintf( 'Course completion ignored: credential already issued for user %d and course %d.', $user->ID, $course_id ) );
				return;
			}

			$lock_key = 'certifier_learndash_issue_' . md5( $user->ID . '|' . $course_id . '|' . $group_id );
			if ( get_transient( $lock_key ) ) {
				$this->debug_log( sprintf( 'Course completion ignored: issue already in progress for user %d and course %d.', $user->ID, $course_id ) );
				return;
			}
			set_transient( $lock_key, 1, 5 * MINUTE_IN_SECONDS );

			$client = Certifier_Learndash_Api_Client::from_settings();
			$result = $client->create_issue_send_credential(
				$group_id,
				$this->get_recipient_name( $user ),
				$user->user_email
			);

			delete_transient( $lock_key );

			if ( empty( $result['success'] ) ) {
				update_user_meta(
					$user->ID,
					$idempotency_key . '_last_error',
					array(
						'course_id' => $course_id,
						'group_id'  => $group_id,
						'status'    => $result['status'] ?? 0,
						'message'   => $result['message'] ?? 'Unknown Certifier API error.',
						'failed_at' => current_time( 'mysql' ),
					)
				);

				$this->debug_log(
					sprintf(
						'Credential issuance failed for user %d and course %d: %s',
						$user->ID,
						$course_id,
						$result['message'] ?? 'Unknown Certifier API error.'
					)
				);
				return;
			}

			$credential = isset( $result['body'] ) && is_array( $result['body'] ) ? $result['body'] : array();
			update_user_meta(
				$user->ID,
				$idempotency_key,
				array(
					'credential_id'        => isset( $credential['id'] ) ? sanitize_text_field( (string) $credential['id'] ) : '',
					'credential_public_id' => isset( $credential['publicId'] ) ? sanitize_text_field( (string) $credential['publicId'] ) : '',
					'course_id'            => $course_id,
					'course_title'         => get_the_title( $course_id ),
					'group_id'             => $group_id,
					'issued_at'            => current_time( 'mysql' ),
				)
			);
			delete_user_meta( $user->ID, $idempotency_key . '_last_error' );

			$this->debug_log( sprintf( 'Credential issued for user %d and course %d.', $user->ID, $course_id ) );
		}

		/**
		 * Extract course post from LearnDash hook data.
		 *
		 * @param mixed $data LearnDash hook payload.
		 * @return WP_Post|null
		 */
		private function extract_course( $data ) {
			if ( is_array( $data ) && isset( $data['course'] ) && $data['course'] instanceof WP_Post ) {
				return $data['course'];
			}

			if ( is_array( $data ) && isset( $data['course_id'] ) ) {
				$course = get_post( absint( $data['course_id'] ) );
				return $course instanceof WP_Post ? $course : null;
			}

			return null;
		}

		/**
		 * Extract user from LearnDash hook data.
		 *
		 * @param mixed $data LearnDash hook payload.
		 * @return WP_User|null
		 */
		private function extract_user( $data ) {
			if ( is_array( $data ) && isset( $data['user'] ) && $data['user'] instanceof WP_User ) {
				return $data['user'];
			}

			if ( is_array( $data ) && isset( $data['user_id'] ) ) {
				$user = get_user_by( 'id', absint( $data['user_id'] ) );
				return $user instanceof WP_User ? $user : null;
			}

			return null;
		}

		/**
		 * Build recipient name.
		 *
		 * @param WP_User $user WordPress user.
		 */
		private function get_recipient_name( $user ) {
			$names = array_filter(
				array(
					get_user_meta( $user->ID, 'first_name', true ),
					get_user_meta( $user->ID, 'last_name', true ),
				)
			);

			if ( ! empty( $names ) ) {
				return trim( implode( ' ', $names ) );
			}

			return $user->display_name;
		}

		/**
		 * Build user meta key for idempotency.
		 *
		 * @param int    $user_id User ID.
		 * @param int    $course_id Course ID.
		 * @param string $group_id Certifier group ID.
		 */
		private function get_idempotency_key( $user_id, $course_id, $group_id ) {
			return Certifier_Learndash_Settings::USER_META_PREFIX . md5( $user_id . '|' . $course_id . '|' . $group_id );
		}

		/**
		 * Write to debug log when enabled.
		 *
		 * @param string $message Message to log.
		 */
		private function debug_log( $message ) {
			if ( ! Certifier_Learndash_Settings::is_debug_enabled() ) {
				return;
			}

			error_log( '[Certifier LearnDash] ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
endif;
