<?php include __DIR__ . '/partials/header.php'; ?>

<link rel="stylesheet" href="/map24/assets/css/legacy.css">
<link rel="stylesheet" href="/map24/assets/css/map.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

<style>
body{
  background:#f6f7fb;
  font-family:"Segoe UI",sans-serif;
}

/* ===== Container ===== */
.dashboard-container{
  max-width:1500px;
  margin:auto;
  padding:20px;
}

/* ===== Toolbar ===== */
.toolbar{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:20px;
  flex-wrap:wrap;
}

.toolbar h1{
  font-size:22px;
  font-weight:900;
  margin:0;
}

.toolbar p{
  font-size:13px;
  color:#6b7280;
  font-weight:600;
  margin:4px 0 0;
}

.badge{
  padding:6px 14px;
  border-radius:999px;
  font-size:12px;
  font-weight:900;
  background:#111827;
  color:#fff;
}

/* ===== Layout ===== */
.grid-4{
  display:grid;
  grid-template-columns:repeat(4,1fr);
  gap:14px;
}

.grid-2{
  display:grid;
  grid-template-columns:repeat(2,1fr);
  gap:14px;
}

.card{
  background:#fff;
  border-radius:18px;
  padding:20px;
  box-shadow:0 6px 18px rgba(0,0,0,0.06);
}

.card-title{
  font-size:13px;
  font-weight:800;
  color:#6b7280;
}

.card-value{
  font-size:30px;
  font-weight:900;
  margin-top:8px;
}

.section-title{
  font-size:16px;
  font-weight:900;
  margin-bottom:12px;
}

/* ===== Filter Panel ===== */
.filter-panel{
  display:flex;
  gap:12px;
  flex-wrap:wrap;
  align-items:center;
}

.filter-panel button{
  padding:8px 14px;
  border:none;
  border-radius:999px;
  font-size:12px;
  font-weight:800;
  cursor:pointer;
  transition:.2s;
}

