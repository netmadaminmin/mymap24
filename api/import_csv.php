<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors',0);
error_reporting(E_ALL);

session_start();

/* ================= FILE CHECK ================= */

if(!isset($_FILES['file'])){
    echo json_encode(["error"=>"No file uploaded"]);
    exit;
}

$file = $_FILES['file']['tmp_name'];

if(!is_uploaded_file($file)){
    echo json_encode(["error"=>"Invalid upload"]);
    exit;
}

/* ================= EXTENSION CHECK ================= */

$ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);

if(strtolower($ext) !== "csv"){
    echo json_encode(["error"=>"Only CSV allowed"]);
    exit;
}

/* ================= CREATE TEMP FOLDER ================= */

$uploadDir = __DIR__ . "/../temp/";

if(!is_dir($uploadDir)){
    mkdir($uploadDir,0777,true);
}

/* ================= SAVE FILE ================= */

$newFile = $uploadDir . uniqid("dataset_") . ".csv";

if(!move_uploaded_file($file,$newFile)){
    echo json_encode(["error"=>"Failed to store file"]);
    exit;
}

/* ================= VALIDATE CSV ================= */

$handle = fopen($newFile,"r");

$header = fgetcsv($handle);

fclose($handle);

if(!$header || count($header) < 5){
    unlink($newFile);
    echo json_encode(["error"=>"Invalid CSV structure"]);
    exit;
}

/* ================= SAVE SESSION ================= */

$_SESSION['dataset_file'] = $newFile;

/* ================= RESPONSE ================= */

echo json_encode([
    "success"=>true,
    "message"=>"CSV uploaded successfully"
]);

exit;