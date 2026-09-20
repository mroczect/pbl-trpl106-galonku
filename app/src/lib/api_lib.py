import json
from urllib import error as urlerror
from urllib import request as urlrequest
from urllib.parse import urlencode

from config import auth_config as cfg


class ApiError(Exception):
    def __init__(self, message, status=None, errors=None):
        super().__init__(message)
        self.message = message
        self.status = status
        self.errors = errors


class BaseAPI:
    def __init__(self, base_url=None, timeout=None):
        self.base_url = (base_url or cfg.BASE_URL).rstrip("/")
        self.timeout = timeout or cfg.REQUEST_TIMEOUT

    def request(self, method, path, body=None, token=None, params=None):
        url = f"{self.base_url}{path}"
        if params:
            url += "?" + urlencode(params)

        data = None
        headers = {"Accept": "application/json"}
        if body is not None:
            data = json.dumps(body).encode("utf-8")
            headers["Content-Type"] = "application/json"
        if token:
            headers["Authorization"] = f"Bearer {token}"

        req = urlrequest.Request(url, data=data, headers=headers, method=method)
        try:
            with urlrequest.urlopen(req, timeout=self.timeout) as resp:
                raw = resp.read().decode("utf-8") or "{}"
                return json.loads(raw)
        except urlerror.HTTPError as e:
            raw = e.read().decode("utf-8") or "{}"
            try:
                payload = json.loads(raw)
            except json.JSONDecodeError:
                payload = {}
            raise ApiError(
                payload.get("message", f"HTTP {e.code}"),
                status=e.code,
                errors=payload.get("errors"),
            ) from None
        except urlerror.URLError as e:
            raise ApiError(f"Tidak bisa terhubung ke server: {e.reason}") from None
        except TimeoutError:
            raise ApiError("Koneksi ke server timeout") from None

    def get(self, path, token=None, params=None):
        return self.request("GET", path, token=token, params=params)

    def post(self, path, body=None, token=None):
        return self.request("POST", path, body=body, token=token)

    def put(self, path, body=None, token=None):
        return self.request("PUT", path, body=body, token=token)

    def delete(self, path, token=None):
        return self.request("DELETE", path, token=token)
