# Standalone Prometheus exporter for Comet Server

[![@CometBackup on Twitter](http://img.shields.io/badge/twitter-%40CometBackup-blue.svg?style=flat)](https://twitter.com/CometBackup)

This is a sample PHP script that serves a Prometheus-compatible `/metrics/` endpoint. It pulls information from a Comet Server on-demand.

[![](doc/screenshot.thumb.jpg)](doc/screenshot.png)

This exporter is made available under the terms of the MIT license. You are free to use and modify this script for any purpose as long as you retain the copyright notice in the `LICENSE` file and/or elsewhere in this repository.

## Metrics

This exporter produces the following metrics:

|Name|Type|Description
|---|---|---
|`cometserver_users_total`|Gauge|Total number of users on this Comet Server
|`cometserver_device_is_online{username=,device_id=,device_friendly_name=}=0/1`|Gauge|For every registered device, 0/1 for offline/online
|`cometserver_recentjobs_total{status=...}`|Gauge|Total number of jobs in the last 24 hours

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
