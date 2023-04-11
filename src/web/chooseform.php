<?php
date_default_timezone_set("Asia/Hong_Kong");
include $_SERVER["DOCUMENT_ROOT"] . "/testwsqlnew/conn/conn.php";





$stmt = $conn->prepare("SELECT * FROM `exam` WHERE `examid` = ? ");


$stmt->bind_param("s" , $_POST['examid'] );

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows==1){

    while ($row = $result->fetch_assoc()) {

        //$mt_studentid = json_decode($row['studentid'], true);
        //$mt_timeslots=json_decode($row['timeslots'], true);

        //$timeslotsnum=count($mt_timeslots);

        $mt_deadline = $row['deadline'];
        $datenum = $row['datechoicenum'];
        $timeslotsnum = $row['slotchoicenum'];




        $Astmt = $conn->prepare("SELECT `password` FROM `studentexammatch` WHERE `examid` = ? AND `studentid` = ?" );
        $Astmt->bind_param("ss" , $_POST['examid'], $_POST['studentid']);
        $Astmt->execute();
        $Aresult = $Astmt->get_result();
        
        if ($Aresult->num_rows==1){
            while ($Arow = $Aresult->fetch_assoc()) {
                $stu_pass = $Arow['password'];
                if ($_POST['stupassword'] == $stu_pass){
                    if (time()>strtotime($mt_deadline)){
                        header('Location: result.php?examid='.$_POST['examid']);
                        die();

                    }
                }
            }

        }else{
            
            
            
            header('Location: index.html');
            die();
        }

    }



}else{
    header('Location: index.html');
    die();
}

//$stmt->free_result();












$prefstmt = $conn->prepare("SELECT * FROM `preference` WHERE `examid` = ? AND `studentid` = ? ");


$prefstmt->bind_param("ss" , $_POST['examid'] ,$_POST['studentid'] );

$prefstmt->execute();

$prefresult = $prefstmt->get_result();

$total = $datenum*$timeslotsnum;


if ($prefresult->num_rows>0){

    //$row = $prefresult->fetch_assoc();
    //$existid=$row['id'];
    $prefstmt->free_result();
    $prefstmt->close();
    
    for ($y = 1; $y <= $total; $y++){
    $prefstmt = $conn->prepare("UPDATE `preference` SET `timestamp` = ?, `timeslotid` = ? WHERE `examid` = ? AND `studentid` = ? AND `priority` = ?");
    $prefstmt->bind_param("sissi", $_POST['timestamp'], $_POST['choose'.$y], $_POST['examid'],  $_POST['studentid'], $y);
    $prefstmt->execute();
    $prefstmt->close();
    }
    
    header('Location: sortsequence.php?examid='.$_POST['examid'].'&studentid='.$_POST['studentid']);

}else{
    $prefstmt->free_result();
    $prefstmt->close();

    for ($x = 1; $x <= $total; $x++){
    $prefstmt = $conn->prepare('INSERT INTO `preference` (`id`, `examid`, `studentid`, `timestamp`, `timeslotid`, `priority`) VALUES (NULL, ?, ?, ?, ?, ?);');
    $prefstmt->bind_param("sssii", $_POST['examid'],  $_POST['studentid'] ,$_POST['timestamp'], $_POST['choose'.$x], $x);
    $prefstmt->execute();
    $prefstmt->close();
    }
    header('Location: sortsequence.php?examid='.$_POST['examid'].'&studentid='.$_POST['studentid']);
    //header('Location: sortsequence.php');
}


$stmt->close();



?>
