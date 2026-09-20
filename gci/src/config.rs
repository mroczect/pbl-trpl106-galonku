use std::fs;
use std::path::PathBuf;

use anyhow::Result;
use directories::ProjectDirs;
use serde::{Deserialize, Serialize};

#[derive(Serialize, Deserialize, Debug, Default)]
pub struct Session {
    pub access_token: Option<String>,
    pub refresh_token: Option<String>,
    pub user: Option<serde_json::Value>,
}

fn config_dir() -> PathBuf {
    if let Ok(dir) = std::env::var("GCI_CONFIG_DIR")
        && !dir.is_empty()
    {
        return PathBuf::from(dir);
    }
    ProjectDirs::from("com", "galonku", "gci")
        .map(|d| d.config_dir().to_path_buf())
        .unwrap_or_else(|| PathBuf::from(".gci"))
}

pub fn session_path() -> PathBuf {
    config_dir().join("session.json")
}

pub fn load() -> Result<Option<Session>> {
    let path = session_path();
    if !path.exists() {
        return Ok(None);
    }
    let text = fs::read_to_string(&path)?;
    Ok(Some(serde_json::from_str(&text)?))
}

pub fn save(s: &Session) -> Result<()> {
    let dir = config_dir();
    fs::create_dir_all(&dir)?;
    fs::write(session_path(), serde_json::to_string_pretty(s)?)?;
    Ok(())
}

pub fn clear() -> Result<()> {
    let path = session_path();
    if path.exists() {
        fs::remove_file(path)?;
    }
    Ok(())
}
