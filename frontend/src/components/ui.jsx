import { Loader2, AlertTriangle } from "lucide-react";

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
    primary: "bg-brand-600 hover:bg-brand-700 text-white shadow-sm",
    secondary:
      "bg-white hover:bg-stone-50 text-stone-700 border border-stone-200 shadow-sm",
    ghost: "hover:bg-stone-100 text-stone-600",
    danger: "bg-rose-600 hover:bg-rose-700 text-white shadow-sm",
  };
  const sizes = {
    sm: "h-8 px-3 text-xs gap-1.5 rounded-lg",
    md: "h-10 px-4 text-sm gap-2 rounded-lg",
    lg: "h-11 px-5 text-sm gap-2 rounded-lg",
    icon: "h-10 w-10 rounded-lg",
  };
  return (
    <button
      {...props}
      disabled={loading || props.disabled}
      className={`inline-flex items-center justify-center font-medium transition-all duration-150 disabled:opacity-50 disabled:cursor-not-allowed ${variants[variant]} ${sizes[size]} ${className}`}
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

export function Input({
  label,
  hint,
  error,
  className = "",
  required,
  ...props
}) {
  return (
    <div className={className}>
      {label && (
        <label className="block text-xs font-medium text-stone-700 mb-1.5">
          {label} {required && <span className="text-rose-500">*</span>}
        </label>
      )}
      <input
        {...props}
        className={`w-full h-10 px-3 text-sm bg-white border rounded-lg placeholder:text-stone-400 transition-all focus:outline-none focus:ring-2 focus:ring-brand-500/15 disabled:bg-stone-50 ${
          error
            ? "border-rose-400 focus:border-rose-500"
            : "border-stone-200 focus:border-brand-500"
        }`}
      />
      {hint && !error && <p className="text-xs text-stone-400 mt-1">{hint}</p>}
      {error && (
        <p className="text-xs text-rose-600 mt-1 flex items-center gap-1">
          <AlertTriangle className="w-3 h-3" />
          {error}
        </p>
      )}
    </div>
  );
}

export function Card({ className = "", children, ...props }) {
  return (
    <div
      {...props}
      className={`bg-white border border-stone-200/80 rounded-xl shadow-sm ${className}`}
    >
      {children}
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
