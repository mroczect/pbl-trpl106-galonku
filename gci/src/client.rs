use anyhow::{Context, Result, anyhow};
use reqwest::Method;
use reqwest::blocking::{Client, RequestBuilder};
use serde_json::Value;

use crate::config;

pub struct ApiClient {
    base: String,
    http: Client,
}

impl ApiClient {
    pub fn new(base: String) -> Result<Self> {
        let http = Client::builder()
            .timeout(std::time::Duration::from_secs(20))
            .build()?;
        Ok(Self {
            base: base.trim_end_matches('/').to_string(),
            http,
        })
    }

    fn token(&self) -> Option<String> {
        config::load().ok().flatten().and_then(|s| s.access_token)
    }

    fn build(&self, method: Method, path: &str) -> RequestBuilder {
        let url = format!("{}{}", self.base, path);
        let mut rb = self.http.request(method, url);
        if let Some(t) = self.token() {
            rb = rb.bearer_auth(t);
        }
        rb
    }

    pub fn get(&self, path: &str, query: &[(&str, String)]) -> Result<Value> {
        let rb = self.build(Method::GET, path).query(query);
        Self::send(rb)
    }

    pub fn post(&self, path: &str, body: Value) -> Result<Value> {
        let rb = self.build(Method::POST, path).json(&body);
        Self::send(rb)
    }

    pub fn put(&self, path: &str, body: Value) -> Result<Value> {
        let rb = self.build(Method::PUT, path).json(&body);
        Self::send(rb)
    }

    pub fn delete(&self, path: &str) -> Result<Value> {
        let rb = self.build(Method::DELETE, path);
        Self::send(rb)
    }

    fn send(rb: RequestBuilder) -> Result<Value> {
        let resp = rb.send().context("HTTP request failed")?;
        let status = resp.status();
        let text = resp.text().unwrap_or_default();

        let json: Value = if text.is_empty() {
            Value::Null
        } else {
            serde_json::from_str(&text).unwrap_or(Value::String(text))
        };

        if !status.is_success() {
            let msg = json
                .get("message")
                .and_then(|v| v.as_str())
                .unwrap_or("request failed");
            return Err(anyhow!("HTTP {}: {}", status.as_u16(), msg));
        }

        Ok(json.get("data").cloned().unwrap_or(Value::Null))
    }
}
