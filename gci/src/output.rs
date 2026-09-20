use std::sync::atomic::{AtomicBool, Ordering};

use serde_json::Value;

static JSON_MODE: AtomicBool = AtomicBool::new(false);

pub fn set_json(v: bool) {
    JSON_MODE.store(v, Ordering::Relaxed);
}

pub fn json_mode() -> bool {
    JSON_MODE.load(Ordering::Relaxed)
}

pub fn error(msg: &str) {
    eprintln!("\x1b[31merror:\x1b[0m {}", msg);
}

pub fn success(msg: &str) {
    println!("\x1b[32m✓\x1b[0m {}", msg);
}

pub fn print_json(v: &Value) {
    println!("{}", serde_json::to_string_pretty(v).unwrap_or_default());
}

pub fn print_table(rows: &[Value], columns: &[&str]) {
    if json_mode() {
        print_json(&Value::Array(rows.to_vec()));
        return;
    }

    if rows.is_empty() {
        println!("(no data)");
        return;
    }

    let mut widths: Vec<usize> = columns.iter().map(|c| c.len()).collect();
    let mut data: Vec<Vec<String>> = Vec::new();

    for row in rows {
        let mut line = Vec::new();
        for (i, col) in columns.iter().enumerate() {
            let val = cell_value(row, col);
            widths[i] = widths[i].max(val.chars().count());
            line.push(val);
        }
        data.push(line);
    }

    let header: Vec<String> = columns
        .iter()
        .enumerate()
        .map(|(i, c)| pad(c, widths[i]))
        .collect();
    println!("{}", header.join("  "));
    println!(
        "{}",
        widths
            .iter()
            .map(|w| "-".repeat(*w))
            .collect::<Vec<_>>()
            .join("  ")
    );

    for row in data {
        let line: Vec<String> = row
            .iter()
            .enumerate()
            .map(|(i, v)| pad(v, widths[i]))
            .collect();
        println!("{}", line.join("  "));
    }
}

pub fn pad(s: &str, width: usize) -> String {
    let len = s.chars().count();
    if len >= width {
        s.to_string()
    } else {
        format!("{}{}", s, " ".repeat(width - len))
    }
}

pub fn cell_value(row: &Value, key: &str) -> String {
    row.get(key)
        .map(|v| match v {
            Value::Null => "-".to_string(),
            Value::String(s) => s.clone(),
            Value::Bool(b) => b.to_string(),
            Value::Number(n) => n.to_string(),
            other => other.to_string(),
        })
        .unwrap_or_else(|| "-".to_string())
}
