import tkinter as tk
from tkinter import ttk

from command import AdminService, LogoutCommand
from lib.api_lib import ApiError
from ui.admin_ui import (
    DataTable,
    FormDialog,
    PageHeader,
    confirm,
    show_error,
    show_info,
)


class _AdminBase(ttk.Frame):
    title = "Admin"

    def __init__(self, master, app):
        super().__init__(master, padding=16)
        self.app = app
        self.service = AdminService()
        self.user = app.current_user or {}

        PageHeader(self, self.title, on_back=self.app.show_admin_home).pack(
            fill="x", pady=(0, 12)
        )


class AdminHomeFrame(ttk.Frame):
    def __init__(self, master, app):
        super().__init__(master, padding=24)
        self.app = app
        user = app.current_user or {}

        top = ttk.Frame(self)
        top.pack(fill="x", pady=(0, 16))
        ttk.Label(
            top,
            text=f"Halo, {user.get('name', 'Admin')}",
            font=("TkDefaultFont", 16, "bold"),
        ).pack(side="left")
        ttk.Button(top, text="Keluar", command=self._logout).pack(side="right")

        ttk.Label(self, text=f"Role: {user.get('role_name', '-')}").pack(
            anchor="w", pady=(0, 16)
        )

        ttk.Separator(self).pack(fill="x", pady=(0, 16))

        ttk.Label(self, text="Menu Admin", font=("TkDefaultFont", 11, "bold")).pack(
            anchor="w", pady=(0, 8)
        )

        menu = [
            ("Produk", self.app.show_admin_products),
            ("Pelanggan", self.app.show_admin_customers),
            ("Transaksi", self.app.show_admin_transactions),
            ("Jadwal", self.app.show_admin_schedules),
            ("Pengguna", self.app.show_admin_users),
            ("Log Aktivitas", self.app.show_admin_logs),
        ]
        for label, cmd in menu:
            ttk.Button(self, text=label, command=cmd, width=30).pack(anchor="w", pady=2)

    def _logout(self):
        LogoutCommand().execute()
        self.app.current_user = None
        self.app.show_login()


class ProductsFrame(_AdminBase):
    title = "Produk"

    COLUMNS = ("id", "sku", "name", "category", "price", "stock", "is_active")
    HEADINGS = ("ID", "SKU", "Nama", "Kategori", "Harga", "Stok", "Aktif")

    def __init__(self, master, app):
        super().__init__(master, app)

        toolbar = ttk.Frame(self)
        toolbar.pack(fill="x", pady=(0, 8))
        ttk.Button(toolbar, text="Tambah", command=self.add).pack(side="left")
        ttk.Button(toolbar, text="Edit", command=self.edit).pack(
            side="left", padx=(4, 0)
        )
        ttk.Button(toolbar, text="Hapus", command=self.remove).pack(
            side="left", padx=(4, 0)
        )
        ttk.Button(toolbar, text="Refresh", command=self.reload).pack(
            side="left", padx=(4, 0)
        )

        self.table = DataTable(
            self, list(self.COLUMNS), list(self.HEADINGS), on_double_click=self.edit
        )
        self.table.pack(fill="both", expand=True)

        self.reload()

    def reload(self):
        try:
            rows = self.service.list_products()
        except ApiError as e:
            show_error(self, e.message)
            return
        self.table.set_rows(rows)

    def _fields(self):
        return [
            {"key": "sku", "label": "SKU", "required": True},
            {"key": "name", "label": "Nama", "required": True},
            {
                "key": "category",
                "label": "Kategori",
                "type": "select",
                "options": ["galon", "air", "aksesoris", "lain"],
                "default": "galon",
            },
            {"key": "price", "label": "Harga", "type": "number", "required": True},
            {"key": "stock", "label": "Stok", "type": "number", "default": 0},
        ]

    def add(self):
        def submit(data):
            self.service.create_product(data)
            self.reload()

        try:
            FormDialog(self.winfo_toplevel(), "Tambah Produk", self._fields(), submit)
        except ApiError as e:
            show_error(self, e.message)

    def edit(self):
        row = self.table.get_selected()
        if not row:
            show_info(self, "Pilih produk dulu.")
            return

        fields = self._fields() + [
            {"key": "is_active", "label": "Aktif", "type": "bool", "default": 1}
        ]

        def submit(data):
            payload = dict(data)
            payload.pop("sku", None)
            self.service.update_product(row["id"], payload)
            self.reload()

        FormDialog(
            self.winfo_toplevel(),
            f"Edit Produk #{row['id']}",
            fields,
            submit,
            initial=row,
        )

    def remove(self):
        row = self.table.get_selected()
        if not row:
            show_info(self, "Pilih produk dulu.")
            return
        if not confirm(self, f"Nonaktifkan produk {row.get('name')}?"):
            return
        try:
            self.service.delete_product(row["id"])
        except ApiError as e:
            show_error(self, e.message)
            return
        self.reload()


