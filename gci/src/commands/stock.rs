use anyhow::Result;
use serde_json::json;

use crate::cli::StockCmd;
use crate::client::ApiClient;
use crate::output;
use crate::prompt;

pub fn execute(client: &ApiClient, cmd: StockCmd) -> Result<()> {
    match cmd {
        StockCmd::List {
            product_id,
            type_,
            user_id,
            from,
            to,
            page,
            per_page,
        } => list(
            client,
            StockFilter {
                product_id,
                type_,
                user_id,
                from,
                to,
                page,
                per_page,
            },
        ),
        StockCmd::Adjust {
            product_id,
            new_stock,
            reason,
            notes,
        } => adjust(client, product_id, new_stock, reason, notes),
    }
}

pub struct StockFilter {
    pub product_id: Option<u32>,
    pub type_: Option<String>,
    pub user_id: Option<u32>,
    pub from: Option<String>,
    pub to: Option<String>,
    pub page: Option<u32>,
    pub per_page: Option<u32>,
}

impl StockFilter {
    pub fn to_query(&self) -> Vec<(&'static str, String)> {
        let mut q = Vec::new();
        if let Some(v) = self.product_id {
            q.push(("product_id", v.to_string()));
        }
        if let Some(v) = &self.type_ {
            q.push(("type", v.clone()));
        }
        if let Some(v) = self.user_id {
            q.push(("user_id", v.to_string()));
        }
        if let Some(v) = &self.from {
            q.push(("from", v.clone()));
        }
        if let Some(v) = &self.to {
            q.push(("to", v.clone()));
        }
        if let Some(v) = self.page {
            q.push(("page", v.to_string()));
        }
        if let Some(v) = self.per_page {
            q.push(("per_page", v.to_string()));
        }
        q
    }
}

fn list(client: &ApiClient, filter: StockFilter) -> Result<()> {
    let q = filter.to_query();
    let data = client.get("/stock-movements", &q)?;
    let arr = data.as_array().cloned().unwrap_or_default();
    output::print_table(
        &arr,
        &[
            "id",
            "created_at",
            "product_name",
            "type",
            "qty",
            "stock_before",
            "stock_after",
            "reason",
        ],
    );
    Ok(())
}

fn adjust(
    client: &ApiClient,
    product_id: Option<u32>,
    new_stock: Option<i32>,
    reason: Option<String>,
    notes: Option<String>,
) -> Result<()> {
    let product_id = match product_id {
        Some(v) => v,
        None => prompt::ask("Product ID")?.parse()?,
    };
    let new_stock = match new_stock {
        Some(v) => v,
        None => prompt::ask("New stock")?.parse()?,
    };
    let reason = prompt::ask_opt("Reason", reason)?;

    let mut body = json!({
        "product_id": product_id,
        "new_stock": new_stock,
        "reason": reason,
    });
    if let Some(n) = notes.filter(|s| !s.is_empty()) {
        body["notes"] = json!(n);
    }

    let data = client.post("/stock-movements/adjust", body)?;
    output::print_json(&data);
    Ok(())
}
