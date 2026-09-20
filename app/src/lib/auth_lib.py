import json
from pathlib import Path

from config import auth_config as cfg
from lib.api_lib import ApiError, BaseAPI  # noqa: F401


class SessionStore:
    def __init__(self, path: Path | None = None):
        self.path = path or cfg.TOKEN_FILE

    def save(self, access, refresh, user):
        self.path.parent.mkdir(parents=True, exist_ok=True)
        payload = {
            "access_token": access,
            "refresh_token": refresh,
            "user": user,
        }
        self.path.write_text(json.dumps(payload, ensure_ascii=False), encoding="utf-8")

    def load(self) -> dict | None:
        if not self.path.exists():
            return None
        try:
            return json.loads(self.path.read_text(encoding="utf-8"))
        except OSError, json.JSONDecodeError:
            return None

    def clear(self) -> None:
        try:
            self.path.unlink()
        except FileNotFoundError:
            pass


class AuthAPI(BaseAPI):
    def login(self, email: str, password: str) -> dict:
        res = self.post(cfg.LOGIN_PATH, {"email": email, "password": password})
        return res.get("data", {})

    def register(self, name, email, password, phone=None) -> dict:
        body = {"name": name, "email": email, "password": password}
        if phone:
            body["phone"] = phone
        return self.post(cfg.REGISTER_PATH, body).get("data", {})

    def me(self, access_token: str) -> dict:
        return self.get(cfg.ME_PATH, token=access_token).get("data", {})

    def logout(self, access_token: str, refresh_token: str | None = None) -> dict:
        body = {"refresh_token": refresh_token} if refresh_token else {}
        return self.post(cfg.LOGOUT_PATH, body, token=access_token)
