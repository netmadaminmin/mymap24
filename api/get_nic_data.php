<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

$table = "ads_b_data";

/* ================= INPUT ================= */

$mode = $_GET['mode'] ?? "all";
$icao = $_GET['icao'] ?? "all";
$level = $_GET['level'] ?? "all";
$onlyAnomaly = intval($_GET['only_anomaly'] ?? 0);
$icaoListMode = intval($_GET['icao_list'] ?? 0);

$limitAircraft = intval($_GET['limit_aircraft'] ?? 10);
$limitZones = intval($_GET['limit_zones'] ?? 10);

/* ================= SAFE WHERE ================= */

$where = " WHERE 1=1 ";

if ($icao !== "all" && $icao !== "") {
  $icaoSafe = $conn->real_escape_string($icao);
  $where .= " AND target_address = '$icaoSafe' ";
}

/* ---- NIC Expression ---- */
$nicExpr = "CAST(nucp_nic AS DECIMAL(10,3))";

/* ---- Anomaly Rule ---- */
$anomalyExpr = "(nucp_nic IS NOT NULL AND nucp_nic < 4)";

/* ---- NIC Level Filter ---- */
if ($level === "good") {
  $where .= " AND $nicExpr >= 7 ";
} elseif ($level === "medium") {
  $where .= " AND $nicExpr BETWEEN 4 AND 6.999 ";
} elseif ($level === "critical") {
  $where .= " AND $nicExpr <= 3.999 ";
}

if ($onlyAnomaly === 1) {
  $where .= " AND $anomalyExpr ";
}

/* ---- Lat/Lon ---- */
$validLatLon = "(latitude IS NOT NULL AND longitude IS NOT NULL)";
$latExpr = "CAST(latitude AS DECIMAL(10,6))";
$lonExpr = "CAST(longitude AS DECIMAL(10,6))";

/* ---- Grid ---- */
$gridSize = 0.25;
$zoneExpr = "
  CONCAT(
    'Z',
    FLOOR(($latExpr)/$gridSize),
    '_',
    FLOOR(($lonExpr)/$gridSize)
  )
";

