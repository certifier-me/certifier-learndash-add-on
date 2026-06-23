<?php
/**
 * WordPress admin UI.
 *
 * @package Certifier_Learndash
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Certifier_Learndash_Admin' ) ) :
	/**
	 * Registers settings and renders the settings page.
	 */
	final class Certifier_Learndash_Admin {
		/**
		 * Initialize admin hooks.
		 */
		public static function init() {
			return new self();
		}

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_init', array( $this, 'add_privacy_policy_content' ) );
			add_action( 'admin_notices', array( $this, 'render_admin_notices' ) );
			add_action( 'admin_post_certifier_learndash_test_connection', array( $this, 'handle_test_connection' ) );
		}

		/**
		 * Add admin menu.
		 */
		public function add_admin_menu() {
			add_menu_page(
				__( 'Certifier for LearnDash', 'certifier-learndash' ),
				__( 'Certifier for LearnDash', 'certifier-learndash' ),
				'manage_options',
				'certifier-learndash',
				array( $this, 'render_dashboard_page' ),
				'dashicons-awards',
				56
			);

			add_submenu_page(
				'certifier-learndash',
				__( 'Dashboard', 'certifier-learndash' ),
				__( 'Dashboard', 'certifier-learndash' ),
				'manage_options',
				'certifier-learndash',
				array( $this, 'render_dashboard_page' )
			);

			add_submenu_page(
				'certifier-learndash',
				__( 'Course Issuance', 'certifier-learndash' ),
				__( 'Course Issuance', 'certifier-learndash' ),
				'manage_options',
				'certifier-learndash-course-issuance',
				array( $this, 'render_course_issuance_page' )
			);

			add_submenu_page(
				'certifier-learndash',
				__( 'Settings', 'certifier-learndash' ),
				__( 'Settings', 'certifier-learndash' ),
				'manage_options',
				'certifier-learndash-settings',
				array( $this, 'render_settings_page' )
			);

			add_submenu_page(
				'certifier-learndash',
				__( 'Issue Logs', 'certifier-learndash' ),
				__( 'Issue Logs', 'certifier-learndash' ),
				'manage_options',
				'certifier-learndash-logs',
				array( $this, 'render_logs_page' )
			);
		}

		/**
		 * Register settings.
		 */
		public function register_settings() {
			register_setting(
				'certifier_learndash',
				Certifier_Learndash_Settings::OPTION_NAME,
				array(
					'type'              => 'array',
					'sanitize_callback' => array( 'Certifier_Learndash_Settings', 'sanitize' ),
					'default'           => Certifier_Learndash_Settings::defaults(),
				)
			);
		}

		/**
		 * Add suggested privacy policy text for the data sent to Certifier.
		 */
		public function add_privacy_policy_content() {
			if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
				return;
			}

			$content = '<p>' . __( 'When a learner completes a mapped LearnDash course, this site sends the learner name, email address, mapped Certifier group ID, and issue date to Certifier so a digital credential can be created, issued, and sent.', 'certifier-learndash' ) . '</p>';
			$content .= '<p>' . __( 'Certifier may process this information according to the site owner\'s agreement with Certifier and the Certifier privacy policy.', 'certifier-learndash' ) . '</p>';

			wp_add_privacy_policy_content(
				__( 'Certifier for LearnDash', 'certifier-learndash' ),
				wp_kses_post( wpautop( $content, false ) )
			);
		}

		/**
		 * Handle API connection test.
		 */
		public function handle_test_connection() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to test Certifier settings.', 'certifier-learndash' ) );
			}

			check_admin_referer( 'certifier_learndash_test_connection' );

			$result = Certifier_Learndash_Api_Client::from_settings()->test_connection();
			if ( ! empty( $result['success'] ) ) {
				$this->set_notice( 'success', __( 'Certifier connection works.', 'certifier-learndash' ) );
			} else {
				$message = isset( $result['message'] ) ? $result['message'] : __( 'Certifier connection failed.', 'certifier-learndash' );
				$this->set_notice( 'error', $message );
			}

			wp_safe_redirect( admin_url( 'admin.php?page=certifier-learndash-settings' ) );
			exit;
		}

		/**
		 * Render dashboard page.
		 */
		public function render_dashboard_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$settings         = Certifier_Learndash_Settings::get();
			$learndash_active = class_exists( 'SFWD_LMS' );
			$has_token        = '' !== Certifier_Learndash_Settings::get_access_token();
			$mappings_count   = count( $settings['course_mappings'] );
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'Certifier for LearnDash', 'certifier-learndash' ); ?></h1>

				<div class="card" style="max-width: 960px;">
					<h2><?php esc_html_e( 'Integration Status', 'certifier-learndash' ); ?></h2>
					<table class="widefat striped">
						<tbody>
							<tr>
								<th scope="row"><?php esc_html_e( 'LearnDash', 'certifier-learndash' ); ?></th>
								<td><?php echo esc_html( $learndash_active ? __( 'Active', 'certifier-learndash' ) : __( 'Not active', 'certifier-learndash' ) ); ?></td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Certifier access token', 'certifier-learndash' ); ?></th>
								<td><?php echo esc_html( $has_token ? __( 'Configured', 'certifier-learndash' ) : __( 'Missing', 'certifier-learndash' ) ); ?></td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Course mappings', 'certifier-learndash' ); ?></th>
								<td><?php echo esc_html( sprintf( _n( '%d mapping', '%d mappings', $mappings_count, 'certifier-learndash' ), $mappings_count ) ); ?></td>
							</tr>
						</tbody>
					</table>

					<p>
						<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=certifier-learndash-course-issuance' ) ); ?>">
							<?php esc_html_e( 'Configure course issuance', 'certifier-learndash' ); ?>
						</a>
						<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=certifier-learndash-settings' ) ); ?>">
							<?php esc_html_e( 'Open settings', 'certifier-learndash' ); ?>
						</a>
					</p>
				</div>
			</div>
			<?php
		}

		/**
		 * Render settings page.
		 */
		public function render_settings_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$settings  = Certifier_Learndash_Settings::get();
			$has_token = '' !== Certifier_Learndash_Settings::get_access_token();
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'Certifier Settings', 'certifier-learndash' ); ?></h1>

				<form method="post" action="options.php">
					<?php settings_fields( 'certifier_learndash' ); ?>

					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row">
									<label for="certifier_learndash_api_base_url"><?php esc_html_e( 'API base URL', 'certifier-learndash' ); ?></label>
								</th>
								<td>
									<input
										id="certifier_learndash_api_base_url"
										name="<?php echo esc_attr( Certifier_Learndash_Settings::OPTION_NAME ); ?>[api_base_url]"
										type="url"
										class="regular-text"
										value="<?php echo esc_attr( $settings['api_base_url'] ); ?>"
									/>
									<p class="description"><?php esc_html_e( 'Use production, staging, or your local API URL.', 'certifier-learndash' ); ?></p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="certifier_learndash_access_token"><?php esc_html_e( 'Access token', 'certifier-learndash' ); ?></label>
								</th>
								<td>
									<input
										id="certifier_learndash_access_token"
										name="<?php echo esc_attr( Certifier_Learndash_Settings::OPTION_NAME ); ?>[access_token]"
										type="password"
										class="regular-text"
										value=""
										autocomplete="off"
										placeholder="<?php echo esc_attr( $has_token ? __( 'Saved token is set. Leave blank to keep it.', 'certifier-learndash' ) : __( 'Paste a Certifier personal access token.', 'certifier-learndash' ) ); ?>"
									/>
									<?php if ( $has_token ) : ?>
										<label>
											<input
												name="<?php echo esc_attr( Certifier_Learndash_Settings::OPTION_NAME ); ?>[clear_access_token]"
												type="checkbox"
												value="1"
											/>
											<?php esc_html_e( 'Clear saved token', 'certifier-learndash' ); ?>
										</label>
									<?php endif; ?>
								</td>
							</tr>

							<tr>
								<th scope="row"><?php esc_html_e( 'Debug logging', 'certifier-learndash' ); ?></th>
								<td>
									<input
										name="<?php echo esc_attr( Certifier_Learndash_Settings::OPTION_NAME ); ?>[debug_enabled]"
										type="hidden"
										value="0"
									/>
									<label>
										<input
											name="<?php echo esc_attr( Certifier_Learndash_Settings::OPTION_NAME ); ?>[debug_enabled]"
											type="checkbox"
											value="1"
											<?php checked( ! empty( $settings['debug_enabled'] ) ); ?>
										/>
										<?php esc_html_e( 'Write issuance decisions to the WordPress debug log.', 'certifier-learndash' ); ?>
									</label>
								</td>
							</tr>
						</tbody>
					</table>

					<?php submit_button( __( 'Save settings', 'certifier-learndash' ) ); ?>
				</form>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="certifier_learndash_test_connection" />
					<?php wp_nonce_field( 'certifier_learndash_test_connection' ); ?>
					<?php submit_button( __( 'Test Certifier connection', 'certifier-learndash' ), 'secondary', 'submit', false ); ?>
				</form>
			</div>
			<?php
		}

		/**
		 * Render course issuance page.
		 */
		public function render_course_issuance_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$settings      = Certifier_Learndash_Settings::get();
			$mappings_text = Certifier_Learndash_Settings::mappings_to_text( $settings['course_mappings'] );
			$courses       = $this->get_learndash_courses();
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'Course Issuance', 'certifier-learndash' ); ?></h1>

				<form method="post" action="options.php">
					<?php settings_fields( 'certifier_learndash' ); ?>

					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row">
									<label for="certifier_learndash_course_mappings_text"><?php esc_html_e( 'Course mappings', 'certifier-learndash' ); ?></label>
								</th>
								<td>
									<textarea
										id="certifier_learndash_course_mappings_text"
										name="<?php echo esc_attr( Certifier_Learndash_Settings::OPTION_NAME ); ?>[course_mappings_text]"
										class="large-text code"
										rows="8"
										placeholder="123=01hzy8examplegroupid"
									><?php echo esc_textarea( $mappings_text ); ?></textarea>
									<p class="description">
										<?php esc_html_e( 'Add one mapping per line: LearnDash course ID = Certifier group ID. Credentials are issued when LearnDash fires course completion for a mapped course.', 'certifier-learndash' ); ?>
									</p>

									<?php if ( ! empty( $courses ) ) : ?>
										<p><?php esc_html_e( 'Detected LearnDash courses:', 'certifier-learndash' ); ?></p>
										<ul>
											<?php foreach ( $courses as $course ) : ?>
												<li>
													<code><?php echo esc_html( $course->ID ); ?></code>
													<?php echo esc_html( get_the_title( $course ) ); ?>
												</li>
											<?php endforeach; ?>
										</ul>
									<?php else : ?>
										<p class="description"><?php esc_html_e( 'No LearnDash courses detected yet.', 'certifier-learndash' ); ?></p>
									<?php endif; ?>
								</td>
							</tr>
						</tbody>
					</table>

					<?php submit_button( __( 'Save course mappings', 'certifier-learndash' ) ); ?>
				</form>
			</div>
			<?php
		}

		/**
		 * Render issue logs page.
		 */
		public function render_logs_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$rows = $this->get_log_rows();
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'Issue Logs', 'certifier-learndash' ); ?></h1>

				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'User', 'certifier-learndash' ); ?></th>
							<th><?php esc_html_e( 'Course', 'certifier-learndash' ); ?></th>
							<th><?php esc_html_e( 'Group ID', 'certifier-learndash' ); ?></th>
							<th><?php esc_html_e( 'Status', 'certifier-learndash' ); ?></th>
							<th><?php esc_html_e( 'Time', 'certifier-learndash' ); ?></th>
							<th><?php esc_html_e( 'Message', 'certifier-learndash' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $rows ) ) : ?>
							<tr>
								<td colspan="6"><?php esc_html_e( 'No issue logs yet.', 'certifier-learndash' ); ?></td>
							</tr>
						<?php endif; ?>

						<?php foreach ( $rows as $row ) : ?>
							<?php
							$value     = maybe_unserialize( $row->meta_value );
							$is_error  = str_ends_with( $row->meta_key, '_last_error' );
							$user      = get_user_by( 'id', absint( $row->user_id ) );
							$user_name = $user instanceof WP_User ? $user->user_email : '#' . $row->user_id;
							?>
							<tr>
								<td><?php echo esc_html( $user_name ); ?></td>
								<td>
									<?php
									if ( is_array( $value ) && ! empty( $value['course_id'] ) ) {
										echo esc_html( $value['course_id'] );
										if ( ! empty( $value['course_title'] ) ) {
											echo ' - ' . esc_html( $value['course_title'] );
										}
									}
									?>
								</td>
								<td><?php echo esc_html( is_array( $value ) && ! empty( $value['group_id'] ) ? $value['group_id'] : '' ); ?></td>
								<td><?php echo esc_html( $is_error ? __( 'Failed', 'certifier-learndash' ) : __( 'Issued', 'certifier-learndash' ) ); ?></td>
								<td><?php echo esc_html( is_array( $value ) ? ( $value['issued_at'] ?? $value['failed_at'] ?? '' ) : '' ); ?></td>
								<td>
									<?php
									if ( is_array( $value ) ) {
										echo esc_html( $value['message'] ?? $value['credential_id'] ?? '' );
									}
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php
		}

		/**
		 * Render notices.
		 */
		public function render_admin_notices() {
			$notice = get_transient( 'certifier_learndash_admin_notice' );
			if ( is_array( $notice ) && ! empty( $notice['message'] ) ) {
				delete_transient( 'certifier_learndash_admin_notice' );
				$type = isset( $notice['type'] ) && 'success' === $notice['type'] ? 'success' : 'error';
				printf(
					'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
					esc_attr( $type ),
					esc_html( $notice['message'] )
				);
			}

			if ( ! class_exists( 'SFWD_LMS' ) ) {
				printf(
					'<div class="notice notice-warning"><p>%s</p></div>',
					esc_html__( 'Certifier for LearnDash is active, but LearnDash is not active. Course-completion issuance will start once LearnDash is active.', 'certifier-learndash' )
				);
			}
		}

		/**
		 * Store a one-time admin notice.
		 *
		 * @param string $type Notice type.
		 * @param string $message Notice message.
		 */
		private function set_notice( $type, $message ) {
			set_transient(
				'certifier_learndash_admin_notice',
				array(
					'type'    => $type,
					'message' => $message,
				),
				30
			);
		}

		/**
		 * Get recent issuance log rows.
		 *
		 * @return array<object>
		 */
		private function get_log_rows() {
			global $wpdb;

			$prefix = $wpdb->esc_like( Certifier_Learndash_Settings::USER_META_PREFIX ) . '%';

			return $wpdb->get_results(
				$wpdb->prepare(
					"SELECT user_id, meta_key, meta_value FROM {$wpdb->usermeta} WHERE meta_key LIKE %s ORDER BY umeta_id DESC LIMIT 50",
					$prefix
				)
			);
		}

		/**
		 * Get LearnDash course posts.
		 *
		 * @return WP_Post[]
		 */
		private function get_learndash_courses() {
			if ( ! post_type_exists( 'sfwd-courses' ) ) {
				return array();
			}

			return get_posts(
				array(
					'post_type'      => 'sfwd-courses',
					'post_status'    => 'any',
					'posts_per_page' => 50,
					'orderby'        => 'title',
					'order'          => 'ASC',
				)
			);
		}
	}
endif;
