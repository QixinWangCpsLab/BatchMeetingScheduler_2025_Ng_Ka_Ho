<?php


date_default_timezone_set("Asia/Hong_Kong");
include $_SERVER["DOCUMENT_ROOT"] . "/testwsqlnew/conn/conn.php";

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


$examid = $_POST['examid'];
$roundindex = $_POST['roundindex'];
$password = $_POST['password'];


//$timeslotsarray=[];
$Scount = 0;
//$dateprefarray=[];
//$sparetimeslotarray=[];


for ($z = 1; $z <= $_POST["daycount"]; $z++) {

    
    $insertdate = $_POST["day{$z}date"];
    if ($insertdate != ""){

        $finddate = $conn -> prepare('SELECT * FROM `MeetingDate` WHERE `examid` = ? AND `date` = ? ;');
        $finddate->bind_param("ss", $examid, $insertdate);
        $finddate->execute();
        $finddateR = $finddate->get_result();

        if ($finddateR->num_rows==0){
            $datestmt = $conn -> prepare('INSERT INTO `MeetingDate` (`dateid`, `examid`, `date`) VALUES (NULL,?,?);');
            $datestmt->bind_param("ss", $examid, $insertdate);
            $datestmt->execute();
        }
        //$dateprefarray[$insertdate] = 0;
        $finddate ->free_result();
        $finddate -> close();
    }
}
for ($x = 1; $x <= $_POST["daycount"]; $x++) {



    $date= $_POST["day{$x}date"];
    if ($date != ""){

    for ($y = 0; $y < count($_POST["day{$x}startime"]); $y++) {
        $startime = $_POST["day{$x}startime"][$y];
        $stoptime = $_POST["day{$x}endtime"][$y];
        $timeperiod = (strtotime($stoptime) - strtotime($startime))/60;

        if($timeperiod%$_POST["duration"]==0){



            $timeslotstart=$startime;
            $timeslotend=date("H:i", strtotime("+{$_POST["duration"]} minutes", strtotime($startime)));

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
                }
                //array_push($timeslotsarray,$date."_".$timeslotstart."-".$timeslotend);
                //array_push($sparetimeslotarray, $date."_".$timeslotstart."-".$timeslotend);
                $timeslotstart=$timeslotend;
                $timeslotend=date("H:i", strtotime("+{$_POST["duration"]} minutes", strtotime($timeslotstart)));

            } while (strtotime($timeslotend)<=strtotime($stoptime));



        }


    }
    }

}

$roundindex++;
$stmt = $conn->prepare('UPDATE `exam` SET `deadline`=?, `roundindex` = ? WHERE `examid` = ?');

$stmt->bind_param("sis", $_POST["deadline"],$roundindex, $examid);

$stmt->execute();


$stmt->close();
header("Location: status.php?examid={$examid}&password={$password}&success");




?>