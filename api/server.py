import json
import os
import re
from http.server import HTTPServer, BaseHTTPRequestHandler
from urllib.parse import urlparse, parse_qs
from urllib.request import Request, urlopen

PORT = int(os.environ.get("PORT", 8000))

def clean_promo(val):
    if isinstance(val, str):
        val = re.sub(r'^[^\r\n]*(@Dead_bans|t\.me/Dead_bans|@Your_father_786k|t\.me/Your_father_786k)[^\r\n]*(\r?\n)?', '', val, flags=re.MULTILINE | re.IGNORECASE)
        val = re.sub(r'(https?://)?(t\.me/|@)Dead_bans\b', '', val, flags=re.IGNORECASE)
        val = re.sub(r'(https?://)?(t\.me/|@)Your_father_786k\b', '', val, flags=re.IGNORECASE)
        return re.sub(r'\n{3,}', '\n\n', val).strip()
    elif isinstance(val, dict):
        return {k: clean_promo(v) for k, v in val.items()}
    elif isinstance(val, list):
        return [clean_promo(v) for v in val]
    return val

class ApiHandler(BaseHTTPRequestHandler):
    def do_GET(self):
        parsed = urlparse(self.path)
        params = parse_qs(parsed.query)

        # Get vehicle number from various possible query parameters
        vehicle = (
            params.get('vehicle', [None])[0] or 
            params.get('q', [None])[0] or 
            params.get('query', [None])[0] or 
            params.get('rc', [None])[0] or 
            params.get('num', [None])[0]
        )

        if not vehicle:
            self._send_json(200, {
                "success": False,
                "message": "Please provide a Vehicle Number (e.g. ?vehicle=UP70DN1860 or ?q=UP70DN1860)"
            })
            return

        clean_vehicle = re.sub(r'[^a-zA-Z0-9]', '', vehicle).upper()
        target_url = f"https://altaf-api-maker.base44.app/functions/lookup?tool=veh2num&key=Demo&q={clean_vehicle}"

        try:
            req = Request(target_url, headers={
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
            })
            with urlopen(req, timeout=45) as resp:
                raw_data = resp.read().decode('utf-8')
                data = json.loads(raw_data)
        except Exception as e:
            self._send_json(500, {
                "success": False,
                "message": f"Failed to fetch vehicle data: {str(e)}",
                "query": clean_vehicle
            })
            return

        # Clean third-party branding / keys
        for key in ['developer', 'notice', 'key', 'credits', 'powered_by', 'api_info']:
            data.pop(key, None)

        data = clean_promo(data)

        # Simple & Clean API Output
        output = {
            "success": data.get("success", True),
            "query": clean_vehicle,
            "result": data.get("result"),
            "data": data
        }

        self._send_json(200, output)

    def _send_json(self, status_code, data):
        body = json.dumps(data, indent=2, ensure_ascii=False).encode('utf-8')
        self.send_response(status_code)
        self.send_header('Content-Type', 'application/json; charset=utf-8')
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Content-Length', str(len(body)))
        self.end_headers()
        self.wfile.write(body)

    def log_message(self, format, *args):
        pass  # Quiet logging

if __name__ == '__main__':
    server = HTTPServer(('0.0.0.0', PORT), ApiHandler)
    print(f"API Server running on http://localhost:{PORT}")
    server.serve_forever()
