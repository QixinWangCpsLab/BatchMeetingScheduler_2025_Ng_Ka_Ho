<?php
date_default_timezone_set("Asia/Hong_Kong");
include $_SERVER["DOCUMENT_ROOT"] . "/testwsqlnew/conn/conn.php";
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$examid = $_GET['examid'];
$index = $_GET['index'];
$subject = "Meeting Result (".$_GET['title'].")";
$message = "The following would be the timeslot allocation result:"."\n\n";
$message = $message."Meeting Code: ";
$notscheduled = 0;

$studentstmt = $conn->prepare("SELECT * FROM `studentexammatch` WHERE `examid` = ? AND `scheduled` = ?");
$studentstmt->bind_param("si",$examid, $notscheduled);
$studentstmt->execute();
$studentresult = $studentstmt->get_result();
$studentcount = $studentresult -> num_rows;

$stmt = $conn->prepare("SELECT * FROM `result` WHERE `examid` = ? AND `roundindex` = ?");
$stmt->bind_param("si",$examid,$index);
$stmt->execute();
$result = $stmt->get_result();

$counter = $result->num_rows;
if ($counter >=1){

    while ($row = $result->fetch_assoc()){
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->SMTPAuth = true;

        $mail->Host = "smtp.gmail.com";
        $mail->SMTPSecure = "ssl"; //"PHPMailer::ENCRYPTION_STARTTLS";
        $mail->Port = 465;

        $mail->Username = "@gmail.com";
        $mail->Password = "ytynmodqskpnaxof";

        $mail->setFrom("@gmail.com", "do-not-reply");
        $mail->addAddress($row["studentid"]."@gmail.com", "student");

        $mail->Subject = $subject;
        
        //while ($row = $result -> fetch_assoc()){
            //if ($row["studentid"]== "19065597D"){
        $message=$message.$row["examid"]."\n";
        $message=$message."Student ID:".$row["studentid"]."\n";

        $timestmt = $conn->prepare("SELECT * FROM `meetingtimeslots` WHERE `examid` = ?");
        $timestmt->bind_param("s",$examid);
        $timestmt->execute();
        $tresult = $timestmt->get_result();
        while ($trow = $tresult->fetch_assoc()){
            if ($row["timeslotid"]==$trow["timeslotid"]){
                $message=$message."Allocated Timeslot: ".$trow["timeslot"];
            }
        }
        //}
        
        $mail->Body = $message;

        $mail->send();

        $message = "The following would be the timeslot allocation result:"."\n\n";
        $message = $message."Meeting Code: ";
    }
    
}

if ($studentcount >= 1){
    while ($sturow = $studentresult->fetch_assoc()){
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->SMTPAuth = true;

        $mail->Host = "smtp.gmail.com";
        $mail->SMTPSecure = "ssl"; //"PHPMailer::ENCRYPTION_STARTTLS";
        $mail->Port = 465;

        $mail->Username = "@gmail.com";
        $mail->Password = "";

        $mail->setFrom("@gmail.com", "do-not-reply");
        $mail->addAddress($sturow["studentid"]."@gmail.com", "student");

        $mail->Subject = $subject;
        
        //while ($row = $result -> fetch_assoc()){
            //if ($row["studentid"]== "19065597D"){
                $message=$message.$sturow["examid"]."\n";
                $message=$message."Student ID:".$sturow["studentid"]."\n";
                $message=$message."Allocated Timeslot: 0"."\n"."You may need to wait for another round allocation.";
            //}
        //}
        
        $mail->Body = $message;

        $mail->send();

        $message = "The following would be the timeslot allocation result:"."\n\n";
        $message = $message."Meeting Code: ";
    }
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
        <h1 class="mb-4 text-poly">Your mails have been successfully sent! </h1>



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

