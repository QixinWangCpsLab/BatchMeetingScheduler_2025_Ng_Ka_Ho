<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set("Asia/Hong_Kong");
include $_SERVER["DOCUMENT_ROOT"] . "/testwsqlnew/conn/conn.php";
$stmt = $conn->prepare("SELECT * FROM `exam` WHERE `examid` = ?");
$stmt->bind_param("s" , $_POST['examid']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $roundindex = $row["roundindex"];
}

$stmt -> free_result();
$stmt -> close();

$timeslotsarray = [];



$scheduled = 1;
$notscheduled = 0;

$findslots = $conn->prepare("SELECT * FROM `meetingtimeslots` WHERE `examid` = ? ");

$findslots->bind_param("s" , $_POST['examid'] );

$findslots->execute();

$slotsR = $findslots->get_result();


while ($slotrow = $slotsR -> fetch_assoc()){

    $slotid = $slotrow['timeslotid'];
    array_push($timeslotsarray,$_POST[$slotid]);
    
    if ($_POST[$slotid] == "0"){
        if ($slotrow["scheduled"] == 1){
            //echo '<script>alert("notscheduled")</script>';
            $stmt = $conn->prepare("SELECT * FROM `result` WHERE `examid` = ? AND `timeslotid` = ?");
            $stmt->bind_param("si" , $_POST['examid'], $slotid );
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1){
                
                $update = $conn->prepare("UPDATE `meetingtimeslots` SET `scheduled` = ? WHERE `examid` = ? AND `timeslotid` = ?");
                $update->bind_param("isi", $notscheduled, $_POST['examid'], $slotid);
                $update->execute();
                $update->close();
                
                while ($row = $result -> fetch_assoc()){
                    if($slotrow["timeslotid"] == $row["timeslotid"]){
                        $stuupdate = $conn->prepare("UPDATE `studentexammatch` SET `scheduled` = ? WHERE `examid` = ? AND `studentid` = ?");
                        $stuupdate->bind_param("iss", $notscheduled, $_POST['examid'], $row["studentid"]);
                        $stuupdate->execute();
                        $stuupdate->close();
                    }
                }

                $delete = $conn->prepare("DELETE FROM `result` WHERE `examid` = ? AND `timeslotid` = ?");
                $delete->bind_param("si", $_POST['examid'], $slotid);
                $delete->execute();
                $delete->close();
            
            }

            //header('Location: result.php?examid='.$_POST['examid'].'&Tpassword='.$_POST['password'].'&edit');
            
        }
        

    }else{
        if ($slotrow["scheduled"] == 1){
            //echo '<script>alert("Can IN")</script>';
            $stmt = $conn->prepare("SELECT * FROM `result` WHERE `examid` = ? AND `timeslotid` = ?");
            $stmt->bind_param("si" , $_POST['examid'], $slotid );
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result -> fetch_assoc()){
               if($slotrow["timeslotid"] == $row["timeslotid"]){
                   $notstu = $conn->prepare("UPDATE `studentexammatch` SET `scheduled` = ? WHERE `examid` = ? AND `studentid` = ?");
                   $notstu->bind_param("iss", $notscheduled, $_POST['examid'], $row["studentid"]);
                   $notstu->execute();
                   $notstu->close();
               }
            }
            
            $update = $conn->prepare("UPDATE `result` SET `studentid` = ? WHERE `examid` = ? AND `timeslotid` = ?");
            $update->bind_param("ssi", $_POST[$slotid], $_POST['examid'], $slotid);
            $update->execute();
            $update->close();
            //}

            $stuupdate = $conn->prepare("UPDATE `studentexammatch` SET `scheduled` = ? WHERE `examid` = ? AND `studentid` = ?");
            $stuupdate->bind_param("iss", $scheduled, $_POST['examid'], $_POST[$slotid]);
            $stuupdate->execute();
            $stuupdate->close();
            //header('Location: result.php?examid='.$_POST['examid'].'&Tpassword='.$_POST['password'].'&edit');

        }else{
            //echo '<script>alert("Can IN")</script>';

            $update = $conn->prepare("INSERT INTO `result` (`id`, `examid`, `studentid`, `timeslotid`, `roundindex`) VALUES (NULL, ?, ?, ?, ?);");
            $update->bind_param("ssii", $_POST['examid'], $_POST[$slotid], $slotid, $roundindex);
            $update->execute();
            $update->close();

            $slotupdate = $conn->prepare("UPDATE `meetingtimeslots` SET `scheduled` = ? WHERE `examid` = ? AND `timeslotid` = ?");
            $slotupdate->bind_param("isi", $scheduled, $_POST['examid'], $slotid);
            $slotupdate->execute();
            $slotupdate->close();

            $stuupdate = $conn->prepare("UPDATE `studentexammatch` SET `scheduled` = ? WHERE `examid` = ? AND `studentid` = ?");
            $stuupdate->bind_param("iss", $scheduled, $_POST['examid'], $_POST[$slotid]);
            $stuupdate->execute();
            $stuupdate->close();
            //header('Location: result.php?examid='.$_POST['examid'].'&Tpassword='.$_POST['password'].'&edit');
        }
        

        

    }
        
}

//var_dump($timeslotsarray);

header('Location: result.php?examid='.$_POST['examid'].'&Tpassword='.$_POST['password'].'&edit');


        

?>    

