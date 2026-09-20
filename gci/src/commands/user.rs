use anyhow::Result;
use serde_json::{Map, Value, json};

use crate::cli::UserCmd;
use crate::client::ApiClient;
use crate::output;

pub fn execute(client: &ApiClient, cmd: UserCmd) -> Result<()> {
    match cmd {
        UserCmd::List { page, per_page } => list(client, page, per_page),
        UserCmd::Get { id } => get(client, id),
        UserCmd::Update {
            id,
            name,
            phone,
            role_id,
            is_active,
        } => update(client, id, name, phone, role_id, is_active),
        UserCmd::Delete { id } => delete(client, id),
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

    let data = client.get("/users", &q)?;
    let arr = data.as_array().cloned().unwrap_or_default();
    output::print_table(
        &arr,
        &["id", "name", "email", "phone", "role_name", "is_active"],
    );
    Ok(())
}

fn get(client: &ApiClient, id: u32) -> Result<()> {
    let data = client.get(&format!("/users/{}", id), &[])?;
    output::print_json(&data);
    Ok(())
}

fn update(
    client: &ApiClient,
    id: u32,
    name: Option<String>,
    phone: Option<String>,
    role_id: Option<u32>,
    is_active: Option<bool>,
) -> Result<()> {
    let mut body = Map::new();
    if let Some(v) = name {
        body.insert("name".into(), json!(v));
    }
    if let Some(v) = phone {
        body.insert("phone".into(), json!(v));
    }
    if let Some(v) = role_id {
        body.insert("role_id".into(), json!(v));
    }
    if let Some(v) = is_active {
        body.insert("is_active".into(), json!(if v { 1 } else { 0 }));
    }

    if body.is_empty() {
        anyhow::bail!("no fields to update");
    }

    client.put(&format!("/users/{}", id), Value::Object(body))?;
    output::success("user updated");
    Ok(())
}

fn delete(client: &ApiClient, id: u32) -> Result<()> {
    client.delete(&format!("/users/{}", id))?;
    output::success("user deactivated");
    Ok(())
}
