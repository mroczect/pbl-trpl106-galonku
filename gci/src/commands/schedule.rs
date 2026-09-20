use anyhow::Result;
use serde_json::json;

use crate::cli::ScheduleCmd;
use crate::client::ApiClient;
use crate::output;
use crate::prompt;

pub fn execute(client: &ApiClient, cmd: ScheduleCmd) -> Result<()> {
    match cmd {
        ScheduleCmd::List {
            page,
            per_page,
            status,
            user_id,
            customer_id,
        } => list(client, page, per_page, status, user_id, customer_id),
        ScheduleCmd::Get { id } => get(client, id),
        ScheduleCmd::Create {
            customer_id,
            user_id,
            scheduled_at,
            notes,
        } => create(client, customer_id, user_id, scheduled_at, notes),
        ScheduleCmd::UpdateStatus { id, status } => update_status(client, id, status),
    }
}

fn list(
    client: &ApiClient,
    page: Option<u32>,
    per_page: Option<u32>,
    status: Option<String>,
    user_id: Option<u32>,
    customer_id: Option<u32>,
) -> Result<()> {
    let mut q: Vec<(&str, String)> = Vec::new();
    if let Some(v) = page {
        q.push(("page", v.to_string()));
    }
    if let Some(v) = per_page {
        q.push(("per_page", v.to_string()));
    }
    if let Some(v) = status {
        q.push(("status", v));
    }
    if let Some(v) = user_id {
        q.push(("user_id", v.to_string()));
    }
    if let Some(v) = customer_id {
        q.push(("customer_id", v.to_string()));
    }

    let data = client.get("/schedules", &q)?;
    let arr = data.as_array().cloned().unwrap_or_default();
    output::print_table(
        &arr,
        &[
            "id",
            "customer_name",
            "user_name",
            "scheduled_at",
            "status",
            "notes",
        ],
    );
    Ok(())
}

fn get(client: &ApiClient, id: u32) -> Result<()> {
    let data = client.get(&format!("/schedules/{}", id), &[])?;
    output::print_json(&data);
    Ok(())
}

fn create(
    client: &ApiClient,
    customer_id: Option<u32>,
    user_id: Option<u32>,
    scheduled_at: Option<String>,
    notes: Option<String>,
) -> Result<()> {
    let customer_id = match customer_id {
        Some(v) => v,
        None => prompt::ask("Customer ID")?.parse()?,
    };
    let user_id = match user_id {
        Some(v) => v,
        None => prompt::ask("User ID")?.parse()?,
    };
    let scheduled_at = prompt::ask_opt("Scheduled at (YYYY-MM-DD HH:MM:SS)", scheduled_at)?;

    let mut body = json!({
        "customer_id": customer_id,
        "user_id": user_id,
        "scheduled_at": scheduled_at,
    });
    if let Some(n) = notes.filter(|s| !s.is_empty()) {
        body["notes"] = json!(n);
    }

    let data = client.post("/schedules", body)?;
    let id = data.get("id").and_then(|v| v.as_u64()).unwrap_or(0);
    output::success(&format!("schedule created (id={})", id));
    Ok(())
}

fn update_status(client: &ApiClient, id: u32, status: Option<String>) -> Result<()> {
    let status = prompt::ask_opt("Status", status)?;
    client.put(
        &format!("/schedules/{}/status", id),
        json!({ "status": status }),
    )?;
    output::success("schedule status updated");
    Ok(())
}
