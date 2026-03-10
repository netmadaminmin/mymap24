/* ================= GLOBAL ================= */

let riskMap = null
let riskLayer = null
let aircraftLayer = null

let hexIndex = {}
let selectedHex = null

let aircraftMarkers = {}

let currentMetric = "nic"
let currentFilter = "all"

let rangeMin = 5
let rangeMax = 7

let dashboardData = null
let selectedZoneHighlight = null
let selectedZonePopup = null
let gridVisible = true
let filterActive = false

/* ================= HEX CONFIG ================= */

const HEX_RADIUS = 0.25

const HEX_WIDTH = Math.sqrt(3) * HEX_RADIUS
const HEX_HEIGHT = 2 * HEX_RADIUS

const VERT_DIST = HEX_HEIGHT * 0.75
const HORIZ_DIST = HEX_WIDTH


/* ================= THAILAND BOUNDS ================= */

const LAT_MIN = 5.5
const LAT_MAX = 20.8
const LON_MIN = 97.0
const LON_MAX = 105.8


/******************** CHANGE METRIC ********************/

function changeMetric(){

    const select = document.getElementById("metricSelect")

    if(!select) return

    currentMetric = select.value

    console.log("Metric changed:", currentMetric)

    if(riskLayer){
        riskLayer.clearLayers()
    }

    loadDashboard()

}

/******************** MAP ********************/

function initMap(){

    riskMap = L.map("risk-map").setView([13.5,100.5],6)

    L.tileLayer(
        "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
        {
            maxZoom:18,
            attribution:"© OpenStreetMap"
        }
    ).addTo(riskMap)

    riskLayer = L.layerGroup().addTo(riskMap)
    aircraftLayer = L.layerGroup().addTo(riskMap)

}


/******************** LOAD DASHBOARD ********************/

function loadDashboard(){

    fetch("/map24/api/get_dashboard_data.php?metric="+currentMetric)
    .then(r=>{

        if(!r.ok){
            throw new Error("Server error "+r.status)
        }

        return r.json()

    })
    .then(data=>{

        console.log("FULL API RESPONSE:",data)

        dashboardData = data

        if(data.summary){
            updateSummary(data.summary)
        }

        if(data.worst_aircraft){
            renderWorstAircraft(data.worst_aircraft)
        }

        if(data.worst_zones){
            renderWorstZones(data.worst_zones)
        }

        if(data.zone_grid){
            drawHexGrid(data.zone_grid)
        }

        if(data.time_range){
            setTimeFromDataset(data.time_range)
        }

    })
    .catch(err=>{
        console.error("Dashboard load error:",err)
    })

}


/******************** SUMMARY ********************/

function updateSummary(summary){

    const total = document.getElementById("totalRecords")
    const pos = document.getElementById("positionOK")
    const vel = document.getElementById("velocityOK")
    const pic = document.getElementById("picLow")

    if(total) total.innerText = summary.total ?? "-"

    if(pos) pos.innerText =
        (summary.position_ok ?? 0) + "%"

    if(vel) vel.innerText =
        (summary.velocity_ok ?? 0) + "%"

    if(pic) pic.innerText =
        (summary.pic_low ?? 0) + "%"

}

function setTimeFromDataset(range){

    if(!range) return

    const min = new Date(range.min_time)
    const max = new Date(range.max_time)

    const dateInput = document.getElementById("searchDate")
    const startInput = document.getElementById("startTime")
    const endInput = document.getElementById("endTime")

    if(dateInput){
        dateInput.value = min.toISOString().slice(0,10)
    }

    if(startInput){
        startInput.value = min.toTimeString().slice(0,5)
    }

    if(endInput){
        endInput.value = max.toTimeString().slice(0,5)
    }

}


/******************** WORST AIRCRAFT TABLE ********************/

