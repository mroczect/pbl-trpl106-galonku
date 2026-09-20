use clap::Parser;
use gci::cli::*;

fn parse(args: &[&str]) -> Cli {
    Cli::try_parse_from(args).expect("parse failed")
}

#[test]
fn default_base_url() {
    assert_eq!(
        parse(&["gci", "me"]).base_url,
        "http://localhost:8000/api/v1"
    );
}

#[test]
fn override_base_url() {
    assert_eq!(
        parse(&["gci", "--base-url", "http://x", "me"]).base_url,
        "http://x"
    );
}

#[test]
fn json_flag_default_false() {
    assert!(!parse(&["gci", "me"]).json);
}

#[test]
fn json_flag_on() {
    assert!(parse(&["gci", "--json", "me"]).json);
}

#[test]
fn parse_login() {
    let cli = parse(&["gci", "login", "-e", "a@b.com", "-p", "pw"]);
    match cli.command {
        Command::Login { email, password } => {
            assert_eq!(email.as_deref(), Some("a@b.com"));
            assert_eq!(password.as_deref(), Some("pw"));
        }
        _ => panic!("expected Login"),
    }
}

#[test]
fn parse_login_no_args() {
    let cli = parse(&["gci", "login"]);
    match cli.command {
        Command::Login { email, password } => {
            assert!(email.is_none());
            assert!(password.is_none());
        }
        _ => panic!("expected Login"),
    }
}

#[test]
fn parse_register() {
    let cli = parse(&["gci", "register", "-n", "Bud", "-e", "b@c.com", "-p", "pw"]);
    match cli.command {
        Command::Register {
            name,
            email,
            password,
            phone,
        } => {
            assert_eq!(name.as_deref(), Some("Bud"));
            assert_eq!(email.as_deref(), Some("b@c.com"));
            assert_eq!(password.as_deref(), Some("pw"));
            assert!(phone.is_none());
        }
        _ => panic!("expected Register"),
    }
}

#[test]
fn parse_logout_me_refresh() {
    assert!(matches!(parse(&["gci", "logout"]).command, Command::Logout));
    assert!(matches!(parse(&["gci", "me"]).command, Command::Me));
    assert!(matches!(
        parse(&["gci", "refresh"]).command,
        Command::Refresh
    ));
}

#[test]
fn parse_products_list() {
    let cli = parse(&["gci", "products", "list", "--category", "galon"]);
    match cli.command {
        Command::Products(ProductCmd::List { category, .. }) => {
            assert_eq!(category.as_deref(), Some("galon"));
        }
        _ => panic!(),
    }
}

#[test]
fn parse_products_get() {
    let cli = parse(&["gci", "products", "get", "42"]);
    match cli.command {
        Command::Products(ProductCmd::Get { id }) => assert_eq!(id, 42),
        _ => panic!(),
    }
}

#[test]
fn parse_products_low_stock_default() {
    let cli = parse(&["gci", "products", "low-stock"]);
    match cli.command {
        Command::Products(ProductCmd::LowStock { threshold }) => assert_eq!(threshold, 10),
        _ => panic!(),
    }
}

#[test]
fn parse_products_low_stock_custom() {
    let cli = parse(&["gci", "products", "low-stock", "--threshold", "25"]);
    match cli.command {
        Command::Products(ProductCmd::LowStock { threshold }) => assert_eq!(threshold, 25),
        _ => panic!(),
    }
}

#[test]
fn parse_products_create() {
    let cli = parse(&[
        "gci",
        "products",
        "create",
        "--sku",
        "X-1",
        "--name",
        "Produk",
        "--price",
        "20000",
        "--category",
        "galon",
        "--stock",
        "5",
    ]);
    match cli.command {
        Command::Products(ProductCmd::Create {
            sku,
            name,
            price,
            category,
            stock,
        }) => {
            assert_eq!(sku.as_deref(), Some("X-1"));
            assert_eq!(name.as_deref(), Some("Produk"));
            assert_eq!(price, Some(20000.0));
            assert_eq!(category.as_deref(), Some("galon"));
            assert_eq!(stock, Some(5));
        }
        _ => panic!(),
    }
}

#[test]
fn parse_products_update() {
    let cli = parse(&["gci", "products", "update", "1", "--price", "25000"]);
    match cli.command {
        Command::Products(ProductCmd::Update { id, price, .. }) => {
            assert_eq!(id, 1);
            assert_eq!(price, Some(25000.0));
        }
        _ => panic!(),
    }
}

#[test]
fn parse_products_delete() {
    let cli = parse(&["gci", "products", "delete", "9"]);
    match cli.command {
        Command::Products(ProductCmd::Delete { id }) => assert_eq!(id, 9),
        _ => panic!(),
    }
}

