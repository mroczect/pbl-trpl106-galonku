import { useEffect } from "react";
import { Loader2, X } from "lucide-react";

/* ---------- Button ---------- */
export function Button({
  variant = "primary",
  size = "md",
  loading = false,
  icon: Icon,
  children,
  className = "",
  ...props
}) {
  const variants = {
    primary: "bg-brand-600 hover:bg-brand-700 text-white",
    secondary:
      "bg-white hover:bg-stone-50 text-stone-700 border border-stone-200",
    ghost: "hover:bg-stone-100 text-stone-600",
    danger: "bg-rose-600 hover:bg-rose-700 text-white",
  };
  const sizes = {
    sm: "h-8 px-3 text-xs gap-1.5",
    md: "h-9 px-4 text-sm gap-2",
    lg: "h-11 px-5 text-sm gap-2",
  };
  return (
    <button
      {...props}
      disabled={loading || props.disabled}
      className={`inline-flex items-center justify-center font-medium rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed ${variants[variant]} ${sizes[size]} ${className}`}
    >
      {loading ? (
        <Loader2 className="w-4 h-4 animate-spin" />
      ) : Icon ? (
        <Icon className="w-4 h-4" />
      ) : null}
      {children}
    </button>
  );
}

export function Input({ label, hint, error, className = "", ...props }) {
  return (
    <div className={className}>
      {label && (
        <label className="block text-xs font-medium text-stone-700 mb-1.5">
          {label}
        </label>
      )}
      <input
        {...props}
        className={`w-full h-9 px-3 text-sm bg-white border border-stone-200 rounded-lg placeholder:text-stone-400 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10 disabled:bg-stone-50 disabled:text-stone-500 transition ${
          error ? "border-rose-400" : ""
        }`}
      />
      {hint && !error && <p className="text-xs text-stone-400 mt-1">{hint}</p>}
      {error && <p className="text-xs text-rose-600 mt-1">{error}</p>}
    </div>
  );
}

export function Select({ label, children, className = "", ...props }) {
  return (
    <div className={className}>
      {label && (
        <label className="block text-xs font-medium text-stone-700 mb-1.5">
          {label}
        </label>
      )}
      <select
        {...props}
        className="w-full h-9 px-3 text-sm bg-white border border-stone-200 rounded-lg focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10 transition"
      >
        {children}
      </select>
    </div>
  );
}

export function Card({ className = "", children }) {
  return (
    <div className={`bg-white border border-stone-200 rounded-xl ${className}`}>
      {children}
    </div>
  );
}

export function Badge({ variant = "default", children }) {
  const variants = {
    default: "bg-stone-100 text-stone-700",
    success: "bg-emerald-50 text-emerald-700",
    warning: "bg-amber-50 text-amber-700",
    danger: "bg-rose-50 text-rose-700",
    info: "bg-blue-50 text-blue-700",
    brand: "bg-brand-50 text-brand-700",
  };
  return (
    <span
      className={`inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full ${
        variants[variant] ?? variants.default
      }`}
    >
      {children}
    </span>
  );
}

export function StatusBadge({ status }) {
  const map = {
    pending: "warning",
    paid: "success",
    partial: "info",
    cancelled: "danger",
    on_route: "info",
    done: "success",
  };

  if (typeof status !== "string" || status.length === 0) {
    return <Badge variant="default">—</Badge>;
  }

  return (
    <Badge variant={map[status] || "default"}>
      {status.replace(/_/g, " ")}
    </Badge>
  );
}

export function PageHeader({ title, description, action }) {
  return (
    <div className="flex items-start justify-between mb-6">
      <div>
        <h1 className="text-xl font-semibold text-stone-900">{title}</h1>
        {description && (
          <p className="text-sm text-stone-500 mt-0.5">{description}</p>
        )}
      </div>
      {action}
    </div>
  );
}

export function EmptyState({ icon: Icon, title, description, action }) {
  return (
    <div className="text-center py-16 px-6">
      {Icon && (
        <div className="inline-flex items-center justify-center w-12 h-12 rounded-full bg-stone-100 mb-4">
          <Icon className="w-5 h-5 text-stone-400" />
        </div>
      )}
      <h3 className="text-sm font-medium text-stone-900">{title}</h3>
      {description && (
        <p className="text-sm text-stone-500 mt-1">{description}</p>
      )}
      {action && <div className="mt-4">{action}</div>}
    </div>
  );
}

export function Loading() {
  return (
    <div className="flex items-center justify-center py-16">
      <Loader2 className="w-5 h-5 text-stone-400 animate-spin" />
    </div>
  );
}

export function Modal({ open, onClose, title, children, size = "md" }) {
  useEffect(() => {
    if (!open) return;

    const onKey = (e) => {
      if (e.key === "Escape") onClose();
    };
    document.addEventListener("keydown", onKey);

    const prevOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";

    return () => {
      document.removeEventListener("keydown", onKey);
      document.body.style.overflow = prevOverflow;
    };
  }, [open, onClose]);

  if (!open) return null;

  const sizes = {
    sm: "max-w-sm",
    md: "max-w-lg",
    lg: "max-w-2xl",
    xl: "max-w-3xl",
  };

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/40 backdrop-blur-sm"
      onClick={onClose}
      role="dialog"
      aria-modal="true"
    >
      <div
        className={`bg-white rounded-xl w-full shadow-xl ${sizes[size]} max-h-[90vh] flex flex-col`}
        onClick={(e) => e.stopPropagation()}
      >
        {title && (
          <div className="px-6 py-4 border-b border-stone-200 flex items-center justify-between shrink-0">
            <h2 className="text-sm font-semibold text-stone-900">{title}</h2>
            <button
              type="button"
              onClick={onClose}
              className="p-1 text-stone-400 hover:text-stone-700 rounded-md"
              aria-label="Close"
            >
              <X className="w-4 h-4" />
            </button>
          </div>
        )}
        <div className="overflow-auto">{children}</div>
      </div>
    </div>
  );
}

export function ConfirmDialog({
  open,
  title = "Konfirmasi",
  message,
  confirmLabel = "Ya, lanjutkan",
  cancelLabel = "Batal",
  variant = "danger",
  loading = false,
  onConfirm,
  onCancel,
}) {
  return (
    <Modal open={open} onClose={onCancel} title={title} size="sm">
      <div className="p-6">
        <p className="text-sm text-stone-600">{message}</p>
        <div className="flex gap-2 mt-5">
          <Button
            variant={variant}
            className="flex-1"
            loading={loading}
            onClick={onConfirm}
          >
            {confirmLabel}
          </Button>
          <Button variant="secondary" onClick={onCancel} disabled={loading}>
            {cancelLabel}
          </Button>
        </div>
      </div>
    </Modal>
  );
}
