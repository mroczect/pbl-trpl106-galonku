import tkinter as tk
from tkinter import ttk


class FormField(ttk.Frame):
    def __init__(self, master, label: str, show: str | None = None):
        super().__init__(master)
        ttk.Label(self, text=label).pack(anchor="w")
        self.var = tk.StringVar()
        self.entry = ttk.Entry(self, textvariable=self.var, show=show or "")
        self.entry.pack(fill="x", pady=(2, 0))

    def get(self) -> str:
        return self.var.get()

    def set(self, value: str) -> None:
        self.var.set(value)


class ErrorBanner(ttk.Frame):
    def __init__(self, master):
        super().__init__(master)
        self.var = tk.StringVar()
        self.label = ttk.Label(
            self,
            textvariable=self.var,
            foreground="red",
            wraplength=340,
            justify="left",
        )
        self.label.pack(anchor="w")

    def show(self, message: str) -> None:
        self.var.set(message)
        self.pack(fill="x", pady=(0, 8))

    def hide(self) -> None:
        self.var.set("")
        self.pack_forget()
