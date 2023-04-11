<?php
date_default_timezone_set("Asia/Hong_Kong");
include $_SERVER["DOCUMENT_ROOT"] . "/testwsqlnew/conn/conn.php";

$stmt = $conn->prepare("SELECT * FROM `exam` WHERE `examid` = ? ");

$stmt->bind_param("s" , $_GET['examid'] );

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows==1){
    while ($row = $result->fetch_assoc()) {
        $mt_password = $row['password'];
        $password = $_GET['password'];
    
        if ($mt_password == $password){
            $mt_title = $row['title'];;
            $mt_subject = $row['subject'];
            $mt_teacher = $row['teacher'];
            $mt_duration = $row['duration'];
            $mt_deadline = $row['deadline'];
            $mt_datechoicenum = $row['datechoicenum'];
            $mt_slotchoicenum = $row['slotchoicenum'];
            $roundindex = $row['roundindex'];
            // $mt_studentid = json_decode($row['studentid'], true);
        }
        else{
            echo '<script>alert("This is not the correct exam edit password")</script>';
            echo '<script>window.location.href = "teacherview.html";</script>';
            die();
        }

    }

    $stmt->free_result();
    $stmt->close();

}

if (time()>strtotime($mt_deadline)){
    $stmt = $conn->prepare("SELECT * FROM `result` WHERE `examid` = ? ");
    $stmt->bind_param("s" , $_GET['examid'] );
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows<1){
        // echo '<script>alert("You should get the result first")</script>';
        // echo '<script>window.location.href = "checkallresult.html";</script>';

        $stmt->free_result();
        $stmt->close();
    }

}
else{
    // echo '<script>alert("The timeslot allocation is still not yet available")</script>';
    // echo '<script>window.location.href = "teacherview.html";</script>';
    // $stmt->free_result();
    // $stmt->close();
    // die();
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



                <h1 class="mb-4 text-poly">Edit results</h1>


                <h4>Meeting title: <small class="text-secondary"> <?php echo $mt_title ?></small></h4>
                <h4>Subject title: <small class="text-secondary"><?php echo $mt_subject ?></small></h4>
                <h4>Teacher name: <small class="text-secondary"><?php echo $mt_teacher ?></small></h4>
                <h4>Duration of each meeting (minutes): <small class="text-secondary"><?php echo $mt_duration ?></small></h4>
                <h4>Deadline for Input: <small class="text-secondary"> <?php echo $mt_deadline ?> </small></h4>
                <h4>Meeting code: <small class="text-secondary"> <?php echo $_GET['examid'] ?> </small></h4>

                <form action="editform.php" method="post" enctype="multipart/form-data">

                <table class="table mt-5">
                    <thead>
                    <tr>
                        <th scope="col">Timeslot</th>
                        <th scope="col">Student id</th>
                    </tr>
                    </thead>
                    <tbody>

                    <?php
                    $studentarray = [];

                    $stustmt = $conn->prepare("SELECT * FROM `studentexammatch` WHERE `examid` = ?");
                    $stustmt->bind_param("s" , $_GET['examid'] );
                    $stustmt->execute();
                    $sturesult = $stustmt->get_result();
                    while ($sturow = $sturesult-> fetch_assoc()){
                        array_push($studentarray,$sturow["studentid"]);
                    }

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
                                    echo "<tr>
                                                <td>{$slotrow['timeslot']}</td>
                                                <td>
                                                    <select class=\"form-select\" name = '{$row['timeslotid']}' >
                                                    <option selected hidden value='{$row['studentid']}'> {$row['studentid']} </option>
                                          ";

                                    foreach ($studentarray as  $y){
                                        echo "<option value='{$y}'> {$y} </option>";
                                    }

                                    echo "<option value='0'> 0 </option>";
                                    echo "</select> </td></tr>";

                                }
                            }
                        }
                        else if ($slotrow["scheduled"] == 0){
                            echo "<tr>
                                        <td>{$slotrow['timeslot']}</td>
                                        <td>
                                            <select class=\"form-select\" name = '{$slotrow['timeslotid']}' >
                                            <option selected hidden value='0'> 0 </option>
                                  ";

                            foreach ($studentarray as  $y){
                                echo "<option value='{$y}'> {$y} </option>";
                            }
                            echo "<option value='0'> 0 </option>";
                            echo "</select> </td></tr>";
                        }
                    }

                    // foreach ($timeslotsarray as $x  =>  $value){
                    //     echo "<tr><td>{$x}</td>

                    //             <td>
                    //         <select class=\"form-select\" name=\"{$x}\" required>
                    //     <option selected hidden value='{$value}' >{$value}</option>";

                    //     foreach ($mt_studentid as  $value2){

                    //         echo "<option value='{$value2}' >{$value2}</option>";

                    //     }



                    //         echo "</select> </td></tr>";
                    // }


                    ?>


                    </tbody>
                </table>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-poly fw-bold text-white" >Submit</button>
                    </div>

                    <input type="hidden"  name="examid" value="<?php echo $_GET['examid'] ?>">
                    <input type="hidden"  name="password" value="<?php echo $_GET['password'] ?>">

                </form>

            </div>
        </div>
    </main>

</div>




</body>
</html>

    <?php
