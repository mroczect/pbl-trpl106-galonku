use anyhow::{Result, anyhow};
use serde_json::json;

use crate::client::ApiClient;
use crate::config::{self, Session};
use crate::output;
use crate::prompt;

pub fn login(client: &ApiClient, email: Option<String>, password: Option<String>) -> Result<()> {
    let email = prompt::ask_opt("Email", email)?;
    let password = prompt::ask_password_opt("Password", password)?;

    let data = client.post(
        "/auth/login",
        json!({
            "email": email,
            "password": password,
        }),
    )?;

    let session = Session {
        access_token: data
            .get("access_token")
            .and_then(|v| v.as_str())
            .map(String::from),
        refresh_token: data
            .get("refresh_token")
            .and_then(|v| v.as_str())
            .map(String::from),
        user: data.get("user").cloned(),
    };
    config::save(&session)?;

    let name = session
        .user
        .as_ref()
        .and_then(|u| u.get("name"))
        .and_then(|v| v.as_str())
        .unwrap_or("user");
    output::success(&format!("logged in as {}", name));
    Ok(())
}

pub fn register(
    client: &ApiClient,
    name: Option<String>,
    email: Option<String>,
    password: Option<String>,
    phone: Option<String>,
) -> Result<()> {
    let name = prompt::ask_opt("Name", name)?;
    let email = prompt::ask_opt("Email", email)?;
    let password = prompt::ask_password_opt("Password", password)?;
    let phone = phone.filter(|p| !p.is_empty());

    let mut body = json!({
        "name": name,
        "email": email,
        "password": password,
    });
    if let Some(p) = phone {
        body["phone"] = json!(p);
    }

    client.post("/auth/register", body)?;
    output::success("registration successful — run `gci login` to sign in");
    Ok(())
}

pub fn logout(client: &ApiClient) -> Result<()> {
    let session = config::load()?.unwrap_or_default();

    let mut body = json!({});
    if let Some(r) = &session.refresh_token {
        body["refresh_token"] = json!(r);
    }
    let _ = client.post("/auth/logout", body);

    config::clear()?;
    output::success("logged out");
    Ok(())
}

pub fn me(client: &ApiClient) -> Result<()> {
    let data = client.get("/auth/me", &[])?;
    output::print_json(&data);
    Ok(())
}

pub fn refresh(client: &ApiClient) -> Result<()> {
    let session = config::load()?.ok_or_else(|| anyhow!("no session; login first"))?;
    let refresh = session
        .refresh_token
        .ok_or_else(|| anyhow!("no refresh token in session"))?;

    let data = client.post("/auth/refresh", json!({ "refresh_token": refresh }))?;

    let new_session = Session {
        access_token: data
            .get("access_token")
            .and_then(|v| v.as_str())
            .map(String::from),
        refresh_token: data
            .get("refresh_token")
            .and_then(|v| v.as_str())
            .map(String::from),
        user: session.user,
    };
    config::save(&new_session)?;
    output::success("token refreshed");
    Ok(())
}
