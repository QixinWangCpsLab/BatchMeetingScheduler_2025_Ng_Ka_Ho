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

        //if(in_array($_POST['studentid'],$mt_studentid)){
        $pass = $row['password'];
        
        if ($_POST['password'] == $pass){
                $mt_title = $row['title'];
                $mt_subject = $row['subject'];
                $mt_teacher = $row['teacher'];
                $mt_duration = $row['duration'];
                $mt_deadline = $row['deadline'];
                $mt_datechoicenum = $row['datechoicenum'];
                $mt_slotchoicenum = $row['slotchoicenum'];
                $mt_roundindex = $row['roundindex'];
                //$mt_timeslots = json_decode($row['timeslots'], true);
        }else{
            ?>
            <script>
            alert("wrong password");
            window.location.href = 'index.html';
            </script>
            <?php
        }
    } 
}else{
    ?>
    <script>
    alert("wrong meeting code");
    window.location.href = 'index.html';
    </script>
    <?php
}

if (time()< strtotime($mt_deadline)){
    ?>
    <script>
    alert("The existing round is still ongoing");
    window.location.href = 'index.html';
    </script>
    <?php
}

$stmt->free_result();
$stmt->close();

$datearray = [];
$stmt = $conn->prepare("SELECT * FROM `meetingdate` WHERE `examid` = ? ");

$stmt->bind_param("s" , $_POST['examid'] );

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows>=1){

    while ($row = $result->fetch_assoc()) {
        $value = $row["date"];
        array_push($datearray, $row["date"]);
    }
}
$stmt->free_result();
$stmt->close();

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


                <h1 class="mb-4 text-poly">Please select time slots by your preferences</h1>
                
                <?php 
                //echo $_POST['studentid'];
                //echo $stu_pass;
                
                ?>

                <h4>Meeting title: <small class="text-secondary"> <?php echo $mt_title ?></small></h4>
                <h4>Subject title: <small class="text-secondary"><?php echo $mt_subject ?></small></h4>
                <h4>Teacher name: <small class="text-secondary"><?php echo $mt_teacher ?></small></h4>
                <h4>Duration of each meeting (minutes): <small class="text-secondary"><?php echo $mt_duration ?></small></h4>
                <h4>Meeting code: <small class="text-secondary"> <?php echo $_POST['examid'] ?> </small></h4>
                <h4>Original Deadline for Input: <small class="text-secondary"><?php echo $mt_deadline ?> </small></h4>
                <h4>Number of existing round: <small class="text-secondary"> <?php echo $mt_roundindex ?> </small></h4>
                <br>
                <form  action="roundform.php" id="roundform" method="post" enctype="multipart/form-data">

                
                <br>
                <div class="mb-3">
                    <label for="deadline" class="form-label"><h4>New deadline for Input : </h4></label>
                    <input type="datetime-local" class="form-control" id="deadline" name="deadline" required>
                </div>

                <br>

                
                <div class="mb-3">
                <h4>Original date preference for student registration : <br><small class="text-secondary">
                <?php
                    foreach ($datearray as $v) {echo $v . ' &nbsp;&nbsp; ';}
                ?>
                </small></h4>
                </div>

                <br>
                <div class="mb-3 " id="daybox">
                    <div class="row">
                    <div class="col-12">
                        <h4 class="d-inline-block">Meeting day :</h4> 
                        <button type="button" id="adday" class="btn btn-poly fw-bold text-white mb-2 ms-3">Add</button>
                    </div>
                    </div>



                    <div class="card bg-light mx-1 my-3">
                    <div class="card-body">
                        <h5>Additional Day 1 </h5>
                        <div class="col-12">
                            <label for="day1date" class="form-label ">Date : </label>
                            <div class="row">
                                <div class="col mb-2">
                                    <input type="date" class="form-control"  id="day1date" name="day1date"  >
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label  class="form-label fw-bold">Time Periods : </label>
                            <button type="button" class="btn btn-poly fw-bold text-white mb-2 ms-3 addtimeslot" day="1" >Add</button>

                        
                            <div class="row">
                                <div class="col-6">
                                <label class="form-label ">Start time: </label>

                                <div class="row">
                                    <div class="col mb-2">
                                    <input type="time" class="form-control" id="day1startime[]" name="day1startime[]" maxlength="100" >
                                    </div>
                                </div>

                            </div>

                            <div class="col-6">
                                <label   class="form-label ">End time : </label>
                                <div class="row">
                                    <div class="col mb-2">
                                        <input type="time" class="form-control" id=day1endtime[]  name="day1endtime[]" maxlength="100" >
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    </div>
                </div>
                </div>

                <input type="hidden" id="daycount" name="daycount" value="1">
                <input type="hidden"  name="examid" value="<?php echo $_POST['examid'] ?>">
                <input type="hidden"  name="duration" value="<?php echo $mt_duration?>">
                <input type="hidden"  name="roundindex" value="<?php echo $mt_roundindex ?>">
                <input type="hidden"  name="password" value="<?php echo $_POST['password']?>">
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-poly fw-bold text-white" id="submitbtn" >Submit</button>
                </div>

                </form>

                
                

            </div>
        </div>
    </main>