function renderWorstAircraft(list){

    const container = document.getElementById("worstAircraftTable")

    if(!container) return

    let html = "<table>"

    html += "<tr><th>ICAO</th><th>Metric</th><th>Records</th></tr>"

    list.forEach(a=>{

        html += `
        <tr class="clickable-row">
            <td>${a.icao}</td>
            <td>${a.avg.toFixed(2)}</td>
            <td>${a.count}</td>
        </tr>
        `

    })

    html += "</table>"

    container.innerHTML = html

}


/******************** WORST ZONES TABLE ********************/

function renderWorstZones(list){

    const container = document.getElementById("worstZonesTable")

    if(!container) return

    let html = "<table>"

    html += "<tr><th>Zone</th><th>Metric</th><th>Records</th></tr>"

    list.forEach((z,i)=>{

        html += `
        <tr class="clickable-row" data-index="${i}">
            <td>${Number(z.lat).toFixed(2)}, ${Number(z.lon).toFixed(2)}</td>
            <td>${Number(z.avg).toFixed(2)}</td>
            <td>${z.count}</td>
        </tr>
        `

    })

    html += "</table>"

    container.innerHTML = html

    const rows = container.querySelectorAll(".clickable-row")

    rows.forEach((row,i)=>{

        row.addEventListener("click",()=>{

            const z = list[i]

            zoomToZone(
                Number(z.lat),
                Number(z.lon),
                Number(z.avg),
                Number(z.count)
            )

        })

    })

}


/******************** ZOOM TO ZONE ********************/

function zoomToZone(lat,lon,metric,count){

    if(!riskMap) return

    const snapped = snapToHex(lat,lon)

    riskMap.setView([snapped.lat,snapped.lon],8)

    if(selectedZoneHighlight){
        riskMap.removeLayer(selectedZoneHighlight)
    }

    if(selectedZonePopup){
        riskMap.closePopup(selectedZonePopup)
    }

    const hex = createHexagon(snapped.lat,snapped.lon)

    selectedZoneHighlight = L.polygon(hex,{
        color:"#373a43ff",
        weight:3,
        fill:false
    }).addTo(riskMap)

    const popupContent = `
    <b>Zone Analysis</b><br>
    Lat: ${lat.toFixed(3)}<br>
    Lon: ${lon.toFixed(3)}<br>
    Metric: ${metric.toFixed(2)}<br>
    Records: ${count}
    `

    selectedZonePopup = L.popup({
        closeButton:true
    })
    .setLatLng([snapped.lat,snapped.lon])
    .setContent(popupContent)
    .openOn(riskMap)

}


/******************** HEX GRID DRAW ********************/

function drawHexGrid(zones){

    riskLayer.clearLayers()

    const TOL = 0.15

    let row = 0

    for(let lat = LAT_MIN; lat <= LAT_MAX; lat += VERT_DIST){

        for(let lon = LON_MIN; lon <= LON_MAX; lon += HORIZ_DIST){

            let adjLon = lon

            if(row % 2 === 1){
                adjLon += HORIZ_DIST/2
            }

            let metric = null
            let count = 0

            const match = zones.find(z => {

                return (
                    Math.abs(Number(z.lat) - lat) <= TOL &&
                    Math.abs(Number(z.lon) - adjLon) <= TOL
                )

            })

            if(match){
                metric = Number(match.avg_metric)
                count = Number(match.count)
            }

            const hex = createHexagon(lat,adjLon)

            
           let color = "transparent"
           let opacity = 0

            if(metric !== null){

                if(!filterActive){

                    color = getRiskColor(metric)
                    opacity = 0.75

                }
                else{

                    if(zonePassFilter(metric)){
                        color = getRiskColor(metric)
                        opacity = 0.75
                    }
                    else{
                        returcolor = "transparent"
                        opacity = 0
                    }

                }

}

            const polygon = L.polygon(hex,{
                color:"#9ca3af",
                weight:0.6,
                fillColor:color,
                fillOpacity:opacity
            })

            const tooltipText = metric === null
                ? `No Data`
                : `
                Metric: ${metric.toFixed(2)}
                <br>Records: ${count}
                `

            polygon.bindTooltip(tooltipText)

            polygon.addTo(riskLayer)

        }

        row++
    }

}
function setFilter(type){

    currentFilter = type
    filterActive = true

    if(dashboardData && dashboardData.zone_grid){
        drawHexGrid(dashboardData.zone_grid)
    }

}
function applyRangeFilter(){

    const minInput = document.getElementById("rangeMin")
    const maxInput = document.getElementById("rangeMax")

    rangeMin = parseFloat(minInput.value)
    rangeMax = parseFloat(maxInput.value)

    filterActive = true

    if(dashboardData && dashboardData.zone_grid){
        drawHexGrid(dashboardData.zone_grid)
    }

}
function resetSelection(){

    filterActive = false
    currentFilter = "all"

    rangeMin = 5
    rangeMax = 7

    document.getElementById("rangeMin").value = 5
    document.getElementById("rangeMax").value = 7

    if(dashboardData && dashboardData.zone_grid){
        drawHexGrid(dashboardData.zone_grid)
    }

}
/******************** HEX SHAPE ********************/

