<?php
// admin/index.php
// Simple admin page to create/update tracking records via api/save.php
// This page is intentionally self-contained (no external dependencies).
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>RouteTrack Admin</title>
<style>
  body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;max-width:1100px;margin:24px auto;padding:16px;color:#0b1220}
  label{display:block;margin-top:12px;font-weight:600}
  input, select, textarea{width:100%;padding:8px;margin-top:6px;border:1px solid #ccd; border-radius:6px}
  .row{display:flex;gap:12px}
  .col{flex:1}
  button{padding:10px 14px;border-radius:8px;border:none;background:#1E3A5F;color:white;cursor:pointer}
  .small{font-size:13px;color:#556}
  .chip{display:inline-block;padding:6px 10px;background:#eef;border-radius:8px;margin-right:8px;margin-top:6px}
  .history-item{border:1px solid #e6e; padding:8px;border-radius:8px;margin-top:8px}
  .map-preview{height:220px;border:1px dashed #ccd;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#889}
  .success{padding:10px;background:#e6ffed;border:1px solid #bfe8c7;margin-top:12px;border-radius:8px}
</style>
</head>
<body>
<h1>RouteTrack — Admin panel</h1>
<p class="small">Use this panel to create/update tracking entries. The API is saved to /api/data/tracking_{TRACKING}.json and is served at <code>/api/tracking/{TRACKING}</code>.</p>

<form id="trackingForm">
  <div class="row">
    <div class="col">
      <label>Tracking number (leave blank to auto-generate)</label>
      <input id="trackingNumber" name="tracking_number" placeholder="e.g. RT-ABC123" />
    </div>
    <div class="col">
      <label>Current status</label>
      <input id="statusSlider" type="range" min="0" max="3" value="0" />
      <div class="small">Status: <span id="statusLabel">Pending</span></div>
    </div>
  </div>

  <label>Current location</label>
  <input id="currentLocation" name="current_location" placeholder="e.g. New York, NY" />

  <div class="row">
    <div class="col">
      <label>Latitude</label>
      <input id="latitude" name="latitude" placeholder="e.g. 40.7128" />
    </div>
    <div class="col">
      <label>Longitude</label>
      <input id="longitude" name="longitude" placeholder="e.g. -74.0060" />
    </div>
  </div>

  <label>Estimated delivery date/time</label>
  <input id="estimatedDelivery" name="estimated_delivery" type="date" />
  <input id="estimatedTime" name="estimated_time" type="time" style="width:50%;margin-top:8px" />

  <label>Package details (JSON or simple key:value lines)</label>
  <textarea id="packageDetails" rows="4" placeholder="weight: 1.2kg&#10;dimensions: 20x15x8cm&#10;service_type: Express"></textarea>

  <label>Progress steps</label>
  <div id="progressStepsContainer"></div>
  <button type="button" id="addProgressStep" style="margin-top:8px;background:#2C5282">Add Progress Step</button>

  <label style="margin-top:14px">Tracking history</label>
  <div id="historyContainer"></div>
  <button type="button" id="addHistoryItem" style="margin-top:8px;background:#2C5282">Add History Item</button>

  <label style="margin-top:14px">Optional: route coordinates (lat,lng per line)</label>
  <textarea id="routeCoords" rows="4" placeholder="40.7128,-74.0060&#10;40.7130,-74.0100"></textarea>

  <div style="margin-top:14px;display:flex;gap:12px;align-items:center">
    <button id="saveBtn" type="button">Save Tracking</button>
    <button id="randomBtn" type="button" style="background:#E67E22">Generate random tracking</button>
    <div id="statusMessage" style="flex:1"></div>
  </div>

  <div class="success" id="previewBox" style="display:none"></div>
</form>

<script>
const API_BASE = location.origin + '/api'; // you can change this if api runs elsewhere
const statusMap = ['pending','in_transit','out_for_delivery','delivered'];
const statusText = ['Pending','In Transit','Out for Delivery','Delivered'];

const statusSlider = document.getElementById('statusSlider');
const statusLabel = document.getElementById('statusLabel');
const trackingNumberInput = document.getElementById('trackingNumber');
const currentLocationInput = document.getElementById('currentLocation');
const latitudeInput = document.getElementById('latitude');
const longitudeInput = document.getElementById('longitude');
const estimatedDeliveryInput = document.getElementById('estimatedDelivery');
const estimatedTimeInput = document.getElementById('estimatedTime');
const packageDetailsInput = document.getElementById('packageDetails');
const progressStepsContainer = document.getElementById('progressStepsContainer');
const historyContainer = document.getElementById('historyContainer');
const routeCoordsInput = document.getElementById('routeCoords');
const previewBox = document.getElementById('previewBox');
const statusMessage = document.getElementById('statusMessage');

function updateStatusLabel() {
  const v = parseInt(statusSlider.value,10);
  statusLabel.textContent = statusText[v];
}
statusSlider.addEventListener('input', updateStatusLabel);
updateStatusLabel();

document.getElementById('randomBtn').addEventListener('click', () => {
  trackingNumberInput.value = 'RT-' + Math.random().toString(36).slice(2,9).toUpperCase();
});

// dynamic add progress step
document.getElementById('addProgressStep').addEventListener('click', () => {
  const idx = progressStepsContainer.children.length;
  const div = document.createElement('div');
  div.className = 'history-item';
  div.innerHTML = `
    <label>Title</label>
    <input class="step-title" placeholder="Picked up / At facility / Out for delivery" />
    <label>Location</label><input class="step-location" placeholder="City, State" />
    <div style="display:flex;gap:8px">
      <div style="flex:1"><label>Date</label><input class="step-date" type="date" /></div>
      <div style="flex:1"><label>Time</label><input class="step-time" type="time" /></div>
    </div>
    <label>Status</label>
    <select class="step-status">
      <option value="pending">Pending</option>
      <option value="current">Current</option>
      <option value="completed">Completed</option>
    </select>
    <button type="button" class="remove-btn" style="margin-top:8px;background:#E85C4B">Remove</button>
  `;
  div.querySelector('.remove-btn').addEventListener('click', ()=>div.remove());
  progressStepsContainer.appendChild(div);
});

// dynamic add history item
document.getElementById('addHistoryItem').addEventListener('click', () => {
  const div = document.createElement('div');
  div.className = 'history-item';
  div.innerHTML = `
    <label>Event</label><input class="hist-event" placeholder="Package picked up" />
    <label>Location</label><input class="hist-location" placeholder="facility or city" />
    <label>Date/Time</label>
    <div style="display:flex;gap:8px">
      <input class="hist-date" type="date" />
      <input class="hist-time" type="time" />
    </div>
    <label>Details (handler / notes)</label>
    <textarea class="hist-details" rows="2" placeholder="Handled by John"></textarea>
    <label>Lat,Lng (optional)</label>
    <input class="hist-latlng" placeholder="40.7128,-74.0060" />
    <button type="button" class="remove-btn" style="margin-top:8px;background:#E85C4B">Remove</button>
  `;
  div.querySelector('.remove-btn').addEventListener('click', ()=>div.remove());
  historyContainer.appendChild(div);
});

// parse package details text to object
function parsePackageDetails(text) {
  const lines = text.split(/\r?\n/).map(s=>s.trim()).filter(Boolean);
  const obj = {};
  lines.forEach(line=>{
    const m = line.match(/^([^:]+):\s*(.+)$/);
    if (m) {
      obj[m[1].trim().toLowerCase()] = m[2].trim();
    }
  });
  return obj;
}

// read progress steps
function gatherProgressSteps() {
  const steps = [];
  progressStepsContainer.querySelectorAll('.history-item').forEach(div=>{
    const title = div.querySelector('.step-title').value || '';
    const location = div.querySelector('.step-location').value || '';
    const date = div.querySelector('.step-date').value || '';
    const time = div.querySelector('.step-time').value || '';
    const status = div.querySelector('.step-status').value || 'pending';
    steps.push({ title, location, date, time, status });
  });
  return steps;
}

// read history
function gatherHistory() {
  const arr = [];
  historyContainer.querySelectorAll('.history-item').forEach(div=>{
    const event = div.querySelector('.hist-event').value || '';
    const location = div.querySelector('.hist-location').value || '';
    const date = div.querySelector('.hist-date').value;
    const time = div.querySelector('.hist-time').value;
    const timestamp = date ? new Date(date + 'T' + (time || '00:00')).toISOString() : new Date().toISOString();
    const detailsText = div.querySelector('.hist-details').value || '';
    const latlng = div.querySelector('.hist-latlng').value || '';
    const details = {};
    if (detailsText) details.notes = detailsText;
    if (latlng) {
      const p = latlng.split(',').map(s=>s.trim());
      if (p.length===2) {
        details.lat = parseFloat(p[0]); details.lng = parseFloat(p[1]);
      }
    }
    // basic status encoding
    arr.push({
      event,
      location,
      timestamp,
      status: 'in_transit',
      details
    });
  });
  return arr;
}

function gatherRouteCoords() {
  const coords = [];
  routeCoordsInput.value.split(/\r?\n/).map(s=>s.trim()).filter(Boolean).forEach(line=>{
    const parts = line.split(',');
    if (parts.length>=2) {
      const lat = parseFloat(parts[0]);
      const lng = parseFloat(parts[1]);
      if (!isNaN(lat) && !isNaN(lng)) coords.push([lat, lng]);
    }
  });
  return coords;
}

// Save call
document.getElementById('saveBtn').addEventListener('click', async () => {
  statusMessage.textContent = 'Saving…';
  const payload = {
    tracking_number: trackingNumberInput.value.trim() || undefined,
    status: statusMap[parseInt(statusSlider.value,10)],
    description: '',
    current_location: currentLocationInput.value.trim(),
    last_updated: new Date().toISOString(),
    estimated_delivery: estimatedDeliveryInput.value || undefined,
    estimated_time: estimatedTimeInput.value || undefined,
    latitude: latitudeInput.value ? parseFloat(latitudeInput.value) : undefined,
    longitude: longitudeInput.value ? parseFloat(longitudeInput.value) : undefined,
    route_coordinates: gatherRouteCoords(),
    progress_steps: gatherProgressSteps(),
    package_details: parsePackageDetails(packageDetailsInput.value),
    history: gatherHistory()
  };

  try {
    const res = await fetch(API_BASE + '/save.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify(payload)
    });
    const j = await res.json();
    if (j.success) {
      statusMessage.innerHTML = '<strong>Saved:</strong> ' + j.tracking_number + 
        ' — API: <code>' + API_BASE + '/tracking/' + j.tracking_number + '</code>';
      previewBox.style.display = 'block';
      previewBox.textContent = JSON.stringify(payload, null, 2);
      // populate tracking number field with generated value
      trackingNumberInput.value = j.tracking_number;
    } else {
      statusMessage.textContent = 'Error: ' + (j.error || 'Unknown');
    }
  } catch (err) {
    statusMessage.textContent = 'Request failed: ' + err.message;
  }
});
</script>

</body>
</html>
