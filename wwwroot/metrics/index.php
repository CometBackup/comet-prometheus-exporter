<?php

// Mini standalone Prometheus exporter for Comet Server
// Copyright (2019) Comet Backup.com Ltd
// License: MIT

require '../vendor/autoload.php';

$cs = new \Comet\Server(
    getenv('COMET_SERVER_URL'),
    getenv('COMET_ADMIN_USER'),
    getenv('COMET_ADMIN_PASS')
);

// Load some basic information using the Comet Server API
// @ref https://cometbackup.com/docs/api-reference#adminlistusers-list-all-user-accounts

$users = $cs->AdminListUsers();
$total_users = count($users);


// Display the results in Promethus text exposition format
// @ref https://prometheus.io/docs/instrumenting/exposition_formats/ 

header('Content-Type: text/plain; version=0.0.4');

echo "# HELP cometserver_total_users Total number of users on this Comet Server\n";
echo "# TYPE cometserver_total_users gauge\n";
echo "cometserver_total_users {$total_users}\n";
