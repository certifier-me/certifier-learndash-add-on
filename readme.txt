=== Certifier for LearnDash ===
Contributors: kacpercertifier
Tags: certificates, credentials, learndash, lms, digital badges
Requires at least: 6.2
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Issue Certifier credentials automatically when learners complete mapped LearnDash courses.

== Description ==

Certifier for LearnDash connects LearnDash course completions to Certifier digital credentials.

After you configure a Certifier API access token and map LearnDash course IDs to Certifier group IDs, the plugin listens for LearnDash course-completion events and creates, issues, and sends a credential for the learner.

This plugin requires an active LearnDash LMS installation and a Certifier account.

== External services ==

This plugin connects to the Certifier API, a digital credential service provided by Certifier (https://certifier.io). It is used to load the list of Certifier groups in the plugin's admin screens and to create, issue, and send digital credentials to learners.

What data is sent and when:

* When a site administrator opens the Course Issuance screen or clicks "Test Certifier connection" in Settings, the plugin sends the saved access token to the Certifier API to load the Certifier groups for the dropdowns.
* When a learner completes a LearnDash course that an administrator has mapped, the plugin sends the learner's name, the learner's email address, the mapped Certifier group ID, and the issue date to the Certifier API so the credential can be created, issued, and emailed to the learner.

No data is sent to Certifier until a site administrator saves a Certifier access token and maps at least one course. By default, requests are sent to `https://api.certifier.io`; site administrators may change the API base URL in the plugin settings.

Certifier terms of service: https://certifier.io/terms
Certifier privacy policy: https://certifier.io/privacy

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the Plugins screen in WordPress.
3. Make sure LearnDash LMS is installed and active.
4. Go to Certifier for LearnDash > Settings.
5. Add your Certifier API base URL and personal access token.
6. Go to Certifier for LearnDash > Course Issuance.
7. Select the LearnDash course and Certifier group to issue when the course is completed.

== Frequently Asked Questions ==

= Does this plugin require LearnDash? =

Yes. The plugin listens for LearnDash course-completion events. It does not issue credentials unless LearnDash is active and a completed course is mapped to a Certifier group.

= Does this plugin issue credentials for every course? =

No. Credentials are issued only for courses mapped in Certifier for LearnDash > Course Issuance.

= Does the plugin issue duplicate credentials? =

The plugin stores an idempotency record after a successful issue so the same user, course, and Certifier group combination is not issued repeatedly.

= Does this plugin send learner data to Certifier? =

Yes. When a mapped course is completed, the plugin sends the learner name, learner email address, mapped Certifier group ID, and issue date to Certifier so the credential can be created, issued, and sent.

== Screenshots ==

1. Choose from more than 1,000 customizable certificate and badge templates.
2. Upload recipients via spreadsheet or connect them directly from LearnDash.
3. Send certificates or badges in bulk via email.
4. Enable recipients to share and download their credentials.
5. Manage all credentials from one dashboard.
6. Track how recipients interact with certificates.

== Changelog ==

= 0.1.0 =
* Initial release.