class CustomersFrame(_AdminBase):
    title = "Pelanggan"

    COLUMNS = ("id", "name", "phone", "address", "is_active")
    HEADINGS = ("ID", "Nama", "Telepon", "Alamat", "Aktif")

    def __init__(self, master, app):
        super().__init__(master, app)

        toolbar = ttk.Frame(self)
        toolbar.pack(fill="x", pady=(0, 8))
        ttk.Button(toolbar, text="Tambah", command=self.add).pack(side="left")
        ttk.Button(toolbar, text="Edit", command=self.edit).pack(
            side="left", padx=(4, 0)
        )
        ttk.Button(toolbar, text="Hapus", command=self.remove).pack(
            side="left", padx=(4, 0)
        )
        ttk.Button(toolbar, text="Refresh", command=self.reload).pack(
            side="left", padx=(4, 0)
        )

        self.table = DataTable(
            self, list(self.COLUMNS), list(self.HEADINGS), on_double_click=self.edit
        )
        self.table.pack(fill="both", expand=True)

        self.reload()

    def reload(self):
        try:
            rows = self.service.list_customers()
        except ApiError as e:
            show_error(self, e.message)
            return
        self.table.set_rows(rows)

    def _fields(self):
        return [
            {"key": "name", "label": "Nama", "required": True},
            {"key": "phone", "label": "Telepon", "required": True},
            {"key": "address", "label": "Alamat"},
            {"key": "notes", "label": "Catatan"},
        ]

    def add(self):
        def submit(data):
            self.service.create_customer(data)
            self.reload()

        FormDialog(self.winfo_toplevel(), "Tambah Pelanggan", self._fields(), submit)

    def edit(self):
        row = self.table.get_selected()
        if not row:
            show_info(self, "Pilih pelanggan dulu.")
            return

        fields = self._fields() + [
            {"key": "is_active", "label": "Aktif", "type": "bool", "default": 1}
        ]

        def submit(data):
            self.service.update_customer(row["id"], data)
            self.reload()

        FormDialog(
            self.winfo_toplevel(),
            f"Edit Pelanggan #{row['id']}",
            fields,
            submit,
            initial=row,
        )

    def remove(self):
        row = self.table.get_selected()
        if not row:
            show_info(self, "Pilih pelanggan dulu.")
            return
        if not confirm(self, f"Nonaktifkan pelanggan {row.get('name')}?"):
            return
        try:
            self.service.delete_customer(row["id"])
        except ApiError as e:
            show_error(self, e.message)
            return
        self.reload()


