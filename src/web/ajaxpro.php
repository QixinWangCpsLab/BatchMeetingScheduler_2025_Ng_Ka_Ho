<?php
date_default_timezone_set("Asia/Hong_Kong");
include $_SERVER["DOCUMENT_ROOT"] . "/testwsqlnew/conn/conn.php";


    $dateid = $_POST["dateid"];
    $SScounter = 0;
    $findslots  = $conn->prepare("SELECT * FROM `meetingtimeslots` WHERE `dateid` = ? AND `scheduled`= ?");
    $findslots ->bind_param("si" , $dateid, $SScounter);
    $findslots ->execute();
    $slotsR = $findslots ->get_result();
    //$output = '<option disabled selected hidden>Click to select time slots</option>';

    $json =[];
    if ($slotsR->num_rows>=1){
        while ($row = $slotsR->fetch_assoc()) {
            
            //$output .= '<option value = "'.$row['id'].'"> '.$row['timeslot'].'</option>';
            $json[$row['timeslotid']] = $row['timeslot'];
            
        }
    }
    
    //echo $output;
    echo json_encode($json);
    /*
    <select class="form-select" name="choose">
        <option disabled selected hidden>Click to select time slots</option>
    </select>

    */
    





?>
