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

The local environment loads two plugins:

- `Certifier for LearnDash`

If you do not have the official LearnDash plugin yet, start with the stub config:

```bash
npx @wordpress/env start --config .wp-env.stub.json
```

The stub exists only so development can continue before the official LearnDash zip is available. Do not activate it alongside the real LearnDash plugin.

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

## Test without LearnDash

Start with the stub config:

```bash
npx @wordpress/env start --config .wp-env.stub.json
```

1. Go to `Tools -> LearnDash Stub`.
2. Click `Create sample course`.
3. Copy the sample course ID into `Certifier for LearnDash -> Course Issuance`.
4. Map it to a Certifier group ID and save.
5. Return to `Tools -> LearnDash Stub`.
6. Select the course and user.
7. Click `Fire course completed hook`.

The plugin will call:

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

## Test with real LearnDash

1. Deactivate `LearnDash Stub (Dev Only)`.
2. Upload and activate the official LearnDash LMS plugin.
3. Create a course.
4. Map that course ID to a Certifier group ID.
5. Enroll a test user.
6. Complete the course as that user.
7. Confirm the credential appears in Certifier.

The plugin stores an idempotency record in user meta after success, so the same user/course/group combination does not issue repeatedly.