function createHexagon(lat,lon){

    const pts=[]

    for(let i=0;i<6;i++){

        const angle = (Math.PI/180) * (60*i + 30)

        const dx = HEX_RADIUS*Math.cos(angle)
        const dy = HEX_RADIUS*Math.sin(angle)

        pts.push([
            lat + dy,
            lon + dx
        ])
    }

    return pts
}


function snapToHex(lat,lon){

    const row = Math.round((lat - LAT_MIN) / VERT_DIST)

    const centerLat = LAT_MIN + row * VERT_DIST

    let centerLon = LON_MIN + Math.round((lon - LON_MIN) / HORIZ_DIST) * HORIZ_DIST

    if(row % 2 === 1){
        centerLon += HORIZ_DIST/2
    }

    return {
        lat:centerLat,
        lon:centerLon
    }
}


/******************** COLOR SCALE ********************/

function getRiskColor(value){

    let threshold = 7

    if(currentMetric === "nacp") threshold = 8
    if(currentMetric === "nic") threshold = 7
    if(currentMetric === "nacv") threshold = 1
    if(currentMetric === "pic") threshold = 7

    if(value >= threshold){
        return "#16a34a"
    }

    if(value >= threshold - 1){
        return "#facc15"
    }

    return "#ef4444"
}

function toggleGrid(){

  if(!riskLayer) return

  if(gridVisible){
      riskMap.removeLayer(riskLayer)
      gridVisible = false
      document.getElementById("gridToggleBtn").innerText = "Show Grid"
  }
  else{
      riskMap.addLayer(riskLayer)
      gridVisible = true
      document.getElementById("gridToggleBtn").innerText = "Hide Grid"
  }

}
async function handleImport(){

  const fileInput = document.getElementById("csvFile")
  const result = document.getElementById("result")

  if(!fileInput.files.length){
    alert("Please select CSV file")
    return
  }

  const file = fileInput.files[0]

  const formData = new FormData()
  formData.append("file", file)

  result.innerHTML = "Uploading..."

  try{

    const res = await fetch("/map24/api/import_csv.php",{
      method:"POST",
      body:formData
    })

    const data = await res.json()

    if(data.success){

      result.innerHTML = "Import success"
      loadDashboard()

    }else{

      result.innerHTML = "Import failed: " + data.error

    }

  }catch(err){

    result.innerHTML = "Import error"

    console.error(err)

  }

}
function zonePassFilter(metric){

    if(metric === null) return true

    // risk filter
    if(currentFilter === "risk"){

        let threshold = 7

        if(currentMetric === "nacp") threshold = 8
        if(currentMetric === "nacv") threshold = 1
        if(currentMetric === "pic") threshold = 7

        if(metric >= threshold){
            return false
        }

    }

    // range filter
    if(metric < rangeMin || metric > rangeMax){
        return false
    }

    return true
}
/******************** INIT ********************/

document.addEventListener("DOMContentLoaded", () => {

    initMap()
    loadDashboard()

})