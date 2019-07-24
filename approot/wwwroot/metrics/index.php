<?php

// Mini standalone Prometheus exporter for Comet Server
// Copyright (2019) Comet Backup.com Ltd
// License: MIT

require '../../vendor/autoload.php';

$cs = new \Comet\Server(
    getenv('COMET_SERVER_URL'),
    getenv('COMET_ADMIN_USER'),
    getenv('COMET_ADMIN_PASS')
);

$registry = new \Prometheus\CollectorRegistry(new \Prometheus\Storage\InMemory());


// Load some basic information using the Comet Server API

$api_requests_start_time = microtime(true);

$serverinfo = $cs->AdminMetaVersion();

$users = $cs->AdminListUsersFull();

$online_devices = $cs->AdminDispatcherListActive();

$recentjobs = $cs->AdminGetJobsForDateRange(time() - 86400, time()); // Jobs with a runtime intersecting the last 24 hours

$api_requests_end_time = microtime(true);


// Metric
// Time taken to request API data from the Comet Server

$api_duration_gauge = $registry->registerGauge(
    'cometserver',
    'api_lookup_duration',
    "The time required to retrieve data from the Comet Server (ms)"
);
$api_duration_gauge->set(($api_requests_end_time - $api_requests_start_time) * 1000);


// Metric
// Total number of users on the server

$users_total_gauge = $registry->registerGauge(
    'cometserver',
    'users_total',
    'The total number of users on this Comet Server'
);
$users_total_gauge->set(count($users));


// Metric
// Storage Vault current sizes for each user

$vault_size_gauge = $registry->registerGauge(
    'cometserver',
    'storagevault_size_bytes',
    'The last measured size (in bytes) of each Storage Vault',
    ['username', 'vault_id', 'vault_type']
);
foreach($users as $user) {
    foreach($user->Destinations as $storage_vault_id => $storage_vault) {
        $vault_size_gauge->set(
            $storage_vault->Statistics->ClientProvidedSize->Size,
            [
                $user->Username,
                $storage_vault_id,
                $storage_vault->DestinationType,
            ]
        );
    }
}


// Metric
// Categorise recent job counts, to report on them separately as well as in aggregate

$recentjobs_gauge = $registry->registerGauge(
    "cometserver",
    "recentjobs_total",
    "Total number of jobs in the last 24 hours",
    ['status']
);

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

$device_is_online_gauge = $registry->getOrRegisterGauge(
    'cometserver',
    'device_is_online',
    "The online/offline status of each registered device",
    ['username', 'device_id']
);

$device_is_online_lookup = []; // Build inverted index of online devices for traversal
foreach($online_devices as $live_connection) {
    $key = $live_connection->Username . "\x00" . $live_connection->DeviceID;
    $device_is_online_lookup[$key] = true;
}

foreach($users as $username => $user) {
    foreach($user->Devices as $device_id => $device) {
        $is_online = array_key_exists($username . "\x00" . $device_id, $device_is_online_lookup);
        $device_is_online_gauge->set($is_online ? 1 : 0, [$username, $device_id]);
    }
}


// Metric
// Up-to-date status of each device

$device_is_current_gauge = $registry->registerGauge(
    'cometserver',
    'device_is_current',
    "Whether each online device is running the current software version (" . $serverinfo->Version . ")",
    ['username', 'device_id']
);

foreach($online_devices as $live_connection) {
    $device_is_current_gauge->set(
        ($live_connection->ReportedVersion == $serverinfo->Version) ? 1 : 0,
        [$live_connection->Username, $live_connection->DeviceID]
    );
}


// Render result

$renderer = new \Prometheus\RenderTextFormat();
$result = $renderer->render($registry->getMetricFamilySamples());

header('Content-Type: '.\Prometheus\RenderTextFormat::MIME_TYPE);
echo $result;

die();
