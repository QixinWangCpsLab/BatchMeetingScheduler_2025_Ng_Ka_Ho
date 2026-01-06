<?php
date_default_timezone_set("Asia/Hong_Kong");
$config = require __DIR__ . '/config.php';
require __DIR__ . '/testwsqlnew/conn/conn.php';
require 'vendor/autoload.php';

$examid = $_GET['examid'];
$title = isset($_GET['title']) ? $_GET['title'] : '';
$subject = "New Meeting Registration (" . $title . ")";
$notscheduled = 0;

$stmt = $conn->prepare("SELECT studentid, password FROM `studentexammatch` WHERE `examid` = ? AND `scheduled` = ?");
$stmt->bind_param("si", $examid, $notscheduled);
$stmt->execute();
$result = $stmt->get_result();

$examstmt = $conn->prepare("SELECT `deadline`, `roundindex` FROM `exam` WHERE `examid` = ?");
$examstmt->bind_param("s", $examid);
$examstmt->execute();
$examresult = $examstmt->get_result();

$mt_deadline = null;
$mt_roundindex = null;
if ($examrow = $examresult->fetch_assoc()) {
    $mt_deadline = $examrow["deadline"];
    $mt_roundindex = $examrow["roundindex"];
}

$jobsQueued = 0;
if ($result->num_rows >= 1 && $mt_deadline) {
    $insertJob = $conn->prepare("
        INSERT INTO job_queue (type, payload, status, available_at)
        VALUES ('send_exam_invite', ?, 'pending', NOW())
    ");

    while ($row = $result->fetch_assoc()) {
        $payload = json_encode([
            'examid' => $examid,
            'studentid' => $row['studentid'],
            'student_password' => $row['password'],
            'deadline' => $mt_deadline,
            'roundindex' => $mt_roundindex,
            'subject' => $subject,
            'title' => $title,
        ]);
        $insertJob->bind_param("s", $payload);
        $insertJob->execute();
        $jobsQueued++;
    }
    $insertJob->close();

    // Attempt to kick off the worker as a background process for environments without cron.
    // No limit passed so the worker drains the queue in this run.
    kickWorkerAsync(__DIR__ . '/queue_worker.php');
}

function kickWorkerAsync(string $workerPath, ?int $limit = null): bool
{
    $php = PHP_BINARY ? escapeshellarg(PHP_BINARY) : 'php';
    $cmd = $php . ' ' . escapeshellarg($workerPath);
    if ($limit !== null && $limit > 0) {
        $cmd .= ' --limit=' . (int)$limit;
    }

    // Background via popen
    if (function_exists('popen')) {
        $handle = @popen($cmd . ' > /dev/null 2>&1 &', 'r');
        if ($handle !== false) {
            @pclose($handle);
            return true;
        }
    }

    // Background via shell_exec
    if (function_exists('shell_exec')) {
        @shell_exec($cmd . ' > /dev/null 2>&1 &');
        return true;
    }

    // As a fallback, run synchronously for a tiny batch to avoid blocking too long.
    if ($limit > 0) {
        @exec($cmd);
        return true;
    }

    return false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
  <link rel="icon" href="images/favicon.ico" />
  <title>PolyU reservation system</title>
  <link rel="stylesheet" href="styles/bootstrap.min.css" >
  <link rel="stylesheet" href="styles/main.css" >
</head>
<body class="bg-poly d-flex align-items-center h-100">

<div class="container">

  <main class="w-100 m-auto" id="main">
    <div class="card py-md-5 py-2 px-sm-2 px-md-5 my-5 w-100">
      <div class="card-body">
        <h1 class="mb-4 text-poly">Emails queued for delivery</h1>
        <p class="lead">
          We queued invitation emails<?php echo $jobsQueued ? " ({$jobsQueued})" : ""; ?>; they will be delivered in the background.
        </p>

        <div class="d-grid">
          <button type="button" id="return" class="btn btn-secondary fw-bold text-white">Back to Homepage</button>
        </div>

      </div>
    </div>
  </main>

</div>

<script src="scripts/jquery-3.6.0.min.js"></script>

<script>
  $("#return").click(function(){
    window.location.href = "index.html";
  });
</script>


</body>
</html>
