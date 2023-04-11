<?php
date_default_timezone_set("Asia/Hong_Kong");
include $_SERVER["DOCUMENT_ROOT"] . "/testwsqlnew/conn/conn.php";
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;



$stmt = $conn->prepare("SELECT * FROM `exam` WHERE `examid` = ? ");


$stmt->bind_param("s" , $_GET['examid'] );

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows==1){

    while ($row = $result->fetch_assoc()) {

        if ($row["password"] == $_GET['password']){

        $mt_title = $row['title'];
        $mt_subject = $row['subject'];
        $mt_teacher = $row['teacher'];
        $mt_duration = $row['duration'];
        $mt_deadline = $row['deadline'];
        //$mt_studentid = $row['studentid'];
        //$studentnum=count(json_decode($mt_studentid));
        }
        else{
            echo '<script>alert("This is not the correct exam edit password")</script>';
            echo '<script>window.location.href = "teacherview.html";</script>';
        }


    }

    $stmt->free_result();
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(DISTINCT `studentid`) as CS FROM `preference` WHERE `examid` = ? ");


    $stmt->bind_param("s" , $_GET['examid'] );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($srow = $result->fetch_assoc()) {
        $choosestudent= $srow['CS'];
    }
    
    //$choosestudent=$result->num_rows;

    $stmt->free_result();
    $stmt->close();
    
    $schedulednum = 1;

    $stmt = $conn->prepare("SELECT COUNT(*) as SS FROM `studentexammatch` WHERE `examid` = ? AND `scheduled`=?");
    $stmt->bind_param("si" , $_GET['examid'], $schedulednum );
    $stmt->execute();
    $result = $stmt->get_result();
    while ($srow = $result->fetch_assoc()) {
        $scheduledstu= $srow['SS'];
    }
    $stmt->free_result();
    $stmt->close();

 



}else{
    header('Location: index.html');
}

$countstmt = $conn->prepare("SELECT COUNT(*) as C FROM `studentexammatch` WHERE `examid` = ? ");
$countstmt->bind_param("s" , $_GET['examid'] );
$countstmt->execute();
$countresult = $countstmt->get_result();

while ($crow = $countresult->fetch_assoc()) {
    $studentnum= $crow['C'];
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

                <?php
                if (isset($_GET['teacher'])){
                ?>
                <div class="alert alert-success" role="alert">
                    You could generate another round of meeting through <a href='createroundmeeting.html'></a> with the following information.
                    Meeting code :<span class="text-danger"> <?php echo $_GET['examid'] ?> </span><br>
                    Edit password : <span class="text-danger"> <?php echo $_GET['password'] ?> (Please record it for future result editing) </span>
                </div>
                <?php
                }
                ?>

                <?php
                if (isset($_GET['success'])){

                ?>

                <div class="alert alert-success" role="alert">
                    Meeting created successfully!<br>
                    Share the follow code to your student<br>
                    Meeting code :<span class="text-danger"> <?php echo $_GET['examid'] ?> </span><br>
                    Edit password : <span class="text-danger"> <?php echo $_GET['password'] ?> (Please record it for future result editing) </span>
                </div>

            
                <?php
                }
                ?>

                

                <h1 class="mb-4 text-poly">Meeting state</h1>


                <h4>Meeting title: <small class="text-secondary"> <?php echo $mt_title ?></small></h4>
                <h4>Subject title: <small class="text-secondary"><?php echo $mt_subject ?></small></h4>
                <h4>Teacher name: <small class="text-secondary"><?php echo $mt_teacher ?></small></h4>
                <h4>Duration of each meeting (minutes): <small class="text-secondary"><?php echo $mt_duration ?></small></h4>
                <h4>Deadline for Input: <small class="text-secondary"> <?php echo $mt_deadline ?> </small></h4>
                <h4>Meeting code: <small class="text-secondary"> <?php echo $_GET['examid'] ?> </small></h4>



                <div class="card bg-light mx-1 mt-5">
                    <div class="card-body">


                        <h5>Student who have made a choice</h5>


                        <h1 class="display-1 fw-bold"><?php echo $choosestudent?>/<?php echo $studentnum?></h1> 

                        <br>

                        <h5>Student who have gained a scheduled timeslot</h5>


                        <h1 class="display-1 fw-bold"><?php echo $scheduledstu?>/<?php echo $studentnum?></h1> 

                        <?php
                        if (isset($_GET['success'])){
                        ?>
                        
                        <h5>Click the following button for automate mail delivery</h5>
                        <h5>Please wait till the success mail sent page before closing the browser.</h5>
                        <input type="hidden" name="code" id="code" value= <?php echo $_GET['examid'] ?>>
                        <input type="hidden" name="meetingtitle" id="meetingtitle" value= <?php echo $mt_title ?> >
                        
                        <!-- <button type="button" id="download"  class="btn btn-poly fw-bold text-white">Download</button> -->
                        <button type="button" id="send"  class="btn btn-poly fw-bold text-white">Send Mail</button>
                        <?php
                        }
                        ?>
                        <!-- <input type="hidden" name="mtitle" id="mtitle" value= <?php //echo $mt_title ?> > -->
                        <div class="row mt-3">
                            <div class="d-grid">
                                <button type="button" id="return" class="btn btn-secondary fw-bold text-white">Back to Homepage</button>
                            </div>
                        </div>
                    </div>

                    
                </div>

                
                


            </div>
        </div>
    </main>

</div>

<script src="scripts/jquery-3.6.0.min.js"></script>

<script>
    $("#download").click(function(){
        window.location.href = "exportexcel.php?examid="+$("#code").val()+"&title="+$("#meetingtitle").val();
    });
    $("#send").click(function(){
        window.location.href = "sendmail.php?examid="+$("#code").val()+"&title="+$("#meetingtitle").val();
    });
    $("#return").click(function(){
        window.location.href = "index.html";
    });
</script>

</body>
</html>