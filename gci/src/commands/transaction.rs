use anyhow::Result;
use serde_json::json;

use crate::cli::TransactionCmd;
use crate::client::ApiClient;
use crate::output;
use crate::prompt;

pub fn execute(client: &ApiClient, cmd: TransactionCmd) -> Result<()> {
    match cmd {
        TransactionCmd::List {
            page,
            per_page,
            status,
            customer_id,
            user_id,
            type_,
        } => list(client, page, per_page, status, customer_id, user_id, type_),
        TransactionCmd::Get { id } => get(client, id),
        TransactionCmd::Create {
            customer_id,
            type_,
            paid_amount,
            status,
            notes,
            items,
        } => create(
            client,
            customer_id,
            type_,
            paid_amount,
            status,
            notes,
            items,
        ),
        TransactionCmd::UpdateStatus {
            id,
            status,
            paid_amount,
        } => update_status(client, id, status, paid_amount),
    }
}

fn list(
    client: &ApiClient,
    page: Option<u32>,
    per_page: Option<u32>,
    status: Option<String>,
    customer_id: Option<u32>,
    user_id: Option<u32>,
    type_: Option<String>,
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
    if let Some(v) = customer_id {
        q.push(("customer_id", v.to_string()));
    }
    if let Some(v) = user_id {
        q.push(("user_id", v.to_string()));
    }
    if let Some(v) = type_ {
        q.push(("type", v));
    }

    let data = client.get("/transactions", &q)?;
    let arr = data.as_array().cloned().unwrap_or_default();
    output::print_table(
        &arr,
        &[
            "id",
            "invoice_no",
            "customer_name",
            "user_name",
            "total_amount",
            "paid_amount",
            "status",
            "created_at",
        ],
    );
    Ok(())
}

fn get(client: &ApiClient, id: u32) -> Result<()> {
    let data = client.get(&format!("/transactions/{}", id), &[])?;
    output::print_json(&data);
    Ok(())
}

fn create(
    client: &ApiClient,
    customer_id: Option<u32>,
    type_: Option<String>,
    paid_amount: Option<f64>,
    status: Option<String>,
    notes: Option<String>,
    items: Vec<String>,
) -> Result<()> {
    let customer_id = match customer_id {
        Some(v) => v,
        None => prompt::ask("Customer ID")?.parse()?,
    };
    let type_ = type_.unwrap_or_else(|| "sale".into());
    let paid = paid_amount.unwrap_or(0.0);
    let status = status.unwrap_or_else(|| "pending".into());

    let parsed = parse_items(&items)?;
    if parsed.is_empty() {
        anyhow::bail!("at least one `--item PRODUCT_ID:QTY` is required");
    }

    let mut body = json!({
        "customer_id": customer_id,
        "type": type_,
        "status": status,
        "paid_amount": paid,
        "items": parsed,
    });
    if let Some(n) = notes.filter(|s| !s.is_empty()) {
        body["notes"] = json!(n);
    }

    let data = client.post("/transactions", body)?;
    let invoice = data
        .get("invoice_no")
        .and_then(|v| v.as_str())
        .unwrap_or("");
    let id = data.get("id").and_then(|v| v.as_u64()).unwrap_or(0);
    output::success(&format!("transaction created: {} (id={})", invoice, id));
    Ok(())
}

pub fn parse_items(items: &[String]) -> Result<Vec<serde_json::Value>> {
    let mut out = Vec::new();
    for spec in items {
        let parts: Vec<&str> = spec.split(':').collect();
        if parts.len() != 2 {
            anyhow::bail!("item must be PRODUCT_ID:QTY, got '{}'", spec);
        }
        let pid: u32 = parts[0].parse()?;
        let qty: i32 = parts[1].parse()?;
        out.push(json!({ "product_id": pid, "qty": qty }));
    }
    Ok(out)
}

fn update_status(
    client: &ApiClient,
    id: u32,
    status: Option<String>,
    paid_amount: Option<f64>,
) -> Result<()> {
    let status = prompt::ask_opt("Status", status)?;

    let mut body = json!({ "status": status });
    if let Some(p) = paid_amount {
        body["paid_amount"] = json!(p);
    }

    client.put(&format!("/transactions/{}/status", id), body)?;
    output::success("transaction status updated");
    Ok(())
}
