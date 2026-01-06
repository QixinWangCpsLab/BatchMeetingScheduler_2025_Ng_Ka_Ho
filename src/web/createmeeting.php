<?php


date_default_timezone_set("Asia/Hong_Kong");
include $_SERVER["DOCUMENT_ROOT"] . "/testwsqlnew/conn/conn.php";

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function failAndExit(string $message): void {
    http_response_code(400);
    echo "Error: " . htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    exit;
}

function guidv4()
{
    $data = random_bytes(16);

    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function random_str(
    int $length = 64,
    string $keyspace = '0123456789abcdefghijklmnopqrstuvwxyz'
): string {
    if ($length < 1) {
        throw new \RangeException("Length must be a positive integer");
    }
    $pieces = [];
    $max = mb_strlen($keyspace, '8bit') - 1;
    for ($i = 0; $i < $length; ++$i) {
        $pieces []= $keyspace[random_int(0, $max)];
    }
    return implode('', $pieces);
}

$required = ['title', 'subject', 'teacher', 'duration', 'deadline', 'Datechoicenum', 'Slotchoicenum', 'daycount'];
foreach ($required as $field) {
    if (!isset($_POST[$field]) || $_POST[$field] === '') {
        failAndExit("Missing field: {$field}");
    }
}

$title = trim($_POST["title"]);
$subject = trim($_POST["subject"]);
$teacher = trim($_POST["teacher"]);
$duration = (int)$_POST["duration"];
$deadline = $_POST["deadline"];
$dateChoiceNum = (int)$_POST["Datechoicenum"];
$slotChoiceNum = (int)$_POST["Slotchoicenum"];
$dayCount = (int)$_POST["daycount"];

if ($duration < 1) {
    failAndExit("Duration must be a positive number.");
}
if ($dateChoiceNum < 1) {
    failAndExit("Preferred days must be a positive number.");
}
if ($slotChoiceNum < 1) {
    failAndExit("Preferred slots per day must be a positive number.");
}
if ($dayCount < 1) {
    failAndExit("At least one meeting day is required.");
}
if (strtotime($deadline) <= time()) {
    failAndExit("Deadline must be in the future.");
}
if ($dateChoiceNum > $dayCount) {
    failAndExit("Preferred day choices cannot exceed the number of configured days.");
}

if (!isset($_FILES['importfile']) || $_FILES['importfile']['error'] !== UPLOAD_ERR_OK) {
    failAndExit("Student ID upload failed. Please re-upload the file.");
}
$fileName = $_FILES['importfile']['name'];
$inputFileName = $_FILES['importfile']['tmp_name'];
$fileSize = (int)$_FILES['importfile']['size'];
$extAllowed = preg_match('/\.(xlsx|xls|csv)$/i', $fileName);
if ($fileSize <= 0 || $fileSize > 2 * 1024 * 1024) {
    failAndExit("Upload file must be under 2 MB.");
}
if (!$extAllowed) {
    failAndExit("Upload must be a .xlsx, .xls, or .csv file.");
}

$examid =guidv4();

$Scount = 0;

// Insert meeting dates after validating each provided day/time.
for ($z = 1; $z <= $dayCount; $z++) {
    $insertdate = $_POST["day{$z}date"] ?? '';
    if ($insertdate === '') {
        failAndExit("Missing date for Day {$z}.");
    }
    $datestmt = $conn -> prepare('INSERT INTO `MeetingDate` (`dateid`, `examid`, `date`) VALUES (NULL,?,?);');
    $datestmt->bind_param("ss", $examid, $insertdate);
    $datestmt->execute();
}
for ($x = 1; $x <= $dayCount; $x++) {



    $date= $_POST["day{$x}date"];

    for ($y = 0; $y < count($_POST["day{$x}startime"]); $y++) {
        $startime = $_POST["day{$x}startime"][$y];
        $stoptime = $_POST["day{$x}endtime"][$y];
        if ($startime === '' || $stoptime === '') {
            continue;
        }
        $timeperiod = (strtotime($stoptime) - strtotime($startime))/60;

        if($timeperiod <= 0) {
            failAndExit("End time must be after start time for {$date}.");
        }

        if($timeperiod%$duration==0){



            $timeslotstart=$startime;
            $timeslotend=date("H:i", strtotime("+{$duration} minutes", strtotime($startime)));

            do {
                $searchdate = $conn -> prepare('SELECT * FROM `MeetingDate` WHERE `examid`= ? AND `date` = ?');
                $searchdate->bind_param("ss", $examid, $date);
                $searchdate->execute();
                $dateS = $searchdate->get_result();

                if ($dateS->num_rows==1){
                    while ($Srow = $dateS->fetch_assoc()) {
                        $mt_dateid = $Srow['dateid'];
                    }
                }

                $timeslotname = $date."_".$timeslotstart."-".$timeslotend;
                $schedulednum = 0;
                $slotstmt = $conn -> prepare('INSERT INTO `meetingtimeslots` (`timeslotid`, `examid`,`timeslot`,`dateid`,`scheduled`) VALUES (NULL,?,?,?,?);');
                $slotstmt->bind_param("ssii", $examid, $timeslotname, $mt_dateid, $schedulednum);
                $slotstmt->execute();
                $Scount++;

                $timeslotstart=$timeslotend;
                $timeslotend=date("H:i", strtotime("+{$duration} minutes", strtotime($timeslotstart)));

            } while (strtotime($timeslotend)<=strtotime($stoptime));



        } else {
            failAndExit("Time range on {$date} must align with the chosen duration.");
        }


    }

}

if ($Scount < 1) {
    failAndExit("Please provide at least one valid time slot.");
}

$password=random_str(8);


/*
if ($Datechoicenum > $_POST["daycount"]){
    ?>
    <script>
        console.log("get in?");
        alert("Invalid Input");
        
        window.location.href = 'index.html';
    </script>
    <?php
}

if ($Scount > $Slotchoicenum){
    ?>
    <script>
        alert("Invalid Input");
        window.location.href = 'index.html';
    </script>
    <?php
}
*/
//$datepref=json_encode($dateprefarray);

/** Load $inputFileName to a Spreadsheet object **/
$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileName);
$studentidData = $spreadsheet->getActiveSheet()->toArray();
$counttitle = "0";
$studentstmt = $conn -> prepare('INSERT INTO `studentexammatch` (`examid`, `studentid` ,`password`,`scheduled`) VALUES (?,?,?,? );');
foreach ($studentidData as $row){
    if ($counttitle>0){
        $everystudent = trim((string)$row['0']);
        if ($everystudent === '') {
            continue;
        }
        if (!preg_match('/^[A-Za-z0-9_-]{3,64}$/', $everystudent)) {
            failAndExit("Invalid student ID detected: {$everystudent}.");
        }
        $stupassword = random_str(8);

        $SScounter = 0;
        $studentstmt->bind_param("sssi", $examid, $everystudent, $stupassword, $SScounter);
        $studentstmt->execute();
    }
    else{
        $counttitle = "1";
    }
}

$roundindex = 1;

$stmt = $conn->prepare('INSERT INTO `exam` (`examid`, `title`, `subject`, `teacher`, `duration`, `deadline`, `datechoicenum`, `slotchoicenum` ,`password`,`roundindex`) VALUES (?,? ,? ,? ,? ,? ,? ,? ,?,?);');

$stmt->bind_param("ssssisiisi", $examid,  $title , $subject,$teacher ,$duration,$deadline, $dateChoiceNum, $slotChoiceNum, $password,$roundindex);

$stmt->execute();


$datestmt->close();
$studentstmt->close();
$stmt->close();
header("Location: status.php?examid={$examid}&password={$password}&success");
?>
