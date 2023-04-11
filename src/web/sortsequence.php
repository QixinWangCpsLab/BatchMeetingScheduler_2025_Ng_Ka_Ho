<?php
    date_default_timezone_set("Asia/Hong_Kong");
    include $_SERVER["DOCUMENT_ROOT"] . "/testwsqlnew/conn/conn.php";
    
    require 'vendor/autoload.php';
    $countstmt = $conn->prepare("SELECT COUNT(*) as Count FROM `preference` WHERE `examid` = ? AND `studentid` = ? ");
    $countstmt->bind_param("ss" , $_GET['examid'], $_GET['studentid'] );
    $countstmt->execute();
    $countresult = $countstmt->get_result();

    while ($countrow = $countresult->fetch_assoc()) {
        $counter = $countrow['Count'];
    }

    $countstmt->free_result();
    $countstmt->close();

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
  <script src="scripts/jquery-ui.min.js"></script>
  </script>
</head>
<body class="bg-poly d-flex align-items-center h-100">

<div class="container">

  <main class="w-100 m-auto" id="main"  >
    <div class="card py-md-5 py-2 px-sm-2 px-md-5   my-5 w-100"  >
      <div class="card-body" >
        <h1 class="mb-4 text-poly">You could drag and drop the timeslot for sorting your timeslot sequence.</h1>

        <div id="response" ></div>
        <div class="row">
        <div class="d-flex justify-content-center" >
        <table class = "table" id= "original">
            <thead>
            <tr>

                <th>Priority</th>

            </tr>
            </thead>

            <tbody>
            <?php
            for ($x = 1; $x <= $counter; $x++){
                ?>
                <tr> <td><?php echo $x?></td> </tr>
                <?php
            }
            ?>
            </tbody>
            

        </table>
            <table class = "table" id= "sortable">
            
                <thead>
                <tr>
                    <th>Timeslot Sequence</th>
                </tr>
                </thead>
                <tbody>
                <?php

                $stmt = $conn->prepare("SELECT * FROM `preference` WHERE `examid` = ? AND `studentid` = ? ORDER BY `priority` ASC");
                $stmt->bind_param("ss" , $_GET['examid'], $_GET['studentid'] );
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $timeslotid= $row["timeslotid"];
                    $priority = $row["priority"];
                
                    $slotstmt = $conn->prepare("SELECT * FROM `meetingtimeslots` WHERE `examid` = ? AND `timeslotid` = ?");
                    $slotstmt->bind_param("ss" , $_GET['examid'], $timeslotid );
                    $slotstmt->execute();
                    $slotresult = $slotstmt->get_result();

                    while ($slotrow = $slotresult->fetch_assoc()){
                        $timeslot= $slotrow["timeslot"];
                    ?>
                        <div class="panel-body">
                        <div class="mb-3 ">
                        <tr id= "originalorder<?php echo $priority?>" data-index=<?php echo $timeslotid?> data-position=<?php echo $priority?> >
                        
                            
                            <td><?php //echo $timeslotid?> <?php echo $timeslot?></td>
                        </tr>
                        </div>
                        </div>
                    <?php
                }}
                ?>
                </tbody>

            </ul>
            </table>
        </div>
        
        <h2><span id="sortedList"></span></h2>
        <div class="d-grid">
            <button type="button" id="submitbtn" class="btn btn-poly fw-bold text-white" >Submit</button>
        </div>
        

      </div>
    </div>
  </main>

</div>




</body>
</html>

<script>
    $( document ).ready(function() {
        var examid = "<?php echo $_GET['examid']?>";
        var studentid = "<?php echo $_GET['studentid']?>"
        var preference = "<?php echo $counter?>"
        var i;
        var timestamp;

        $("#sortable tbody").sortable({
            update: function(event, ui){
                $(this).children().each(function(index){
                    if ($(this).attr("data-position") != (index+1)){
                        $(this).attr("data-position", (index+1),);
                    }
                });
                
                //saveNewPosition();
                //console.log($(this));
            }
        });

        //function saveNewPosition(){
            
            $("#submitbtn").click(function(){
                var positions = [];
                for (i=1; i<= preference; i++){
                    positions.push([$("#originalorder"+i).attr("data-index"), $("#originalorder"+i).attr("data-position")]);
                }

                console.log(positions);
                timestamp = <?php echo time(); ?>;

                $.ajax({
                    url:"timeslotsequence.php",
                    method:'POST',
                    dataType: 'text',
                    data:{
                        updated : 1,
                        positions : positions,
                        examid : examid,
                        studentid : studentid,
                        timestamp : timestamp
                    },
                    success: function(response){
                        console.log(response);
                    }
                });
                window.location.href = "successchoose.html";
            });

            //     var positions =[];
            //    $(".newpriority").each(function(){
            //        positions.push([$(this).attr("data-index"), $(this).attr("data-position")]);
            //        $(this).removeClass("newpriority");
            //    });
            
            //     console.log(positions);
             
            

        //}
        
        
    });

    


    
</script>