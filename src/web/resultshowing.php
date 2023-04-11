<?php
date_default_timezone_set("Asia/Hong_Kong");
include $_SERVER["DOCUMENT_ROOT"] . "/testwsqlnew/conn/conn.php";


    $viewid = $_POST["viewid"];
    $examid = $_POST["examid"];
    //echo $examid;
    $output = '';




    //$output = '<option disabled selected hidden>Click to select time slots</option>';

    if ($viewid == "all"){

        $findslots  = $conn->prepare("SELECT * FROM `meetingtimeslots` WHERE `examid` = ?");
        $findslots ->bind_param("s" , $examid);
        $findslots ->execute();
        $slotsR = $findslots ->get_result();

        while ($slotrow = $slotsR->fetch_assoc()) {
            if ($slotrow["scheduled"] == 1){
                $stmt = $conn->prepare("SELECT * FROM `result` WHERE `examid` = ?");
                $stmt->bind_param("s" , $examid );
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()){
                    if ($slotrow['timeslotid'] == $row['timeslotid']){
                        $output .= '
                        <tr>
                            <td>'.$slotrow['timeslot'].'</td>
                            <td>'.$row['studentid'].'</td>
                        </tr>';
                    }
                }
            }
            else if ($slotrow["scheduled"] == 0){
                $output .= '
                    <tr>
                        <td>'.$slotrow['timeslot'].'</td>
                        <td> 0 </td>
                    </tr>';
            }
        }
            
        
    }
    if ($viewid == "only"){

        $SScounter = 1;
        $stmt = $conn->prepare("SELECT * FROM `result` WHERE `examid` = ? ORDER BY `timeslotid`");
        $stmt->bind_param("s" , $examid );
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $findslots  = $conn->prepare("SELECT * FROM `meetingtimeslots` WHERE `examid` = ? AND `scheduled`= ?");
            $findslots ->bind_param("si" , $examid, $SScounter);
            $findslots ->execute();
            $slotsR = $findslots ->get_result();
            while ($slotrow = $slotsR->fetch_assoc()) {
                if ($row['timeslotid'] == $slotrow['timeslotid']){
                    $output .= '
                    <tr>
                        <td>'.$slotrow['timeslot'].'</td>
                        <td>'.$row['studentid'].'</td>
                    </tr>';
                }

            }
            //$output = "hello";
            //$output .= '<tr><td> "'.$row['timeslotid'].'"</td><td> "'.$row['studentid'].'"</td></tr>';
        }
    }

    echo $output;
    
    
    

    
    //echo $output;
    //echo json_encode($json);
    /*
    <select class="form-select" name="choose">
        <option disabled selected hidden>Click to select time slots</option>
    </select>

    */
    





?>
