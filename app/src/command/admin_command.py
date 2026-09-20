from lib.admin_lib import AdminAPI
from lib.auth_lib import SessionStore


class AdminService:
    def __init__(self):
        self.api = AdminAPI()
        self.session = SessionStore()

    def _token(self) -> str:
        data = self.session.load() or {}
        token = data.get("access_token")
        if not token:
            raise RuntimeError("Anda belum login")
        return token

    def list_products(self, **kw):
        return self.api.list_products(self._token(), **kw)

    def create_product(self, payload):
        return self.api.create_product(self._token(), payload)

    def update_product(self, pid, payload):
        return self.api.update_product(self._token(), pid, payload)

    def delete_product(self, pid):
        return self.api.delete_product(self._token(), pid)

    def list_customers(self, **kw):
        return self.api.list_customers(self._token(), **kw)

    def create_customer(self, payload):
        return self.api.create_customer(self._token(), payload)

    def update_customer(self, cid, payload):
        return self.api.update_customer(self._token(), cid, payload)

    def delete_customer(self, cid):
        return self.api.delete_customer(self._token(), cid)

    def list_users(self, **kw):
        return self.api.list_users(self._token(), **kw)

    def update_user(self, uid, payload):
        return self.api.update_user(self._token(), uid, payload)

    def list_roles(self):
        return self.api.list_roles(self._token())

    def list_transactions(self, **kw):
        return self.api.list_transactions(self._token(), **kw)

    def create_transaction(self, payload):
        return self.api.create_transaction(self._token(), payload)

    def update_transaction_status(self, tid, payload):
        return self.api.update_transaction_status(self._token(), tid, payload)

    def list_schedules(self, **kw):
        return self.api.list_schedules(self._token(), **kw)

    def create_schedule(self, payload):
        return self.api.create_schedule(self._token(), payload)

    def update_schedule_status(self, sid, payload):
        return self.api.update_schedule_status(self._token(), sid, payload)

    def list_logs(self, limit=200):
        return self.api.list_logs(self._token(), limit)
