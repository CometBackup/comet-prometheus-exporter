<?php

// Mini standalone Prometheus exporter for Comet Server
// Copyright (2019) Comet Backup.com Ltd
// License: MIT

// Page entry point

require '../../vendor/autoload.php';

$exporter = new CometPrometheusExporter\CometPrometheusExporter(
    getenv('COMET_SERVER_URL'),
    getenv('COMET_ADMIN_USER'),
    getenv('COMET_ADMIN_PASS')
);
$metrics = $exporter->metrics();

header('Content-Type: '.\Prometheus\RenderTextFormat::MIME_TYPE);
echo $metrics;
die();
