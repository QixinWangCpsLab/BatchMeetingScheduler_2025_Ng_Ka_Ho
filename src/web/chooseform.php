<?php
date_default_timezone_set("Asia/Hong_Kong");
include $_SERVER["DOCUMENT_ROOT"] . "/testwsqlnew/conn/conn.php";

function failAndExit(string $message): void {
    http_response_code(400);
    echo "<script>alert('".addslashes($message)."');window.history.back();</script>";
    exit;
}

// Use a predictable isolation level for concurrent submissions.
$conn->query('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');

$examId = $_POST['examid'] ?? '';
$studentId = trim($_POST['studentid'] ?? '');
$studentPassword = $_POST['stupassword'] ?? '';
$timestamp = $_POST['timestamp'] ?? '';

if ($examId === '' || $studentId === '' || $studentPassword === '') {
    failAndExit("Missing required information. Please fill in all fields.");
}

$examStmt = $conn->prepare("SELECT `deadline`, `datechoicenum`, `slotchoicenum` FROM `exam` WHERE `examid` = ?");
$examStmt->bind_param("s", $examId);
$examStmt->execute();
$examResult = $examStmt->get_result();

if ($examResult->num_rows !== 1) {
    failAndExit("Meeting not found.");
}

$exam = $examResult->fetch_assoc();
$mt_deadline = $exam['deadline'];
$datenum = (int)$exam['datechoicenum'];
$timeslotsnum = (int)$exam['slotchoicenum'];

if (time() > strtotime($mt_deadline)) {
    failAndExit("The selection deadline has passed.");
}

$studentPassStmt = $conn->prepare("SELECT `password` FROM `studentexammatch` WHERE `examid` = ? AND `studentid` = ?");
$studentPassStmt->bind_param("ss", $examId, $studentId);
$studentPassStmt->execute();
$studentPassResult = $studentPassStmt->get_result();

if ($studentPassResult->num_rows !== 1) {
    failAndExit("Student is not registered for this meeting.");
}

$passwordRow = $studentPassResult->fetch_assoc();
if ($studentPassword !== $passwordRow['password']) {
    failAndExit("Incorrect password.");
}

$total = $datenum * $timeslotsnum;
if ($total < 1) {
    failAndExit("No time slots configured for this meeting.");
}

$selectedTimeslots = [];
for ($priority = 1; $priority <= $total; $priority++) {
    $key = 'choose'.$priority;
    if (!isset($_POST[$key]) || !is_numeric($_POST[$key])) {
        failAndExit("Missing selection for priority {$priority}.");
    }
    $selectedTimeslots[$priority] = (int)$_POST[$key];
    if ($selectedTimeslots[$priority] <= 0) {
        failAndExit("Invalid selection for priority {$priority}.");
    }
}

// Ensure no duplicate slot choices.
if (count(array_unique($selectedTimeslots)) !== count($selectedTimeslots)) {
    failAndExit("Duplicate time-slot selections detected. Please choose unique slots.");
}

// Validate each timeslot belongs to this exam and is available.
$slotCheckStmt = $conn->prepare("SELECT `scheduled` FROM `meetingtimeslots` WHERE `examid` = ? AND `timeslotid` = ?");
foreach ($selectedTimeslots as $priority => $slotId) {
    $slotCheckStmt->bind_param("si", $examId, $slotId);
    $slotCheckStmt->execute();
    $slotCheckResult = $slotCheckStmt->get_result();
    if ($slotCheckResult->num_rows !== 1) {
        failAndExit("Selected time slot #{$priority} is not valid for this meeting.");
    }
    $slotRow = $slotCheckResult->fetch_assoc();
    if ((int)$slotRow['scheduled'] !== 0) {
        failAndExit("Selected time slot #{$priority} is already scheduled.");
    }
}

$prefExistsStmt = $conn->prepare("SELECT 1 FROM `preference` WHERE `examid` = ? AND `studentid` = ? LIMIT 1");
$prefExistsStmt->bind_param("ss", $examId, $studentId);
$prefExistsStmt->execute();
$prefExists = $prefExistsStmt->get_result()->num_rows > 0;

$cleanTimestamp = ctype_digit((string)$timestamp) ? $timestamp : time();

// Batch the preference writes in one transaction to reduce round trips and keep the set consistent.
$conn->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

if ($prefExists) {
    $updatePref = $conn->prepare("UPDATE `preference` SET `timestamp` = ?, `timeslotid` = ? WHERE `examid` = ? AND `studentid` = ? AND `priority` = ?");
    for ($priority = 1; $priority <= $total; $priority++) {
        $updatePref->bind_param("sissi", $cleanTimestamp, $selectedTimeslots[$priority], $examId, $studentId, $priority);
        $updatePref->execute();
    }
    $updatePref->close();
} else {
    $insertPref = $conn->prepare("INSERT INTO `preference` (`examid`, `studentid`, `timestamp`, `timeslotid`, `priority`) VALUES (?, ?, ?, ?, ?)");
    for ($priority = 1; $priority <= $total; $priority++) {
        $insertPref->bind_param("sssii", $examId, $studentId, $cleanTimestamp, $selectedTimeslots[$priority], $priority);
        $insertPref->execute();
    }
    $insertPref->close();
}

$conn->commit();

header('Location: sortsequence.php?examid='.$examId.'&studentid='.$studentId);
?>
