<?php
date_default_timezone_set("Asia/Hong_Kong");
include $_SERVER["DOCUMENT_ROOT"] . "/testwsqlnew/conn/conn.php";

$stmt = $conn->prepare("SELECT * FROM `exam` WHERE `examid` = ? ");

$stmt->bind_param("s" , $_POST['code'] );

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows==1){

    while ($row = $result->fetch_assoc()) {
        
        $mt_studentid = json_decode($row['studentid'], true);


        if(in_array($_POST['studentid'],$mt_studentid)){
            header("Location: choose.php?id={$_POST['studentid']}");

        }else{
            ?>
            <script>
                    alert("wrong student id");
                    window.location.href = 'index.html';
            </script>
            <?php
        }

    }

}else{
    header('Location: index.html');
}


/* while ($matching = $authorizationresult->fetch_assoc()){
                $mt_stupassword = $matching['password'];
            
                if(in_array($_POST['stupassword'],$mt_stupassword)){
            
                    header("Location: choose.php?id={$_POST['studentid']}");
            
                }else{
                    ?>
                    <script>
                            alert("wrong password");
                            window.location.href = 'index.html';
                    </script>
                    <?php
                }
            }
*/

$stmt->free_result();
$authorizationstmt->free_result();
$stmt->close();
$authorizationstmt->close();

