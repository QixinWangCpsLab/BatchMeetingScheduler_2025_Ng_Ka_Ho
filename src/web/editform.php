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

$scheduled = 1;
$notscheduled = 0;

// Fetch all slots for this exam.
$findslots = $conn->prepare("SELECT timeslotid, scheduled FROM `meetingtimeslots` WHERE `examid` = ? ");
$findslots->bind_param("s" , $_POST['examid'] );
$findslots->execute();
$slotsR = $findslots->get_result();
$slots = [];
while ($slotrow = $slotsR -> fetch_assoc()){
    $slots[] = $slotrow;
}

$assignments = [];
foreach ($slots as $slotrow) {
    $slotid = $slotrow['timeslotid'];
    $assignments[$slotid] = isset($_POST[$slotid]) ? trim($_POST[$slotid]) : "0";
}

// Detect duplicate student assignments across timeslots.
$seenStudents = [];
foreach ($assignments as $slotId => $studentId) {
    if ($studentId !== "0" && $studentId !== "") {
        if (isset($seenStudents[$studentId])) {
            echo "<script>alert('Duplicate assignment detected: student {$studentId} is assigned to multiple timeslots. Please ensure one slot per student.'); history.back();</script>";
            exit;
        }
        $seenStudents[$studentId] = true;
    }
}

$conn->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

foreach ($slots as $slotrow){

    $slotid = $slotrow['timeslotid'];
    $postedStudent = $assignments[$slotid];
    
    if ($postedStudent === "0" || $postedStudent === ""){
        if ($slotrow["scheduled"] == 1){
            $stmt = $conn->prepare("SELECT studentid FROM `result` WHERE `examid` = ? AND `timeslotid` = ? AND `roundindex` = ?");
            $stmt->bind_param("sii" , $_POST['examid'], $slotid, $roundindex );
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1){
                
                $update = $conn->prepare("UPDATE `meetingtimeslots` SET `scheduled` = ? WHERE `examid` = ? AND `timeslotid` = ?");
                $update->bind_param("isi", $notscheduled, $_POST['examid'], $slotid);
                $update->execute();
                $update->close();
                
                while ($row = $result -> fetch_assoc()){
                    $stuupdate = $conn->prepare("UPDATE `studentexammatch` SET `scheduled` = ? WHERE `examid` = ? AND `studentid` = ?");
                    $stuupdate->bind_param("iss", $notscheduled, $_POST['examid'], $row["studentid"]);
                    $stuupdate->execute();
                    $stuupdate->close();
                }

                $delete = $conn->prepare("DELETE FROM `result` WHERE `examid` = ? AND `timeslotid` = ? AND `roundindex` = ?");
                $delete->bind_param("sii", $_POST['examid'], $slotid, $roundindex);
                $delete->execute();
                $delete->close();
            
            }

        }
        

    }else{
        // Clear any previous slot this student had in this exam/round to prevent ghost assignments.
        $clearOtherSlots = $conn->prepare("SELECT timeslotid FROM `result` WHERE `examid` = ? AND `studentid` = ? AND `roundindex` = ? AND `timeslotid` <> ?");
        $clearOtherSlots->bind_param("ssii", $_POST['examid'], $postedStudent, $roundindex, $slotid);
        $clearOtherSlots->execute();
        $otherSlotsResult = $clearOtherSlots->get_result();
        while ($other = $otherSlotsResult->fetch_assoc()) {
            $otherSlotId = (int)$other['timeslotid'];
            $conn->query("UPDATE `meetingtimeslots` SET `scheduled` = {$notscheduled} WHERE `examid` = '" . $conn->real_escape_string($_POST['examid']) . "' AND `timeslotid` = {$otherSlotId}");
            $conn->query("DELETE FROM `result` WHERE `examid` = '" . $conn->real_escape_string($_POST['examid']) . "' AND `timeslotid` = {$otherSlotId} AND `roundindex` = {$roundindex}");
        }
        $clearOtherSlots->close();

        if ($slotrow["scheduled"] == 1){
            $stmt = $conn->prepare("SELECT studentid FROM `result` WHERE `examid` = ? AND `timeslotid` = ? AND `roundindex` = ?");
            $stmt->bind_param("sii" , $_POST['examid'], $slotid, $roundindex );
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result -> fetch_assoc()){
               $notstu = $conn->prepare("UPDATE `studentexammatch` SET `scheduled` = ? WHERE `examid` = ? AND `studentid` = ?");
               $notstu->bind_param("iss", $notscheduled, $_POST['examid'], $row["studentid"]);
               $notstu->execute();
               $notstu->close();
            }
            
            $update = $conn->prepare("UPDATE `result` SET `studentid` = ? WHERE `examid` = ? AND `timeslotid` = ? AND `roundindex` = ?");
            $update->bind_param("ssii", $postedStudent, $_POST['examid'], $slotid, $roundindex);
            $update->execute();
            $update->close();

            $stuupdate = $conn->prepare("UPDATE `studentexammatch` SET `scheduled` = ? WHERE `examid` = ? AND `studentid` = ?");
            $stuupdate->bind_param("iss", $scheduled, $_POST['examid'], $postedStudent);
            $stuupdate->execute();
            $stuupdate->close();

        }else{

            $update = $conn->prepare("INSERT INTO `result` (`id`, `examid`, `studentid`, `timeslotid`, `roundindex`) VALUES (NULL, ?, ?, ?, ?);");
            $update->bind_param("ssii", $_POST['examid'], $postedStudent, $slotid, $roundindex);
            $update->execute();
            $update->close();

            $slotupdate = $conn->prepare("UPDATE `meetingtimeslots` SET `scheduled` = ? WHERE `examid` = ? AND `timeslotid` = ?");
            $slotupdate->bind_param("isi", $scheduled, $_POST['examid'], $slotid);
            $slotupdate->execute();
            $slotupdate->close();

            $stuupdate = $conn->prepare("UPDATE `studentexammatch` SET `scheduled` = ? WHERE `examid` = ? AND `studentid` = ?");
            $stuupdate->bind_param("iss", $scheduled, $_POST['examid'], $postedStudent);
            $stuupdate->execute();
            $stuupdate->close();
        }
        
    }
        
}

$conn->commit();

header('Location: result.php?examid='.$_POST['examid'].'&Tpassword='.$_POST['password'].'&edit');


        

?>    

