mod common;

use common::{err, ok, with_tmp_config_dir};
use gci::client::ApiClient;
use gci::commands::auth;
use gci::config::{self, Session};
use mockito::Server;
use serde_json::json;
use serial_test::serial;

#[test]
#[serial]
fn login_saves_session() {
    with_tmp_config_dir(|| {
        let mut s = Server::new();
        let _m = s
            .mock("POST", "/auth/login")
            .with_status(200)
            .with_body(ok(json!({
                "user": { "id": 1, "name": "Admin" },
                "access_token": "A",
                "refresh_token": "R"
            })))
            .create();

        let c = ApiClient::new(s.url()).unwrap();
        auth::login(&c, Some("a@b.com".into()), Some("pw".into())).unwrap();

        let sess = config::load().unwrap().unwrap();
        assert_eq!(sess.access_token.as_deref(), Some("A"));
        assert_eq!(sess.refresh_token.as_deref(), Some("R"));
        assert_eq!(sess.user.unwrap()["name"], "Admin");
    });
}

#[test]
#[serial]
fn login_bad_credentials_errors() {
    with_tmp_config_dir(|| {
        let mut s = Server::new();
        let _m = s
            .mock("POST", "/auth/login")
            .with_status(401)
            .with_body(err("Invalid email or password"))
            .create();

        let c = ApiClient::new(s.url()).unwrap();
        let e = auth::login(&c, Some("a@b.com".into()), Some("bad".into()))
            .unwrap_err()
            .to_string();
        assert!(e.contains("401"));
        assert!(config::load().unwrap().is_none());
    });
}

#[test]
#[serial]
fn register_success_no_session_write() {
    with_tmp_config_dir(|| {
        let mut s = Server::new();
        let _m = s
            .mock("POST", "/auth/register")
            .with_status(201)
            .with_body(ok(json!({ "id": 5 })))
            .create();

        let c = ApiClient::new(s.url()).unwrap();
        auth::register(
            &c,
            Some("Budi".into()),
            Some("b@c.com".into()),
            Some("pw".into()),
            None,
        )
        .unwrap();

        assert!(config::load().unwrap().is_none());
    });
}

#[test]
#[serial]
fn register_with_phone() {
    with_tmp_config_dir(|| {
        let mut s = Server::new();
        let _m = s
            .mock("POST", "/auth/register")
            .match_body(mockito::Matcher::PartialJson(json!({"phone": "0800"})))
            .with_status(201)
            .with_body(ok(json!({ "id": 6 })))
            .create();

        let c = ApiClient::new(s.url()).unwrap();
        auth::register(
            &c,
            Some("A".into()),
            Some("a@b.com".into()),
            Some("pw".into()),
            Some("0800".into()),
        )
        .unwrap();
    });
}

#[test]
#[serial]
fn register_409_errors() {
    with_tmp_config_dir(|| {
        let mut s = Server::new();
        let _m = s
            .mock("POST", "/auth/register")
            .with_status(409)
            .with_body(err("Email already registered"))
            .create();

        let c = ApiClient::new(s.url()).unwrap();
        assert!(
            auth::register(
                &c,
                Some("A".into()),
                Some("a@b.com".into()),
                Some("pw".into()),
                None
            )
            .is_err()
        );
    });
}

#[test]
#[serial]
fn logout_clears_session() {
    with_tmp_config_dir(|| {
        config::save(&Session {
            access_token: Some("A".into()),
            refresh_token: Some("R".into()),
            user: None,
        })
        .unwrap();

        let mut s = Server::new();
        let _m = s
            .mock("POST", "/auth/logout")
            .with_status(200)
            .with_body(ok(json!(null)))
            .create();

        let c = ApiClient::new(s.url()).unwrap();
        auth::logout(&c).unwrap();
        assert!(config::load().unwrap().is_none());
    });
}

#[test]
#[serial]
fn logout_clears_even_if_server_errors() {
    with_tmp_config_dir(|| {
        config::save(&Session {
            access_token: Some("A".into()),
            refresh_token: Some("R".into()),
            user: None,
        })
        .unwrap();

        let mut s = Server::new();
        let _m = s
            .mock("POST", "/auth/logout")
            .with_status(500)
            .with_body(err("boom"))
            .create();

        let c = ApiClient::new(s.url()).unwrap();
        auth::logout(&c).unwrap();
        assert!(config::load().unwrap().is_none());
    });
}

#[test]
#[serial]
fn me_calls_endpoint() {
    with_tmp_config_dir(|| {
        let mut s = Server::new();
        let _m = s
            .mock("GET", "/auth/me")
            .with_status(200)
            .with_body(ok(json!({ "id": 1, "email": "a@b.com" })))
            .create();

        let c = ApiClient::new(s.url()).unwrap();
        auth::me(&c).unwrap();
    });
}

#[test]
#[serial]
fn refresh_without_session_errors() {
    with_tmp_config_dir(|| {
        let s = Server::new();
        let c = ApiClient::new(s.url()).unwrap();
        let e = auth::refresh(&c).unwrap_err().to_string();
        assert!(e.contains("no session"));
    });
}

#[test]
#[serial]
fn refresh_updates_session() {
    with_tmp_config_dir(|| {
        config::save(&Session {
            access_token: Some("oldA".into()),
            refresh_token: Some("oldR".into()),
            user: None,
        })
        .unwrap();

        let mut s = Server::new();
        let _m = s
            .mock("POST", "/auth/refresh")
            .with_status(200)
            .with_body(ok(json!({
                "access_token": "newA",
                "refresh_token": "newR"
            })))
            .create();

        let c = ApiClient::new(s.url()).unwrap();
        auth::refresh(&c).unwrap();

        let sess = config::load().unwrap().unwrap();
        assert_eq!(sess.access_token.as_deref(), Some("newA"));
        assert_eq!(sess.refresh_token.as_deref(), Some("newR"));
    });
}

#[test]
#[serial]
fn refresh_with_invalid_token_errors() {
    with_tmp_config_dir(|| {
        config::save(&Session {
            access_token: Some("A".into()),
            refresh_token: Some("R".into()),
            user: None,
        })
        .unwrap();

        let mut s = Server::new();
        let _m = s
            .mock("POST", "/auth/refresh")
            .with_status(401)
            .with_body(err("Invalid refresh token"))
            .create();

        let c = ApiClient::new(s.url()).unwrap();
        assert!(auth::refresh(&c).is_err());
    });
}
