mod common;

use common::{err, ok};
use gci::client::ApiClient;
use gci::commands::{log, role};
use mockito::Server;
use serde_json::json;

#[test]
fn role_list_calls_endpoint() {
    let mut s = Server::new();
    let _m = s
        .mock("GET", "/roles")
        .with_status(200)
        .with_body(ok(json!([
            { "id": 1, "name": "administrator" },
            { "id": 2, "name": "agent" },
            { "id": 3, "name": "customer" }
        ])))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    role::list(&c).unwrap();
}

#[test]
fn log_list_default_limit() {
    let mut s = Server::new();
    let _m = s
        .mock("GET", "/logs")
        .match_query(mockito::Matcher::UrlEncoded("limit".into(), "100".into()))
        .with_status(200)
        .with_body(ok(json!([])))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    log::list(&c, 100).unwrap();
}

#[test]
fn log_list_custom_limit() {
    let mut s = Server::new();
    let _m = s
        .mock("GET", "/logs")
        .match_query(mockito::Matcher::UrlEncoded("limit".into(), "50".into()))
        .with_status(200)
        .with_body(ok(json!([])))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    log::list(&c, 50).unwrap();
}

#[test]
fn log_forbidden_errors() {
    let mut s = Server::new();
    let _m = s
        .mock("GET", "/logs")
        .with_status(403)
        .with_body(err("Access denied"))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    assert!(log::list(&c, 100).is_err());
}
