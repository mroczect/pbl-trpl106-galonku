mod common;

use common::{err, ok};
use gci::cli::StockCmd;
use gci::client::ApiClient;
use gci::commands::stock;
use mockito::Server;
use serde_json::json;

#[test]
fn list_no_filter() {
    let mut s = Server::new();
    let _m = s
        .mock("GET", "/stock-movements")
        .with_status(200)
        .with_body(ok(json!([{ "id": 1, "type": "out" }])))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    stock::execute(
        &c,
        StockCmd::List {
            product_id: None,
            type_: None,
            user_id: None,
            from: None,
            to: None,
            page: None,
            per_page: None,
        },
    )
    .unwrap();
}

#[test]
fn list_with_filters() {
    let mut s = Server::new();
    let _m = s
        .mock("GET", "/stock-movements")
        .match_query(mockito::Matcher::AllOf(vec![
            mockito::Matcher::UrlEncoded("product_id".into(), "1".into()),
            mockito::Matcher::UrlEncoded("type".into(), "out".into()),
            mockito::Matcher::UrlEncoded("from".into(), "2026-01-01".into()),
        ]))
        .with_status(200)
        .with_body(ok(json!([])))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    stock::execute(
        &c,
        StockCmd::List {
            product_id: Some(1),
            type_: Some("out".into()),
            user_id: None,
            from: Some("2026-01-01".into()),
            to: None,
            page: None,
            per_page: None,
        },
    )
    .unwrap();
}

#[test]
fn adjust_sends_correct_body() {
    let mut s = Server::new();
    let _m = s
        .mock("POST", "/stock-movements/adjust")
        .match_body(mockito::Matcher::PartialJson(json!({
            "product_id": 1,
            "new_stock": 45,
            "reason": "Stock opname"
        })))
        .with_status(200)
        .with_body(ok(json!({ "before": 48, "after": 45, "changed": true })))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    stock::execute(
        &c,
        StockCmd::Adjust {
            product_id: Some(1),
            new_stock: Some(45),
            reason: Some("Stock opname".into()),
            notes: None,
        },
    )
    .unwrap();
}

#[test]
fn adjust_with_notes() {
    let mut s = Server::new();
    let _m = s
        .mock("POST", "/stock-movements/adjust")
        .match_body(mockito::Matcher::PartialJson(json!({ "notes": "3 bocor" })))
        .with_status(200)
        .with_body(ok(json!({ "before": 48, "after": 45, "changed": true })))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    stock::execute(
        &c,
        StockCmd::Adjust {
            product_id: Some(1),
            new_stock: Some(45),
            reason: Some("opname".into()),
            notes: Some("3 bocor".into()),
        },
    )
    .unwrap();
}

#[test]
fn adjust_product_not_found_errors() {
    let mut s = Server::new();
    let _m = s
        .mock("POST", "/stock-movements/adjust")
        .with_status(404)
        .with_body(err("Product not found"))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    assert!(
        stock::execute(
            &c,
            StockCmd::Adjust {
                product_id: Some(99),
                new_stock: Some(10),
                reason: Some("opname".into()),
                notes: None,
            }
        )
        .is_err()
    );
}

#[test]
fn adjust_negative_stock_validation_error() {
    let mut s = Server::new();
    let _m = s
        .mock("POST", "/stock-movements/adjust")
        .with_status(422)
        .with_body(err("Validation failed"))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    assert!(
        stock::execute(
            &c,
            StockCmd::Adjust {
                product_id: Some(1),
                new_stock: Some(-1),
                reason: Some("x".into()),
                notes: None,
            }
        )
        .is_err()
    );
}
