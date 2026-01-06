<?php
date_default_timezone_set("Asia/Hong_Kong");
include $_SERVER["DOCUMENT_ROOT"] . "/testwsqlnew/conn/conn.php";

// Add debugging to check if table exists
$check_table = $conn->query("SHOW TABLES LIKE 'exam'");
if ($check_table->num_rows == 0) {
    die("Error: The 'exam' table doesn't exist in the database. Make sure your database is properly initialized.");
}


$stmt = $conn->prepare("SELECT * FROM `exam` WHERE `examid` = ? ");

// Add error handling for prepare
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

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
        if (time()>strtotime($mt_deadline)){
            // Process allocations after the deadline using transactional locking to avoid double booking.
            $examId = $_GET['examid'];

            // Set a predictable isolation level for this session to avoid stale reads during allocation.
            $conn->query('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');

            $studentQueue = $conn->prepare("
                SELECT p.studentid
                FROM preference p
                JOIN studentexammatch s ON s.examid = p.examid AND s.studentid = p.studentid
                WHERE p.examid = ?
                AND s.scheduled = 0
                GROUP BY p.studentid
                ORDER BY MIN(p.timestamp) ASC
            ");
            $studentQueue->bind_param("s", $examId);
            $studentQueue->execute();
            $studentResult = $studentQueue->get_result();

            $preferencesStmt = $conn->prepare("
                SELECT timeslotid
                FROM preference
                WHERE examid = ? AND studentid = ?
                ORDER BY priority ASC
            ");

            $lockStudentStmt = $conn->prepare("
                SELECT scheduled
                FROM studentexammatch
                WHERE examid = ? AND studentid = ?
                FOR UPDATE
            ");
            $lockSlotStmt = $conn->prepare("
                SELECT scheduled
                FROM meetingtimeslots
                WHERE examid = ? AND timeslotid = ?
                FOR UPDATE
            ");
            $insertResultStmt = $conn->prepare("
                INSERT INTO result (examid, studentid, timeslotid, roundindex)
                VALUES (?, ?, ?, ?)
            ");
            $markStudentStmt = $conn->prepare("
                UPDATE studentexammatch
                SET scheduled = 1
                WHERE examid = ? AND studentid = ?
            ");
            $markSlotStmt = $conn->prepare("
                UPDATE meetingtimeslots
                SET scheduled = 1
                WHERE examid = ? AND timeslotid = ?
            ");

            while ($studentRow = $studentResult->fetch_assoc()) {
                $studentId = $studentRow['studentid'];

                $preferencesStmt->bind_param("ss", $examId, $studentId);
                $preferencesStmt->execute();
                $prefResult = $preferencesStmt->get_result();

                while ($prefRow = $prefResult->fetch_assoc()) {
                    $slotId = (int)$prefRow['timeslotid'];

                    // Start an atomic allocation attempt for this student and slot.
                    $conn->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

                    $lockStudentStmt->bind_param("ss", $examId, $studentId);
                    $lockStudentStmt->execute();
                    $studentLockResult = $lockStudentStmt->get_result();
                    $studentLocked = $studentLockResult->fetch_assoc();

                    $lockSlotStmt->bind_param("si", $examId, $slotId);
                    $lockSlotStmt->execute();
                    $slotLockResult = $lockSlotStmt->get_result();
                    $slotLocked = $slotLockResult->fetch_assoc();

                    $studentAvailable = $studentLocked && (int)$studentLocked['scheduled'] === 0;
                    $slotAvailable = $slotLocked && (int)$slotLocked['scheduled'] === 0;

                    if ($studentAvailable && $slotAvailable) {
                        $insertResultStmt->bind_param("ssii", $examId, $studentId, $slotId, $roundindex);
                        $insertSuccess = $insertResultStmt->execute();

                        if ($insertSuccess) {
                            $markStudentStmt->bind_param("ss", $examId, $studentId);
                            $markStudentStmt->execute();

                            $markSlotStmt->bind_param("si", $examId, $slotId);
                            $markSlotStmt->execute();

                            $conn->commit();
                            // Move to the next student once a slot is successfully reserved.
                            break;
                        }
                    }

                    // Either the slot/student is no longer available or insert failed due to constraint; rollback and try next preference.
                    $conn->rollback();
                }
            }

            $studentQueue->free_result();
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
                                $stmt = $conn->prepare("
                                    SELECT r.studentid, m.timeslot
                                    FROM result r
                                    JOIN meetingtimeslots m ON m.timeslotid = r.timeslotid
                                    WHERE r.examid = ? AND r.studentid = ? AND r.roundindex = ?
                                ");
                                $stmt->bind_param("ssi" , $_GET['examid'], $_GET['studentid'], $roundindex );
                                $stmt->execute();
                                $result = $stmt->get_result();

                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr><td>{$row['timeslot']}</td><td>{$row['studentid']}</td></tr>";
                                }
                            }

                            if (isset($_GET['Tpassword'])){
                                $findslots  = $conn->prepare("
                                    SELECT m.timeslotid, m.timeslot, m.scheduled, r.studentid
                                    FROM meetingtimeslots m
                                    LEFT JOIN result r
                                      ON r.examid = m.examid AND r.timeslotid = m.timeslotid AND r.roundindex = ?
                                    WHERE m.examid = ?
                                    ORDER BY m.timeslotid ASC
                                ");
                                $findslots ->bind_param("is" , $roundindex, $_GET['examid']);
                                $findslots ->execute();
                                $slotsR = $findslots ->get_result();

                                while ($slotrow = $slotsR->fetch_assoc()) {
                                    if ((int)$slotrow["scheduled"] === 1 && $slotrow["studentid"]) {
                                        echo "<tr><td>{$slotrow['timeslot']}</td><td>{$slotrow['studentid']}</td></tr>";
                                    } else {
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



