<?php
date_default_timezone_set("Asia/Hong_Kong");
include $_SERVER["DOCUMENT_ROOT"] . "/testwsqlnew/conn/conn.php";

$stmt = $conn->prepare("SELECT * FROM `exam` WHERE `examid` = ? ");

$stmt->bind_param("s" , $_POST['code'] );

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows==1){

    while ($row = $result->fetch_assoc()) {

        //$mt_studentid = json_decode($row['studentid'], true);

        //if(in_array($_POST['studentid'],$mt_studentid)){
        $SScounter = 0;
        $Astmt = $conn->prepare("SELECT `password` FROM `studentexammatch` WHERE `examid` = ? AND `studentid` = ? AND `scheduled` = ?" );
        $Astmt->bind_param("ssi" , $_POST['code'], $_POST['studentid'],$SScounter);
        $Astmt->execute();
        $Aresult = $Astmt->get_result();
        
        if ($Aresult->num_rows==1){
            while ($Arow = $Aresult->fetch_assoc()) {
                $stu_pass = $Arow['password'];
                if ($_POST['stupassword'] == $stu_pass){
                    $mt_title = $row['title'];
                    $mt_subject = $row['subject'];
                    $mt_teacher = $row['teacher'];
                    $mt_duration = $row['duration'];
                    $mt_deadline = $row['deadline'];
                    $mt_datechoicenum = $row['datechoicenum'];
                    $mt_slotchoicenum = $row['slotchoicenum'];
                    //$mt_timeslots = json_decode($row['timeslots'], true);
                    if (time()>strtotime($mt_deadline)){
                        ?>
                        <script>
                        alert("you have passed the deadline");
                        window.location.href = 'studentview.html';
                        </script>
                        <?php
                    }
                }
                else{
                    ?>
                    <script>
                    alert("wrong password");
                    window.location.href = 'studentview.html';
                    </script>
                    <?php
                }
            }    
            
        }
        

        else{
            ?>
            <script>
                alert("you are not avaliable for this meeting allocation");
                window.location.href = 'index.html';
            </script>
            <?php
        }

    }



}else{
    ?>
    <script>
        alert("Meeting code is not existed");
        window.location.href = 'index.html';
    </script>
    <?php
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
                // echo $_POST['studentid'];
                // echo $stu_pass;
                





                /*$total = ['2022-01-03_00:02',"2022-01-03_00:07",'2022-01-03_01:03','2022-01-03_15:13'];
                $sum = strtotime('2022-01-03_00:00');
                $sum2=0;
                foreach ($total as $v){
                    $sum1=strtotime($v)-$sum;
                    $sum2= $sum2+$sum1;
                    echo date('Y-m-d_H:i',$v);
                    echo (" ");
                }
                $sum3=$sum+$sum2;
                echo date("H:i",$sum3);
                */
                
                ?>   
                
                <h4>Meeting title: <small class="text-secondary"> <?php echo $mt_title ?></small></h4>
                <h4>Subject title: <small class="text-secondary"><?php echo $mt_subject ?></small></h4>
                <h4>Teacher name: <small class="text-secondary"><?php echo $mt_teacher ?></small></h4>
                <h4>Duration of each meeting (minutes): <small class="text-secondary"><?php echo $mt_duration ?></small></h4>
                <h4>Deadline for Input: <small class="text-secondary"> <?php echo $mt_deadline ?> </small></h4>
                <h4>Meeting code: <small class="text-secondary"> <?php echo $_POST['code'] ?> </small></h4>


                <form  action="chooseform.php" id="formchoose" method="post" enctype="multipart/form-data">

                    <input type="hidden"  name="studentid" value="<?php echo $_POST['studentid'] ?>">
                    <input type="hidden"  name="examid" value="<?php echo $_POST['code'] ?>">
                    <input type="hidden"  name="stupassword" value="<?php echo $_POST['stupassword'] ?>">
                    <!--
                    <div class="mb-3 mt-5">
                    <label for="code" class="form-label">Date 1</label>
                        <select class="date1 form-select" name="date1" id="date1" required>
                            <option disabled selected hidden>Click to select date preference</option>
                        
                            <?php
                            /*

                            
                            if ($dateR->num_rows>=1){
                                while ($Drow = $dateR->fetch_assoc()) {
                                    ?>
                                    <option value="<?php echo $Drow['id']?>"> <?php echo $Drow['date']?> </option>";
                                    <?php
                                }
                            }

                            */
                            ?>

                        </select>
                       
                    <br>
                    
                      

                    -->
                    <?php

                
                    
                    $y = 1;

                    
                    for ($x = 1; $x <= $mt_datechoicenum; $x++) {
                    ?>    
                    
                        <div class="mb-3">
                            <label for="code" class="form-label">Date<?php echo $x?></label>
                            <select class="date form-select" name="date<?php echo $x?>" id="date<?php echo $x?>" required>
                                <option  disabled selected hidden>Click to select date preference</option>

                                <?php 
                                $dateprefstmt = $conn->prepare("SELECT * FROM `MeetingDate` WHERE `examid` = ? ");

                                $dateprefstmt->bind_param("s" , $_POST['code'] );
                
                                $dateprefstmt->execute();
                
                                $dateR = $dateprefstmt->get_result();


                                if ($dateR->num_rows>=1){
                                    while ($Drow = $dateR->fetch_assoc()) {

                                        $stmt = $conn->prepare("SELECT * FROM `meetingtimeslots` WHERE `examid` = ? AND `dateid` = ? ");
                                        $stmt->bind_param("si" , $_POST['code'], $Drow['dateid'] );                        
                                        $stmt->execute();
                                        $result = $stmt->get_result();

                                        $valid = 1;
                                        $counter = 0;
                                        while ($row = $result->fetch_assoc()) {
                                            if ($row["scheduled"]==0){
                                                $valid = 0;
                                                if ($valid == 0){
                                                    $counter++ ;
                                                }
                                            }                                          
                                        }

                                        if ($valid == 0){
                                        ?>
                                        <option value="<?php echo $Drow['dateid']?>"> <?php echo $Drow['date']?> </option>;
                                        <?php
                                        }
                                        
                                    }
                                }

                                ?>

                            </select>

                        </div>
                        <?php
                    
                        for ($y; $y <= $mt_slotchoicenum * $x; $y++){
                            ?>
                            <div class="mb-3">
                                <label for="code" class="form-label">Timeslot Choice <?php echo $y?></label>
                                <select class="choose form-select" name="choose<?php echo $y?>" id="choose<?php echo $y?>" required>
                                    <option  disabled selected hidden>Click to select timeslots</option>

                                </select>

                            </div>

                            <?php
                        }
                        
                    }

                    

                    ?>
                    
                    

                    
                    <div class="row">
                        <div class="col-6">
                            <div class="d-grid">
                                <button type="button" id="reselect" class="btn btn-primary fw-bold text-white" >Reselect</button>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-grid">
                                <button type="button" id="submitbtn" class="btn btn-poly fw-bold text-white" >Next</button>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="timestamp" value="">




                </form>



            </div>
        </div>
    </main>

</div>


<script src="scripts/jquery-3.6.0.min.js"></script>
<script>
    $( document ).ready(function() {
    //const timeslots = <?php //echo json_encode($mt_timeslots)    ?> ;
    var datechoosenum = <?php echo $mt_datechoicenum  ?> ;
    var slotchoosenum = <?php echo $mt_slotchoicenum ?> ;
    var total = datechoosenum*slotchoosenum + datechoosenum;
    var i;
    var timestamp;
    var deadline = "<?php echo $mt_deadline ?>";
    var validation = 0;

    //var remaintimeslots= $.extend( true, [], timeslots );
    //$(".form-select").prop( "disabled", true );
    //$(".date1").prop( "disabled", false );
    //$("#choose1").prop( "disabled", false );
        
    $("#submitbtn").click(function(){
    
        //timestamp = new Date();
        timestamp = <?php echo time(); ?>;
        $("[name='timestamp']").val(timestamp);
        console.log(timestamp);
        $(".form-select").prop( "disabled", false );
        $("#formchoose").submit();

        // $('.form-select').each(function() {
        //     if ($(this).val() != null) {
        //         validation++;
        //         if (validation == total){
        //             $("#formchoose").submit();
        //         }
        //         $(".form-select").prop( "disabled", false );
        //     }
        // });

        // if (validation != total){
        //     validation = 0;
        // }
        

        
    });

    
    $("#reselect").click(function(){
        $("#date1").prop( "disabled", false );
        $("#choose1").prop( "disabled", false );


        $('.form-select').each(function() {
            
            var selectedVal = $(this).val();
            console.log(selectedVal);
            var datename = $(this).attr('id');
            if (selectedVal!=''){
                if (datename.match("choose")){
                var select = datename.replace("choose", "");
                var thisselect = parseInt(select);
                var loopcounter = thisselect;

                for (loopcounter; loopcounter <= datechoosenum*slotchoosenum; loopcounter++){
                    $("#choose"+loopcounter+" option[value='"+selectedVal+"']").show();
                }
                }else if (datename.match("date")){
                    var select = datename.replace("date", "");
                    var thisselect = parseInt(select);
                    var loopcounter = thisselect;

                    for (loopcounter; loopcounter <= datechoosenum; loopcounter++){
                        $("#date"+loopcounter+" option[value='"+selectedVal+"']").show();
                    }
                }
            }
            
        });

        $('.form-select').each(function() {
            var datename = $(this).attr('id');
            if (datename.match("date")){
                var option =`<option disabled="" selected="" hidden="">Click to select date preference</option>`;
                $("#"+datename).append(option);
            }

            if (datename.match("choose")){
                var option =`<option disabled="" selected="" hidden="">Click to select timeslots</option>`;
                $("#"+datename).append(option);
            }
        });

        
    });

    
    


    
    
    $(".form-select").change(function(){
        var sid = $(this).attr('id');


        if (sid.match("choose")){
            var select = sid.replace("choose", "");
            var thisselect = parseInt(select);
            $("#choose"+(parseInt(thisselect)+1)).prop( "disabled", false );
            $("#choose"+(parseInt(thisselect))).prop( "disabled", true );
        }
        
    });

    //var Dnum = 1;
    //for (Dnum; Dnum<= datechoosenum; Dnum++){
    $(".date").change(function(){
        var dateid = $(this).val();
        var datename = $(this).attr('id');
        var Dselect= datename.replace("date", "");
        var DateSelect = parseInt(Dselect);
        var num = (DateSelect-1) * slotchoosenum + 1;

        //console.log(dateid);
        //console.log(DateSelect);
        
        
        $.ajax({
            url:"ajaxpro.php",
            method:'POST',
            data:{dateid : dateid},
            success: function(data){
                //$(".choose1").html(data);
                //console.log(data);
                
                var option =`<option disabled="" selected="" hidden="">Click to select timeslots</option>`;
                $.each(JSON.parse(data),function(key,value){ 
                    //console.log(key, value);
                    option+=`<option value="`+key+`">`+value+`</option>`;
                });
                
                for (num; num <= slotchoosenum* DateSelect; num++){

                    $("#choose"+num).empty().append(option);
                    $("#choose"+num).change(function(){
                        var slotchosenvalue = $(this).val();
                        var sid = $(this).attr('id');
                        var select = sid.replace("choose", "");
                        var thisselect = parseInt(select);
                        var counter = thisselect;

                        console.log(sid);
                        console.log(thisselect);
                        
                        if (slotchosenvalue!=''){
                            console.log(slotchosenvalue);
                            for (counter; counter <= slotchoosenum* DateSelect; counter++){
                            $("#choose"+counter+" option[value='"+slotchosenvalue+"']").hide();
                                }
                        }

                    });
                }

        }});

        if (dateid!=''){
            var dc = DateSelect;
            for (dc; dc <= datechoosenum; dc++){
            $("#date"+dc+" option[value='"+dateid+"']").hide();
            }
        }

        $("#date"+(DateSelect+1)).prop( "disabled", false );
        $("#date"+(DateSelect)).prop( "disabled", true );
    });
    
    //}


});
/*
    $(".form-select").change(function(){

        //var thiselect = $(this).attr('id').charAt($(this).attr('id').length - 1);

        //var thiselect = $(this).attr('id').replace("date", "");
        console.log(DateSelect);
        
    };


        
        if ($(this).attr('id').match("choose")){
            var thiselecta = $(this).attr('id').replace("choose", "");
            var selectedtimeslot = remaintimeslots.indexOf($(this).val());

            if (selectedtimeslot > -1) {
            remaintimeslots.splice(selectedtimeslot, 1);
            }

            if (parseInt(thiselecta)<10){



                

                var option =`<option disabled="" selected="" hidden="">Click to select time slots</option>`

                remaintimeslots.forEach(function(value){
                    option+=`<option value="`+value+`">`+value+`</option>`;
                });


                $("#choose"+(parseInt(thiselecta)+1)).empty().append(option);
            }



        }

    });
    
    */
    

</script>


</body>
</html>
