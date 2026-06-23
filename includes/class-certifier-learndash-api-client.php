<?php
/**
 * Certifier API client.
 *
 * @package Certifier_Learndash
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Certifier_Learndash_Api_Client' ) ) :
	/**
	 * Small wrapper around WordPress HTTP APIs.
	 */
	final class Certifier_Learndash_Api_Client {
		/**
		 * API base URL.
		 *
		 * @var string
		 */
		private $api_base_url;

		/**
		 * Certifier personal access token.
		 *
		 * @var string
		 */
		private $access_token;

		/**
		 * Constructor.
		 *
		 * @param string $api_base_url API base URL.
		 * @param string $access_token Certifier personal access token.
		 */
		public function __construct( $api_base_url, $access_token ) {
			$this->api_base_url = untrailingslashit( (string) $api_base_url );
			$this->access_token = (string) $access_token;
		}

		/**
		 * Build a client from saved settings.
		 */
		public static function from_settings() {
			return new self(
				Certifier_Learndash_Settings::get_api_base_url(),
				Certifier_Learndash_Settings::get_access_token()
			);
		}

		/**
		 * Test credentials by reading one group.
		 *
		 * @return array<string, mixed>
		 */
		public function test_connection() {
			return $this->request( 'GET', '/v1/groups?limit=1' );
		}

		/**
		 * List Certifier groups for admin dropdowns.
		 *
		 * @return array<string, mixed>
		 */
		public function list_groups( $cursor = null ) {
			$query = array(
				'limit' => 100,
			);

			if ( null !== $cursor && '' !== $cursor ) {
				$query['cursor'] = (string) $cursor;
			}

			return $this->request( 'GET', '/v1/groups?' . http_build_query( $query, '', '&', PHP_QUERY_RFC3986 ) );
		}

		/**
		 * Create, issue, and send a credential.
		 *
		 * @param string $group_id Certifier group ID.
		 * @param string $recipient_name Recipient name.
		 * @param string $recipient_email Recipient email.
		 * @return array<string, mixed>
		 */
		public function create_issue_send_credential( $group_id, $recipient_name, $recipient_email ) {
			return $this->request(
				'POST',
				'/v1/credentials/create-issue-send',
				array(
					'groupId'   => $group_id,
					'recipient' => array(
						'name'  => $recipient_name,
						'email' => $recipient_email,
					),
					'issueDate' => current_time( 'Y-m-d' ),
				)
			);
		}

		/**
		 * Send an HTTP request to Certifier.
		 *
		 * @param string               $method HTTP method.
		 * @param string               $path API path.
		 * @param array<string, mixed> $body Optional JSON body.
		 * @return array<string, mixed>
		 */
		private function request( $method, $path, $body = null ) {
			if ( '' === $this->api_base_url ) {
				return array(
					'success' => false,
					'status'  => 0,
					'message' => 'Certifier API base URL is missing.',
				);
			}

			if ( '' === $this->access_token ) {
				return array(
					'success' => false,
					'status'  => 0,
					'message' => 'Certifier access token is missing.',
				);
			}

			$args = array(
				'method'  => $method,
				'timeout' => 20,
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->access_token,
					'Accept'        => 'application/json',
					'Certifier-Version' => CERTIFIER_LEARNDASH_API_VERSION,
					'Content-Type'  => 'application/json',
					'User-Agent'    => 'Certifier LearnDash/' . CERTIFIER_LEARNDASH_VERSION,
				),
			);

			if ( null !== $body ) {
				$args['body'] = wp_json_encode( $body );
			}

			$response = wp_remote_request( $this->api_base_url . '/' . ltrim( $path, '/' ), $args );
			if ( is_wp_error( $response ) ) {
				return array(
					'success' => false,
					'status'  => 0,
					'message' => $response->get_error_message(),
				);
			}

			$status       = (int) wp_remote_retrieve_response_code( $response );
			$raw_body     = (string) wp_remote_retrieve_body( $response );
			$decoded_body = json_decode( $raw_body, true );
			if ( ! is_array( $decoded_body ) ) {
				$decoded_body = array();
			}

			if ( $status >= 200 && $status < 300 ) {
				return array(
					'success' => true,
					'status'  => $status,
					'body'    => $decoded_body,
				);
			}

			return array(
				'success' => false,
				'status'  => $status,
				'body'    => $decoded_body,
				'message' => $this->extract_error_message( $decoded_body, $raw_body ),
			);
		}

		/**
		 * Extract a human readable API error.
		 *
		 * @param array<string, mixed> $body Decoded JSON body.
		 * @param string               $raw_body Raw response body.
		 */
		private function extract_error_message( $body, $raw_body ) {
			foreach ( array( 'message', 'error', 'detail' ) as $key ) {
				if ( isset( $body[ $key ] ) && is_scalar( $body[ $key ] ) ) {
					return (string) $body[ $key ];
				}
			}

			if ( isset( $body['error'] ) && is_array( $body['error'] ) && isset( $body['error']['message'] ) && is_scalar( $body['error']['message'] ) ) {
				return (string) $body['error']['message'];
			}

			if ( isset( $body['errors'] ) ) {
				return wp_json_encode( $body['errors'] );
			}

			return '' !== $raw_body ? $raw_body : 'Certifier API request failed.';
		}
	}
endif;
