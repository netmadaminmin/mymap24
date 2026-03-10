document.addEventListener("DOMContentLoaded", function () {

/* ================= GLOBAL ================= */
let map = null;
let nicLayer = null;
let hexGrid = [];
let nicTrendChart = null;
let anomalyChart = null;
let hexVisible = true;

/* ================= SAFE ================= */
function safeNum(x, fallback = 0) {
  const n = parseFloat(x);
  return isNaN(n) ? fallback : n;
}

function safeText(x, fallback = "-") {
  return (x === null || x === undefined || x === "") ? fallback : x;
}

/* ================= LEVEL ================= */
function getLevel(nic) {
  nic = safeNum(nic, -1);
  if (nic < 0) return {text:"NO DATA", class:"nodata"};
  if (nic <= 3) return {text:"CRITICAL", class:"critical"};
  if (nic <= 6) return {text:"WARNING", class:"warn"};
  return {text:"GOOD", class:"good"};
}

/* ================= THAILAND BOUNDS ================= */
const THAI_BOUNDS = {
  latMin: 5.5,
  latMax: 20.8,
  lonMin: 97.0,
  lonMax: 105.5
};

const HEX_RADIUS_KM = 30;
const HEX_TOLERANCE_DEG = 0.15;

/* ================= KM ================= */
function kmToLatDeg(km){ return km/111.0; }
function kmToLonDeg(km,lat){ return km/(111.0*Math.cos(lat*Math.PI/180)); }

/* ================= HEX ================= */
function createHexagonKm(lat,lon,radiusKm){
  const pts=[];
  for(let i=0;i<6;i++){
    const angle=(60*i-30)*Math.PI/180;
    const dx=radiusKm*Math.cos(angle);
    const dy=radiusKm*Math.sin(angle);
    pts.push([
      lat+(dy/111),
      lon+(dx/(111*Math.cos(lat*Math.PI/180)))
    ]);
  }
  return pts;
}

/* ================= GRID ================= */
function generateThailandGrid(){
  hexGrid=[];
  const latStep=kmToLatDeg(1.5*HEX_RADIUS_KM);
  let row=0;

  for(let lat=THAI_BOUNDS.latMin; lat<=THAI_BOUNDS.latMax; lat+=latStep){
    const lonStep=kmToLonDeg(Math.sqrt(3)*HEX_RADIUS_KM,lat);
    const offset=(row%2===0)?0:lonStep/2;

    for(let lon=THAI_BOUNDS.lonMin; lon<=THAI_BOUNDS.lonMax; lon+=lonStep){
      const lonFixed=lon+offset;
      if(lonFixed>THAI_BOUNDS.lonMax) continue;
      hexGrid.push({lat:lat,lon:lonFixed});
    }
    row++;
  }
}

/* ================= MAP ================= */
function initMap(){
  if(map) return;

  map=L.map("nic-map").setView([13.5,100.5],6);

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",{
    maxZoom:18,
    attribution:"© OpenStreetMap"
  }).addTo(map);

  nicLayer=L.layerGroup().addTo(map);
  generateThailandGrid();
}

function getZoneColor(v){
  v=safeNum(v,-1);
  if(v<0) return "#9ca3af";
  if(v<=3) return "#dc2626";
  if(v<=6) return "#f59e0b";
  return "#16a34a";
}

function findNearestZone(zones,lat,lon){
  let best=null, bestDist=999;
  zones.forEach(z=>{
    if(!z.lat_min) return;
    const cLat=(safeNum(z.lat_min)+safeNum(z.lat_max))/2;
    const cLon=(safeNum(z.lon_min)+safeNum(z.lon_max))/2;
    const dLat=Math.abs(cLat-lat);
    const dLon=Math.abs(cLon-lon);
    if(dLat<=HEX_TOLERANCE_DEG && dLon<=HEX_TOLERANCE_DEG){
      const dist=Math.sqrt(dLat*dLat+dLon*dLon);
      if(dist<bestDist){bestDist=dist; best=z;}
    }
  });
  return best;
}

function drawZones(zones){
  nicLayer.clearLayers();
  if(!hexVisible) return;

  hexGrid.forEach(cell=>{
    const data=findNearestZone(zones,cell.lat,cell.lon);

    let avg=-1,records=0,anomalies=0,zoneId="No Data";

    if(data){
      avg=safeNum(data.avg_nic,-1);
      records=safeNum(data.records);
      anomalies=safeNum(data.anomalies);
      zoneId=data.zone_id;
    }

    const hex=L.polygon(
      createHexagonKm(cell.lat,cell.lon,HEX_RADIUS_KM),
      {
        color:"#9da7b5",
        weight:0.6,
        fillColor:records>0?getZoneColor(avg):"transparent",
        fillOpacity:records>0?0.75:0
      }
    );

    hex.bindPopup(`
      <b>Zone:</b> ${zoneId}<br>
      <b>Avg NIC:</b> ${avg>=0?avg.toFixed(2):"No Data"}<br>
      <b>Records:</b> ${records}<br>
      <b>Anomalies:</b> ${anomalies}
    `);

    hex.addTo(nicLayer);
  });
}

/* ================= ZOOM HELPERS ================= */

function zoomToAircraft(icao){
  if(!icao || !map) return;

  fetch(`/map24/api/get_nic_data.php?mode=aircraft_position&icao=${encodeURIComponent(icao)}`)
    .then(res=>res.json())
    .then(data=>{
      if(data.lat && data.lon){
        map.setView([parseFloat(data.lat), parseFloat(data.lon)], 9);
      }
    })
    .catch(err=>console.error("ZOOM AIRCRAFT ERROR:",err));
}

