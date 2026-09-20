use anyhow::Result;

use crate::client::ApiClient;
use crate::output;

pub fn list(client: &ApiClient) -> Result<()> {
    let data = client.get("/roles", &[])?;
    let arr = data.as_array().cloned().unwrap_or_default();
    output::print_table(&arr, &["id", "name", "description"]);
    Ok(())
}
