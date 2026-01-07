<?php
date_default_timezone_set("Asia/Hong_Kong");
$config = require __DIR__ . '/config.php';
require __DIR__ . '/testwsqlnew/conn/conn.php';
require 'vendor/autoload.php';

$examid = $_GET['examid'];
$index = $_GET['index'];
$title = isset($_GET['title']) ? $_GET['title'] : '';
$subject = "Meeting Result (" . $title . ")";
$notscheduled = 0;

$studentstmt = $conn->prepare("SELECT `studentid` FROM `studentexammatch` WHERE `examid` = ? AND `scheduled` = ?");
$studentstmt->bind_param("si",$examid, $notscheduled);
$studentstmt->execute();
$studentresult = $studentstmt->get_result();

$stmt = $conn->prepare("
    SELECT r.studentid, m.timeslot
    FROM result r
    JOIN meetingtimeslots m ON m.timeslotid = r.timeslotid
    WHERE r.examid = ? AND r.roundindex = ?
");
$stmt->bind_param("si", $examid, $index);
$stmt->execute();
$result = $stmt->get_result();

$jobsQueued = 0;
if ($result->num_rows >= 1) {
    $insertJob = $conn->prepare("
        INSERT INTO job_queue (type, payload, status, available_at)
        VALUES ('send_result_notice', ?, 'pending', NOW())
    ");

    while ($row = $result->fetch_assoc()) {
        $payload = json_encode([
            'examid' => $examid,
            'studentid' => $row['studentid'],
            'timeslot' => $row['timeslot'],
            'allocated' => true,
            'subject' => $subject,
            'title' => $title,
        ]);
        $insertJob->bind_param("s", $payload);
        $insertJob->execute();
        $jobsQueued++;
    }
    $insertJob->close();
}

if ($studentresult->num_rows >= 1) {
    $insertJob = $conn->prepare("
        INSERT INTO job_queue (type, payload, status, available_at)
        VALUES ('send_result_notice', ?, 'pending', NOW())
    ");

    while ($sturow = $studentresult->fetch_assoc()) {
        $payload = json_encode([
            'examid' => $examid,
            'studentid' => $sturow['studentid'],
            'timeslot' => '0',
            'allocated' => false,
            'subject' => $subject,
            'title' => $title,
        ]);
        $insertJob->bind_param("s", $payload);
        $insertJob->execute();
        $jobsQueued++;
    }
    $insertJob->close();
}

if ($jobsQueued > 0) {
    kickWorkerAsync(__DIR__ . '/queue_worker.php');
}

function kickWorkerAsync(string $workerPath, ?int $limit = null): bool
{
    $php = PHP_BINARY ? escapeshellarg(PHP_BINARY) : 'php';
    $cmd = $php . ' ' . escapeshellarg($workerPath);
    if ($limit !== null && $limit > 0) {
        $cmd .= ' --limit=' . (int)$limit;
    }

    if (function_exists('popen')) {
        $handle = @popen($cmd . ' > /dev/null 2>&1 &', 'r');
        if ($handle !== false) {
            @pclose($handle);
            return true;
        }
    }

    if (function_exists('shell_exec')) {
        @shell_exec($cmd . ' > /dev/null 2>&1 &');
        return true;
    }

    if ($limit === null || $limit > 0) {
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

  <main class="w-100 m-auto" id="main"  >
    <div class="card py-md-5 py-2 px-sm-2 px-md-5   my-5 w-100"  >
      <div class="card-body" >
        <h1 class="mb-4 text-poly">Emails queued for delivery</h1>
        <p class="lead">
          We queued result emails<?php echo $jobsQueued ? " ({$jobsQueued})" : ""; ?>; they will be delivered in the background.
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

