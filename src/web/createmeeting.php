<?php


date_default_timezone_set("Asia/Hong_Kong");
include $_SERVER["DOCUMENT_ROOT"] . "/testwsqlnew/conn/conn.php";

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


$examid =guidv4();

//$timeslotsarray=[];
$Scount = 0;


for ($z = 1; $z <= $_POST["daycount"]; $z++) {
    $insertdate = $_POST["day{$z}date"];
    $datestmt = $conn -> prepare('INSERT INTO `MeetingDate` (`dateid`, `examid`, `date`) VALUES (NULL,?,?);');
    $datestmt->bind_param("ss", $examid, $insertdate);
    $datestmt->execute();
}
for ($x = 1; $x <= $_POST["daycount"]; $x++) {



    $date= $_POST["day{$x}date"];

    for ($y = 0; $y < count($_POST["day{$x}startime"]); $y++) {
        $startime = $_POST["day{$x}startime"][$y];
        $stoptime = $_POST["day{$x}endtime"][$y];
        $timeperiod = (strtotime($stoptime) - strtotime($startime))/60;

        if($timeperiod%$_POST["duration"]==0){



            $timeslotstart=$startime;
            $timeslotend=date("H:i", strtotime("+{$_POST["duration"]} minutes", strtotime($startime)));

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
                $slotstmt = $conn -> prepare('INSERT INTO `MeetingTimeslots` (`timeslotid`, `examid`,`timeslot`,`dateid`,`scheduled`) VALUES (NULL,?,?,?,?);');
                $slotstmt->bind_param("ssii", $examid, $timeslotname, $mt_dateid, $schedulednum);
                $slotstmt->execute();
                $Scount++;

                $timeslotstart=$timeslotend;
                $timeslotend=date("H:i", strtotime("+{$_POST["duration"]} minutes", strtotime($timeslotstart)));

            } while (strtotime($timeslotend)<=strtotime($stoptime));



        }


    }

}

//$timeslots=json_encode($timeslotsarray);


$password=random_str(8);


$Datechoicenum =$_POST["Datechoicenum"];
$Slotchoicenum =$_POST["Slotchoicenum"];
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

$fileName = $_FILES['importfile']['name'];
$inputFileName = $_FILES['importfile']['tmp_name'];
/** Load $inputFileName to a Spreadsheet object **/
$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileName);
$studentidData = $spreadsheet->getActiveSheet()->toArray();
$counttitle = "0";

foreach ($studentidData as $row){
    if ($counttitle>0){
        $everystudent = $row['0'];
        $stupassword = random_str(8);

        $SScounter = 0;
        $studentstmt = $conn -> prepare('INSERT INTO `studentexammatch` (`examid`, `studentid` ,`password`,`scheduled`) VALUES (?,?,?,? );');
        $studentstmt->bind_param("sssi", $examid, $everystudent, $stupassword, $SScounter);
        $studentstmt->execute();
    }
    else{
        $counttitle = "1";
    }
}

$roundindex = 1;

$stmt = $conn->prepare('INSERT INTO `exam` (`examid`, `title`, `subject`, `teacher`, `duration`, `deadline`, `datechoicenum`, `slotchoicenum` ,`password`,`roundindex`) VALUES (?,? ,? ,? ,? ,? ,? ,? ,?,?);');

$stmt->bind_param("ssssisiisi", $examid,  $_POST["title"] , $_POST["subject"],$_POST["teacher"] ,$_POST["duration"],$_POST["deadline"], $Datechoicenum, $Slotchoicenum, $password,$roundindex);

$stmt->execute();


$datestmt->close();
$studentstmt->close();
$stmt->close();
header("Location: status.php?examid={$examid}&password={$password}&success");





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
?>