use clap::Parser;
use gci::cli::Cli;

fn main() {
    let cli = Cli::parse();
    gci::output::set_json(cli.json);
    if let Err(e) = gci::run(cli) {
        gci::output::error(&format!("{:#}", e));
        std::process::exit(1);
    }
}
