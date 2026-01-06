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

$examid = $_POST['examid'] ?? '';
$roundindex = (int)($_POST['roundindex'] ?? 0);
$password = $_POST['password'] ?? '';
$duration = (int)($_POST['duration'] ?? 0);
$newDeadline = $_POST['deadline'] ?? '';
$daycount = (int)($_POST["daycount"] ?? 0);

if ($examid === '' || $roundindex < 1 || $duration < 5 || $password === '') {
    failAndExit("Invalid request.");
}
if ($daycount < 1) {
    failAndExit("Day count must be at least 1.");
}
if (strtotime($newDeadline) <= time()) {
    failAndExit("Deadline must be set in the future.");
}

$slotsCreated = 0;

for ($z = 1; $z <= $daycount; $z++) {
    $insertdate = $_POST["day{$z}date"] ?? '';
    if ($insertdate === '') {
        continue;
    }

    $finddate = $conn -> prepare('SELECT * FROM `MeetingDate` WHERE `examid` = ? AND `date` = ? ;');
    $finddate->bind_param("ss", $examid, $insertdate);
    $finddate->execute();
    $finddateR = $finddate->get_result();

    if ($finddateR->num_rows==0){
        $datestmt = $conn -> prepare('INSERT INTO `MeetingDate` (`dateid`, `examid`, `date`) VALUES (NULL,?,?);');
        $datestmt->bind_param("ss", $examid, $insertdate);
        $datestmt->execute();
    }
    $finddate ->free_result();
    $finddate -> close();
}

for ($x = 1; $x <= $daycount; $x++) {

    $date= $_POST["day{$x}date"] ?? '';
    if ($date === ""){
        continue;
    }

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
                $finddate = $conn -> prepare('SELECT * FROM `MeetingDate` WHERE `examid`= ? AND `date` = ?');
                $finddate->bind_param("ss", $examid, $date);
                $finddate->execute();
                $finddateR = $finddate->get_result();

                if ($finddateR->num_rows==1){
                    while ($Srow = $finddateR->fetch_assoc()) {
                        $mt_dateid = $Srow['dateid'];
                    }
                }

                $timeslotname = $date."_".$timeslotstart."-".$timeslotend;
                $schedulednum = 0;

                $findslot = $conn -> prepare('SELECT * FROM `MeetingTimeslots` WHERE `examid` = ? AND `timeslot` = ? ;');
                $findslot->bind_param("ss", $examid, $timeslotname);
                $findslot->execute();
                $findslotRR = $findslot->get_result();

                if ($findslotRR->num_rows==0){
                    $slotstmt = $conn -> prepare('INSERT INTO `MeetingTimeslots` (`timeslotid`, `examid`,`timeslot`,`dateid`,`scheduled`) VALUES (NULL,?,?,?,?);');
                    $slotstmt->bind_param("ssii", $examid, $timeslotname, $mt_dateid, $schedulednum);
                    $slotstmt->execute();
                    $slotsCreated++;
                }

                $timeslotstart=$timeslotend;
                $timeslotend=date("H:i", strtotime("+{$duration} minutes", strtotime($timeslotstart)));

            } while (strtotime($timeslotend)<=strtotime($stoptime));

        } else {
            failAndExit("Time range on {$date} must align with the meeting duration.");
        }

    }
}

$roundindex++;
$stmt = $conn->prepare('UPDATE `exam` SET `deadline`=?, `roundindex` = ? WHERE `examid` = ?');

$stmt->bind_param("sis", $newDeadline,$roundindex, $examid);

$stmt->execute();
$stmt->close();

header("Location: status.php?examid={$examid}&password={$password}&success");
?>
