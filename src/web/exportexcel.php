<?php
date_default_timezone_set("Asia/Hong_Kong");
include $_SERVER["DOCUMENT_ROOT"] . "/testwsqlnew/conn/conn.php";
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$exportstmt = $conn->prepare("SELECT * FROM `studentexammatch` WHERE `examid` = ? ");
$exportstmt->bind_param("s",$_GET['examid']);
$exportstmt->execute();
$exportresult = $exportstmt->get_result();

$row_cnt = $exportresult->num_rows;
if ($row_cnt >=1){
    $exportsheet = new Spreadsheet();
    $sheet = $exportsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'Meeting Code');
    $sheet->setCellValue('B1', 'Meeting Title');
    $sheet->setCellValue('C1', 'Student ID');
    $sheet->setCellValue('D1', 'Password');
    
    $countrow = 2;
    foreach($exportresult as $erow){
        $sheet->setCellValue('A'.$countrow, $erow['examid']);
        $sheet->setCellValue('B'.$countrow, $_GET['title']);
        $sheet->setCellValue('C'.$countrow, $erow['studentid']);
        $sheet->setCellValue('D'.$countrow, $erow['password']);
        $countrow++;
    }

    $writer = new Xlsx($exportsheet); 
    //'.urlencode($name).'
    //$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($exportsheet, 'Xlsx');
    $name = "student.xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename= "student.xlsx"');
    $writer->save('php://output'); 
    $exportstmt->close();

    
}else{
    ?>
    <script>
        alert("Cannot find any record");
        window.location.href = 'index.html';
    </script>
    <?php
    }
?>