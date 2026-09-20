mod common;

use common::{err, ok};
use gci::cli::UserCmd;
use gci::client::ApiClient;
use gci::commands::user;
use mockito::Server;
use serde_json::json;

#[test]
fn list_calls_endpoint() {
    let mut s = Server::new();
    let _m = s
        .mock("GET", "/users")
        .with_status(200)
        .with_body(ok(json!([{ "id": 1, "name": "Admin" }])))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    user::execute(
        &c,
        UserCmd::List {
            page: None,
            per_page: None,
        },
    )
    .unwrap();
}

#[test]
fn get_calls_endpoint() {
    let mut s = Server::new();
    let _m = s
        .mock("GET", "/users/1")
        .with_status(200)
        .with_body(ok(json!({ "id": 1, "name": "Admin" })))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    user::execute(&c, UserCmd::Get { id: 1 }).unwrap();
}

#[test]
fn get_404_errors() {
    let mut s = Server::new();
    let _m = s
        .mock("GET", "/users/99")
        .with_status(404)
        .with_body(err("User not found"))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    assert!(user::execute(&c, UserCmd::Get { id: 99 }).is_err());
}

#[test]
fn update_partial() {
    let mut s = Server::new();
    let _m = s
        .mock("PUT", "/users/2")
        .match_body(mockito::Matcher::PartialJson(json!({ "name": "New" })))
        .with_status(200)
        .with_body(ok(json!(null)))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    user::execute(
        &c,
        UserCmd::Update {
            id: 2,
            name: Some("New".into()),
            phone: None,
            role_id: None,
            is_active: None,
        },
    )
    .unwrap();
}

#[test]
fn update_role_id() {
    let mut s = Server::new();
    let _m = s
        .mock("PUT", "/users/2")
        .match_body(mockito::Matcher::PartialJson(json!({ "role_id": 3 })))
        .with_status(200)
        .with_body(ok(json!(null)))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    user::execute(
        &c,
        UserCmd::Update {
            id: 2,
            name: None,
            phone: None,
            role_id: Some(3),
            is_active: None,
        },
    )
    .unwrap();
}

#[test]
fn update_empty_errors() {
    let s = Server::new();
    let c = ApiClient::new(s.url()).unwrap();
    assert!(
        user::execute(
            &c,
            UserCmd::Update {
                id: 1,
                name: None,
                phone: None,
                role_id: None,
                is_active: None,
            }
        )
        .is_err()
    );
}

#[test]
fn delete_calls_endpoint() {
    let mut s = Server::new();
    let _m = s
        .mock("DELETE", "/users/9")
        .with_status(200)
        .with_body(ok(json!(null)))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    user::execute(&c, UserCmd::Delete { id: 9 }).unwrap();
}
