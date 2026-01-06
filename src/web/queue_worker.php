<?php
// CLI worker to process background jobs (emails, parsing, etc.).
// Run via: php queue_worker.php [--limit=N]
date_default_timezone_set("Asia/Hong_Kong");
$config = require __DIR__ . '/config.php';
require __DIR__ . '/testwsqlnew/conn/conn.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "This script should be run from the command line.\n");
    exit(1);
}

$mailConfig = $config['mail'];
$appConfig = $config['app'];

$maxJobs = null; // null = run until the queue is empty; set via --limit to cap.
if (!empty($argv)) {
    foreach ($argv as $arg) {
        if (strpos($arg, '--limit=') === 0) {
            $limitVal = (int)substr($arg, strlen('--limit='));
            if ($limitVal > 0) {
                $maxJobs = $limitVal;
            }
        }
    }
}

$processed = 0;
while (true) {
    if ($maxJobs !== null && $processed >= $maxJobs) {
        break;
    }
    $conn->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

    $job = $conn->query("
        SELECT id, type, payload, attempts
        FROM job_queue
        WHERE status = 'pending' AND available_at <= NOW()
        ORDER BY id ASC
        LIMIT 1
        FOR UPDATE
    ");

    if (!$job || $job->num_rows === 0) {
        $conn->commit();
        break;
    }

    $jobRow = $job->fetch_assoc();
    $jobId = (int)$jobRow['id'];
    $jobType = $jobRow['type'];
    $payload = json_decode($jobRow['payload'], true) ?: [];
    $attempts = (int)$jobRow['attempts'];

    // Mark in progress
    $mark = $conn->prepare("UPDATE job_queue SET status = 'in_progress', attempts = attempts + 1, updated_at = NOW() WHERE id = ?");
    $mark->bind_param("i", $jobId);
    $mark->execute();
    $conn->commit();

    $error = null;

    try {
        switch ($jobType) {
            case 'send_exam_invite':
                sendExamInvite($payload, $mailConfig, $appConfig);
                break;
            case 'parse_sheet':
                // Placeholder: implement spreadsheet parsing if/when needed.
                throw new RuntimeException('parse_sheet handler not implemented');
            default:
                throw new RuntimeException("Unknown job type: {$jobType}");
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }

    if ($error === null) {
        $done = $conn->prepare("UPDATE job_queue SET status = 'done', last_error = NULL, updated_at = NOW() WHERE id = ?");
        $done->bind_param("i", $jobId);
        $done->execute();
    } else {
        // Basic retry with backoff: delay next attempt by 2^attempts minutes, cap at 60 minutes.
        $delayMinutes = min(60, pow(2, $attempts));
        $fail = $conn->prepare("
            UPDATE job_queue
            SET status = 'pending',
                last_error = ?,
                available_at = DATE_ADD(NOW(), INTERVAL ? MINUTE),
                updated_at = NOW()
            WHERE id = ?
        ");
        $fail->bind_param("sii", $error, $delayMinutes, $jobId);
        $fail->execute();
    }

    $processed++;
}

function sendExamInvite(array $payload, array $mailConfig, array $appConfig): void
{
    $required = ['examid', 'studentid', 'student_password', 'deadline', 'roundindex', 'subject', 'title'];
    foreach ($required as $key) {
        if (!array_key_exists($key, $payload)) {
            throw new InvalidArgumentException("Missing payload key: {$key}");
        }
    }

    $message = "The following would be the registration information:\n";
    $message .= "Meeting Code: {$payload['examid']}\n";
    $message .= "Student ID: {$payload['studentid']}\n";
    $message .= "Password: {$payload['student_password']}\n";
    $message .= "URL: {$appConfig['base_url']}/index.html\n";
    if ((int)$payload['roundindex'] > 1) {
        $message .= "The above registration details would be same as the first round, only deadline would be updated\nNew ";
    }
    $message .= "Deadline: {$payload['deadline']}";

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->SMTPAuth = true;
    $mail->Host = $mailConfig['host'];
    $mail->SMTPSecure = $mailConfig['encryption'];
    $mail->Port = $mailConfig['port'];
    $mail->Username = $mailConfig['username'];
    $mail->Password = $mailConfig['password'];
    $mail->setFrom($mailConfig['from_address'], $mailConfig['from_name']);
    $mail->addAddress($payload['studentid'] . $mailConfig['student_domain'], "student");
    $mail->Subject = $payload['subject'];
    $mail->Body = $message;
    $mail->send();
}