#[test]
fn parse_stock_list_all() {
    let cli = parse(&[
        "gci",
        "stock",
        "list",
        "--product-id",
        "1",
        "--type",
        "out",
        "--user-id",
        "2",
        "--from",
        "2026-01-01",
        "--to",
        "2026-12-31",
        "--page",
        "2",
        "--per-page",
        "50",
    ]);
    match cli.command {
        Command::Stock(StockCmd::List {
            product_id,
            type_,
            user_id,
            from,
            to,
            page,
            per_page,
        }) => {
            assert_eq!(product_id, Some(1));
            assert_eq!(type_.as_deref(), Some("out"));
            assert_eq!(user_id, Some(2));
            assert_eq!(from.as_deref(), Some("2026-01-01"));
            assert_eq!(to.as_deref(), Some("2026-12-31"));
            assert_eq!(page, Some(2));
            assert_eq!(per_page, Some(50));
        }
        _ => panic!(),
    }
}

#[test]
fn parse_stock_adjust() {
    let cli = parse(&[
        "gci",
        "stock",
        "adjust",
        "--product-id",
        "1",
        "--new-stock",
        "45",
        "--reason",
        "opname",
    ]);
    match cli.command {
        Command::Stock(StockCmd::Adjust {
            product_id,
            new_stock,
            reason,
            notes,
        }) => {
            assert_eq!(product_id, Some(1));
            assert_eq!(new_stock, Some(45));
            assert_eq!(reason.as_deref(), Some("opname"));
            assert!(notes.is_none());
        }
        _ => panic!(),
    }
}

#[test]
fn parse_customers_list() {
    let cli = parse(&[
        "gci",
        "customers",
        "list",
        "--page",
        "1",
        "--per-page",
        "20",
    ]);
    match cli.command {
        Command::Customers(CustomerCmd::List { page, per_page }) => {
            assert_eq!(page, Some(1));
            assert_eq!(per_page, Some(20));
        }
        _ => panic!(),
    }
}

#[test]
fn parse_customers_get() {
    let cli = parse(&["gci", "customers", "get", "3"]);
    match cli.command {
        Command::Customers(CustomerCmd::Get { id }) => assert_eq!(id, 3),
        _ => panic!(),
    }
}

#[test]
fn parse_customers_create() {
    let cli = parse(&[
        "gci",
        "customers",
        "create",
        "--name",
        "Ibu",
        "--phone",
        "0800",
        "--address",
        "Jl.",
        "--notes",
        "cat",
    ]);
    match cli.command {
        Command::Customers(CustomerCmd::Create {
            name,
            phone,
            address,
            notes,
        }) => {
            assert_eq!(name.as_deref(), Some("Ibu"));
            assert_eq!(phone.as_deref(), Some("0800"));
            assert_eq!(address.as_deref(), Some("Jl."));
            assert_eq!(notes.as_deref(), Some("cat"));
        }
        _ => panic!(),
    }
}

#[test]
fn parse_customers_update() {
    let cli = parse(&[
        "gci",
        "customers",
        "update",
        "1",
        "--name",
        "New",
        "--is-active",
        "true",
    ]);
    match cli.command {
        Command::Customers(CustomerCmd::Update {
            id,
            name,
            is_active,
            ..
        }) => {
            assert_eq!(id, 1);
            assert_eq!(name.as_deref(), Some("New"));
            assert_eq!(is_active, Some(true));
        }
        _ => panic!(),
    }
}

#[test]
fn parse_customers_delete() {
    let cli = parse(&["gci", "customers", "delete", "7"]);
    match cli.command {
        Command::Customers(CustomerCmd::Delete { id }) => assert_eq!(id, 7),
        _ => panic!(),
    }
}

#[test]
fn parse_transactions_list() {
    let cli = parse(&[
        "gci",
        "transactions",
        "list",
        "--status",
        "paid",
        "--customer-id",
        "1",
        "--user-id",
        "2",
        "--type",
        "sale",
    ]);
    match cli.command {
        Command::Transactions(TransactionCmd::List {
            status,
            customer_id,
            user_id,
            type_,
            ..
        }) => {
            assert_eq!(status.as_deref(), Some("paid"));
            assert_eq!(customer_id, Some(1));
            assert_eq!(user_id, Some(2));
            assert_eq!(type_.as_deref(), Some("sale"));
        }
        _ => panic!(),
    }
}

#[test]
fn parse_transactions_get() {
    let cli = parse(&["gci", "transactions", "get", "5"]);
    match cli.command {
        Command::Transactions(TransactionCmd::Get { id }) => assert_eq!(id, 5),
        _ => panic!(),
    }
}

#[test]
fn parse_transactions_create_with_items() {
    let cli = parse(&[
        "gci",
        "transactions",
        "create",
        "--customer-id",
        "1",
        "--item",
        "1:2",
        "--item",
        "3:1",
    ]);
    match cli.command {
        Command::Transactions(TransactionCmd::Create {
            customer_id, items, ..
        }) => {
            assert_eq!(customer_id, Some(1));
            assert_eq!(items, vec!["1:2".to_string(), "3:1".to_string()]);
        }
        _ => panic!(),
    }
}

