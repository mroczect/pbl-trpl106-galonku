mod common;

use common::{err, ok};
use gci::cli::CustomerCmd;
use gci::client::ApiClient;
use gci::commands::customer;
use mockito::Server;
use serde_json::json;

#[test]
fn list_calls_endpoint() {
    let mut s = Server::new();
    let _m = s
        .mock("GET", "/customers")
        .with_status(200)
        .with_body(ok(json!([{ "id": 1, "name": "Ibu" }])))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    customer::execute(
        &c,
        CustomerCmd::List {
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
        .mock("GET", "/customers/1")
        .with_status(200)
        .with_body(ok(json!({ "id": 1, "name": "Ibu" })))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    customer::execute(&c, CustomerCmd::Get { id: 1 }).unwrap();
}

#[test]
fn get_404_errors() {
    let mut s = Server::new();
    let _m = s
        .mock("GET", "/customers/99")
        .with_status(404)
        .with_body(err("Customer not found"))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    assert!(customer::execute(&c, CustomerCmd::Get { id: 99 }).is_err());
}

#[test]
fn create_with_all_fields() {
    let mut s = Server::new();
    let _m = s
        .mock("POST", "/customers")
        .match_body(mockito::Matcher::PartialJson(json!({
            "name": "Ibu Siti",
            "phone": "0800",
            "address": "Jl. Merdeka",
            "notes": "langganan"
        })))
        .with_status(201)
        .with_body(ok(json!({ "id": 3 })))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    customer::execute(
        &c,
        CustomerCmd::Create {
            name: Some("Ibu Siti".into()),
            phone: Some("0800".into()),
            address: Some("Jl. Merdeka".into()),
            notes: Some("langganan".into()),
        },
    )
    .unwrap();
}

#[test]
fn create_minimal() {
    let mut s = Server::new();
    let _m = s
        .mock("POST", "/customers")
        .with_status(201)
        .with_body(ok(json!({ "id": 4 })))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    customer::execute(
        &c,
        CustomerCmd::Create {
            name: Some("A".into()),
            phone: Some("0800".into()),
            address: None,
            notes: None,
        },
    )
    .unwrap();
}

#[test]
fn create_409_errors() {
    let mut s = Server::new();
    let _m = s
        .mock("POST", "/customers")
        .with_status(409)
        .with_body(err("Phone number already registered"))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    assert!(
        customer::execute(
            &c,
            CustomerCmd::Create {
                name: Some("A".into()),
                phone: Some("0800".into()),
                address: None,
                notes: None,
            }
        )
        .is_err()
    );
}

#[test]
fn update_partial_body() {
    let mut s = Server::new();
    let _m = s
        .mock("PUT", "/customers/1")
        .match_body(mockito::Matcher::PartialJson(json!({ "name": "Baru" })))
        .with_status(200)
        .with_body(ok(json!(null)))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    customer::execute(
        &c,
        CustomerCmd::Update {
            id: 1,
            name: Some("Baru".into()),
            phone: None,
            address: None,
            notes: None,
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
        customer::execute(
            &c,
            CustomerCmd::Update {
                id: 1,
                name: None,
                phone: None,
                address: None,
                notes: None,
                is_active: None,
            }
        )
        .is_err()
    );
}

#[test]
fn update_is_active_false_maps_to_0() {
    let mut s = Server::new();
    let _m = s
        .mock("PUT", "/customers/1")
        .match_body(mockito::Matcher::PartialJson(json!({ "is_active": 0 })))
        .with_status(200)
        .with_body(ok(json!(null)))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    customer::execute(
        &c,
        CustomerCmd::Update {
            id: 1,
            name: None,
            phone: None,
            address: None,
            notes: None,
            is_active: Some(false),
        },
    )
    .unwrap();
}

#[test]
fn delete_calls_endpoint() {
    let mut s = Server::new();
    let _m = s
        .mock("DELETE", "/customers/1")
        .with_status(200)
        .with_body(ok(json!(null)))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    customer::execute(&c, CustomerCmd::Delete { id: 1 }).unwrap();
}
