use anyhow::Result;
use serde_json::{Map, Value, json};

use crate::cli::ProductCmd;
use crate::client::ApiClient;
use crate::output;
use crate::prompt;

pub fn execute(client: &ApiClient, cmd: ProductCmd) -> Result<()> {
    match cmd {
        ProductCmd::List {
            page,
            per_page,
            category,
        } => list(client, page, per_page, category),
        ProductCmd::Get { id } => get(client, id),
        ProductCmd::LowStock { threshold } => low_stock(client, threshold),
        ProductCmd::Create {
            sku,
            name,
            price,
            category,
            stock,
        } => create(client, sku, name, price, category, stock),
        ProductCmd::Update {
            id,
            name,
            category,
            price,
            stock,
            is_active,
        } => update(client, id, name, category, price, stock, is_active),
        ProductCmd::Delete { id } => delete(client, id),
    }
}

fn list(
    client: &ApiClient,
    page: Option<u32>,
    per_page: Option<u32>,
    category: Option<String>,
) -> Result<()> {
    let mut q: Vec<(&str, String)> = Vec::new();
    if let Some(p) = page {
        q.push(("page", p.to_string()));
    }
    if let Some(pp) = per_page {
        q.push(("per_page", pp.to_string()));
    }
    if let Some(c) = category {
        q.push(("category", c));
    }

    let data = client.get("/products", &q)?;
    let arr = data.as_array().cloned().unwrap_or_default();
    output::print_table(
        &arr,
        &[
            "id",
            "sku",
            "name",
            "category",
            "price",
            "stock",
            "is_active",
        ],
    );
    Ok(())
}

fn get(client: &ApiClient, id: u32) -> Result<()> {
    let data = client.get(&format!("/products/{}", id), &[])?;
    output::print_json(&data);
    Ok(())
}

fn low_stock(client: &ApiClient, threshold: u32) -> Result<()> {
    let data = client.get(
        "/products/low-stock",
        &[("threshold", threshold.to_string())],
    )?;
    let arr = data.as_array().cloned().unwrap_or_default();
    output::print_table(&arr, &["id", "sku", "name", "stock"]);
    Ok(())
}

fn create(
    client: &ApiClient,
    sku: Option<String>,
    name: Option<String>,
    price: Option<f64>,
    category: Option<String>,
    stock: Option<i32>,
) -> Result<()> {
    let sku = prompt::ask_opt("SKU", sku)?;
    let name = prompt::ask_opt("Name", name)?;
    let price = match price {
        Some(p) => p,
        None => prompt::ask("Price")?.parse()?,
    };
    let category = category.unwrap_or_else(|| "galon".into());
    let stock = stock.unwrap_or(0);

    let body = json!({
        "sku": sku,
        "name": name,
        "price": price,
        "category": category,
        "stock": stock,
    });
    let data = client.post("/products", body)?;
    let id = data.get("id").and_then(|v| v.as_u64()).unwrap_or(0);
    output::success(&format!("product created (id={})", id));
    Ok(())
}

fn update(
    client: &ApiClient,
    id: u32,
    name: Option<String>,
    category: Option<String>,
    price: Option<f64>,
    stock: Option<i32>,
    is_active: Option<bool>,
) -> Result<()> {
    let mut body = Map::new();
    if let Some(v) = name {
        body.insert("name".into(), json!(v));
    }
    if let Some(v) = category {
        body.insert("category".into(), json!(v));
    }
    if let Some(v) = price {
        body.insert("price".into(), json!(v));
    }
    if let Some(v) = stock {
        body.insert("stock".into(), json!(v));
    }
    if let Some(v) = is_active {
        body.insert("is_active".into(), json!(if v { 1 } else { 0 }));
    }

    if body.is_empty() {
        anyhow::bail!("no fields to update; pass --name/--price/--stock/etc.");
    }

    client.put(&format!("/products/{}", id), Value::Object(body))?;
    output::success("product updated");
    Ok(())
}

fn delete(client: &ApiClient, id: u32) -> Result<()> {
    client.delete(&format!("/products/{}", id))?;
    output::success("product deactivated");
    Ok(())
}
