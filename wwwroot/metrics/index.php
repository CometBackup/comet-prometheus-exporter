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

$registry = new \Prometheus\CollectorRegistry(new \Prometheus\Storage\InMemory());


// Load some basic information using the Comet Server API

$users = $cs->AdminListUsersFull();

$online_devices = $cs->AdminDispatcherListActive();

$recentjobs = $cs->AdminGetJobsForDateRange(time() - 86400, time()); // Jobs from the last 24 hours


// Metric
// Total number of users on the server

$gauge = $registry->getOrRegisterGauge('cometserver', 'total_users', 'The total number of users on this Comet Server', []);
$gauge->set(count($users), []);


// Metric
// Categorise recent job counts, to report on them separately as well as in aggregate

$recentjobs_gauge = $registry->getOrRegisterGauge("cometserver", "recentjobs", "Total number of jobs in the last 24 hours", ['status']);

$recentjobs_gauge->set(0, ['success']);
$recentjobs_gauge->set(0, ['running']);
$recentjobs_gauge->set(0, ['warning']);
$recentjobs_gauge->set(0, ['quota_exceeded']);
$recentjobs_gauge->set(0, ['error']);

foreach($recentjobs as $job) {
    if ($job->Status >= \Comet\Def::JOB_STATUS_STOP_SUCCESS__MIN && $job->Status <= \Comet\Def::JOB_STATUS_STOP_SUCCESS__MAX) {
        $recentjobs_gauge->inc(['success']);

    } else if ($job->Status >= \Comet\Def::JOB_STATUS_RUNNING__MIN && $job->Status <= \Comet\Def::JOB_STATUS_RUNNING__MAX) {
        $recentjobs_gauge->inc(['running']);

    } else if ($job->Status == \Comet\Def::JOB_STATUS_FAILED_WARNING) {
        $recentjobs_gauge->inc(['warning']);

    } else if ($job->Status == \Comet\Def::JOB_STATUS_FAILED_QUOTA) {
        $recentjobs_gauge->inc(['quota_exceeded']);

    } else {
        $recentjobs_gauge->inc(['error']);

    }
}


// Metric
// Online/offline status of each device

$deviceGauge = $registry->getOrRegisterGauge('cometserver', 'device', "The online/offline status of each registered device", ['username', 'device_id', 'device_friendly_name']);

$device_is_online_lookup = []; // Build inverted index of online devices for traversal
foreach($online_devices as $live_connection) {
    $key = $live_connection->Username . "\x00" . $live_connection->DeviceID;
    $device_is_online_lookup[$key] = true;
}

foreach($users as $username => $user) {
    foreach($user->Devices as $device_id => $device) {
        $is_online = array_key_exists($username . "\x00" . $device_id, $device_is_online_lookup);
        $deviceGauge->set($is_online ? 1 : 0, [$username, $device_id, $device->FriendlyName]);
    }
}


// Render result

$renderer = new \Prometheus\RenderTextFormat();
$result = $renderer->render($registry->getMetricFamilySamples());

header('Content-Type: '.\Prometheus\RenderTextFormat::MIME_TYPE);
echo $result;

die();
