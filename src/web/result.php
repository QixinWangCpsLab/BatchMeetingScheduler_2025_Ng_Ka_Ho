<?php
date_default_timezone_set("Asia/Hong_Kong");
include $_SERVER["DOCUMENT_ROOT"] . "/testwsqlnew/conn/conn.php";




$stmt = $conn->prepare("SELECT * FROM `exam` WHERE `examid` = ? ");

$stmt->bind_param("s" , $_GET['examid'] );

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows==1){

    while ($row = $result->fetch_assoc()) {

        if (isset($_GET['Tpassword'])){
            $mt_password = $row['password'];
            $password = $_GET['Tpassword'];
        
            if ($mt_password == $password){
                $mt_title = $row['title'];;
                $mt_subject = $row['subject'];
                $mt_teacher = $row['teacher'];
                $mt_duration = $row['duration'];
                $mt_deadline = $row['deadline'];
                $mt_datechoicenum = $row['datechoicenum'];
                $mt_slotchoicenum = $row['slotchoicenum'];
                $roundindex = $row['roundindex'];
            }
            else{
                echo '<script>alert("This is not the correct exam edit password")</script>';
                echo '<script>window.location.href = "teacherview.html";</script>';
            }
        
        }else if (isset($_GET['studentid'])){
            
            $password= $_GET['password'];

            $studentstmt = $conn->prepare("SELECT * FROM `studentexammatch` WHERE `examid` = ? AND `studentid` = ?");
            $studentstmt->bind_param("ss" , $_GET['examid'],$_GET['studentid'] );
            $studentstmt->execute();
            $ssresult = $studentstmt->get_result();

            

            if ($ssresult->num_rows==1){
                while($ssrow = $ssresult -> fetch_assoc()){
                    $ms_password=$ssrow["password"];
                }
                
                if ($password == $ms_password){
                    $mt_title = $row['title'];
                    $mt_subject = $row['subject'];
                    $mt_teacher = $row['teacher'];
                    $mt_duration = $row['duration'];
                    $mt_deadline = $row['deadline'];
                    $mt_datechoicenum = $row['datechoicenum'];
                    $mt_slotchoicenum = $row['slotchoicenum'];
                    $roundindex = $row['roundindex'];
                }
                else{
                    echo '<script>alert("This is not the correct student password")</script>';
                    echo '<script>window.location.href = "studentview.html";</script>';
                }

            }else{
                echo '<script>alert("Student is not available for this allocation")</script>';
                echo '<script>window.location.href = "studentview.html";</script>';
            }
            
            
        }else{
            echo '<script>window.location.href = "studentview.html";</script>';
        }
    }
            

}else{
    $stmt->free_result();
    $stmt->close();
    echo '<script>alert("This is not the correct meeting code")</script>';
    echo '<script>window.location.href = "index.html";</script>';
    //header('Location: index.html');
}

$stmt->free_result();
$stmt->close();

if ($roundindex == 1){
    if (time()<strtotime($mt_deadline)){
        echo '<script>alert("The first round has not yet accomplished")</script>';
        echo '<script>window.location.href = "index.html";</script>';
    }
}

$stmt = $conn->prepare("SELECT * FROM `result` WHERE `examid` = ? AND `roundindex`= ?");