.btn-dark{ background:#111827; color:#fff; }
.btn-dark:hover{ background:#1f2937; }

.btn-outline{
  background:#fff;
  border:1px solid #d1d5db;
}
.btn-outline:hover{
  background:#f3f4f6;
}

.filter-panel input{
  width:70px;
  padding:6px;
  border-radius:8px;
  border:1px solid #d1d5db;
  font-weight:700;
  text-align:center;
}

/* ===== Table ===== */
table{
  width:100%;
  border-collapse:collapse;
  font-size:13px;
}

th,td{
  padding:10px;
  border-bottom:1px solid rgba(0,0,0,0.08);
}

.row-danger{ background:#fee2e2; }
.row-warning{ background:#fef3c7; }
.row-good{ background:#dcfce7; }

.row-danger:hover,
.row-warning:hover,
.row-good:hover{
  filter:brightness(.95);
  cursor:pointer;
}

/* ===== Map ===== */
#risk-map{
  width:100%;
  height:460px;
  border-radius:18px;
}

/* ===== Toggle Button ===== */
.grid-toggle-btn{
  background:#000;
  color:#fff;
  border:none;
  padding:8px 16px;
  border-radius:999px;
  font-size:12px;
  font-weight:800;
  cursor:pointer;
  box-shadow:0 6px 18px rgba(0,0,0,0.35);
  transition:.2s;
}

.grid-toggle-btn:hover{
  background:#1f2937;
  transform:scale(1.05);
}

/* ===== Responsive ===== */
@media(max-width:1200px){
  .grid-4{ grid-template-columns:repeat(2,1fr); }
  .grid-2{ grid-template-columns:1fr; }
}
.card-desc {
  margin-top: 6px;
  font-size: 13px;
  color: #6b7280;
  line-height: 1.4;
}
/* ================= CLICKABLE ROW ================= */

.clickable-row {
  transition: all 0.2s ease;
}

.clickable-row:hover {
  background-color: #eef2ff;
  transform: translateX(4px);
  font-weight: 600;
}
/* ===== INFO ICON ===== */

.info-icon {
  display:inline-block;
  margin-left:6px;
  cursor:pointer;
  font-weight:700;
  color:#2563eb;
  transition:0.2s;
}

.info-icon:hover {
  transform:scale(1.2);
}

/* ===== MODAL ===== */

.metric-modal {
  display:none;
  position:fixed;
  z-index:9999;
  left:0;
  top:0;
  width:100%;
  height:100%;
  background:rgba(0,0,0,0.4);
}

.metric-modal-content {
  background:#ffffff;
  margin:8% auto;
  padding:25px;
  width:90%;
  max-width:500px;
  border-radius:12px;
  position:relative;
  animation:fadeIn 0.2s ease;
}

.close-btn {
  position:absolute;
  top:12px;
  right:15px;
  cursor:pointer;
  font-weight:bold;
  color:#6b7280;
}

.close-btn:hover {
  color:#111827;
}
.map-info-btn {
  background:#ffffff;
  border:none;
  padding:6px 10px;
  border-radius:6px;
  cursor:pointer;
  font-weight:bold;
  box-shadow:0 2px 6px rgba(0,0,0,0.2);
}

.map-info-btn:hover {
  background:#f3f4f6;
}
.map-search-box {
  position: absolute;
  top: 15px;
  left: 15px;
  z-index: 999;
  display: flex;
  gap: 8px;
}

.map-search-box input {
  padding: 6px 10px;
  border-radius: 6px;
  border: 1px solid #ccc;
  width: 220px;
}

.map-search-box button {
  background: #2563eb;
  color: white;
  border: none;
  padding: 6px 12px;
  border-radius: 6px;
  cursor: pointer;
}
@keyframes fadeIn {
  from {opacity:0; transform:translateY(-10px);}
  to {opacity:1; transform:translateY(0);}
}
/* ===== Toolbar Right ===== */
.toolbar-right{
  display:flex;
  gap:14px;
  align-items:center;
  flex-wrap:wrap;
}

/* ===== Import Form ===== */
.import-form{
  display:flex;
  gap:8px;
  align-items:center;
  background:#ffffff;
  padding:6px 10px;
  border-radius:999px;
  box-shadow:0 3px 10px rgba(0,0,0,0.05);
}

.import-form input[type="file"]{
  font-size:12px;
  border:none;
}

.import-form button{
  padding:6px 14px;
  border:none;
  border-radius:999px;
  font-size:12px;
  font-weight:800;
  cursor:pointer;
}
input[type="date"],
input[type="time"]{
  width:140px;
  padding:6px;
  border-radius:8px;
  border:1px solid #d1d5db;
  font-weight:600;
}
.metric-guide{
  margin-top:8px;
  background:#f9fafb;
  border:1px solid #e5e7eb;
  padding:10px;
  border-radius:10px;
  font-size:12px;
  line-height:1.6;
  width:220px;
}

.guide-row{
  font-weight:600;
  color:#374151;
}
/* ===== MAP WRAPPER ===== */
.map-wrapper{
  position:relative;
}

/* ===== MAP SEARCH ===== */
.map-search-box{
  position:absolute;
  top:15px;
  left:15px;
  z-index:900;
  display:flex;
  gap:6px;
}

/* ===== GRID BUTTON ===== */
.grid-toggle-btn{
  position:absolute;
  top:15px;
  right:15px;
  z-index:900;
  background:#111827;
  color:#fff;
  border:none;
  padding:6px 14px;
  border-radius:999px;
  font-size:12px;
  font-weight:800;
  cursor:pointer;
}

/* ===== LEGEND ===== */
#mapLegend{
  position:absolute;
  bottom:20px;
  left:20px;
  background:rgba(255,255,255,0.95);
  padding:10px 12px;
  border-radius:10px;
  font-size:12px;
  box-shadow:0 4px 12px rgba(0,0,0,0.2);
  z-index:900;
}

/* ===== METRIC GUIDE ===== */
.metric-guide{
  position:absolute;
  top:60px;
  right:15px;
  background:rgba(255,255,255,0.95);
  border:1px solid #e5e7eb;
  padding:10px;
  border-radius:10px;
  font-size:12px;
  width:170px;
  box-shadow:0 4px 12px rgba(0,0,0,0.15);
  z-index:900;
}

.legend-color{
  display:inline-block;
  width:12px;
  height:12px;
  border-radius:3px;
  margin-right:6px;
}

.good{background:#16a34a;}
.warn{background:#facc15;}
.risk{background:#ef4444;}


</style>

<div class="dashboard-container">

  <!-- HEADER -->
<div class="toolbar">

  <!-- LEFT -->
  <div>
    <h1>Monitoring – ADS-B Quality Dashboard</h1>
    <p>
      ตรวจสอบคุณภาพของสัญญาณ ADS-B ที่รับจากเครื่องบิน 
      โดยเน้นตรวจสอบความแม่นยำของตำแหน่งและความเร็วว่าอยู่ในเกณฑ์มาตรฐานหรือไม่
    </p>
  </div>

  <!-- RIGHT -->
  <div class="toolbar-right">

      <!-- IMPORT FORM -->
<div id="result"></div>
<div class="import-form">
    <input type="file" id="csvFile" accept=".csv">
    <button class="btn-dark" onclick="handleImport()">Import</button>
</div>

      <!-- DATASET BADGE -->
      <span id="datasetRange" class="badge">
          Today
      </span>

  </div>

</div>

<div class="metric-info-wrapper">

  <span class="metric-info-title">
    Technical Metrics
    <span class="info-icon" onclick="openMetricInfo()">ℹ</span>
  </span>

</div>

<!-- TIME FILTER -->
<div class="card" style="margin-bottom:15px;">
  <div class="section-title">Time & Aircraft Search</div>

  <div class="filter-panel">

    <!-- DATE -->
    <label style="font-size:12px;font-weight:800;">Date:</label>
    <input type="date" id="searchDate">

    <!-- START TIME -->
    <label style="font-size:12px;font-weight:800;">From:</label>
    <input type="time" id="startTime">

    <!-- END TIME -->
    <label style="font-size:12px;font-weight:800;">To:</label>
    <input type="time" id="endTime">

    <button class="btn-dark" onclick="applyAdvancedSearch()">Search</button>
    <button class="btn-outline" onclick="resetAdvancedSearch()">Reset</button>

  </div>

<!-- POPUP -->
<div id="metricInfoModal" class="metric-modal">
  <div class="metric-modal-content">
    <span class="close-btn" onclick="closeMetricInfo()">✕</span>

    <h3>📡 Technical Metrics</h3>
    <p>ค่าเมทริกซ์เหล่านี้เป็นค่ามาตรฐานสากลจากสัญญาณ ADS-B ของเครื่องบิน</p>

    <ul>
      <li><b>NIC</b> – Navigation Integrity Category ระดับความน่าเชื่อถือของตำแหน่ง เพื่อป้องกันการรบกวนสัญญาณ 
        (ผ่านเมื่อ ≥ 7)</li>

      <li><b>NACp</b> – Navigation Accuracy Category for Position ระดับความแม่นยำของพิกัดพิกัด (ละติจูด/ลองจิจูด)
        (ผ่านเมื่อ ≥ 8)</li>

      <li><b>NACv</b> – Navigation Accuracy Category for Velocit ระดับความแม่นยำของความเร็วที่เครื่องบินรายงาน  
        (ผ่านเมื่อ ≥ 1)</li>

      <li><b>PIC</b> – Position Integrity Category ค่าบ่งชี้ความสมบูรณ์ของตำแหน่งที่คำนวณจากระบบ GPS
        (< 7 ถือว่าต่ำ)</li>
    </ul>
  </div>
</div>
  <!-- KPI -->
<div class="grid-4">

  <div class="card">
    <div class="card-title">Total Records (Today)</div>
    <div id="totalRecords" class="card-value">-</div>
    <div class="card-desc">
      จำนวนข้อมูล ADS-B ที่รับเข้าระบบ(ทุกแพ็กเก็ตที่ผ่านการบันทึก)
    </div>
  </div>

  <div class="card">
    <div class="card-title">% Position Accurancy </div>
    <div id="positionOK" class="card-value">-</div>
    <div class="card-desc">
      เปอร์เซ็นต์ข้อมูลที่มีคุณภาพตำแหน่งดี
    </div>
  </div>

  <div class="card">
    <div class="card-title">% Velocity Quality </div>
    <div id="velocityOK" class="card-value">-</div>
    <div class="card-desc">
      เปอร์เซ็นต์ข้อมูลความเร็วที่ผ่านเกณฑ์ความถูกต้อง
    </div>
  </div>

  <div class="card">
    <div class="card-title">% PIC ต่ำกว่า Threshold</div>
    <div id="picLow" class="card-value">-</div>
    <div class="card-desc">
      สัดส่วนข้อมูลที่มีค่า Position Integrity Category ต่ำกว่าเกณฑ์ที่กำหนด
    </div>
  </div>

</div>

  <div style="height:20px;"></div>

  <!-- TABLES -->
  <div class="grid-2">
    <div class="card">
      <div class="section-title">Worst 5 Aircraft</div>
      <div class="card-desc">5 อันดับอากาศยานที่มีคุณภาพสัญญาณต่ำสุด</div>
      <div id="worstAircraftTable"></div>
    </div>
    <div class="card">
      <div class="section-title">Worst 5 Zones</div>
      <div class="card-desc"> 5 อันดับพื้นที่ที่มีคุณภาพสัญญาณต่ำสุด</div>
      <div id="worstZonesTable"></div>
    </div>
  </div>

  <div style="height:20px;"></div>

  <!-- MAP -->
  <div class="card">

<div class="section-title">Risk Zones Map – Daily Snapshot</div>

<div style="margin-bottom:15px;">
<label style="font-size:12px;font-weight:800;">Metric:</label>

<select id="metricSelect" onchange="changeMetric()"
style="padding:6px 10px;border-radius:8px;font-weight:700;">
<option value="nic">NIC (Position Integrity)</option>
<option value="nacp">NACp (Position Accuracy)</option>
<option value="nacv">NACv (Velocity Accuracy)</option>
<option value="pic">PIC (Integrity Category)</option>
</select>
</div>

<!-- FILTER -->
<div class="filter-panel" style="margin-top:30px;">
<button class="btn-dark" onclick="setFilter('all')">All</button>
<button class="btn-dark" onclick="setFilter('risk')">Integrity Risk</button>

<span style="font-size:12px;font-weight:800;">Range:</span>

<input id="rangeMin" type="number" value="5" step="0.1">
<span>-</span>
<input id="rangeMax" type="number" value="7" step="0.1">

<button class="btn-outline" onclick="applyRangeFilter()">Apply</button>
<button class="btn-outline" onclick="resetSelection()">Reset</button>

</div>


<!-- MAP WRAPPER -->
<div class="map-wrapper">

<!-- MAP -->
<div id="risk-map"></div>


<!-- GRID BUTTON -->
<button class="grid-toggle-btn" onclick="toggleGrid()" id="gridToggleBtn">
Hide Grid
</button>

<!-- METRIC GUIDE -->
<div class="metric-guide">

<b>Metric Guide</b>

<div class="guide-row">NIC ≥ 7 → ผ่าน</div>
<div class="guide-row">NACp ≥ 8 → ผ่าน</div>
<div class="guide-row">NACv ≥ 1 → ผ่าน</div>
<div class="guide-row">PIC ≥ 7 → ผ่าน</div>

</div>

<!-- MAP LEGEND -->
<div id="mapLegend">

<b>Signal Quality</b>

<div>
<span class="legend-color good"></span>
Good
</div>

<div>
<span class="legend-color warn"></span>
Warning
</div>

<div>
<span class="legend-color risk"></span>
Risk
</div>

</div>

</div>

</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="/map24/assets/js/dashboard.js"></script>

<?php include __DIR__ . '/partials/footer.php'; ?>