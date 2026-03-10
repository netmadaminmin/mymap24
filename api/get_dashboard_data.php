<?php
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors',0);
error_reporting(E_ALL);

session_start();

$file = $_SESSION['dataset_file'] ?? null;

if(!$file || !file_exists($file)){
    echo json_encode(["error"=>"Dataset not uploaded"]);
    exit;
}

/* ---------------- COLUMN FINDER ---------------- */

function findColumn($header,$names){
    foreach($header as $i=>$col){
        $c = strtolower(trim($col));

        foreach($names as $name){
            if(strpos($c,$name)!==false){
                return $i;
            }
        }
    }
    return null;
}

/* ---------------- OPEN FILE ---------------- */

$handle = fopen($file,"r");

if(!$handle){
    echo json_encode(["error"=>"Cannot open dataset"]);
    exit;
}

$header = fgetcsv($handle);

/* ---------------- FIND COLUMNS ---------------- */

$latCol  = findColumn($header,["lat"]);
$lonCol  = findColumn($header,["lon"]);
$icaoCol = findColumn($header,["target","icao","address"]);

$nicCol  = findColumn($header,["nic"]);
$nacpCol = findColumn($header,["nacp"]);
$nacvCol = findColumn($header,["nacv"]);
$picCol  = findColumn($header,["pic"]);
$timeCol = findColumn($header,["time","timestamp"]);

/* ---------------- INIT ---------------- */

$total=0;
$posOK=0;
$velOK=0;
$picLow=0;

$aircraft=[];
$zones=[];
$points=[];

$minTime = null;
$maxTime = null;
/* ---------------- READ CSV ---------------- */

while(($row=fgetcsv($handle))!==false){

    $lat = ($latCol!==null) ? floatval($row[$latCol] ?? 0) : 0;
    $lon = ($lonCol!==null) ? floatval($row[$lonCol] ?? 0) : 0;

    $icao = ($icaoCol!==null) ? ($row[$icaoCol] ?? "unknown") : "unknown";

    $nic  = ($nicCol!==null)  ? floatval($row[$nicCol] ?? 0)  : 0;
    $nacp = ($nacpCol!==null) ? floatval($row[$nacpCol] ?? 0) : 0;
    $nacv = ($nacvCol!==null) ? floatval($row[$nacvCol] ?? 0) : 0;
    $pic  = ($picCol!==null)  ? floatval($row[$picCol] ?? 0)  : 0;

    $total++;

    if($nic>=7 && $nacp>=8) $posOK++;
    if($nacv>=1) $velOK++;
    if($pic<7) $picLow++;

    /* aircraft */

    if(!isset($aircraft[$icao])){
        $aircraft[$icao]=[
            "icao"=>$icao,
            "score"=>0,
            "count"=>0
        ];
    }

    $score = ($nic+$nacp+$nacv+$pic)/4;

    $aircraft[$icao]["score"] += $score;
    $aircraft[$icao]["count"]++;

    /* zones */

    if($lat!=0 && $lon!=0){

        $zoneKey = round($lat,1)."_".round($lon,1);

        if(!isset($zones[$zoneKey])){
            $zones[$zoneKey]=[
                "lat"=>$lat,
                "lon"=>$lon,
                "score"=>0,
                "count"=>0
            ];
        }

        $zones[$zoneKey]["score"] += $score;
        $zones[$zoneKey]["count"]++;

        $points[]=[
            "lat"=>$lat,
            "lon"=>$lon,
            "nic"=>$nic
        ];
    }
    if($timeCol !== null){

    $t = strtotime($row[$timeCol] ?? "");

    if($t){

        if($minTime === null || $t < $minTime){
            $minTime = $t;
        }

        if($maxTime === null || $t > $maxTime){
            $maxTime = $t;
        }

    }
}
}

fclose($handle);

/* ---------------- AIRCRAFT AVG ---------------- */

foreach($aircraft as &$a){
    if($a["count"]>0){
        $a["avg"]=$a["score"]/$a["count"];
    }else{
        $a["avg"]=0;
    }
}

usort($aircraft,function($a,$b){
    return $a["avg"] <=> $b["avg"];
});

$worstAircraft=array_slice($aircraft,0,5);

/* ---------------- ZONES AVG ---------------- */

foreach($zones as &$z){
    if($z["count"]>0){
        $z["avg"]=$z["score"]/$z["count"];
    }else{
        $z["avg"]=0;
    }
}

usort($zones,function($a,$b){
    return $a["avg"] <=> $b["avg"];
});

$worstZones=array_slice($zones,0,5);

/* ---------------- SUMMARY ---------------- */

if($total==0){
    $summary=[
        "total"=>0,
        "position_ok"=>0,
        "velocity_ok"=>0,
        "pic_low"=>0
    ];
}else{
    $summary=[
        "total"=>$total,
        "position_ok"=>round($posOK/$total*100,1),
        "velocity_ok"=>round($velOK/$total*100,1),
        "pic_low"=>round($picLow/$total*100,1)
    ];
}


$zoneGrid=[];

foreach($zones as $z){

    $avg=$z["score"]/$z["count"];

    $zoneGrid[]=[
        "lat"=>floatval($z["lat"]),
        "lon"=>floatval($z["lon"]),
        "avg_metric"=>$avg,
        "count"=>$z["count"]
    ];
}

$timeRange = null;

if($minTime && $maxTime){
    $timeRange = [
        "min_time" => date("Y-m-d H:i:s",$minTime),
        "max_time" => date("Y-m-d H:i:s",$maxTime)
    ];
}

/* ---------------- OUTPUT ---------------- */

echo json_encode([

"summary"=>$summary,
"worst_aircraft"=>$worstAircraft,
"worst_zones"=>$worstZones,
"zone_grid"=>$zoneGrid,
"points"=>$points,
"time_range"=>$timeRange

],JSON_UNESCAPED_UNICODE);