$stmt->bind_param("si" , $_GET['examid'], $roundindex );

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows<1){
 

        $preferencenum = $mt_datechoicenum*$mt_slotchoicenum;

        
        if (time()>strtotime($mt_deadline)){

            $studentidcopy=[];
            // $timeslotsarray=[];

            $stmt = $conn->prepare("SELECT * FROM `preference` WHERE `examid` = ? GROUP BY `studentid` ORDER BY `timestamp` ASC;");

            $stmt->bind_param("s" , $_GET['examid'] );

            $stmt->execute();

            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $notscheduled0 = 0;
                $stu = $conn->prepare("SELECT * FROM `studentexammatch` WHERE `examid` = ? AND `studentid` = ? AND `scheduled` = ?");
                $stu->bind_param("ssi" , $_GET['examid'],$row['studentid'], $notscheduled0);
                $stu->execute();
                $sturesult = $stu->get_result();
                while ($sturow = $sturesult->fetch_assoc()) {
                    array_push($studentidcopy,$row['studentid']);
                }
            }


            $stmt->free_result();
            $stmt->close();


            foreach ($studentidcopy as $y => $value){
                $counterboo = 1;
                $sql = "SELECT * FROM `preference` WHERE `examid` = \"{$_GET['examid']}\"  AND `studentid`= \"$value\" ORDER BY `priority` ASC ";
                $result = $conn->query($sql);
                
                while ($row = $result->fetch_assoc()){
                    for ($x = 1; $x <= $preferencenum; $x++){
                        $notscheduled = 0;
                        $timeselection = $conn->prepare('SELECT * FROM `meetingtimeslots` WHERE `examid` = ? AND `scheduled` = ?');
                        $timeselection->bind_param("si", $_GET['examid'], $notscheduled);
                        $timeselection->execute();
                        $selectedR = $timeselection->get_result();

                        while ($selectedrow = $selectedR -> fetch_assoc()){
                            if ($selectedrow["timeslotid"] == $row["timeslotid"] && $counterboo>0){
                                $slotid = $selectedrow['timeslotid'];
                                // $timeslotsarray[$row['timeslotid']]=$value;
                                
                                $prefstmt = $conn->prepare('INSERT INTO `result` (`id`, `examid`, `studentid`, `timeslotid`,`roundindex`) VALUES (NULL, ?, ?, ?,?);');
                                $prefstmt->bind_param("ssii", $_GET['examid'] ,$value, $slotid, $roundindex);
                                $prefstmt->execute();
                                
                                $num = 1;
                                $scheduled = $conn->prepare('UPDATE `studentexammatch` SET `scheduled` = ? WHERE `examid` = ? AND `studentid` = ?;');
                                $scheduled->bind_param("iss", $num, $_GET['examid'] ,$value);
                                $scheduled->execute();

                                $scheduledT = $conn->prepare('UPDATE `meetingtimeslots` SET `scheduled` = ? WHERE `timeslotid` = ?;');
                                $scheduledT->bind_param("is", $num ,$slotid);
                                $scheduledT->execute();

                                unset($studentidcopy[$y]);

                                $counterboo = -1;
                                //var_dump($counterboo);
                                //var_dump($studentidcopy);
                            }
                        }
                    }
                }

            }                 
            
            

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
        <script src="scripts/jquery-3.6.0.min.js"></script>
    </head>
    <body class="bg-poly d-flex align-items-center h-100">

    <div class="container">

        <main class="w-100 m-auto" id="main"  >
            <div class="card py-md-5 py-2 px-sm-2 px-md-5   my-5 w-100"  >
                <div class="card-body" >



                    <h1 class="mb-4 text-poly">Time slot allocation results</h1>

                    <?php
                    if (isset($_GET['edit'])){

                        ?>

                        <div class="alert alert-success" role="alert">
                            Edit result successfully!<br>

                        </div>

                        <?php
                    }

                    ?>


                    <h4>Meeting title: <small class="text-secondary"> <?php echo $mt_title ?></small></h4>
                    <h4>Subject title: <small class="text-secondary"><?php echo $mt_subject ?></small></h4>
                    <h4>Teacher name: <small class="text-secondary"><?php echo $mt_teacher ?></small></h4>
                    <h4>Duration of each meeting (minutes): <small class="text-secondary"><?php echo $mt_duration ?></small></h4>
                    <h4>Deadline time: <small class="text-secondary"> <?php echo $mt_deadline ?> </small></h4>
                    <h4>Meeting code: <small class="text-secondary"> <?php echo $_GET['examid'] ?> </small></h4>
                

                    <?php //echo $roundindex ?>
                    <?php
                    if (isset($_GET['Tpassword'])){
                        ?>
                        <br>
                        <div class="mb-3">
                        <label for="selectview" class="form-label">Select View:</label>
                        <select class="view">
                            <option disabled selected hidden>Click to select display view</option>
                            <option value="all"> Display All Timeslots </option>;
                            <option value="only"> Display Students' Selected Timeslots Only</option>;
                        </select>
                        </div>
                    <?php 
                    } 
                    ?>

                    <table class="table mt-5">
                        <thead>
                        <tr>
                            <th scope="col">Time slot</th>
                            <th scope="col">Student id</th>
                        </tr>
                        </thead>
                        <tbody class = "displayviews">

                        <?php

                            if (isset($_GET['studentid'])){

                                $stmt = $conn->prepare("SELECT * FROM `result` WHERE `examid` = ? AND `studentid`=?");
                                $stmt->bind_param("ss" , $_GET['examid'], $_GET['studentid'] );
                                $stmt->execute();
                                $result = $stmt->get_result();

                                while ($row = $result->fetch_assoc()) {
                                    $timestmt = $conn->prepare("SELECT * FROM `meetingtimeslots` WHERE `timeslotid` = ?");
                                    $timestmt->bind_param("s" , $row["timeslotid"] );
                                    $timestmt->execute();
                                    $timeslotresult = $timestmt->get_result();
                                    while ($Trow = $timeslotresult->fetch_assoc()) {
                                        $timeslotscheduled = $Trow["timeslot"];
                                        echo "<tr><td>{$timeslotscheduled}</td><td>{$row["studentid"]}</td></tr>";
                                    }
                                }  

                            }
                            if (isset($_GET['Tpassword'])){
                                $findslots  = $conn->prepare("SELECT * FROM `meetingtimeslots` WHERE `examid` = ?");
                                $findslots ->bind_param("s" , $_GET['examid']);
                                $findslots ->execute();
                                $slotsR = $findslots ->get_result();
                        
                                while ($slotrow = $slotsR->fetch_assoc()) {
                                    if ($slotrow["scheduled"] == 1){
                                        $stmt = $conn->prepare("SELECT * FROM `result` WHERE `examid` = ?");
                                        $stmt->bind_param("s" , $_GET['examid'] );
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                        
                                        while ($row = $result->fetch_assoc()){
                                            if ($slotrow['timeslotid'] == $row['timeslotid']){
                                                echo "<tr><td>{$slotrow['timeslot']}</td><td>{$row['studentid']}</td></tr>";
                                            }
                                        }
                                    }
                                    else if ($slotrow["scheduled"] == 0){
                                        echo "<tr><td>{$slotrow['timeslot']}</td><td>0</td></tr>";
                                    }
                                }

                            }
                            
                            //     $stmt = $conn->prepare("SELECT * FROM `result` WHERE `examid` = ? ORDER BY `timeslotid`");
                            //     $stmt->bind_param("s" , $_GET['examid'] );
                            //     $stmt->execute();
                            //     $result = $stmt->get_result();
                            //     //var_dump($timeslotsarray);
                            

                                         

                        
                        ?>


                        </tbody>
                    </table>
                    <?php
                    if (isset($_GET['Tpassword'])){
                        ?>
                        <input type="hidden" name="code" id="code" value= <?php echo $_GET['examid'] ?>>
                        <input type="hidden" name="meetingtitle" id="meetingtitle" value= <?php echo $mt_title ?> >
                        <input type="hidden" name="roundindex" id="roundindex" value= <?php echo $roundindex ?> >
                        <div class="d-grid">
                        <button type="button" id="send"  class="btn btn-poly fw-bold text-white">Send Mail</button>
                        </div>
                        <?php
                    }
                    ?>


                    <div class="row mt-3">
                    <div class="col">
                        <div class="d-grid">
                            <button type="button" id="return" class="btn btn-secondary fw-bold text-white">Back to Homepage</button>
                        </div>
                    </div>
                    </div>            
                </div>
            </div>
        </main>

    </div>

<script src="scripts/jquery-3.6.0.min.js"></script>
<script>
    $("#send").click(function(){
        window.location.href = "sendresultmail.php?examid="+$("#code").val()+"&title="+$("#meetingtitle").val()+"&index="+$("#roundindex").val();
    });

    $(".view").change(function(){
        var viewid = $(this).val();
        var examid = "<?php echo $_GET['examid']?>";
        //alert(viewid);
        $.ajax({
            url:'resultshowing.php',
            method:'POST',
            data:{
                viewid : viewid,
                examid : examid
            },
            success: function(data){
                $(".displayviews").html(data);
            }
        })

    });

    $("#return").click(function(){
        window.location.href = "index.html";
    });
</script>


    </body>
    </html>

<?php



