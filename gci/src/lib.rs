pub mod cli;
pub mod client;
pub mod commands;
pub mod config;
pub mod output;
pub mod prompt;

use cli::{Cli, Command};
use client::ApiClient;

pub fn run(cli: Cli) -> anyhow::Result<()> {
    let client = ApiClient::new(cli.base_url)?;
    match cli.command {
        Command::Login { email, password } => commands::auth::login(&client, email, password),
        Command::Register {
            name,
            email,
            password,
            phone,
        } => commands::auth::register(&client, name, email, password, phone),
        Command::Logout => commands::auth::logout(&client),
        Command::Me => commands::auth::me(&client),
        Command::Refresh => commands::auth::refresh(&client),
        Command::Products(sub) => commands::product::execute(&client, sub),
        Command::Stock(sub) => commands::stock::execute(&client, sub),
        Command::Customers(sub) => commands::customer::execute(&client, sub),
        Command::Transactions(sub) => commands::transaction::execute(&client, sub),
        Command::Schedules(sub) => commands::schedule::execute(&client, sub),
        Command::Users(sub) => commands::user::execute(&client, sub),
        Command::Roles => commands::role::list(&client),
        Command::Logs { limit } => commands::log::list(&client, limit),
    }
}
