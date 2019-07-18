# Standalone Prometheus exporter for Comet Server

[![@CometBackup on Twitter](http://img.shields.io/badge/twitter-%40CometBackup-blue.svg?style=flat)](https://twitter.com/CometBackup)

This is a sample PHP script that serves a Prometheus-compatible `/metrics/` endpoint. It pulls information from a Comet Server on-demand.

## Usage

```bash
# Build Docker image
docker build . -t comet-prometheus-exporter:latest

# Run container instance against target Comet Server
docker run -d --restart=always -e COMET_SERVER_URL="http://127.0.0.1/" -e COMET_ADMIN_USER="admin" -e COMET_ADMIN_PASS="admin" -p 80:80 comet-prometheus-exporter:latest
```
