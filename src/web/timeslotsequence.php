<?php
date_default_timezone_set("Asia/Hong_Kong");
include $_SERVER["DOCUMENT_ROOT"] . "/testwsqlnew/conn/conn.php";


    if (isset($_POST["updated"])){
        $code = $_POST["examid"];
        $studentcode = $_POST["studentid"];
        $timestamp = $_POST["timestamp"];

        foreach($_POST["positions"] as $positions ){
            $timeslotid = $positions[0];
            $newpriority = $positions[1];
        
            $updateslots  = $conn->prepare("UPDATE `preference` SET `priority` = ? , timestamp = ? WHERE `examid` = ? AND `studentid` = ? AND `timeslotid` = ?");
            $updateslots ->bind_param("isssi" , $newpriority,$timestamp, $code, $studentcode, $timeslotid );
            $updateslots ->execute();
            $updateslots->close();
        }
        exit("success");

    }
    // $dateid = $_POST["dateid"];
    // $findslots  = $conn->prepare("SELECT * FROM `meetingtimeslots` WHERE `dateid` = ? ");
    // $findslots ->bind_param("s" , $dateid );
    // $findslots ->execute();
    // $slotsR = $findslots ->get_result();
    // $output = '<option disabled selected hidden>Click to select time slots</option>';

    // $json =[];
    // if ($slotsR->num_rows>=1){
    //     while ($row = $slotsR->fetch_assoc()) {
            
    //         //$output .= '<option value = "'.$row['id'].'"> '.$row['timeslot'].'</option>';
    //         $json[$row['timeslotid']] = $row['timeslot'];
            
    //     }
    // }
    
    // //echo $output;
    // echo json_encode($json);   





?>
