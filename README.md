# Standalone Prometheus exporter for Comet Server

[![@CometBackup on Twitter](https://img.shields.io/badge/twitter-%40CometBackup-blue.svg?style=flat)](https://twitter.com/CometBackup)

This is a sample PHP script that serves a Prometheus-compatible `/metrics/` endpoint. It pulls information from a Comet Server on-demand.

[![](doc/screenshot.thumb.jpg)](doc/screenshot.png)

This exporter is made available under the terms of the MIT license. You are free to use and modify this script for any purpose as long as you retain the copyright notice in the `LICENSE` file and/or elsewhere in this repository.

## Metrics

This exporter produces the following metrics:

|Name|Type|Description
|---|---|---
|`cometserver_api_lookup_duration`|Gauge|The time required to retrieve data from the Comet Server (ms)
|`cometserver_device_is_current{username,device_id}`|Gauge|Whether each online device is running the current software version (x.x.x)
|`cometserver_device_is_online{username,device_id}`|Gauge|The online/offline status of each registered device
|`cometserver_lastbackup_download_size_bytes{username,protected_item_id}`|Gauge|The size (bytes) downloaded during most recent completed backup job for this Protected Item
|`cometserver_lastbackup_end_time{username,protected_item_id}`|Gauge|The end time of the most recent completed backup job for this Protected Item
|`cometserver_lastbackup_file_count{username,protected_item_id}`|Gauge|The number of files in the most recent completed backup job for this Protected Item
|`cometserver_lastbackup_file_size_bytes{username,protected_item_id}`|Gauge|The size (bytes) of the data selected for backup on disk, as of the most recent completed backup job for this Protected Item
|`cometserver_lastbackup_start_time{username,protected_item_id}`|Gauge|The start time of the most recent completed backup job for this Protected Item
|`cometserver_lastbackup_upload_size_bytes{username,protected_item_id}`|Gauge|The size (bytes) uploaded during most recent completed backup job for this Protected Item
|`cometserver_recentjobs_total{status}`|Gauge|Total number of jobs in the last 24 hours
|`cometserver_storagevault_size_bytes{username,vault_id,vault_type}`|Gauge|The last measured size (in bytes) of each Storage Vault
|`cometserver_storagevault_quota_bytes{username,vault_id,vault_type}`|Gauge|The quota limit for each Storage Vault, if one is set
|`cometserver_users_total`|Gauge|Total number of users on this Comet Server

## Installing

### Running the exporter (Docker)

```bash
# Build Docker image
docker build . -t comet-prometheus-exporter:latest

# Run container instance against target Comet Server
docker run -d --restart=always -e COMET_SERVER_URL="http://127.0.0.1/" -e COMET_ADMIN_USER="admin" -e COMET_ADMIN_PASS="admin" -p 80:80 comet-prometheus-exporter:latest
```

### Running the exporter (without Docker)

1. Install necessary dependencies
    - `php`, `php-curl`
2. Configure web server to host `/wwwroot` directory
3. Configure web server to set configuration environment variables

### Adding to Prometheus

Add the exporter's URL to `prometheus.yml` in the `scrape_configs` > `static_configs` > `targets` array.