#[test]
fn parse_transactions_update_status() {
    let cli = parse(&[
        "gci",
        "transactions",
        "update-status",
        "5",
        "--status",
        "paid",
        "--paid-amount",
        "26000",
    ]);
    match cli.command {
        Command::Transactions(TransactionCmd::UpdateStatus {
            id,
            status,
            paid_amount,
        }) => {
            assert_eq!(id, 5);
            assert_eq!(status.as_deref(), Some("paid"));
            assert_eq!(paid_amount, Some(26000.0));
        }
        _ => panic!(),
    }
}

#[test]
fn parse_schedules_list() {
    let cli = parse(&[
        "gci",
        "schedules",
        "list",
        "--status",
        "pending",
        "--user-id",
        "1",
        "--customer-id",
        "2",
    ]);
    match cli.command {
        Command::Schedules(ScheduleCmd::List {
            status,
            user_id,
            customer_id,
            ..
        }) => {
            assert_eq!(status.as_deref(), Some("pending"));
            assert_eq!(user_id, Some(1));
            assert_eq!(customer_id, Some(2));
        }
        _ => panic!(),
    }
}

#[test]
fn parse_schedules_get() {
    let cli = parse(&["gci", "schedules", "get", "11"]);
    match cli.command {
        Command::Schedules(ScheduleCmd::Get { id }) => assert_eq!(id, 11),
        _ => panic!(),
    }
}

#[test]
fn parse_schedules_create() {
    let cli = parse(&[
        "gci",
        "schedules",
        "create",
        "--customer-id",
        "1",
        "--user-id",
        "2",
        "--scheduled-at",
        "2026-09-22 10:00:00",
    ]);
    match cli.command {
        Command::Schedules(ScheduleCmd::Create {
            customer_id,
            user_id,
            scheduled_at,
            ..
        }) => {
            assert_eq!(customer_id, Some(1));
            assert_eq!(user_id, Some(2));
            assert_eq!(scheduled_at.as_deref(), Some("2026-09-22 10:00:00"));
        }
        _ => panic!(),
    }
}

#[test]
fn parse_schedules_update_status() {
    let cli = parse(&["gci", "schedules", "update-status", "3", "--status", "done"]);
    match cli.command {
        Command::Schedules(ScheduleCmd::UpdateStatus { id, status }) => {
            assert_eq!(id, 3);
            assert_eq!(status.as_deref(), Some("done"));
        }
        _ => panic!(),
    }
}

#[test]
fn parse_users_list() {
    let cli = parse(&["gci", "users", "list", "--page", "1"]);
    match cli.command {
        Command::Users(UserCmd::List { page, .. }) => assert_eq!(page, Some(1)),
        _ => panic!(),
    }
}

#[test]
fn parse_users_get() {
    let cli = parse(&["gci", "users", "get", "2"]);
    match cli.command {
        Command::Users(UserCmd::Get { id }) => assert_eq!(id, 2),
        _ => panic!(),
    }
}

#[test]
fn parse_users_update() {
    let cli = parse(&[
        "gci",
        "users",
        "update",
        "2",
        "--role-id",
        "3",
        "--is-active",
        "true",
    ]);
    match cli.command {
        Command::Users(UserCmd::Update {
            id,
            role_id,
            is_active,
            ..
        }) => {
            assert_eq!(id, 2);
            assert_eq!(role_id, Some(3));
            assert_eq!(is_active, Some(true));
        }
        _ => panic!(),
    }
}

#[test]
fn parse_users_delete() {
    let cli = parse(&["gci", "users", "delete", "8"]);
    match cli.command {
        Command::Users(UserCmd::Delete { id }) => assert_eq!(id, 8),
        _ => panic!(),
    }
}

#[test]
fn parse_roles() {
    assert!(matches!(parse(&["gci", "roles"]).command, Command::Roles));
}

#[test]
fn parse_logs_default_limit() {
    let cli = parse(&["gci", "logs"]);
    match cli.command {
        Command::Logs { limit } => assert_eq!(limit, 100),
        _ => panic!(),
    }
}

#[test]
fn parse_logs_custom_limit() {
    let cli = parse(&["gci", "logs", "--limit", "50"]);
    match cli.command {
        Command::Logs { limit } => assert_eq!(limit, 50),
        _ => panic!(),
    }
}

#[test]
fn unknown_command_errors() {
    assert!(Cli::try_parse_from(["gci", "frobnicate"]).is_err());
}

#[test]
fn missing_required_id_errors() {
    assert!(Cli::try_parse_from(["gci", "products", "get"]).is_err());
}
