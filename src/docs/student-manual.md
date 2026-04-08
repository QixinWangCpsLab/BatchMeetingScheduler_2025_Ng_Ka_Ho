# Student Manual

This manual explains how students use the current reservation system.

## What You Need Before You Start

You need the following information from the invitation email or your instructor:

- Meeting code
- Your student ID
- Your student password
- The submission deadline

The student password is not your normal account password. It is the password generated for this meeting.

## Student Home

Open:

- `studentview.html`

Available actions:

- Choose timeslots preferences
- Check allocation result
- Check chosen timeslots

## Choose Timeslot Preferences

Open:

- `studentinput.html`

Enter:

- Meeting code
- Student ID
- Student password

After login, the system shows:

- meeting title
- subject title
- teacher name
- duration of each meeting
- deadline
- meeting code

You then select:

- the required number of preferred dates
- the required number of preferred timeslots for each selected date

Important rules shown or enforced by the system:

- choices must be unique
- choices must be entered in priority order
- the meeting must still be before the deadline
- only valid available timeslots can be submitted

When you submit successfully, the system saves your ranked preferences.

## Check Your Chosen Timeslots

Open:

- `checkchoose.html`

Enter:

- Meeting code
- Student ID
- Student password

The system shows the timeslots you selected and their priority order.

If you still want to update your choices before the deadline, the page provides a link back to the student input page.

## Check Your Allocation Result

Open:

- `checkstudentresult.html`

Enter:

- Meeting code
- Student ID
- Student password

The result page shows your assigned timeslot if one has been allocated for your current round.

If no allocation is available yet, it usually means one of these conditions applies:

- the instructor has not generated results yet
- the deadline has not passed yet
- you were not allocated in the current round

## What Comes in the Invitation Email

The invitation email is intended to provide:

- meeting code
- student ID
- student password
- application link
- submission deadline

Keep this email until the meeting process is complete.

## What Comes in the Result Email

If result emails are configured and sent by the instructor, the email can tell you:

- your meeting code
- your student ID
- your allocated timeslot

If you were not allocated yet, the message may indicate that you need to wait for another round.

## Using the Chatbot

Open:

- `chatbot.php`

What the chatbot can do:

- explain how to use teacher and student pages
- answer process questions about creating meetings, choosing timeslots, and checking results

What the chatbot cannot do:

- submit your preferences
- change your meeting data
- send emails
- book a slot directly for you

It is a help tool only.

## Troubleshooting for Students

### The meeting code does not work

Check:

- letter and number case
- whether you copied the full code
- whether the instructor gave you the correct meeting

### My password does not work

Use the meeting-specific password from the invitation email. The current system treats this value as case-sensitive.

### I cannot submit preferences

Possible causes:

- the deadline has passed
- one or more selections are missing
- one or more selected timeslots are duplicated
- your account is not registered for that meeting

### I want to change my choices

Before the deadline:

1. open `checkchoose.html` or `studentinput.html`
2. log in again with meeting code, student ID, and student password
3. resubmit your preferences

### I do not see a result

Possible causes:

- result generation has not happened yet
- the meeting is still before the deadline
- you were not allocated in the current round

If in doubt, contact the instructor with your meeting code and student ID.
