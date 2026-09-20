use anyhow::Result;
use serde_json::{Map, Value, json};

use crate::cli::CustomerCmd;
use crate::client::ApiClient;
use crate::output;
use crate::prompt;

pub fn execute(client: &ApiClient, cmd: CustomerCmd) -> Result<()> {
    match cmd {
        CustomerCmd::List { page, per_page } => list(client, page, per_page),
        CustomerCmd::Get { id } => get(client, id),
        CustomerCmd::Create {
            name,
            phone,
            address,
            notes,
        } => create(client, name, phone, address, notes),
        CustomerCmd::Update {
            id,
            name,
            phone,
            address,
            notes,
            is_active,
        } => update(client, id, name, phone, address, notes, is_active),
        CustomerCmd::Delete { id } => delete(client, id),
    }
}

fn list(client: &ApiClient, page: Option<u32>, per_page: Option<u32>) -> Result<()> {
    let mut q: Vec<(&str, String)> = Vec::new();
    if let Some(v) = page {
        q.push(("page", v.to_string()));
    }
    if let Some(v) = per_page {
        q.push(("per_page", v.to_string()));
    }

    let data = client.get("/customers", &q)?;
    let arr = data.as_array().cloned().unwrap_or_default();
    output::print_table(&arr, &["id", "name", "phone", "address", "is_active"]);
    Ok(())
}

fn get(client: &ApiClient, id: u32) -> Result<()> {
    let data = client.get(&format!("/customers/{}", id), &[])?;
    output::print_json(&data);
    Ok(())
}

fn create(
    client: &ApiClient,
    name: Option<String>,
    phone: Option<String>,
    address: Option<String>,
    notes: Option<String>,
) -> Result<()> {
    let name = prompt::ask_opt("Name", name)?;
    let phone = prompt::ask_opt("Phone", phone)?;

    let mut body = json!({ "name": name, "phone": phone });
    if let Some(a) = address.filter(|s| !s.is_empty()) {
        body["address"] = json!(a);
    }
    if let Some(n) = notes.filter(|s| !s.is_empty()) {
        body["notes"] = json!(n);
    }

    let data = client.post("/customers", body)?;
    let id = data.get("id").and_then(|v| v.as_u64()).unwrap_or(0);
    output::success(&format!("customer created (id={})", id));
    Ok(())
}

fn update(
    client: &ApiClient,
    id: u32,
    name: Option<String>,
    phone: Option<String>,
    address: Option<String>,
    notes: Option<String>,
    is_active: Option<bool>,
) -> Result<()> {
    let mut body = Map::new();
    if let Some(v) = name {
        body.insert("name".into(), json!(v));
    }
    if let Some(v) = phone {
        body.insert("phone".into(), json!(v));
    }
    if let Some(v) = address {
        body.insert("address".into(), json!(v));
    }
    if let Some(v) = notes {
        body.insert("notes".into(), json!(v));
    }
    if let Some(v) = is_active {
        body.insert("is_active".into(), json!(if v { 1 } else { 0 }));
    }

    if body.is_empty() {
        anyhow::bail!("no fields to update");
    }

    client.put(&format!("/customers/{}", id), Value::Object(body))?;
    output::success("customer updated");
    Ok(())
}

fn delete(client: &ApiClient, id: u32) -> Result<()> {
    client.delete(&format!("/customers/{}", id))?;
    output::success("customer deactivated");
    Ok(())
}
