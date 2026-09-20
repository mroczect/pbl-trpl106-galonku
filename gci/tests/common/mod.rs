#![allow(dead_code)]

use serde_json::json;

pub fn ok(data: serde_json::Value) -> String {
    json!({ "success": true, "message": "OK", "data": data }).to_string()
}

pub fn err(message: &str) -> String {
    json!({ "success": false, "message": message }).to_string()
}

pub fn with_tmp_config_dir<F: FnOnce()>(f: F) {
    let dir = tempfile::tempdir().unwrap();
    let prev = std::env::var("GCI_CONFIG_DIR").ok();
    unsafe { std::env::set_var("GCI_CONFIG_DIR", dir.path()) };
    f();
    match prev {
        Some(p) => unsafe { std::env::set_var("GCI_CONFIG_DIR", &p) },
        None => unsafe { std::env::remove_var("GCI_CONFIG_DIR") },
    }
}
