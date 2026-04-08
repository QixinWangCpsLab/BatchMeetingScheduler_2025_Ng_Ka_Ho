# System Architecture

This document describes the current implementation shape of the Batch Meeting Scheduler so future maintainers can reason about changes before editing the code.

## Runtime Stack

- PHP 8.0 running under Apache
- MySQL 8.0
- Composer dependencies:
  - `phpoffice/phpspreadsheet`
  - `phpmailer/phpmailer`
- Docker Compose for local orchestration
- jQuery and Bootstrap-based static frontend pages
- Optional Gemini-backed chatbot integration

## High-Level Application Model

The system is a server-rendered PHP application for scheduling teacher-student meetings through ranked student preferences.

There are three major user-facing surfaces:

- Teacher workflows
- Student workflows
- Reservation help chatbot

There is also one operational subsystem:

- Background email delivery through a DB-backed queue

## Main User Flows

### Teacher Flow

Teacher entry page: `src/web/teacherview.html`

Available actions:

- Create first-round meeting
- Create another round of meeting
- Check registration status
- Check allocation results
- Edit allocation results

Typical first-round flow:

1. Teacher opens `createmeeting.html`.
2. Form posts to `createmeeting.php`.
3. PHP validates inputs, parses uploaded student IDs, creates the meeting, meeting dates, timeslots, student records, and teacher edit password.
4. Teacher is redirected to `status.php`.
5. From `status.php`, the teacher can queue invitation emails.

Typical result flow:

1. Teacher opens `checkallresult.html`.
2. Form redirects to `result.php`.
3. `result.php` can trigger allocation generation after the deadline if results do not yet exist.
4. Teacher can view allocations and queue result emails.

### Student Flow

Student entry page: `src/web/studentview.html`

Available actions:

- Choose timeslot preferences
- Check allocation result
- Check chosen timeslots

Typical preference flow:

1. Student opens `studentinput.html`.
2. Form posts to `choose.php`.
3. `choose.php` validates meeting code, student ID, password, and deadline.
4. Student selects dates and timeslots on the generated preference page.
5. Form posts to `chooseform.php`.
6. `chooseform.php` validates uniqueness and slot availability, then writes rows into `preference`.

### Chatbot Flow

- UI page: `chatbot.php`
- Backend endpoint: `api_chat.php`
- Session-backed chat history
- External API call to Gemini

The chatbot is informational only. It does not submit forms, mutate records, or send emails.

## Subsystems

### Meeting Creation

Main files:

- `createmeeting.html`
- `createmeeting.php`
- `roundmeeting.html`
- `createroundmeeting.php`
- `roundform.php`

Responsibilities:

- Create the meeting record in `exam`
- Create allowed dates in `MeetingDate`
- Expand each configured date range into discrete timeslots in `meetingtimeslots`
- Import student IDs from spreadsheet upload
- Generate per-student passwords in `studentexammatch`
- Generate a teacher edit password for later management
- Advance the round and deadline when additional rounds are created

### Preference Capture

Main files:

- `studentinput.html`
- `choose.php`
- `chooseform.php`
- `showchoose.php`
- `ajaxpro.php`
- `sortsequence.php`

Responsibilities:

- Authenticate the student against `studentexammatch`
- Enforce meeting deadline
- Show valid date/time options
- Persist ranked preferences
- Allow the student to review submitted preferences

### Allocation and Result Management

Main files:

- `checkallresult.html`
- `result.php`
- `editresult.html`
- `editresult.php`
- `checkstudentresult.html`

Responsibilities:

- Trigger or display slot allocation results
- Prevent double-booking with transactional checks
- Show teacher and student result views
- Allow teacher-side manual adjustment after allocation

### Async Email Queue

Main files:

- `sendmail.php`
- `sendresultmail.php`
- `queue_worker.php`
- `config.php`

Responsibilities:

- Queue invitation and result emails in `job_queue`
- Run a worker that drains pending jobs
- Retry failed jobs with exponential backoff
- Isolate email sending from the HTTP request path

### Chatbot

Main files:

- `chatbot.php`
- `api_chat.php`

Responsibilities:

- Render the assistant UI
- Keep short session history
- Constrain responses to existing system workflows

## Data Model

The schema is defined in `src/db/capstone_project.sql`.

### `exam`

Stores one logical meeting campaign.

Important fields:

- `examid`: public meeting code and primary key
- `title`
- `subject`
- `teacher`
- `duration`
- `deadline`
- `datechoicenum`
- `slotchoicenum`
- `password`: teacher edit password
- `roundindex`: current round number

### `MeetingDate`

Stores allowed dates for a meeting.

Key relationship:

- `examid` links each date to one meeting

### `meetingtimeslots`

Stores the actual selectable slot inventory.

Important fields:

- `timeslotid`
- `examid`
- `timeslot`
- `dateid`
- `scheduled`

### `studentexammatch`

Stores which students belong to which meeting and whether they have been allocated.

Important fields:

- `examid`
- `studentid`
- `password`
- `scheduled`

### `preference`

Stores ranked student choices.

Important fields:

- `examid`
- `studentid`
- `timestamp`
- `timeslotid`
- `priority`

### `result`

Stores assigned slots.

Important constraints:

- one result per student per round
- one result per timeslot per round

### `job_queue`

Stores asynchronous tasks.

Important fields:

- `type`
- `payload`
- `status`
- `available_at`
- `attempts`
- `last_error`

Currently used job types:

- `send_exam_invite`
- `send_result_notice`

Reserved placeholder:

- `parse_sheet`

## Request and Control Flow

The codebase follows a page-per-action pattern rather than an MVC or API-first structure.

Common pattern:

1. Static HTML page collects inputs.
2. Form posts or redirects to a PHP page.
3. PHP file handles validation, DB access, and often rendering.
4. Redirects or inline JavaScript alerts are used for control flow.

Examples:

- `createmeeting.html` -> `createmeeting.php` -> `status.php`
- `studentinput.html` -> `choose.php` -> `chooseform.php` -> `sortsequence.php`
- `checkallresult.html` -> `result.php`

## Concurrency and Allocation Behavior

The most sensitive concurrency logic is in `result.php` and `chooseform.php`.

- `chooseform.php` sets transaction isolation to `READ COMMITTED` before writing preferences.
- `result.php` uses `FOR UPDATE` locking and explicit transactions to avoid allocating the same slot to multiple students.
- `result` table uniqueness constraints provide a second layer of protection if application logic races.

This means result generation is not just presentation logic. It also performs business-critical mutation.

## Configuration Model

Primary configuration file:

- `src/web/config.php`

Current behavior:

- Reads DB, mail, and app settings from environment variables
- Provides defaults when environment variables are missing
- Is consumed by DB connection code and queue-driven mail sending

Chatbot-specific environment variables are read directly in `api_chat.php`.

## What to Read Before Making Changes

- For environment or credential changes: `config.php`, `conn.php`, `docker-compose.yml`
- For meeting creation changes: `createmeeting.php`, `roundform.php`, `capstone_project.sql`
- For preference changes: `choose.php`, `chooseform.php`, `showchoose.php`
- For allocation changes: `result.php`, `editresult.php`, `capstone_project.sql`
- For email changes: `sendmail.php`, `sendresultmail.php`, `queue_worker.php`
- For chatbot changes: `chatbot.php`, `api_chat.php`
