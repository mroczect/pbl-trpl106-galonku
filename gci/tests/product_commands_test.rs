mod common;

use common::{err, ok};
use gci::cli::ProductCmd;
use gci::client::ApiClient;
use gci::commands::product;
use mockito::Server;
use serde_json::json;

#[test]
fn list_calls_endpoint() {
    let mut s = Server::new();
    let _m = s
        .mock("GET", "/products")
        .with_status(200)
        .with_body(ok(json!([{ "id": 1, "sku": "X" }])))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    product::execute(
        &c,
        ProductCmd::List {
            page: None,
            per_page: None,
            category: None,
        },
    )
    .unwrap();
}

#[test]
fn list_with_category() {
    let mut s = Server::new();
    let _m = s
        .mock("GET", "/products")
        .match_query(mockito::Matcher::UrlEncoded(
            "category".into(),
            "galon".into(),
        ))
        .with_status(200)
        .with_body(ok(json!([])))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    product::execute(
        &c,
        ProductCmd::List {
            page: None,
            per_page: None,
            category: Some("galon".into()),
        },
    )
    .unwrap();
}

#[test]
fn get_calls_endpoint() {
    let mut s = Server::new();
    let _m = s
        .mock("GET", "/products/1")
        .with_status(200)
        .with_body(ok(json!({ "id": 1, "sku": "X" })))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    product::execute(&c, ProductCmd::Get { id: 1 }).unwrap();
}

#[test]
fn low_stock_default_threshold() {
    let mut s = Server::new();
    let _m = s
        .mock("GET", "/products/low-stock")
        .match_query(mockito::Matcher::UrlEncoded(
            "threshold".into(),
            "10".into(),
        ))
        .with_status(200)
        .with_body(ok(json!([])))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    product::execute(&c, ProductCmd::LowStock { threshold: 10 }).unwrap();
}

#[test]
fn low_stock_custom_threshold() {
    let mut s = Server::new();
    let _m = s
        .mock("GET", "/products/low-stock")
        .match_query(mockito::Matcher::UrlEncoded(
            "threshold".into(),
            "25".into(),
        ))
        .with_status(200)
        .with_body(ok(json!([])))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    product::execute(&c, ProductCmd::LowStock { threshold: 25 }).unwrap();
}

#[test]
fn create_sends_correct_body() {
    let mut s = Server::new();
    let _m = s
        .mock("POST", "/products")
        .match_body(mockito::Matcher::PartialJson(json!({
            "sku": "NEW-1",
            "name": "Produk Baru",
            "price": 15000.0,
            "category": "galon",
            "stock": 10
        })))
        .with_status(201)
        .with_body(ok(json!({ "id": 9 })))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    product::execute(
        &c,
        ProductCmd::Create {
            sku: Some("NEW-1".into()),
            name: Some("Produk Baru".into()),
            price: Some(15000.0),
            category: Some("galon".into()),
            stock: Some(10),
        },
    )
    .unwrap();
}

#[test]
fn create_409_errors() {
    let mut s = Server::new();
    let _m = s
        .mock("POST", "/products")
        .with_status(409)
        .with_body(err("SKU already used"))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    assert!(
        product::execute(
            &c,
            ProductCmd::Create {
                sku: Some("X".into()),
                name: Some("Y".into()),
                price: Some(1.0),
                category: None,
                stock: None,
            }
        )
        .is_err()
    );
}

#[test]
fn update_sends_partial_body() {
    let mut s = Server::new();
    let _m = s
        .mock("PUT", "/products/1")
        .match_body(mockito::Matcher::PartialJson(json!({ "price": 25000.0 })))
        .with_status(200)
        .with_body(ok(json!(null)))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    product::execute(
        &c,
        ProductCmd::Update {
            id: 1,
            name: None,
            category: None,
            price: Some(25000.0),
            stock: None,
            is_active: None,
        },
    )
    .unwrap();
}

#[test]
fn update_empty_body_errors() {
    let s = Server::new();
    let c = ApiClient::new(s.url()).unwrap();
    let e = product::execute(
        &c,
        ProductCmd::Update {
            id: 1,
            name: None,
            category: None,
            price: None,
            stock: None,
            is_active: None,
        },
    )
    .unwrap_err()
    .to_string();
    assert!(e.contains("no fields"));
}

#[test]
fn update_is_active_true_is_1() {
    let mut s = Server::new();
    let _m = s
        .mock("PUT", "/products/1")
        .match_body(mockito::Matcher::PartialJson(json!({ "is_active": 1 })))
        .with_status(200)
        .with_body(ok(json!(null)))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    product::execute(
        &c,
        ProductCmd::Update {
            id: 1,
            name: None,
            category: None,
            price: None,
            stock: None,
            is_active: Some(true),
        },
    )
    .unwrap();
}

#[test]
fn update_is_active_false_is_0() {
    let mut s = Server::new();
    let _m = s
        .mock("PUT", "/products/1")
        .match_body(mockito::Matcher::PartialJson(json!({ "is_active": 0 })))
        .with_status(200)
        .with_body(ok(json!(null)))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    product::execute(
        &c,
        ProductCmd::Update {
            id: 1,
            name: None,
            category: None,
            price: None,
            stock: None,
            is_active: Some(false),
        },
    )
    .unwrap();
}

#[test]
fn delete_calls_endpoint() {
    let mut s = Server::new();
    let _m = s
        .mock("DELETE", "/products/1")
        .with_status(200)
        .with_body(ok(json!(null)))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    product::execute(&c, ProductCmd::Delete { id: 1 }).unwrap();
}

#[test]
fn delete_404_errors() {
    let mut s = Server::new();
    let _m = s
        .mock("DELETE", "/products/99")
        .with_status(404)
        .with_body(err("Not found"))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    assert!(product::execute(&c, ProductCmd::Delete { id: 99 }).is_err());
}
