mod common;

use common::{err, ok};
use gci::cli::TransactionCmd;
use gci::client::ApiClient;
use gci::commands::transaction;
use mockito::Server;
use serde_json::json;

#[test]
fn list_calls_endpoint() {
    let mut s = Server::new();
    let _m = s
        .mock("GET", "/transactions")
        .with_status(200)
        .with_body(ok(json!([{ "id": 1, "invoice_no": "INV-1" }])))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    transaction::execute(
        &c,
        TransactionCmd::List {
            page: None,
            per_page: None,
            status: None,
            customer_id: None,
            user_id: None,
            type_: None,
        },
    )
    .unwrap();
}

#[test]
fn list_with_filters() {
    let mut s = Server::new();
    let _m = s
        .mock("GET", "/transactions")
        .match_query(mockito::Matcher::AllOf(vec![
            mockito::Matcher::UrlEncoded("status".into(), "paid".into()),
            mockito::Matcher::UrlEncoded("type".into(), "sale".into()),
        ]))
        .with_status(200)
        .with_body(ok(json!([])))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    transaction::execute(
        &c,
        TransactionCmd::List {
            page: None,
            per_page: None,
            status: Some("paid".into()),
            customer_id: None,
            user_id: None,
            type_: Some("sale".into()),
        },
    )
    .unwrap();
}

#[test]
fn get_calls_endpoint() {
    let mut s = Server::new();
    let _m = s
        .mock("GET", "/transactions/5")
        .with_status(200)
        .with_body(ok(json!({ "id": 5, "invoice_no": "INV-5" })))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    transaction::execute(&c, TransactionCmd::Get { id: 5 }).unwrap();
}

#[test]
fn create_with_items() {
    let mut s = Server::new();
    let _m = s
        .mock("POST", "/transactions")
        .match_body(mockito::Matcher::PartialJson(json!({
            "customer_id": 1,
            "type": "sale",
            "items": [
                { "product_id": 1, "qty": 2 },
                { "product_id": 3, "qty": 1 }
            ]
        })))
        .with_status(201)
        .with_body(ok(json!({ "id": 10, "invoice_no": "INV-XYZ" })))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    transaction::execute(
        &c,
        TransactionCmd::Create {
            customer_id: Some(1),
            type_: Some("sale".into()),
            paid_amount: None,
            status: None,
            notes: None,
            items: vec!["1:2".into(), "3:1".into()],
        },
    )
    .unwrap();
}

#[test]
fn create_without_items_errors() {
    let s = Server::new();
    let c = ApiClient::new(s.url()).unwrap();
    let e = transaction::execute(
        &c,
        TransactionCmd::Create {
            customer_id: Some(1),
            type_: None,
            paid_amount: None,
            status: None,
            notes: None,
            items: vec![],
        },
    )
    .unwrap_err()
    .to_string();
    assert!(e.contains("at least one"));
}

#[test]
fn create_insufficient_stock_errors() {
    let mut s = Server::new();
    let _m = s
        .mock("POST", "/transactions")
        .with_status(422)
        .with_body(err("Insufficient stock"))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    assert!(
        transaction::execute(
            &c,
            TransactionCmd::Create {
                customer_id: Some(1),
                type_: None,
                paid_amount: None,
                status: None,
                notes: None,
                items: vec!["1:99999".into()],
            }
        )
        .is_err()
    );
}

#[test]
fn update_status_minimal() {
    let mut s = Server::new();
    let _m = s
        .mock("PUT", "/transactions/5/status")
        .match_body(mockito::Matcher::PartialJson(json!({ "status": "paid" })))
        .with_status(200)
        .with_body(ok(json!(null)))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    transaction::execute(
        &c,
        TransactionCmd::UpdateStatus {
            id: 5,
            status: Some("paid".into()),
            paid_amount: None,
        },
    )
    .unwrap();
}

#[test]
fn update_status_with_paid_amount() {
    let mut s = Server::new();
    let _m = s
        .mock("PUT", "/transactions/5/status")
        .match_body(mockito::Matcher::PartialJson(json!({
            "status": "paid",
            "paid_amount": 26000.0
        })))
        .with_status(200)
        .with_body(ok(json!(null)))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    transaction::execute(
        &c,
        TransactionCmd::UpdateStatus {
            id: 5,
            status: Some("paid".into()),
            paid_amount: Some(26000.0),
        },
    )
    .unwrap();
}
