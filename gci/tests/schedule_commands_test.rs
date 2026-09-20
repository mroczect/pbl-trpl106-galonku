mod common;

use common::{err, ok};
use gci::cli::ScheduleCmd;
use gci::client::ApiClient;
use gci::commands::schedule;
use mockito::Server;
use serde_json::json;

#[test]
fn list_calls_endpoint() {
    let mut s = Server::new();
    let _m = s
        .mock("GET", "/schedules")
        .with_status(200)
        .with_body(ok(json!([{ "id": 1, "status": "pending" }])))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    schedule::execute(
        &c,
        ScheduleCmd::List {
            page: None,
            per_page: None,
            status: None,
            user_id: None,
            customer_id: None,
        },
    )
    .unwrap();
}

#[test]
fn list_with_filters() {
    let mut s = Server::new();
    let _m = s
        .mock("GET", "/schedules")
        .match_query(mockito::Matcher::AllOf(vec![
            mockito::Matcher::UrlEncoded("status".into(), "pending".into()),
            mockito::Matcher::UrlEncoded("user_id".into(), "2".into()),
        ]))
        .with_status(200)
        .with_body(ok(json!([])))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    schedule::execute(
        &c,
        ScheduleCmd::List {
            page: None,
            per_page: None,
            status: Some("pending".into()),
            user_id: Some(2),
            customer_id: None,
        },
    )
    .unwrap();
}

#[test]
fn get_calls_endpoint() {
    let mut s = Server::new();
    let _m = s
        .mock("GET", "/schedules/1")
        .with_status(200)
        .with_body(ok(json!({ "id": 1, "status": "pending" })))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    schedule::execute(&c, ScheduleCmd::Get { id: 1 }).unwrap();
}

#[test]
fn create_with_scheduled_at() {
    let mut s = Server::new();
    let _m = s
        .mock("POST", "/schedules")
        .match_body(mockito::Matcher::PartialJson(json!({
            "customer_id": 1,
            "user_id": 2,
            "scheduled_at": "2026-09-22 10:00:00"
        })))
        .with_status(201)
        .with_body(ok(json!({ "id": 5 })))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    schedule::execute(
        &c,
        ScheduleCmd::Create {
            customer_id: Some(1),
            user_id: Some(2),
            scheduled_at: Some("2026-09-22 10:00:00".into()),
            notes: None,
        },
    )
    .unwrap();
}

#[test]
fn create_past_date_errors() {
    let mut s = Server::new();
    let _m = s
        .mock("POST", "/schedules")
        .with_status(422)
        .with_body(err("scheduled_at must be in the future"))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    assert!(
        schedule::execute(
            &c,
            ScheduleCmd::Create {
                customer_id: Some(1),
                user_id: Some(2),
                scheduled_at: Some("2020-01-01 00:00:00".into()),
                notes: None,
            }
        )
        .is_err()
    );
}

#[test]
fn update_status_on_route() {
    let mut s = Server::new();
    let _m = s
        .mock("PUT", "/schedules/1/status")
        .match_body(mockito::Matcher::PartialJson(
            json!({ "status": "on_route" }),
        ))
        .with_status(200)
        .with_body(ok(json!(null)))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    schedule::execute(
        &c,
        ScheduleCmd::UpdateStatus {
            id: 1,
            status: Some("on_route".into()),
        },
    )
    .unwrap();
}

#[test]
fn update_status_done() {
    let mut s = Server::new();
    let _m = s
        .mock("PUT", "/schedules/3/status")
        .match_body(mockito::Matcher::PartialJson(json!({ "status": "done" })))
        .with_status(200)
        .with_body(ok(json!(null)))
        .create();

    let c = ApiClient::new(s.url()).unwrap();
    schedule::execute(
        &c,
        ScheduleCmd::UpdateStatus {
            id: 3,
            status: Some("done".into()),
        },
    )
    .unwrap();
}
