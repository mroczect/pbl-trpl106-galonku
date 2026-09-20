use gci::commands::stock::StockFilter;
use gci::commands::transaction::parse_items;
use gci::output::{cell_value, pad};
use gci::prompt::ask_opt;
use serde_json::json;

// ---------- parse_items ----------

#[test]
fn parse_items_single() {
    let v = parse_items(&["1:2".into()]).unwrap();
    assert_eq!(v.len(), 1);
    assert_eq!(v[0]["product_id"], 1);
    assert_eq!(v[0]["qty"], 2);
}

#[test]
fn parse_items_multiple() {
    let v = parse_items(&["1:2".into(), "3:4".into()]).unwrap();
    assert_eq!(v.len(), 2);
    assert_eq!(v[1]["product_id"], 3);
    assert_eq!(v[1]["qty"], 4);
}

#[test]
fn parse_items_empty_input() {
    assert!(parse_items(&[]).unwrap().is_empty());
}

#[test]
fn parse_items_rejects_missing_colon() {
    assert!(parse_items(&["1".into()]).is_err());
}

#[test]
fn parse_items_rejects_extra_colon() {
    assert!(parse_items(&["1:2:3".into()]).is_err());
}

#[test]
fn parse_items_rejects_non_numeric_product() {
    assert!(parse_items(&["abc:2".into()]).is_err());
}

#[test]
fn parse_items_rejects_non_numeric_qty() {
    assert!(parse_items(&["1:x".into()]).is_err());
}

#[test]
fn parse_items_negative_qty_accepted_by_parser() {
    // parser sendiri tidak memvalidasi qty > 0; backend yang menolak
    assert!(parse_items(&["1:-1".into()]).is_ok());
}

// ---------- StockFilter ----------

fn empty_filter() -> StockFilter {
    StockFilter {
        product_id: None,
        type_: None,
        user_id: None,
        from: None,
        to: None,
        page: None,
        per_page: None,
    }
}

#[test]
fn stock_filter_empty() {
    assert!(empty_filter().to_query().is_empty());
}

#[test]
fn stock_filter_single_product() {
    let mut f = empty_filter();
    f.product_id = Some(7);
    assert_eq!(f.to_query(), vec![("product_id", "7".into())]);
}

#[test]
fn stock_filter_single_type() {
    let mut f = empty_filter();
    f.type_ = Some("out".into());
    assert_eq!(f.to_query(), vec![("type", "out".into())]);
}

#[test]
fn stock_filter_single_user() {
    let mut f = empty_filter();
    f.user_id = Some(2);
    assert_eq!(f.to_query(), vec![("user_id", "2".into())]);
}

#[test]
fn stock_filter_from_to() {
    let mut f = empty_filter();
    f.from = Some("2026-01-01".into());
    f.to = Some("2026-12-31".into());
    assert_eq!(
        f.to_query(),
        vec![("from", "2026-01-01".into()), ("to", "2026-12-31".into()),]
    );
}

#[test]
fn stock_filter_pagination() {
    let mut f = empty_filter();
    f.page = Some(2);
    f.per_page = Some(30);
    assert_eq!(
        f.to_query(),
        vec![("page", "2".into()), ("per_page", "30".into())]
    );
}

#[test]
fn stock_filter_all_fields_order() {
    let f = StockFilter {
        product_id: Some(1),
        type_: Some("out".into()),
        user_id: Some(2),
        from: Some("a".into()),
        to: Some("b".into()),
        page: Some(3),
        per_page: Some(4),
    };
    let q = f.to_query();
    assert_eq!(q.len(), 7);
    assert_eq!(q[0].0, "product_id");
    assert_eq!(q[1].0, "type");
    assert_eq!(q[2].0, "user_id");
    assert_eq!(q[3].0, "from");
    assert_eq!(q[4].0, "to");
    assert_eq!(q[5].0, "page");
    assert_eq!(q[6].0, "per_page");
}

// ---------- output::pad ----------

#[test]
fn pad_shorter() {
    assert_eq!(pad("hi", 5), "hi   ");
}

#[test]
fn pad_equal() {
    assert_eq!(pad("hello", 5), "hello");
}

#[test]
fn pad_longer() {
    assert_eq!(pad("longer", 3), "longer");
}

#[test]
fn pad_empty() {
    assert_eq!(pad("", 3), "   ");
}

#[test]
fn pad_unicode_counts_chars() {
    assert_eq!(pad("日本", 4), "日本  ");
}

// ---------- output::cell_value ----------

#[test]
fn cell_string() {
    assert_eq!(cell_value(&json!({"a": "x"}), "a"), "x");
}

#[test]
fn cell_integer() {
    assert_eq!(cell_value(&json!({"a": 42}), "a"), "42");
}

#[test]
fn cell_float() {
    assert_eq!(cell_value(&json!({"a": 1.5}), "a"), "1.5");
}

#[test]
fn cell_bool_true() {
    assert_eq!(cell_value(&json!({"a": true}), "a"), "true");
}

#[test]
fn cell_bool_false() {
    assert_eq!(cell_value(&json!({"a": false}), "a"), "false");
}

#[test]
fn cell_null() {
    assert_eq!(cell_value(&json!({"a": null}), "a"), "-");
}

#[test]
fn cell_missing_key() {
    assert_eq!(cell_value(&json!({}), "missing"), "-");
}

#[test]
fn cell_object() {
    assert_eq!(cell_value(&json!({"a": {"b": 1}}), "a"), r#"{"b":1}"#);
}

#[test]
fn cell_array() {
    assert_eq!(cell_value(&json!({"a": [1, 2]}), "a"), "[1,2]");
}

// ---------- prompt::ask_opt ----------

#[test]
fn ask_opt_returns_provided() {
    assert_eq!(ask_opt("X", Some("hello".into())).unwrap(), "hello");
}
