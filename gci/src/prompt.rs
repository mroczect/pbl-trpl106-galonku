use std::io::{self, Write};

use anyhow::Result;

pub fn ask(label: &str) -> Result<String> {
    print!("{}: ", label);
    io::stdout().flush()?;
    let mut s = String::new();
    io::stdin().read_line(&mut s)?;
    Ok(s.trim().to_string())
}

pub fn ask_password(label: &str) -> Result<String> {
    Ok(rpassword::prompt_password(format!("{}: ", label))?)
}

pub fn ask_opt(label: &str, provided: Option<String>) -> Result<String> {
    match provided {
        Some(v) if !v.is_empty() => Ok(v),
        _ => ask(label),
    }
}

pub fn ask_password_opt(label: &str, provided: Option<String>) -> Result<String> {
    match provided {
        Some(v) if !v.is_empty() => Ok(v),
        _ => ask_password(label),
    }
}
