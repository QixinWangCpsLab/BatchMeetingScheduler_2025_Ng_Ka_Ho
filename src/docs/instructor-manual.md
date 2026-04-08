# Instructor Manual

This manual explains how instructors use the current reservation system.

## What You Need to Keep

After creating a meeting, keep these items safely:

- Meeting code
- Teacher edit password
- Meeting deadline
- Student upload file used for the meeting

You will need the meeting code and edit password to check status, view results, create another round, and edit results.

## Instructor Home

Open:

- `teacherview.html`

Available actions:

- Create 1st new meeting
- Create another round of meeting
- Check registration status
- Check allocation results
- Edit allocation results

## Create 1st New Meeting

Open:

- `createmeeting.html`

Enter:

- Meeting title
- Subject title
- Teacher name
- Duration of each meeting in minutes
- Number of preferred days each student must choose
- Number of preferred timeslots per day each student must choose
- Deadline
- One or more meeting days
- One or more time periods for each day
- Student ID upload file

Student upload file rules shown by the page:

- accepted formats are spreadsheet-style uploads
- the first row is treated as a header
- each following row should contain one student ID in the first column

What happens next:

1. Submit the form.
2. The system generates a meeting code and a teacher edit password.
3. You are redirected to the meeting status page.
4. The status page can queue invitation emails.

Important notes:

- Deadline must be in the future.
- Each time period must align exactly with the meeting duration.
- At least one valid timeslot must be generated.

## Send Invitation Emails

After a successful first-round meeting creation, the status page shows a `Send Mail` button.

What it does:

1. Queues invitation emails for students who have not yet been scheduled.
2. Starts the background worker when possible.
3. Sends each student their meeting code, student ID, password, and deadline.

If the page says emails were queued, that means the jobs were written to the queue. Delivery still depends on valid mail configuration.

## Check Registration Status

Open:

- `checkstatus.html`

Enter:

- Meeting code
- Teacher edit password

The meeting status page shows:

- meeting details
- number of students who have submitted preferences
- number of students already scheduled

Depending on how you arrived there, it may also provide:

- `Send Mail`
- the meeting code and edit password reminder

## Check Allocation Results

Open:

- `checkallresult.html`

Enter:

- Meeting code
- Teacher edit password

What the result page does:

- displays meeting details
- shows the allocated student for each timeslot
- may trigger allocation generation after the deadline if results do not already exist
- provides a button to send result emails

Teacher view options:

- Display all timeslots
- Display students' selected timeslots only

Important behavior:

- First-round results are intended to be available only after the deadline.
- Allocation uses the preferences already submitted by students.

## Send Result Emails

From the result page, click `Send Mail`.

What it does:

- queues result emails for allocated students
- also queues notices for students who are still unallocated in the current result set

Students then use the student-side result page to confirm their allocation directly in the system.

## Edit Allocation Results

Open:

- `editresult.html`

Enter:

- Meeting code
- Teacher edit password

The edit page allows you to manually assign or clear student-timeslot mappings.

Important page rule:

- each student should be assigned to at most one timeslot

The page warns about duplicate assignment. Use `0` to clear a slot when needed.

## Create Another Round of Meeting

Open:

- `roundmeeting.html`

Enter:

- Meeting code
- Teacher edit password

The next page shows the existing meeting context and lets you:

- review the current round count
- add additional dates and times for the extra round
- set a new deadline

When you submit:

- the system increments the round index
- new dates may be added to the same meeting
- new timeslots may be added if they do not already exist

Use this when some students remain unallocated after an earlier round.

## Export Status

The codebase includes an `exportexcel.php` action referenced from the status page logic. If you use that function in your environment, treat the exported data as a snapshot of the current registration state.

## Troubleshooting for Instructors

### I forgot the edit password

The current system relies on the password shown at meeting creation time. There is no separate password recovery flow in the current pages.

### Students did not receive emails

Possible causes:

- email jobs were queued but the worker failed
- SMTP settings are incorrect
- the configured email domain suffix does not match the intended student addresses

### Results are not visible yet

Check:

- the meeting deadline has passed
- students actually submitted preferences
- results were generated or edited for the current round

### I need to change results manually

Use the `Edit allocation results` flow rather than editing the database directly unless you are acting as a developer with a clear rollback plan.
