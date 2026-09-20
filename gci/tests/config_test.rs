mod common;

use common::with_tmp_config_dir;
use gci::config::{self, Session};
use serial_test::serial;

#[test]
#[serial]
fn load_returns_none_when_missing() {
    with_tmp_config_dir(|| {
        assert!(config::load().unwrap().is_none());
    });
}

#[test]
#[serial]
fn save_then_load_roundtrip() {
    with_tmp_config_dir(|| {
        let s = Session {
            access_token: Some("A".into()),
            refresh_token: Some("R".into()),
            user: Some(serde_json::json!({"id": 1, "name": "Admin"})),
        };
        config::save(&s).unwrap();

        let loaded = config::load().unwrap().unwrap();
        assert_eq!(loaded.access_token.as_deref(), Some("A"));
        assert_eq!(loaded.refresh_token.as_deref(), Some("R"));
        assert_eq!(loaded.user.unwrap()["name"], "Admin");
    });
}

#[test]
#[serial]
fn save_overwrites_previous() {
    with_tmp_config_dir(|| {
        config::save(&Session {
            access_token: Some("first".into()),
            ..Default::default()
        })
        .unwrap();
        config::save(&Session {
            access_token: Some("second".into()),
            ..Default::default()
        })
        .unwrap();
        assert_eq!(
            config::load().unwrap().unwrap().access_token.as_deref(),
            Some("second")
        );
    });
}

#[test]
#[serial]
fn clear_removes_file() {
    with_tmp_config_dir(|| {
        config::save(&Session {
            access_token: Some("x".into()),
            ..Default::default()
        })
        .unwrap();
        assert!(config::load().unwrap().is_some());
        config::clear().unwrap();
        assert!(config::load().unwrap().is_none());
    });
}

#[test]
#[serial]
fn clear_on_missing_is_ok() {
    with_tmp_config_dir(|| {
        config::clear().unwrap();
        config::clear().unwrap();
    });
}

#[test]
#[serial]
fn default_session_has_none_fields() {
    let s = Session::default();
    assert!(s.access_token.is_none());
    assert!(s.refresh_token.is_none());
    assert!(s.user.is_none());
}

#[test]
#[serial]
fn session_path_under_config_dir() {
    with_tmp_config_dir(|| {
        let p = config::session_path();
        let dir = std::env::var("GCI_CONFIG_DIR").unwrap();
        assert!(p.starts_with(&dir));
        assert!(p.to_string_lossy().ends_with("session.json"));
    });
}
