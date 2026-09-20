import tkinter as tk
from tkinter import messagebox, ttk


class PageHeader(ttk.Frame):

    def __init__(self, master, title: str, on_back):
        super().__init__(master)
        left = ttk.Frame(self)
        left.pack(side="left", fill="x", expand=True)
        ttk.Label(left, text=title, font=("TkDefaultFont", 14, "bold")).pack(anchor="w")
        ttk.Button(self, text="← Kembali", command=on_back).pack(side="right")


class DataTable(ttk.Frame):

    def __init__(self, master, columns, headings, on_double_click=None):
        super().__init__(master)
        self.columns = columns

        container = ttk.Frame(self)
        container.pack(fill="both", expand=True)

        self.tree = ttk.Treeview(
            container, columns=columns, show="headings", selectmode="browse"
        )
        for col, head in zip(columns, headings):
            self.tree.heading(col, text=head)
            self.tree.column(col, width=110, anchor="w")

        vsb = ttk.Scrollbar(container, orient="vertical", command=self.tree.yview)
        self.tree.configure(yscrollcommand=vsb.set)

        self.tree.pack(side="left", fill="both", expand=True)
        vsb.pack(side="right", fill="y")

        self._rows_by_id: dict[str, dict] = {}

        if on_double_click:
            self.tree.bind("<Double-1>", lambda _e: on_double_click())

    def set_rows(self, rows: list[dict], id_key: str = "id") -> None:
        self.tree.delete(*self.tree.get_children())
        self._rows_by_id.clear()
        for row in rows:
            rid = str(row.get(id_key, ""))
            values = [self._fmt(row.get(c, "")) for c in self.columns]
            self.tree.insert("", "end", iid=rid, values=values)
            self._rows_by_id[rid] = row

    def get_selected(self) -> dict | None:
        sel = self.tree.selection()
        if not sel:
            return None
        return self._rows_by_id.get(sel[0])

    @staticmethod
    def _fmt(v) -> str:
        if v is None:
            return ""
        return str(v)


class FormDialog(tk.Toplevel):

    def __init__(self, parent, title: str, fields: list, on_submit, initial=None):
        super().__init__(parent)
        self.title(title)
        self.transient(parent)
        self.resizable(False, False)

        self.fields = fields
        self.on_submit = on_submit
        self.initial = initial or {}
        self.vars: dict[str, tk.Variable] = {}
        self._error_var = tk.StringVar()

        body = ttk.Frame(self, padding=16)
        body.pack(fill="both", expand=True)

        for f in fields:
            self._add_field(body, f)

        ttk.Label(body, textvariable=self._error_var, foreground="red").pack(
            anchor="w", pady=(8, 0)
        )

        btns = ttk.Frame(body)
        btns.pack(fill="x", pady=(12, 0))
        ttk.Button(btns, text="Simpan", command=self._submit).pack(
            side="right", padx=(4, 0)
        )
        ttk.Button(btns, text="Batal", command=self.destroy).pack(side="right")

        self.update_idletasks()
        self.grab_set()
        self._center(parent)

    def _add_field(self, parent, f):
        key = f["key"]
        label = f.get("label", key)
        ftype = f.get("type", "text")

        wrap = ttk.Frame(parent)
        wrap.pack(fill="x", pady=(0, 8))
        ttk.Label(wrap, text=label).pack(anchor="w")

        initial_val = self.initial.get(key, f.get("default", ""))

        if ftype == "select":
            var = tk.StringVar(value=str(initial_val))
            opts = f.get("options", [])
            cb = ttk.Combobox(wrap, textvariable=var, values=opts, state="readonly")
            cb.pack(fill="x")
        elif ftype == "bool":
            var = tk.IntVar(value=int(initial_val or 0))
            ttk.Checkbutton(wrap, variable=var).pack(anchor="w")
        else:
            var = tk.StringVar(value=str(initial_val))
            show = "*" if f.get("password") else ""
            ttk.Entry(wrap, textvariable=var, show=show).pack(fill="x")

        self.vars[key] = var

    def _submit(self):
        data = {}
        for f in self.fields:
            key = f["key"]
            raw = self.vars[key].get()
            ftype = f.get("type", "text")

            if f.get("required") and (raw == "" or raw is None):
                self._error_var.set(f"{f.get('label', key)} wajib diisi")
                return

            if ftype == "number" and raw != "":
                try:
                    raw = float(raw) if "." in str(raw) else int(raw)
                except ValueError:
                    self._error_var.set(f"{f.get('label', key)} harus angka")
                    return

            data[key] = raw

        try:
            self.on_submit(data)
        except Exception as e:  # noqa: BLE001
            self._error_var.set(str(e))
            return

        self.destroy()

    def _center(self, parent):
        parent.update_idletasks()
        px, py = parent.winfo_rootx(), parent.winfo_rooty()
        pw, ph = parent.winfo_width(), parent.winfo_height()
        w, h = self.winfo_width(), self.winfo_height()
        x = px + (pw - w) // 2
        y = py + (ph - h) // 2
        self.geometry(f"+{max(0, x)}+{max(0, y)}")


def confirm(parent, message: str) -> bool:
    return messagebox.askyesno("Konfirmasi", message, parent=parent)


def show_error(parent, message: str) -> None:
    messagebox.showerror("Error", message, parent=parent)


def show_info(parent, message: str) -> None:
    messagebox.showinfo("Info", message, parent=parent)
