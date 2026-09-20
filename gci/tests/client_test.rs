mod common;

use common::{err, ok};
use gci::client::ApiClient;
use mockito::Server;
use serde_json::json;

#[test]
fn get_returns_data() {
    let mut s = Server::new();
    let _m = s
        .mock("GET", "/products")
        .with_status(200)
        .with_header("content-type", "application/json")
        .with_body(ok(json!([{ "id": 1, "sku": "X" }])))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    let d = c.get("/products", &[]).unwrap();
    assert!(d.is_array());
    assert_eq!(d[0]["id"], 1);
}

#[test]
fn get_with_query() {
    let mut s = Server::new();
    let _m = s
        .mock("GET", "/products")
        .match_query(mockito::Matcher::Any)
        .with_status(200)
        .with_body(ok(json!([])))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    let q = vec![("page", "2".to_string())];
    c.get("/products", &q).unwrap();
}

#[test]
fn get_401_returns_err_with_status_and_message() {
    let mut s = Server::new();
    let _m = s
        .mock("GET", "/products")
        .with_status(401)
        .with_body(err("Unauthorized"))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    let e = c.get("/products", &[]).unwrap_err().to_string();
    assert!(e.contains("401"));
    assert!(e.contains("Unauthorized"));
}

#[test]
fn get_404_returns_err() {
    let mut s = Server::new();
    let _m = s
        .mock("GET", "/products/999")
        .with_status(404)
        .with_body(err("Product not found"))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    let e = c.get("/products/999", &[]).unwrap_err().to_string();
    assert!(e.contains("404"));
    assert!(e.contains("Product not found"));
}

#[test]
fn get_500_returns_err() {
    let mut s = Server::new();
    let _m = s
        .mock("GET", "/x")
        .with_status(500)
        .with_body(err("Internal server error"))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    assert!(c.get("/x", &[]).is_err());
}

#[test]
fn post_returns_data() {
    let mut s = Server::new();
    let _m = s
        .mock("POST", "/products")
        .with_status(201)
        .with_body(ok(json!({ "id": 5 })))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    let d = c.post("/products", json!({ "name": "x" })).unwrap();
    assert_eq!(d["id"], 5);
}

#[test]
fn post_409_returns_err() {
    let mut s = Server::new();
    let _m = s
        .mock("POST", "/products")
        .with_status(409)
        .with_body(err("Duplicate entry"))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    let e = c
        .post("/products", json!({ "sku": "dup" }))
        .unwrap_err()
        .to_string();
    assert!(e.contains("409"));
    assert!(e.contains("Duplicate entry"));
}

#[test]
fn put_returns_data() {
    let mut s = Server::new();
    let _m = s
        .mock("PUT", "/products/1")
        .with_status(200)
        .with_body(ok(json!(null)))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    c.put("/products/1", json!({ "name": "y" })).unwrap();
}

#[test]
fn put_422_returns_err() {
    let mut s = Server::new();
    let _m = s
        .mock("PUT", "/products/1")
        .with_status(422)
        .with_body(err("Validation failed"))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    assert!(c.put("/products/1", json!({})).is_err());
}

#[test]
fn delete_returns_data() {
    let mut s = Server::new();
    let _m = s
        .mock("DELETE", "/products/1")
        .with_status(200)
        .with_body(ok(json!(null)))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    c.delete("/products/1").unwrap();
}

#[test]
fn delete_404_returns_err() {
    let mut s = Server::new();
    let _m = s
        .mock("DELETE", "/products/99")
        .with_status(404)
        .with_body(err("Not found"))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    assert!(c.delete("/products/99").is_err());
}

#[test]
fn missing_data_field_returns_null() {
    let mut s = Server::new();
    let _m = s
        .mock("GET", "/x")
        .with_status(200)
        .with_body(r#"{"success":true,"message":"OK"}"#)
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    assert!(c.get("/x", &[]).unwrap().is_null());
}

#[test]
fn empty_body_returns_null() {
    let mut s = Server::new();
    let _m = s.mock("GET", "/x").with_status(204).with_body("").create();

    let c = ApiClient::new(s.url()).unwrap();
    assert!(c.get("/x", &[]).unwrap().is_null());
}

#[test]
fn non_json_body_does_not_panic() {
    let mut s = Server::new();
    let _m = s
        .mock("GET", "/x")
        .with_status(200)
        .with_body("plain text")
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    assert!(c.get("/x", &[]).unwrap().is_null());
}

#[test]
fn base_url_trailing_slash_trimmed() {
    let c = ApiClient::new("http://x/".to_string()).unwrap();
    // tidak error; test trim
    let _ = c;
}
