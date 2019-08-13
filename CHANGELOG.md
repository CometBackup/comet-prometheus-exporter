# Changelog

## v0.5.0 (2019-08-13)

- BREAKING: Add zero values for `cometserver_lastbackup_status` enum-style metric
- Feature: Add `vault_name`, `device_name`, `protected_item_name` labels to the `storagevault_*`, `device_is_*`, and `lastbackup_*` metrics respectively
- Enhancement: Document recommended development environment

## v0.4.0 (2019-07-30)

- Feature: Add `cometserver_lastbackup_status` enum-style metric

## v0.3.0 (2019-07-25)

- BREAKING: Remove `device_friendly_name` label from `cometserver_device_is_online` metric
- Feature: Add `cometserver_api_lookup_duration` metric
- Feature: Add `cometserver_device_is_current` per-device metric
- Feature: Add `cometserver_storagevault_size_bytes` and `cometserver_storagevault_quota_bytes` per-Storage Vault metrics
- Feature: Add `cometserver_lastbackup_` metrics (start time, end time, file count, file size, upload size, download size)
- Enhancement: Improve performance by enabling HTTP body gzip in Docker container
- Fix a cosmetic issue with mixed-content warnings loading the Shield badge in the README.md file

## v0.2.0 (2019-07-22)

- BREAKING: Remove `cometserver_recentjobs_total` and `cometserver_online_devices` metrics. Suggest PromQL `SUM()` as replacement
- BREAKING: Rename `cometserver_total_users` to `cometserver_users_total`
- BREAKING: Rename `cometserver_recentjobs` to `cometserver_recentjobs_total`
- Feature: New `cometserver_device_is_online` metric for per-device online/offline state
- Convert to `jimdo/prometheus_client` library (Apache-2.0 license)

## v0.1.0 (2019-07-19)

- Initial public release
