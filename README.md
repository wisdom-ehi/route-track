# RouteTrack Simple PHP Backend

Overview
- Lightweight PHP backend for the provided frontend to serve tracking data.
- No database required: tracking records are saved as JSON files under /api/data/.
- Admin UI located at /admin/index.php to create or update entries.

Deploy steps
1. Create a folder structure on your hosting:
   - /api/
   - /api/data/ (must be writable by web server user)
   - /admin/

2. Upload these files:
   - /api/.htaccess
   - /api/tracking.php
   - /api/save.php
   - /admin/index.php

3. Make sure `/api/data/` is writable:
   - chmod 755 or 775 depending on host; if needed 0775 or 0777 (temporary) so PHP can write files.

4. Point your frontend `API_BASE_URL` to your backend root:
   - In the frontend JS replace:
     const API_BASE_URL = 'https://185.27.134.33/api';
   - with:
     const API_BASE_URL = 'https://routetrack.ct.ws/api';

Endpoints
- GET  /api/tracking/<TRACKING_NUMBER>
  - Returns the saved JSON for the tracking number. (404 if not found)
- POST /api/save.php
  - Accepts JSON payload to create/update a record. Returns { success: true, tracking_number: 'RT-...' }

Data format
- The admin UI posts a JSON object with fields like:
  - tracking_number
  - status (pending | in_transit | out_for_delivery | delivered | exception)
  - description
  - current_location
  - last_updated (ISO datetime)
  - estimated_delivery (YYYY-MM-DD)
  - estimated_time (HH:MM)
  - latitude, longitude (floats)
  - route_coordinates: [[lat,lng], ...]
  - progress_steps: [{title, location, date, time, status}, ...]
  - package_details: {weight, dimensions, ...}
  - history: [{event, location, timestamp, status, details}, ...]

Security notes
- Current CORS is set to '*' for simplicity. Set `Access-Control-Allow-Origin` in `api/*.php` to your frontend origin (e.g. https://routetrack.ct.ws or the domain serving your frontend) to reduce risk.
- This is a minimal admin panel without authentication. Add authentication (HTTP auth, or a simple password check) in save.php/admin if you expose it publicly.
- Sanitize/validate all inputs if you extend this for production use.

If you want, I can:
- Add a simple password protection to /admin/ and to api/save.php.
- Add sample seed data or an example tracking JSON file you can upload to /api/data/ for testing.
- Convert the admin UI to require a password or to use a tiny token.