function zoomToZone(zoneId){
  if(!zoneId || !map) return;

  const zone = hexGrid.find(cell=>{
    return `${cell.lat.toFixed(2)}_${cell.lon.toFixed(2)}` === zoneId;
  });

  if(zone){
    map.setView([zone.lat, zone.lon], 7);
  }
}

/* ================= CHART ================= */
function drawTrendChart(data){
  const canvas=document.getElementById("nicTrendChart");
  if(!canvas) return;
  if(nicTrendChart) nicTrendChart.destroy();
  if(!data.length) return;

  nicTrendChart=new Chart(canvas,{
    type:"line",
    data:{
      labels:data.map(d=>d.day),
      datasets:[{
        label:"Average NIC (All Data)",
        data:data.map(d=>safeNum(d.avg_nic)),
        borderWidth:2,
        tension:0.3
      }]
    },
    options:{responsive:true,maintainAspectRatio:false}
  });
}

function drawAnomalyChart(data){
  const canvas=document.getElementById("anomalyChart");
  if(!canvas) return;
  if(anomalyChart) anomalyChart.destroy();
  if(!data.length) return;

  anomalyChart=new Chart(canvas,{
    type:"bar",
    data:{
      labels:data.map(d=>d.day),
      datasets:[{
        label:"Anomalies (NIC < 4)",
        data:data.map(d=>safeNum(d.count))
      }]
    },
    options:{responsive:true,maintainAspectRatio:false}
  });
}

/* ================= TABLE ================= */
function renderAircraftTable(data){
  const tbody=document.getElementById("aircraftTable");
  if(!tbody) return;
  tbody.innerHTML="";

  if(!data.length){
    tbody.innerHTML=`<tr><td colspan="5">No Data</td></tr>`;
    return;
  }

  data.forEach(a=>{
    const level=getLevel(a.avg_nic);
    tbody.innerHTML+=`
      <tr onclick="zoomToAircraft('${a.icao}')" style="cursor:pointer">
        <td>${safeText(a.icao)}</td>
        <td>${safeNum(a.records)}</td>
        <td>${safeNum(a.avg_nic).toFixed(2)}</td>
        <td>${safeNum(a.anomalies)}</td>
        <td><span class="badge ${level.class}">${level.text}</span></td>
      </tr>`;
  });
}

function renderZoneTable(data){
  const tbody=document.getElementById("zoneTable");
  if(!tbody) return;
  tbody.innerHTML="";

  if(!data.length){
    tbody.innerHTML=`<tr><td colspan="5">No Data</td></tr>`;
    return;
  }

  data.forEach(z=>{
    const level=getLevel(z.avg_nic);
    tbody.innerHTML+=`
      <tr onclick="zoomToZone('${z.zone_id}')" style="cursor:pointer">
        <td>${safeText(z.zone_id)}</td>
        <td>${safeNum(z.records)}</td>
        <td>${safeNum(z.avg_nic).toFixed(2)}</td>
        <td>${safeNum(z.anomalies)}</td>
        <td><span class="badge ${level.class}">${level.text}</span></td>
      </tr>`;
  });
}

/* ================= LOAD ICAO LIST ================= */
async function loadIcaoList(){
  try{
    const res = await fetch("/map24/api/get_nic_data.php?icao_list=1");
    const data = await res.json();

    const select = document.getElementById("icaoSelect");
    if(!select) return;

    select.innerHTML = `<option value="all">All Aircraft</option>`;

    if(Array.isArray(data.icao_list)){
      data.icao_list.forEach(code=>{
        const opt = document.createElement("option");
        opt.value = code;
        opt.textContent = code;
        select.appendChild(opt);
      });
    }

  }catch(err){
    console.error("LOAD ICAO LIST ERROR:",err);
  }
}

/* ================= LOAD DASHBOARD ================= */
async function loadNICDashboard(){
  try{
    initMap();

    const icao=document.getElementById("icaoSelect")?.value||"all";
    const level=document.getElementById("nicLevelSelect")?.value||"all";
    const onlyAnomaly=document.getElementById("onlyAnomalyCheck")?.checked?1:0;

    let url="/map24/api/get_nic_data.php?mode=all";

    if(icao!=="all") url+=`&icao=${encodeURIComponent(icao)}`;
    if(level!=="all") url+=`&level=${level}`;
    url+=`&only_anomaly=${onlyAnomaly}`;

    console.log("ALL DATA URL:",url);

    const res=await fetch(url);
    const data=await res.json();

    document.getElementById("totalRecords").innerText=safeNum(data.summary?.total_records);
    document.getElementById("avgNIC").innerText=safeNum(data.summary?.avg_nic).toFixed(2);
    document.getElementById("anomalyCount").innerText=safeNum(data.summary?.total_anomalies);
    document.getElementById("worstAircraft").innerText=safeText(data.summary?.worst_aircraft);
    document.getElementById("riskZones").innerText=safeNum(data.summary?.risk_zone_count);

    drawZones(data.risk_zones||[]);
    drawTrendChart(data.daily_nic||[]);
    drawAnomalyChart(data.daily_anomalies||[]);
    renderAircraftTable(data.top_aircraft||[]);
    renderZoneTable(data.top_zones||[]);

  }catch(err){
    console.error("NIC DASHBOARD ERROR:",err);
  }
}

/* ================= EVENTS ================= */
["icaoSelect","nicLevelSelect"].forEach(id=>{
  const el=document.getElementById(id);
  if(el) el.addEventListener("change",loadNICDashboard);
});

const anomalyCheck=document.getElementById("onlyAnomalyCheck");
if(anomalyCheck)
  anomalyCheck.addEventListener("change",loadNICDashboard);

/* ================= INIT ================= */
loadIcaoList();
loadNICDashboard();

});