class TransactionsFrame(_AdminBase):
    title = "Transaksi"

    COLUMNS = (
        "id",
        "invoice_no",
        "customer_name",
        "user_name",
        "total_amount",
        "paid_amount",
        "status",
        "created_at",
    )
    HEADINGS = (
        "ID",
        "Invoice",
        "Pelanggan",
        "Kasir",
        "Total",
        "Bayar",
        "Status",
        "Tanggal",
    )

    def __init__(self, master, app):
        super().__init__(master, app)

        toolbar = ttk.Frame(self)
        toolbar.pack(fill="x", pady=(0, 8))
        ttk.Button(toolbar, text="Transaksi Baru", command=self.add).pack(side="left")
        ttk.Button(toolbar, text="Ubah Status", command=self.change_status).pack(
            side="left", padx=(4, 0)
        )
        ttk.Button(toolbar, text="Refresh", command=self.reload).pack(
            side="left", padx=(4, 0)
        )

        self.table = DataTable(self, list(self.COLUMNS), list(self.HEADINGS))
        self.table.pack(fill="both", expand=True)

        self.reload()

    def reload(self):
        try:
            rows = self.service.list_transactions()
        except ApiError as e:
            show_error(self, e.message)
            return
        self.table.set_rows(rows)

    def add(self):
        try:
            customers = self.service.list_customers()
            products = self.service.list_products()
        except ApiError as e:
            show_error(self, e.message)
            return

        win = tk.Toplevel(self.winfo_toplevel())
        win.title("Transaksi Baru")
        win.transient(self.winfo_toplevel())
        win.grab_set()

        body = ttk.Frame(win, padding=16)
        body.pack(fill="both", expand=True)

        cust_map = {f"{c['id']} - {c['name']}": c["id"] for c in customers}
        prod_map = {
            f"{p['id']} - {p['name']} (Rp {p['price']})": (p["id"], float(p["price"]))
            for p in products
        }

        ttk.Label(body, text="Pelanggan").pack(anchor="w")
        cust_var = tk.StringVar()
        ttk.Combobox(
            body,
            textvariable=cust_var,
            values=list(cust_map),
            state="readonly",
            width=40,
        ).pack(fill="x", pady=(2, 8))

        ttk.Label(body, text="Produk").pack(anchor="w")
        prod_var = tk.StringVar()
        ttk.Combobox(
            body,
            textvariable=prod_var,
            values=list(prod_map),
            state="readonly",
            width=40,
        ).pack(fill="x", pady=(2, 8))

        ttk.Label(body, text="Qty").pack(anchor="w")
        qty_var = tk.StringVar(value="1")
        ttk.Entry(body, textvariable=qty_var).pack(fill="x", pady=(2, 8))

        ttk.Label(body, text="Status").pack(anchor="w")
        status_var = tk.StringVar(value="pending")
        ttk.Combobox(
            body,
            textvariable=status_var,
            values=["pending", "paid", "partial", "cancelled"],
            state="readonly",
        ).pack(fill="x", pady=(2, 8))

        ttk.Label(body, text="Bayar (Rp)").pack(anchor="w")
        paid_var = tk.StringVar(value="0")
        ttk.Entry(body, textvariable=paid_var).pack(fill="x", pady=(2, 8))

        err = tk.StringVar()
        ttk.Label(body, textvariable=err, foreground="red").pack(anchor="w")

        def submit():
            try:
                if not cust_var.get() or not prod_var.get():
                    err.set("Pelanggan dan produk wajib dipilih")
                    return
                cid = cust_map[cust_var.get()]
                pid, _price = prod_map[prod_var.get()]
                qty = int(qty_var.get())
                if qty < 1:
                    err.set("Qty minimal 1")
                    return

                payload = {
                    "customer_id": cid,
                    "type": "sale",
                    "status": status_var.get(),
                    "paid_amount": float(paid_var.get() or 0),
                    "items": [{"product_id": pid, "qty": qty}],
                }
                self.service.create_transaction(payload)
            except ApiError as e:
                err.set(e.message)
                return
            except Exception as e:  # noqa: BLE001
                err.set(str(e))
                return

            win.destroy()
            self.reload()

        btns = ttk.Frame(body)
        btns.pack(fill="x", pady=(12, 0))
        ttk.Button(btns, text="Simpan", command=submit).pack(side="right")
        ttk.Button(btns, text="Batal", command=win.destroy).pack(
            side="right", padx=(0, 4)
        )

    def change_status(self):
        row = self.table.get_selected()
        if not row:
            show_info(self, "Pilih transaksi dulu.")
            return

        win = tk.Toplevel(self.winfo_toplevel())
        win.title(f"Ubah Status #{row['id']}")
        win.transient(self.winfo_toplevel())
        win.grab_set()

        body = ttk.Frame(win, padding=16)
        body.pack(fill="both", expand=True)

        ttk.Label(body, text="Status").pack(anchor="w")
        status_var = tk.StringVar(value=row.get("status", "pending"))
        ttk.Combobox(
            body,
            textvariable=status_var,
            values=["pending", "paid", "partial", "cancelled"],
            state="readonly",
        ).pack(fill="x", pady=(2, 8))

        ttk.Label(body, text="Bayar (Rp)").pack(anchor="w")
        paid_var = tk.StringVar(value=str(row.get("paid_amount", 0)))
        ttk.Entry(body, textvariable=paid_var).pack(fill="x", pady=(2, 8))

        err = tk.StringVar()
        ttk.Label(body, textvariable=err, foreground="red").pack(anchor="w")

        def submit():
            try:
                payload = {
                    "status": status_var.get(),
                    "paid_amount": float(paid_var.get() or 0),
                }
                self.service.update_transaction_status(row["id"], payload)
            except ApiError as e:
                err.set(e.message)
                return
            win.destroy()
            self.reload()

        btns = ttk.Frame(body)
        btns.pack(fill="x", pady=(12, 0))
        ttk.Button(btns, text="Simpan", command=submit).pack(side="right")
        ttk.Button(btns, text="Batal", command=win.destroy).pack(
            side="right", padx=(0, 4)
        )


