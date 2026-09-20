import sys
import tkinter as tk
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))

from command import FetchMeCommand, get_saved_user
from config.main import (
    APP_NAME,
    WINDOW_HEIGHT,
    WINDOW_MIN_HEIGHT,
    WINDOW_MIN_WIDTH,
    WINDOW_WIDTH,
)
from gui.admin_gui import (
    AdminHomeFrame,
    CustomersFrame,
    LogsFrame,
    ProductsFrame,
    SchedulesFrame,
    TransactionsFrame,
    UsersFrame,
)
from gui.auth_gui import LoginFrame, RegisterFrame
from gui.user_gui import UserHomeFrame
from ui.main import center_window, setup_theme


class GalonkuApp:
    def __init__(self):
        self.root = tk.Tk()
        self.root.title(APP_NAME)
        self.root.minsize(WINDOW_MIN_WIDTH, WINDOW_MIN_HEIGHT)
        setup_theme(self.root)
        center_window(self.root, WINDOW_WIDTH, WINDOW_HEIGHT)

        self.container = tk.Frame(self.root)
        self.container.pack(fill="both", expand=True)

        self.current: tk.Frame | None = None
        self.current_user: dict | None = None
        self._bootstrap()


    def _bootstrap(self):
        user = get_saved_user()
        if user:
            user = FetchMeCommand().execute() or None

        if user:
            self.show_home(user)
        else:
            self.show_login()

    def run(self):
        self.root.mainloop()


    def _swap(self, factory):
        if self.current is not None:
            self.current.destroy()
        self.current = factory(self.container, self)
        self.current.pack(fill="both", expand=True)


    def show_login(self):
        self.current_user = None
        self._swap(LoginFrame)

    def show_register(self):
        self._swap(RegisterFrame)

    def show_home(self, user: dict):
        self.current_user = user
        role = user.get("role_name") or user.get("role")
        if role == "administrator":
            self.show_admin_home()
        else:
            self._swap(UserHomeFrame)


    def show_admin_home(self):
        self._swap(AdminHomeFrame)

    def show_admin_products(self):
        self._swap(ProductsFrame)

    def show_admin_customers(self):
        self._swap(CustomersFrame)

    def show_admin_transactions(self):
        self._swap(TransactionsFrame)

    def show_admin_schedules(self):
        self._swap(SchedulesFrame)

    def show_admin_users(self):
        self._swap(UsersFrame)

    def show_admin_logs(self):
        self._swap(LogsFrame)


def main():
    GalonkuApp().run()


if __name__ == "__main__":
    main()
