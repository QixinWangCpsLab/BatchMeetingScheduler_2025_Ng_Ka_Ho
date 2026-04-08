# Maintenance Guide

This guide is for future developers extending or repairing the current system without breaking the live workflows.

## Maintenance Principles

- Preserve the existing teacher and student page flows unless a change is intentionally user-facing.
- Prefer small, traceable changes because each PHP entrypoint often handles UI, validation, and DB writes together.
- Confirm schema assumptions before changing business logic; many relationships are enforced in code rather than the DB.
- Keep new secrets in environment variables, not in PHP source defaults.

## Safe Extension Points

### Add or Adjust Configuration

Primary files:

- `src/web/config.php`
- `src/web/testwsqlnew/conn/conn.php`
- `src/docker-compose.yml`

Recommended process:

1. Add the new config key in `config.php`.
2. Read it from the consuming PHP file through the config array.
3. Add a local environment override path in Docker Compose or `.env`.
4. Document the new variable in `docs/developer-setup.md`.

Avoid reading raw environment variables in many separate files unless the feature is intentionally standalone like the current chatbot.

### Add a New Teacher or Student Flow

Current application pattern:

- HTML page provides the form
- PHP page validates, queries, mutates, and renders or redirects

To fit the existing system:

1. Add the entry action to `teacherview.html` or `studentview.html` if it is user-facing.
2. Create a dedicated handler page rather than overloading an unrelated file.
3. Reuse existing DB entities where possible.
4. Keep redirects and success states consistent with the current page navigation style.
5. Update the user manuals after the feature lands.

### Add a New Background Job

Relevant files:

- `src/db/capstone_project.sql`
- `src/web/sendmail.php`
- `src/web/sendresultmail.php`
- `src/web/queue_worker.php`

Recommended process:

1. Reuse `job_queue` rather than introducing a second queue mechanism.
2. Insert a new `type` plus JSON `payload`.
3. Add a handler branch in `queue_worker.php`.
4. Validate required payload keys inside the handler.
5. Decide whether the job is safe to retry.
6. Document the new job type in the architecture and maintenance docs.

The placeholder `parse_sheet` case already shows where future worker extensions belong.

### Extend Chatbot Behavior

Relevant files:

- `src/web/chatbot.php`
- `src/web/api_chat.php`

Constraints:

- The chatbot is intentionally informational only.
- It should explain system workflows already visible in the UI.
- It should not claim to create meetings, submit forms, edit data, or send emails.

If you extend it:

1. Update the system instruction in `api_chat.php`.
2. Keep the prompt grounded in actual system pages.
3. Add any new environment variables to setup docs.
4. Test both with and without a Gemini key present.

## Fragile Areas

### Result Generation in `result.php`

This file is both a page renderer and an allocator.

Before editing:

- understand the deadline checks
- understand how `roundindex` is used
- preserve the `FOR UPDATE` locking pattern
- preserve the unique constraints in `result`

If you change allocation rules, manually test concurrent or repeated result generation scenarios.

### Preference Submission in `chooseform.php`

This file enforces:

- student authentication
- deadline checks
- unique slot selection
- per-slot validity
- transactional preference writes

If you change choice counts, slot validation, or how updates work, verify both first-time submission and overwrite behavior.

### Meeting Creation in `createmeeting.php` and `roundform.php`

These files:

- expand date ranges into discrete slots
- import student IDs
- generate passwords
- write several related tables in one request

Any change to time-slot generation logic can affect:

- what students can select
- what teachers can allocate
- whether later rounds behave correctly

### Mail Queue Launching

`sendmail.php` and `sendresultmail.php` try to trigger `queue_worker.php` asynchronously.

Risks:

- asynchronous launch behavior differs by environment
- container/process restrictions may block background execution
- jobs may remain pending even though the UI says emails were queued

When troubleshooting mail, inspect `job_queue` instead of trusting the page message alone.

## What to Check Before Schema Changes

Any schema change should be checked against:

- `src/db/capstone_project.sql`
- all PHP queries touching the table
- user flows that read or write the affected fields
- queue payloads if they rely on the data

At minimum, review impact on:

- `exam`
- `studentexammatch`
- `meetingtimeslots`
- `preference`
- `result`
- `job_queue`

## What to Check Before Mail Changes

- `config.php` contains the setting names used by the worker
- invitation emails include the correct base URL
- `MAIL_STUDENT_DOMAIN` and `MAIL_RESULT_DOMAIN` match the intended recipient pattern
- retry behavior in `queue_worker.php` still makes sense for the failure mode
- the user manuals still describe teacher-triggered email actions accurately

## What to Check Before Changing Result Logic

- deadline gating for first-round results
- round handling in `exam.roundindex`
- uniqueness constraints in `result`
- `scheduled` flags in `studentexammatch` and `meetingtimeslots`
- teacher-side edit flow in `editresult.php`
- student-side result lookup in `result.php`

## Verification Checklist After Any Non-Trivial Change

- The homepage still links to the correct teacher and student entry pages.
- Teacher can create a meeting and obtain meeting code plus edit password.
- Student can log in with meeting code, student ID, and student password.
- Student can save preferences and view them later.
- Teacher can view status and results.
- Result emails or invite emails still queue successfully if the feature was touched.
- Chatbot still responds or fails gracefully if its feature was touched.