class SchedulesFrame(_AdminBase):
    title = "Jadwal"

    COLUMNS = ("id", "customer_name", "user_name", "scheduled_at", "status")
    HEADINGS = ("ID", "Pelanggan", "Petugas", "Waktu", "Status")

    def __init__(self, master, app):
        super().__init__(master, app)

        toolbar = ttk.Frame(self)
        toolbar.pack(fill="x", pady=(0, 8))
        ttk.Button(toolbar, text="Jadwal Baru", command=self.add).pack(side="left")
        ttk.Button(toolbar, text="Ubah Status", command=self.change_status).pack(
            side="left", padx=(4, 0)
        )
        ttk.Button(toolbar, text="Refresh", command=self.reload).pack(
            side="left", padx=(4, 0)
        )

        self.table = DataTable(self, list(self.COLUMNS), list(self.HEADINGS))
        self.table.pack(fill="both", expand=True)

        self.reload()

    def reload(self):
        try:
            rows = self.service.list_schedules()
        except ApiError as e:
            show_error(self, e.message)
            return
        self.table.set_rows(rows)

    def add(self):
        try:
            customers = self.service.list_customers()
            users = self.service.list_users()
        except ApiError as e:
            show_error(self, e.message)
            return

        win = tk.Toplevel(self.winfo_toplevel())
        win.title("Jadwal Baru")
        win.transient(self.winfo_toplevel())
        win.grab_set()

        body = ttk.Frame(win, padding=16)
        body.pack(fill="both", expand=True)

        cust_map = {f"{c['id']} - {c['name']}": c["id"] for c in customers}
        user_map = {
            f"{u['id']} - {u['name']} ({u['role_name']})": u["id"] for u in users
        }

        ttk.Label(body, text="Pelanggan").pack(anchor="w")
        cust_var = tk.StringVar()
        ttk.Combobox(
            body,
            textvariable=cust_var,
            values=list(cust_map),
            state="readonly",
            width=40,
        ).pack(fill="x", pady=(2, 8))

        ttk.Label(body, text="Petugas").pack(anchor="w")
        user_var = tk.StringVar()
        ttk.Combobox(
            body,
            textvariable=user_var,
            values=list(user_map),
            state="readonly",
            width=40,
        ).pack(fill="x", pady=(2, 8))

        ttk.Label(body, text="Waktu (YYYY-MM-DD HH:MM:SS)").pack(anchor="w")
        when_var = tk.StringVar()
        ttk.Entry(body, textvariable=when_var).pack(fill="x", pady=(2, 8))

        ttk.Label(body, text="Catatan").pack(anchor="w")
        notes_var = tk.StringVar()
        ttk.Entry(body, textvariable=notes_var).pack(fill="x", pady=(2, 8))

        err = tk.StringVar()
        ttk.Label(body, textvariable=err, foreground="red").pack(anchor="w")

        def submit():
            try:
                if not cust_var.get() or not user_var.get() or not when_var.get():
                    err.set("Pelanggan, petugas, dan waktu wajib diisi")
                    return
                payload = {
                    "customer_id": cust_map[cust_var.get()],
                    "user_id": user_map[user_var.get()],
                    "scheduled_at": when_var.get().strip(),
                    "notes": notes_var.get().strip() or None,
                }
                self.service.create_schedule(payload)
            except ApiError as e:
                err.set(e.message)
                return
            win.destroy()
            self.reload()

        btns = ttk.Frame(body)
        btns.pack(fill="x", pady=(12, 0))
        ttk.Button(btns, text="Simpan", command=submit).pack(side="right")
        ttk.Button(btns, text="Batal", command=win.destroy).pack(
            side="right", padx=(0, 4)
        )

    def change_status(self):
        row = self.table.get_selected()
        if not row:
            show_info(self, "Pilih jadwal dulu.")
            return

        win = tk.Toplevel(self.winfo_toplevel())
        win.title(f"Ubah Status #{row['id']}")
        win.transient(self.winfo_toplevel())
        win.grab_set()

        body = ttk.Frame(win, padding=16)
        body.pack(fill="both", expand=True)

        ttk.Label(body, text="Status").pack(anchor="w")
        status_var = tk.StringVar(value=row.get("status", "pending"))
        ttk.Combobox(
            body,
            textvariable=status_var,
            values=["pending", "on_route", "done", "cancelled"],
            state="readonly",
        ).pack(fill="x", pady=(2, 8))

        err = tk.StringVar()
        ttk.Label(body, textvariable=err, foreground="red").pack(anchor="w")

        def submit():
            try:
                self.service.update_schedule_status(
                    row["id"], {"status": status_var.get()}
                )
            except ApiError as e:
                err.set(e.message)
                return
            win.destroy()
            self.reload()

        btns = ttk.Frame(body)
        btns.pack(fill="x", pady=(12, 0))
        ttk.Button(btns, text="Simpan", command=submit).pack(side="right")
        ttk.Button(btns, text="Batal", command=win.destroy).pack(
            side="right", padx=(0, 4)
        )


