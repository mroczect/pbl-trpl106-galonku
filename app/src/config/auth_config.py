from pathlib import Path

BASE_URL = "http://localhost:8000/api/v1"

LOGIN_PATH = "/auth/login"
REGISTER_PATH = "/auth/register"
ME_PATH = "/auth/me"
LOGOUT_PATH = "/auth/logout"

REQUEST_TIMEOUT = 20

TOKEN_DIR = Path.home() / ".galonku"
TOKEN_FILE = TOKEN_DIR / "session.json"
