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
5. Add course mappings, one per line:

```text
123=01hzy8examplegroupid
```

The left side is the LearnDash course post ID. The right side is the Certifier group ID.

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
3. Map that course ID to a Certifier group ID.
4. Enroll a test user.
5. Complete the course as that user.
6. Confirm the credential appears in Certifier.

The plugin stores an idempotency record in user meta after success, so the same user/course/group combination does not issue repeatedly.
