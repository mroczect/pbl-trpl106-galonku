from lib.api_lib import BaseAPI


class AdminAPI(BaseAPI):
    # ---------- Products ----------
    def list_products(self, token, page=1, per_page=200):
        return self.get(
            "/products", token=token, params={"page": page, "per_page": per_page}
        ).get("data", [])

    def create_product(self, token, payload):
        return self.post("/products", body=payload, token=token)

    def update_product(self, token, pid, payload):
        return self.put(f"/products/{pid}", body=payload, token=token)

    def delete_product(self, token, pid):
        return self.delete(f"/products/{pid}", token=token)

    def list_customers(self, token, page=1, per_page=200):
        return self.get(
            "/customers", token=token, params={"page": page, "per_page": per_page}
        ).get("data", [])

    def create_customer(self, token, payload):
        return self.post("/customers", body=payload, token=token)

    def update_customer(self, token, cid, payload):
        return self.put(f"/customers/{cid}", body=payload, token=token)

    def delete_customer(self, token, cid):
        return self.delete(f"/customers/{cid}", token=token)

    def list_users(self, token, page=1, per_page=200):
        return self.get(
            "/users", token=token, params={"page": page, "per_page": per_page}
        ).get("data", [])

    def update_user(self, token, uid, payload):
        return self.put(f"/users/{uid}", body=payload, token=token)

    def delete_user(self, token, uid):
        return self.delete(f"/users/{uid}", token=token)

    def list_roles(self, token):
        return self.get("/roles", token=token).get("data", [])

    def list_transactions(self, token, page=1, per_page=200):
        return self.get(
            "/transactions",
            token=token,
            params={"page": page, "per_page": per_page},
        ).get("data", [])

    def create_transaction(self, token, payload):
        return self.post("/transactions", body=payload, token=token)

    def update_transaction_status(self, token, tid, payload):
        return self.put(f"/transactions/{tid}/status", body=payload, token=token)

    def list_schedules(self, token, page=1, per_page=200):
        return self.get(
            "/schedules", token=token, params={"page": page, "per_page": per_page}
        ).get("data", [])

    def create_schedule(self, token, payload):
        return self.post("/schedules", body=payload, token=token)

    def update_schedule_status(self, token, sid, payload):
        return self.put(f"/schedules/{sid}/status", body=payload, token=token)

    def list_logs(self, token, limit=200):
        return self.get("/logs", token=token, params={"limit": limit}).get("data", [])
