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
$subject = "New Meeting Registration (".$_GET['title'].")";
$message = "The following would be the registration information:"."\n";
$message = $message."Meeting Code: ";
$notscheduled = 0;

$stmt = $conn->prepare("SELECT * FROM `studentexammatch` WHERE `examid` = ? AND `scheduled` = ? ");
$stmt->bind_param("si",$examid,$notscheduled);
$stmt->execute();
$result = $stmt->get_result();

$examstmt = $conn->prepare("SELECT * FROM `exam` WHERE `examid` = ?");
$examstmt->bind_param("s",$examid);
$examstmt->execute();
$examresult = $examstmt->get_result();

while ($examrow = $examresult->fetch_assoc()){
    $mt_deadline = $examrow["deadline"];
    $mt_roundindex = $examrow["roundindex"];
}

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
        $mail->Password = "";

        $mail->setFrom("@gmail.com", "do-not-reply");
        $mail->addAddress($row["studentid"]."@gmail.com", "student");

        $mail->Subject = $subject;
        
        //while ($row = $result -> fetch_assoc()){
            
            $message=$message.$row["examid"]."\n";
            $message=$message."Student ID:".$row["studentid"]."\n";
            $message=$message."Password: ".$row["password"]."\n";
            $message=$message."URL: http://www2.comp.polyu.edu.hk/~//web/index.html"."\n";
            if ($mt_roundindex > 1){ 
                $message=$message."The above registration details would be same as the first round, only deadline would be updated"."\n"."New ";
            }

            $message=$message."Deadline: ".$mt_deadline;
        //}
        
        $mail->Body = $message;

        $mail->send();

        $message = "The following would be the registration information:"."\n";
        $message = $message."Meeting Code: ";
        
    }
    
}else{
    ?>
    <script>
        alert("Cannot find any record");
        window.location.href = 'index.html';
    </script>
    <?php
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