</div>


<script src="scripts/jquery-3.6.0.min.js"></script>
<script>
  $(document).ready(function() {


    var add_day_button = $("#adday");
    var daycount=2;

    $('#daybox').on("click", ".addtimeslot", function(e) {
      e.preventDefault();

      $(this).parent().append('<div class="row"> <div class="col"> <div class="row"> <div class="col mb-2"> <input type="time" class="form-control"   name="day'+$(this).attr("day")+'startime[]" maxlength="100" required> </div> </div> </div> <div class="col"> <div class="row"> <div class="col mb-2"> <input type="time" class="form-control"   name="day'+$(this).attr("day")+'endtime[]" maxlength="100" required> </div> </div> </div> <div class="col-auto"> <button type="button" class="btn btn-danger m-0  delete fw-bold">Delete</button> </div> </div>');
      
    });


    $(add_day_button).click(function (e) {
      e.preventDefault();

      $(this).parent().parent().parent().append('<div class="card bg-light mx-1 my-3"> <div class="card-body"> <div class="d-flex justify-content-between"> <h5 class="card-title">Additional Day '+daycount+'</h5> <a class="btn btn-danger  fw-bold delete " id="day'+daycount+'delete" day="'+daycount+'"   role="button">Delete</a> </div> <div class="col-12"> <label for="day'+daycount+'date" class="form-label ">Date : </label> <div class="row"> <div class="col mb-2"> <input type="date" class="form-control"  id="day'+daycount+'date" name="day'+daycount+'date"  required> </div> </div> </div> <div class="col-12"> <label  class="form-label fw-bold">Time Periods : </label> <button type="button" class="btn btn-poly fw-bold text-white mb-2 ms-3 addtimeslot" day="'+daycount+'" >Add</button> <div class="row"> <div class="col-6"> <label class="form-label ">Start time: </label> <div class="row"> <div class="col mb-2"> <input type="time" class="form-control"   name="day'+daycount+'startime[]" maxlength="100" required> </div> </div> </div> <div class="col-6"> <label   class="form-label ">End time : </label> <div class="row"> <div class="col mb-2"> <input type="time" class="form-control"   name="day'+daycount+'endtime[]" maxlength="100" required> </div> </div> </div> </div> </div> </div> </div>');

      if (daycount>2){
        $('#day'+(daycount-1)+'delete').addClass("disabled");
      }

      $('#daycount').val(daycount);
      daycount++;
      

    });

   

    $('#daybox').on("click", ".delete", function(e) {
      e.preventDefault();


      if ( $(this).attr("day") != null){
        $('#day'+(daycount-2)+'delete').removeClass("disabled");
        $('#daycount').val(daycount-2);
        daycount--;
        $(this).parent('div').parent('div').parent('div').remove();
      }else{
        $(this).parent('div').parent('div').remove();
      }



    })

    
    // $("#submitbtn").click(function(){
    //     $("#roundform").submit();
    // });
  });

</script>


</body>
</html>
