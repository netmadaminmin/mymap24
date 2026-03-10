<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors',0);
error_reporting(E_ALL);

session_start();

if(!isset($_FILES['file'])){
    echo json_encode(["error"=>"No file uploaded"]);
    exit;
}

$file=$_FILES['file']['tmp_name'];

if(!is_uploaded_file($file)){
    echo json_encode(["error"=>"Invalid upload"]);
    exit;
}

$ext=pathinfo($_FILES['file']['name'],PATHINFO_EXTENSION);

if(strtolower($ext)!=="csv"){
    echo json_encode(["error"=>"Only CSV allowed"]);
    exit;
}

$uploadDir=__DIR__."/../temp/";

if(!is_dir($uploadDir)){
    mkdir($uploadDir,0777,true);
}

$newFile=$uploadDir.uniqid("dataset_").".csv";

if(!move_uploaded_file($file,$newFile)){
    echo json_encode(["error"=>"Failed to store file"]);
    exit;
}

$handle=fopen($newFile,"r");
$header=fgetcsv($handle);
fclose($handle);

if(!$header){
    unlink($newFile);
    echo json_encode(["error"=>"Invalid CSV"]);
    exit;
}

$_SESSION['dataset_file']=$newFile;
$_SESSION['dataset_header']=$header;

echo json_encode([
    "success"=>true,
    "columns"=>$header
]);