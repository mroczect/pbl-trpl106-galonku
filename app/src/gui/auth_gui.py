from tkinter import ttk

from command import LoginCommand, RegisterCommand
from lib.auth_lib import ApiError
from ui.auth_ui import ErrorBanner, FormField


class _BaseFrame(ttk.Frame):
    def __init__(self, master, app):
        super().__init__(master, padding=32)
        self.app = app


class LoginFrame(_BaseFrame):
    def __init__(self, master, app):
        super().__init__(master, app)

        ttk.Label(self, text="Galonku", font=("TkDefaultFont", 18, "bold")).pack(
            anchor="w"
        )
        ttk.Label(self, text="Masuk ke akun Anda").pack(anchor="w", pady=(0, 16))

        self.error = ErrorBanner(self)

        self.email = FormField(self, "Email")
        self.email.pack(fill="x", pady=(0, 8))

        self.password = FormField(self, "Password", show="*")
        self.password.pack(fill="x", pady=(0, 8))

        self.email.entry.bind("<Return>", lambda _e: self.on_submit())
        self.password.entry.bind("<Return>", lambda _e: self.on_submit())

        self.submit = ttk.Button(self, text="Masuk", command=self.on_submit)
        self.submit.pack(fill="x", pady=(8, 8))

        bottom = ttk.Frame(self)
        bottom.pack(fill="x")
        ttk.Label(bottom, text="Belum punya akun?").pack(side="left")
        ttk.Button(bottom, text="Daftar", command=self.app.show_register).pack(
            side="left", padx=(4, 0)
        )

    def on_submit(self):
        self.error.hide()
        email = self.email.get().strip()
        password = self.password.get()

        if not email or not password:
            self.error.show("Email dan password wajib diisi.")
            return

        self.submit.config(state="disabled", text="Memproses...")
        try:
            user = LoginCommand(email, password).execute()
        except ApiError as e:
            self.error.show(e.message)
        except Exception as e:  # noqa: BLE001
            self.error.show(f"Terjadi kesalahan: {e}")
        else:
            self.app.show_home(user)
        finally:
            self.submit.config(state="normal", text="Masuk")


class RegisterFrame(_BaseFrame):
    def __init__(self, master, app):
        super().__init__(master, app)

        ttk.Label(self, text="Daftar Akun", font=("TkDefaultFont", 18, "bold")).pack(
            anchor="w"
        )
        ttk.Label(self, text="Buat akun pelanggan baru").pack(anchor="w", pady=(0, 16))

        self.error = ErrorBanner(self)

        self.name = FormField(self, "Nama Lengkap")
        self.name.pack(fill="x", pady=(0, 8))

        self.email = FormField(self, "Email")
        self.email.pack(fill="x", pady=(0, 8))

        self.password = FormField(self, "Password", show="*")
        self.password.pack(fill="x", pady=(0, 8))

        self.phone = FormField(self, "No. HP (opsional)")
        self.phone.pack(fill="x", pady=(0, 8))

        self.error.pack(fill="x")

        self.submit = ttk.Button(self, text="Buat Akun", command=self.on_submit)
        self.submit.pack(fill="x", pady=(8, 8))

        bottom = ttk.Frame(self)
        bottom.pack(fill="x")
        ttk.Label(bottom, text="Sudah punya akun?").pack(side="left")
        ttk.Button(bottom, text="Masuk", command=self.app.show_login).pack(
            side="left", padx=(4, 0)
        )

    def on_submit(self):
        self.error.hide()
        name = self.name.get().strip()
        email = self.email.get().strip()
        password = self.password.get()
        phone = self.phone.get().strip()

        if not name or not email or not password:
            self.error.show("Nama, email, dan password wajib diisi.")
            return
        if len(password) < 6:
            self.error.show("Password minimal 6 karakter.")
            return

        self.submit.config(state="disabled", text="Mendaftar...")
        try:
            RegisterCommand(name, email, password, phone).execute()
        except ApiError as e:
            if e.errors:
                msg = "; ".join(
                    f"{field}: {', '.join(msgs)}" for field, msgs in e.errors.items()
                )
                self.error.show(msg)
            else:
                self.error.show(e.message)
        except Exception as e:  # noqa: BLE001
            self.error.show(f"Terjadi kesalahan: {e}")
        else:
            self.app.show_login()
        finally:
            self.submit.config(state="normal", text="Buat Akun")
