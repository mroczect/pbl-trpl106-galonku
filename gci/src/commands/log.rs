use anyhow::Result;

use crate::client::ApiClient;
use crate::output;

pub fn list(client: &ApiClient, limit: u32) -> Result<()> {
    let data = client.get("/logs", &[("limit", limit.to_string())])?;
    let arr = data.as_array().cloned().unwrap_or_default();
    output::print_table(
        &arr,
        &[
            "id",
            "created_at",
            "user_name",
            "action",
            "entity",
            "entity_id",
            "ip_address",
        ],
    );
    Ok(())
}
