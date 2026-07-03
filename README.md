# Certifier for LearnDash

WordPress plugin for issuing Certifier credentials when a learner completes a LearnDash course.

## Local setup

From this directory:

```bash
npx @wordpress/env start
```

Open `http://localhost:8888/wp-admin` and log in with:

```text
admin / password
```

The local environment loads this plugin:

- `Certifier for LearnDash`

Upload and activate the official LearnDash LMS plugin in the local WordPress admin before testing course completions.

## Configure

1. Go to `Certifier for LearnDash -> Settings`.
2. Set the Certifier API base URL, for example `https://api.certifier.io`.
3. Paste a Certifier personal access token.
4. Go to `Certifier for LearnDash -> Course Issuance`.
5. Select a LearnDash course and the Certifier group that should be issued when that course is completed.

## API request

When a mapped LearnDash course is completed, the plugin will call:

```text
POST /v1/credentials/create-issue-send
```

with:

```json
{
  "groupId": "01hzy8examplegroupid",
  "recipient": {
    "name": "Admin",
    "email": "admin@example.org"
  },
  "issueDate": "2026-06-18"
}
```

## Test with LearnDash

1. Upload and activate the official LearnDash LMS plugin.
2. Create a course.
3. Map the course to a Certifier group.
4. Enroll a test user.
5. Complete the course as that user.
6. Confirm the credential appears in Certifier.

The plugin stores an idempotency record in user meta after success, so the same user/course/group combination does not issue repeatedly.

## Release to WordPress.org

Releases are deployed to the WordPress.org plugin directory (slug: `certifier-learndash`) by the `Deploy to WordPress.org` GitHub Actions workflow.

To publish a release:

1. Bump `Version` in `certifier-learndash.php` and `CERTIFIER_LEARNDASH_VERSION`.
2. Bump `Stable tag` and add a changelog entry in `readme.txt`.
3. Merge to `main`, then create a GitHub release with a matching tag (e.g. `v0.2.0`).

Publishing the release triggers the workflow, which commits the plugin to SVN `trunk`, creates the SVN tag, syncs `.wordpress-org/` to the SVN `assets` directory (plugin icon, banner, and screenshots for the directory listing), and attaches the built zip to the GitHub release.

The workflow requires the `SVN_USERNAME` and `SVN_PASSWORD` repository secrets (the wordpress.org account credentials), which are only available after the plugin is approved.

Directory listing assets go in `.wordpress-org/` (not shipped inside the plugin zip):

- `icon-128x128.png`, `icon-256x256.png`
- `banner-772x250.png`, `banner-1544x500.png`
- `screenshot-1.png`, `screenshot-2.png`, ... (matching the `== Screenshots ==` section in `readme.txt`)

The CI workflow lints all PHP files and runs the official WordPress Plugin Check on every push and pull request.