class UsersFrame(_AdminBase):
    title = "Pengguna"

    COLUMNS = ("id", "name", "email", "role_name", "is_active")
    HEADINGS = ("ID", "Nama", "Email", "Role", "Aktif")

    def __init__(self, master, app):
        super().__init__(master, app)

        toolbar = ttk.Frame(self)
        toolbar.pack(fill="x", pady=(0, 8))
        ttk.Button(toolbar, text="Edit", command=self.edit).pack(side="left")
        ttk.Button(toolbar, text="Refresh", command=self.reload).pack(
            side="left", padx=(4, 0)
        )

        self.table = DataTable(
            self, list(self.COLUMNS), list(self.HEADINGS), on_double_click=self.edit
        )
        self.table.pack(fill="both", expand=True)

        self.reload()

    def reload(self):
        try:
            rows = self.service.list_users()
        except ApiError as e:
            show_error(self, e.message)
            return
        self.table.set_rows(rows)

    def edit(self):
        row = self.table.get_selected()
        if not row:
            show_info(self, "Pilih pengguna dulu.")
            return

        try:
            roles = self.service.list_roles()
        except ApiError as e:
            show_error(self, e.message)
            return

        role_names = [r["name"] for r in roles]
        role_map = {r["name"]: r["id"] for r in roles}

        fields = [
            {"key": "name", "label": "Nama", "required": True},
            {"key": "phone", "label": "Telepon"},
            {
                "key": "role_name",
                "label": "Role",
                "type": "select",
                "options": role_names,
            },
            {"key": "is_active", "label": "Aktif", "type": "bool", "default": 1},
        ]

        def submit(data):
            payload = {
                "name": data["name"],
                "phone": data.get("phone") or None,
                "role_id": role_map.get(data["role_name"]),
                "is_active": int(data.get("is_active", 0)),
            }
            self.service.update_user(row["id"], payload)
            self.reload()

        FormDialog(
            self.winfo_toplevel(),
            f"Edit Pengguna #{row['id']}",
            fields,
            submit,
            initial=row,
        )


class LogsFrame(_AdminBase):
    title = "Log Aktivitas"

    COLUMNS = ("id", "created_at", "user_name", "action", "entity", "ip_address")
    HEADINGS = ("ID", "Waktu", "User", "Aksi", "Entity", "IP")

    def __init__(self, master, app):
        super().__init__(master, app)

        toolbar = ttk.Frame(self)
        toolbar.pack(fill="x", pady=(0, 8))
        ttk.Button(toolbar, text="Refresh", command=self.reload).pack(side="left")

        self.table = DataTable(self, list(self.COLUMNS), list(self.HEADINGS))
        self.table.pack(fill="both", expand=True)

        self.reload()

    def reload(self):
        try:
            rows = self.service.list_logs(limit=200)
        except ApiError as e:
            show_error(self, e.message)
            return
        self.table.set_rows(rows)
