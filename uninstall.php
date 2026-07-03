<?php
/**
 * Uninstall cleanup for Certifier for LearnDash.
 *
 * Removes plugin settings, issuance records, and leftover transients.
 * Plugin classes are not loaded during uninstall, so option and meta
 * names are duplicated here as literals.
 *
 * @package Certifier_Learndash
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Delete plugin data for the current site.
 */
function certifier_learndash_uninstall_site() {
	global $wpdb;

	delete_option( 'certifier_learndash_settings' );
	delete_transient( 'certifier_learndash_admin_notice' );

	// Issuance records and error logs stored in usermeta. Core meta APIs
	// cannot delete by meta_key LIKE, so a direct query is required.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time uninstall cleanup.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
			$wpdb->esc_like( '_certifier_learndash_issue_' ) . '%'
		)
	);

	// In-progress issue locks stored as transients in the options table.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time uninstall cleanup.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_certifier_learndash_issue_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_certifier_learndash_issue_' ) . '%'
		)
	);
}

if ( is_multisite() ) {
	$certifier_learndash_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $certifier_learndash_site_ids as $certifier_learndash_site_id ) {
		switch_to_blog( $certifier_learndash_site_id );
		certifier_learndash_uninstall_site();
		restore_current_blog();
	}
} else {
	certifier_learndash_uninstall_site();
}
