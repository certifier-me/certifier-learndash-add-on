=== Certifier for LearnDash ===
Contributors: certifier
Tags: certificates, credentials, learndash, lms, digital badges
Requires at least: 6.2
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Issue Certifier credentials automatically when learners complete mapped LearnDash courses.

== Description ==

Certifier for LearnDash connects LearnDash course completions to Certifier digital credentials.

After you configure a Certifier API access token and map LearnDash course IDs to Certifier group IDs, the plugin listens for LearnDash course-completion events and creates, issues, and sends a credential for the learner.

This plugin requires an active LearnDash LMS installation and a Certifier account.

= External services =

This plugin connects to the Certifier API to load available Certifier groups and to create, issue, and send credentials.

By default, requests are sent to `https://api.certifier.io`. Site administrators may change the API base URL in the plugin settings.

When the Course Issuance screen is opened, the plugin requests the list of Certifier groups for the saved access token so the groups can be selected from a dropdown.

When a mapped LearnDash course is completed, the plugin sends the learner name, learner email address, mapped Certifier group ID, and issue date to Certifier. This happens only after a site administrator configures a Certifier access token and course mappings.

Service information is available at https://certifier.io.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/certifier-learndash-add-on` directory, or install the plugin through the WordPress plugins screen.
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

== Changelog ==

= 0.1.0 =
* Initial release.
