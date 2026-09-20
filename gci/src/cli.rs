use clap::{Parser, Subcommand};

#[derive(Parser, Debug)]
#[command(name = "gci", version, about = "Galonku CLI — full API client")]
pub struct Cli {
    #[arg(
        long,
        default_value = "http://localhost:8000/api/v1",
        env = "GCI_BASE_URL",
        global = true
    )]
    pub base_url: String,

    #[arg(long, global = true)]
    pub json: bool,

    #[command(subcommand)]
    pub command: Command,
}

#[derive(Subcommand, Debug)]
pub enum Command {
    Login {
        #[arg(short, long)]
        email: Option<String>,
        #[arg(short, long)]
        password: Option<String>,
    },
    Register {
        #[arg(short, long)]
        name: Option<String>,
        #[arg(short, long)]
        email: Option<String>,
        #[arg(short, long)]
        password: Option<String>,
        #[arg(long)]
        phone: Option<String>,
    },
    Logout,
    Me,
    Refresh,

    #[command(subcommand)]
    Products(ProductCmd),

    #[command(subcommand)]
    Stock(StockCmd),

    #[command(subcommand)]
    Customers(CustomerCmd),

    #[command(subcommand)]
    Transactions(TransactionCmd),

    #[command(subcommand)]
    Schedules(ScheduleCmd),

    #[command(subcommand)]
    Users(UserCmd),

    Roles,

    Logs {
        #[arg(long, default_value_t = 100)]
        limit: u32,
    },
}

#[derive(Subcommand, Debug)]
pub enum ProductCmd {
    List {
        #[arg(long)]
        page: Option<u32>,
        #[arg(long)]
        per_page: Option<u32>,
        #[arg(long)]
        category: Option<String>,
    },
    Get {
        id: u32,
    },
    LowStock {
        #[arg(long, default_value_t = 10)]
        threshold: u32,
    },
    Create {
        #[arg(long)]
        sku: Option<String>,
        #[arg(long)]
        name: Option<String>,
        #[arg(long)]
        price: Option<f64>,
        #[arg(long)]
        category: Option<String>,
        #[arg(long)]
        stock: Option<i32>,
    },
    Update {
        id: u32,
        #[arg(long)]
        name: Option<String>,
        #[arg(long)]
        category: Option<String>,
        #[arg(long)]
        price: Option<f64>,
        #[arg(long)]
        stock: Option<i32>,
        #[arg(long)]
        is_active: Option<bool>,
    },
    Delete {
        id: u32,
    },
}

#[derive(Subcommand, Debug)]
pub enum StockCmd {
    List {
        #[arg(long)]
        product_id: Option<u32>,
        #[arg(long = "type")]
        type_: Option<String>,
        #[arg(long)]
        user_id: Option<u32>,
        #[arg(long)]
        from: Option<String>,
        #[arg(long)]
        to: Option<String>,
        #[arg(long)]
        page: Option<u32>,
        #[arg(long)]
        per_page: Option<u32>,
    },
    Adjust {
        #[arg(long)]
        product_id: Option<u32>,
        #[arg(long)]
        new_stock: Option<i32>,
        #[arg(long)]
        reason: Option<String>,
        #[arg(long)]
        notes: Option<String>,
    },
}

#[derive(Subcommand, Debug)]
pub enum CustomerCmd {
    List {
        #[arg(long)]
        page: Option<u32>,
        #[arg(long)]
        per_page: Option<u32>,
    },
    Get {
        id: u32,
    },
    Create {
        #[arg(long)]
        name: Option<String>,
        #[arg(long)]
        phone: Option<String>,
        #[arg(long)]
        address: Option<String>,
        #[arg(long)]
        notes: Option<String>,
    },
    Update {
        id: u32,
        #[arg(long)]
        name: Option<String>,
        #[arg(long)]
        phone: Option<String>,
        #[arg(long)]
        address: Option<String>,
        #[arg(long)]
        notes: Option<String>,
        #[arg(long)]
        is_active: Option<bool>,
    },
    Delete {
        id: u32,
    },
}

#[derive(Subcommand, Debug)]
pub enum TransactionCmd {
    List {
        #[arg(long)]
        page: Option<u32>,
        #[arg(long)]
        per_page: Option<u32>,
        #[arg(long)]
        status: Option<String>,
        #[arg(long)]
        customer_id: Option<u32>,
        #[arg(long)]
        user_id: Option<u32>,
        #[arg(long = "type")]
        type_: Option<String>,
    },
    Get {
        id: u32,
    },
    Create {
        #[arg(long)]
        customer_id: Option<u32>,
        #[arg(long = "type")]
        type_: Option<String>,
        #[arg(long)]
        paid_amount: Option<f64>,
        #[arg(long)]
        status: Option<String>,
        #[arg(long)]
        notes: Option<String>,
        #[arg(long = "item", value_name = "PRODUCT_ID:QTY")]
        items: Vec<String>,
    },
    UpdateStatus {
        id: u32,
        #[arg(long)]
        status: Option<String>,
        #[arg(long)]
        paid_amount: Option<f64>,
    },
}

#[derive(Subcommand, Debug)]
pub enum ScheduleCmd {
    List {
        #[arg(long)]
        page: Option<u32>,
        #[arg(long)]
        per_page: Option<u32>,
        #[arg(long)]
        status: Option<String>,
        #[arg(long)]
        user_id: Option<u32>,
        #[arg(long)]
        customer_id: Option<u32>,
    },
    Get {
        id: u32,
    },
    Create {
        #[arg(long)]
        customer_id: Option<u32>,
        #[arg(long)]
        user_id: Option<u32>,
        #[arg(long)]
        scheduled_at: Option<String>,
        #[arg(long)]
        notes: Option<String>,
    },
    UpdateStatus {
        id: u32,
        #[arg(long)]
        status: Option<String>,
    },
}

#[derive(Subcommand, Debug)]
pub enum UserCmd {
    List {
        #[arg(long)]
        page: Option<u32>,
        #[arg(long)]
        per_page: Option<u32>,
    },
    Get {
        id: u32,
    },
    Update {
        id: u32,
        #[arg(long)]
        name: Option<String>,
        #[arg(long)]
        phone: Option<String>,
        #[arg(long)]
        role_id: Option<u32>,
        #[arg(long)]
        is_active: Option<bool>,
    },
    Delete {
        id: u32,
    },
}
