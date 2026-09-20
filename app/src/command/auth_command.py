from lib.auth_lib import ApiError, AuthAPI, SessionStore


class _BaseCommand:
    def __init__(self):
        self.api = AuthAPI()
        self.store = SessionStore()


class LoginCommand(_BaseCommand):
    def __init__(self, email: str, password: str):
        super().__init__()
        self.email = email.strip()
        self.password = password

    def execute(self) -> dict:
        data = self.api.login(self.email, self.password)
        user = data.get("user") or {}
        access = data.get("access_token")
        refresh = data.get("refresh_token")

        if not access or not user:
            raise ApiError("Respons login tidak lengkap")

        self.store.save(access, refresh, user)
        return user


class RegisterCommand(_BaseCommand):
    def __init__(self, name: str, email: str, password: str, phone: str | None = None):
        super().__init__()
        self.name = name.strip()
        self.email = email.strip()
        self.password = password
        self.phone = (phone or "").strip() or None

    def execute(self) -> dict:
        return self.api.register(self.name, self.email, self.password, self.phone)


class LogoutCommand(_BaseCommand):
    def execute(self) -> None:
        session = self.store.load() or {}
        access = session.get("access_token")
        refresh = session.get("refresh_token")

        try:
            if access:
                self.api.logout(access, refresh)
        except ApiError:
            pass
        finally:
            self.store.clear()


class FetchMeCommand(_BaseCommand):
    def execute(self) -> dict | None:
        session = self.store.load() or {}
        access = session.get("access_token")
        if not access:
            return None

        try:
            user = self.api.me(access)
        except ApiError:
            self.store.clear()
            return None

        self.store.save(access, session.get("refresh_token"), user)
        return user


def get_saved_user() -> dict | None:
    session = SessionStore().load() or {}
    return session.get("user")
