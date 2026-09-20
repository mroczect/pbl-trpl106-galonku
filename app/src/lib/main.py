import re

EMAIL_RE = re.compile(r"^[^@\s]+@[^@\s]+\.[^@\s]+$")
PHONE_RE = re.compile(r"^[0-9+\-\s]{8,20}$")


def is_valid_email(value: str) -> bool:
    return bool(EMAIL_RE.match((value or "").strip()))


def is_valid_phone(value: str) -> bool:
    return bool(PHONE_RE.match((value or "").strip()))


def format_rupiah(amount) -> str:
    try:
        return "Rp " + f"{int(amount):,}".replace(",", ".")
    except TypeError, ValueError:
        return "Rp 0"
