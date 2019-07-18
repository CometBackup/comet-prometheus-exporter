<?php

// Mini standalone Prometheus exporter for Comet Server
// Copyright (2019) Comet Backup.com Ltd
// License: MIT
//
// To use this script:
// 1. update the connection details below
// 2. serve on `/metrics` endpoint (i.e. by saving as a /metrics/index.php file)
// 3. configure prometheus to look at /metrics/

define('COMET_SERVER', 'http://127.0.0.1/');
define('COMET_ADMIN_USER', 'admin');
define('COMET_ADMIN_PASS', 'admin');


/**
 * Make an HTTP request to the Comet Server
 *
 * @ref https://cometbackup.com/docs/api#php-curl
 * @return {string|boolean} HTTP response body, or false on cURL failure
 */
function comet_request($endpoint, array $params=[]) {		
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, COMET_SERVER.'api/v1/admin/list-users');
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array_merge(['Username' => COMET_ADMIN_USER, 'AuthType' => 'Password', 'Password' => COMET_ADMIN_PASS], $params));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch);
	curl_close($ch);
	return $response;
}


// Load some basic information using the Comet Server API
// @ref https://cometbackup.com/docs/api-reference#adminlistusers-list-all-user-accounts

$users = json_decode(comet_request('api/v1/admin/list-users'), true);
$total_users = count($users);


// Display the results in Promethus text exposition format
// @ref https://prometheus.io/docs/instrumenting/exposition_formats/ 

header('Content-Type: text/plain; version=0.0.4');

echo "# HELP cometserver_total_users Total number of users on this Comet Server\n";
echo "# TYPE cometserver_total_users gauge\n";
echo "cometserver_total_users {$total_users}\n";