try {

/* =========================================================
   ICAO LIST
========================================================= */
if ($icaoListMode === 1) {

  $sqlICAO = "
    SELECT DISTINCT target_address AS icao
    FROM `$table`
    WHERE target_address IS NOT NULL AND target_address != ''
    ORDER BY icao ASC
    LIMIT 5000
  ";

  $resICAO = $conn->query($sqlICAO);
  $icaoList = [];

  while ($r = $resICAO->fetch_assoc()) {
    $icaoList[] = $r['icao'];
  }

  echo json_encode(["icao_list"=>$icaoList]);
  exit;
}

/* =========================================================
   AIRCRAFT POSITION (ใช้ zoom)
========================================================= */
if ($mode === "aircraft_position" && $icao !== "all") {

  $icaoSafe = $conn->real_escape_string($icao);

  $sql = "
    SELECT $latExpr AS lat, $lonExpr AS lon
    FROM `$table`
    WHERE target_address = '$icaoSafe'
    AND $validLatLon
    ORDER BY time_generated DESC
    LIMIT 1
  ";

  $res = $conn->query($sql);

  if ($row = $res->fetch_assoc()) {
    echo json_encode([
      "lat"=>floatval($row['lat']),
      "lon"=>floatval($row['lon'])
    ]);
  } else {
    echo json_encode([]);
  }
  exit;
}

/* =========================================================
   ZONE POSITION (ใช้ zoom)
========================================================= */
if ($mode === "zone_position" && isset($_GET['zone'])) {

  $zoneSafe = $conn->real_escape_string($_GET['zone']);

  $sql = "
    SELECT 
      AVG($latExpr) AS lat,
      AVG($lonExpr) AS lon
    FROM `$table`
    WHERE $zoneExpr = '$zoneSafe'
    AND $validLatLon
  ";

  $res = $conn->query($sql);
  $row = $res->fetch_assoc();

  echo json_encode([
    "lat"=>floatval($row['lat']),
    "lon"=>floatval($row['lon'])
  ]);
  exit;
}

/* =========================================================
   SUMMARY
========================================================= */
$sqlSummary = "
  SELECT 
    COUNT(*) AS total_records,
    AVG($nicExpr) AS avg_nic,
    SUM($anomalyExpr) AS total_anomalies
  FROM `$table`
  $where
";

$summaryRow = $conn->query($sqlSummary)->fetch_assoc();

/* =========================================================
   TOP AIRCRAFT
========================================================= */
$sqlTopAircraft = "
  SELECT 
    target_address AS icao,
    COUNT(*) AS records,
    AVG($nicExpr) AS avg_nic,
    SUM($anomalyExpr) AS anomalies,
    AVG($latExpr) AS center_lat,
    AVG($lonExpr) AS center_lon
  FROM `$table`
  $where
  AND target_address IS NOT NULL
  GROUP BY target_address
  HAVING records >= 20
  ORDER BY anomalies DESC
  LIMIT $limitAircraft
";

$resAircraft = $conn->query($sqlTopAircraft);
$topAircraft = [];

while ($r = $resAircraft->fetch_assoc()) {

  $topAircraft[] = [
    "icao"=>$r['icao'],
    "records"=>intval($r['records']),
    "avg_nic"=>round($r['avg_nic'],2),
    "anomalies"=>intval($r['anomalies']),
    "center_lat"=>floatval($r['center_lat']),
    "center_lon"=>floatval($r['center_lon'])
  ];
}

/* =========================================================
   TOP ZONES
========================================================= */
$sqlTopZones = "
  SELECT
    $zoneExpr AS zone_id,
    COUNT(*) AS records,
    AVG($nicExpr) AS avg_nic,
    SUM($anomalyExpr) AS anomalies,
    AVG($latExpr) AS center_lat,
    AVG($lonExpr) AS center_lon
  FROM `$table`
  $where
  AND $validLatLon
  GROUP BY zone_id
  HAVING records >= 20
  ORDER BY anomalies DESC
  LIMIT $limitZones
";

$resZones = $conn->query($sqlTopZones);
$topZones = [];

while ($r = $resZones->fetch_assoc()) {

  $topZones[] = [
    "zone_id"=>$r['zone_id'],
    "records"=>intval($r['records']),
    "avg_nic"=>round($r['avg_nic'],2),
    "anomalies"=>intval($r['anomalies']),
    "center_lat"=>floatval($r['center_lat']),
    "center_lon"=>floatval($r['center_lon'])
  ];
}

/* =========================================================
   RISK ZONES (สำหรับ hex map)
========================================================= */
$sqlRisk = "
  SELECT
    $zoneExpr AS zone_id,
    COUNT(*) AS records,
    AVG($nicExpr) AS avg_nic,
    SUM($anomalyExpr) AS anomalies,
    MIN($latExpr) AS lat_min,
    MAX($latExpr) AS lat_max,
    MIN($lonExpr) AS lon_min,
    MAX($lonExpr) AS lon_max
  FROM `$table`
  $where
  AND $validLatLon
  GROUP BY zone_id
";

$resRisk = $conn->query($sqlRisk);
$riskZones = [];

while ($r = $resRisk->fetch_assoc()) {
  $riskZones[] = $r;
}
/* =========================================================
   DAILY AVERAGE NIC TREND
========================================================= */
$sqlDailyNIC = "
  SELECT 
    DATE(time_stamp) AS day,
    AVG(CAST(nucp_nic AS DECIMAL(5,2))) AS avg_nic
  FROM ads_b_data
  WHERE nucp_nic IS NOT NULL
    AND nucp_nic != ''
  GROUP BY DATE(time_stamp)
  ORDER BY day ASC";

$resDailyNIC = $conn->query($sqlDailyNIC);
$dailyNIC = [];

if ($resDailyNIC) {
  while ($r = $resDailyNIC->fetch_assoc()) {
    $dailyNIC[] = [
      "day"=>$r['day'],
      "avg_nic"=>round(floatval($r['avg_nic']),2)
    ];
  }
}

/* =========================================================
   DAILY NIC ANOMALIES (NIC < 4)
========================================================= */
$sqlDailyAnomaly = "
SELECT 
  DATE(time_stamp) AS day,
  COUNT(*) AS count
FROM ads_b_data
WHERE CAST(nucp_nic AS DECIMAL(5,2)) < 4
GROUP BY DATE(time_stamp)
ORDER BY day ASC
";

$resDailyAnomaly = $conn->query($sqlDailyAnomaly);
$dailyAnomalies = [];

if ($resDailyAnomaly) {
  while ($r = $resDailyAnomaly->fetch_assoc()) {
    $dailyAnomalies[] = [
      "day"=>$r['day'],
      "count"=>intval($r['count'])
    ];
  }
}

/* =========================================================
   OUTPUT
========================================================= */
echo json_encode([
  "summary"=>[
    "total_records"=>intval($summaryRow['total_records']),
    "avg_nic"=>$summaryRow['avg_nic'] !== null 
        ? round(floatval($summaryRow['avg_nic']),2) 
        : 0,
    "total_anomalies"=>intval($summaryRow['total_anomalies'])
  ],
  "top_aircraft"=>$topAircraft,
  "top_zones"=>$topZones,
  "risk_zones"=>$riskZones,
  "daily_nic"=>$dailyNIC,
  "daily_anomalies"=>$dailyAnomalies
], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
  echo json_encode([
    "error" => $e->getMessage()
  ], JSON_UNESCAPED_UNICODE);
}