from tkinter import ttk

from command import LogoutCommand


class UserHomeFrame(ttk.Frame):
    def __init__(self, master, app):
        super().__init__(master, padding=32)
        self.app = app
        user = app.current_user or {}

        top = ttk.Frame(self)
        top.pack(fill="x", pady=(0, 16))
        ttk.Label(
            top,
            text=f"Halo, {user.get('name', 'Pengguna')}",
            font=("TkDefaultFont", 16, "bold"),
        ).pack(side="left")
        ttk.Button(top, text="Keluar", command=self._logout).pack(side="right")

        ttk.Label(
            self,
            text="Halaman ini tidak memiliki fitur untuk role Anda.",
        ).pack(anchor="w", pady=(0, 16))

        card = ttk.LabelFrame(self, text="Informasi Akun", padding=16)
        card.pack(fill="x")

        rows = [
            ("Nama", user.get("name", "-")),
            ("Email", user.get("email", "-")),
            ("Telepon", user.get("phone", "-")),
            ("Role", user.get("role_name", "-")),
        ]
        for label, value in rows:
            row = ttk.Frame(card)
            row.pack(fill="x", pady=2)
            ttk.Label(row, text=label, width=12).pack(side="left")
            ttk.Label(row, text=str(value)).pack(side="left")

    def _logout(self):
        LogoutCommand().execute()
        self.app.current_user = None
        self.app.show_login()
