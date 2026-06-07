# Screen Time API

Base path: /screentime/api.php
All responses are JSON.

## Common Response Shape
- success: boolean
- data: object (on success)
- error: string (on error)
- message: string (optional details)

## Endpoints

### Probe (register or refresh a target)
GET /screentime/api.php?endpoint=probe&username=...&hostname=...

Query params:
- username (required)
- hostname (required)

Response:
- success: true
- data:
  - target_id: integer
  - username: string
  - hostname: string
  - allowed_until: string|null (Y-m-d H:i:s)
  - remaining_seconds: integer
  - last_probe_at: string (Y-m-d H:i:s)

Notes:
- Creates the target if it does not exist.
- Updates last_probe_at on every call.

### Grant (set allowed time)
POST /screentime/api.php?endpoint=grant

Accepts application/json or form-encoded.

Body:
- target_id (required)
- minutes (optional, integer) OR allowed_until (optional, Y-m-d H:i:s)

Response:
- success: true
- data:
  - target_id: integer
  - allowed_until: string (Y-m-d H:i:s)

Notes:
- If minutes is provided, allowed_until is computed from now.
- If allowed_until is provided, it must be in Y-m-d H:i:s.

### Recent (list recent probes)
GET /screentime/api.php?endpoint=recent&days=7

Query params:
- days (optional, integer; default 7)

Response:
- success: true
- data: array of targets
  - id
  - username
  - hostname
  - allowed_until
  - last_probe_at

### Status (check a target)
GET /screentime/api.php?endpoint=status&target_id=...
GET /screentime/api.php?endpoint=status&username=...&hostname=...

Query params:
- target_id (optional)
- username + hostname (required if target_id not provided)

Response:
- success: true
- data:
  - target_id: integer
  - username: string
  - hostname: string
  - allowed_until: string|null (Y-m-d H:i:s)
  - remaining_seconds: integer
  - last_probe_at: string (Y-m-d H:i:s)

## Errors
- 404 for unknown endpoints
- 405 for wrong HTTP methods
- 500 for database errors
