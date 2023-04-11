<?php
date_default_timezone_set("Asia/Hong_Kong");
include $_SERVER["DOCUMENT_ROOT"] . "/testwsqlnew/conn/conn.php";

$stmt = $conn->prepare("SELECT * FROM `exam` WHERE `examid` = ? ");


$stmt->bind_param("s" , $_POST['code'] );

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows==1){

    while ($row = $result->fetch_assoc()){

        //$mt_studentid = json_decode($row['studentid'], true);

        $studentstmt = $conn->prepare("SELECT * FROM `studentexammatch` WHERE `examid` = ? AND `studentid` = ?");
        $studentstmt->bind_param("ss" , $_POST['code'],$_POST['studentid'] );
        $studentstmt->execute();
        $ssresult = $studentstmt->get_result();

        if ($ssresult->num_rows==1){
            while($ssrow = $ssresult -> fetch_assoc()){
                $ms_password=$ssrow["password"];
            }

            if ($_POST['stupassword'] == $ms_password){
                $mt_title = $row['title'];
                $mt_subject = $row['subject'];
                $mt_teacher = $row['teacher'];
                $mt_duration = $row['duration'];
                $mt_deadline = $row['deadline'];
                //$mt_timeslots = json_decode($row['timeslots'], true);
                //$mt_choicenum = $row['choicenum'];

                $stmt->free_result();
                $stmt->close();     
        
            }
            else{
                ?>
                <script>
                alert("wrong password");
                window.location.href = 'index.html';
                </script>
                <?php
            }
            
        }else{
            ?>
            <script>
                alert("wrong student id");
                window.location.href = 'index.html';
            </script>
            <?php
        }
    }
        
    
}else{
    header('Location: index.html');

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


                <h1 class="mb-4 text-poly">The following is your choose</h1>

                <div class="alert alert-danger" role="alert">
                    If you want to update your choose, click this <a href="studentinput.html">link</a>
                </div>


                <h4>Meeting title: <small class="text-secondary"> <?php echo $mt_title ?></small></h4>
                <h4>Subject title: <small class="text-secondary"><?php echo $mt_subject ?></small></h4>
                <h4>Teacher name: <small class="text-secondary"><?php echo $mt_teacher ?></small></h4>
                <h4>Duration of each meeting (minutes): <small class="text-secondary"><?php echo $mt_duration ?></small></h4>
                <h4>Deadline for Input: <small class="text-secondary"> <?php echo $mt_deadline ?> </small></h4>
                <h4>Meeting code: <small class="text-secondary"> <?php echo $_POST['code'] ?> </small></h4>
                <h4>Your Student ID: <small class="text-secondary"> <?php echo $_POST['studentid'] ?> </small></h4>

                <table class="table mt-5">
                    <thead>
                    <tr>
                        <th scope="col">Timeslot</th>
                        <th scope="col">Priority</th>
                    </tr>
                    </thead>
                    <tbody>

                        <?php

                            if (isset($_POST['studentid'])){

                                $stmt = $conn->prepare("SELECT * FROM `preference` WHERE `examid` = ? AND `studentid`=? ORDER BY `priority` ");
                                $stmt->bind_param("ss" , $_POST['code'], $_POST['studentid'] );
                                $stmt->execute();
                                $result = $stmt->get_result();
                            

                                while ($row = $result->fetch_assoc()) {
                                    $timestmt = $conn->prepare("SELECT * FROM `meetingtimeslots` WHERE `timeslotid` = ?");
                                    $timestmt->bind_param("s" , $row["timeslotid"] );
                                    $timestmt->execute();
                                    $timeslotresult = $timestmt->get_result();
                                    while ($Trow = $timeslotresult->fetch_assoc()) {
                                        $timeslotscheduled = $Trow["timeslot"];
                                        echo "<tr><td>{$timeslotscheduled}</td><td>{$row["priority"]}</td></tr>";
                                    }
                                }
                            }                    

                        
                        ?>


                    </tbody>
                </table>

                <!-- <form  action="" method="post" enctype="multipart/form-data">

                    <input type="hidden"  name="studentid" value="<?php echo $_POST['studentid'] ?>">
                    <input type="hidden"  name="examid" value="<?php echo $_POST['code'] ?>"> -->
<!-- 
                    <div class="mb-3 mt-5">
                        <label for="code" class="form-label">First choose</label>
                        <select class="form-select" name="choose1" required>
                            <?php
                                //echo "<option disabled selected hidden>{$studentchoose['choose1']}</option>";
                            ?>
                        </select>

                    </div>
                    <?php



                    //$choose_num=count($mt_timeslots)>10? 10 :count($mt_timeslots);
                    //$choose_num = $mt_choicenum;
                    //$choose_words=["First","Second","Third","Fourth","Fifth","Sixth","Seventh","Eighth","Ninth","Tenth"];

                    //for ($x = 2; $x <= $choose_num; $x++) {
                        ?>
                        <div class="mb-3">
                            <label for="code" class="form-label"><?php //echo $choose_words[$x-1] ?> choose</label>
                            <select class="form-select" name="choose<?php //echo $x ?>" required>
                                <?php
                                    //echo "<option disabled selected hidden >".$studentchoose['choose'.$x]."</option>";
                                ?>
                            </select>
                        </div>
                        <?php
                    //}?>
 -->
                </form>



            </div>
        </div>
    </main>

</div>




</body>
</html>