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

$users = $cs->AdminListUsersFull();

$online_devices = $cs->AdminDispatcherListActive();

$recentjobs = $cs->AdminGetJobsForDateRange(time() - 86400, time()); // Jobs from the last 24 hours


// Build inverted index of online devices for traversal

$device_is_online_lookup = [];
foreach($online_devices as $live_connection) {
    $key = $live_connection->Username . "\x00" . $live_connection->DeviceID;
    $device_is_online_lookup[$key] = true;
}


// Categorise recent job counts, to report on them separately as well as in aggregate

$recentjobs_success_ct = 0;
$recentjobs_running_ct = 0;
$recentjobs_warning_ct = 0;
$recentjobs_quota_ct   = 0;
$recentjobs_failure_ct = 0;
foreach($recentjobs as $job) {
    if ($job->Status >= \Comet\Def::JOB_STATUS_STOP_SUCCESS__MIN && $job->Status <= \Comet\Def::JOB_STATUS_STOP_SUCCESS__MAX) {
        $recentjobs_success_ct++;

    } else if ($job->Status >= \Comet\Def::JOB_STATUS_RUNNING__MIN && $job->Status <= \Comet\Def::JOB_STATUS_RUNNING__MAX) {
        $recentjobs_running_ct++;

    } else if ($job->Status == \Comet\Def::JOB_STATUS_FAILED_WARNING) {
        $recentjobs_warning_ct++;

    } else if ($job->Status == \Comet\Def::JOB_STATUS_FAILED_QUOTA) {
        $recentjobs_quota_ct++;

    } else {
        $recentjobs_failure_ct++;

    }
}


// Display the results in Prometheus text exposition format
// @ref https://prometheus.io/docs/instrumenting/exposition_formats/ 

header('Content-Type: text/plain; version=0.0.4');
?>
# HELP cometserver_total_users Total number of users on this Comet Server
# TYPE cometserver_total_users gauge
cometserver_total_users <?=count($users)?> 

# HELP cometserver_recentjobs Total number of jobs in the last 24 hours
# TYPE cometserver_recentjobs gauge
cometserver_recentjobs{status="success"} <?=$recentjobs_success_ct?> 
cometserver_recentjobs{status="running"} <?=$recentjobs_running_ct?> 
cometserver_recentjobs{status="warning"} <?=$recentjobs_warning_ct?> 
cometserver_recentjobs{status="quota_exceeded"} <?=$recentjobs_quota_ct?> 
cometserver_recentjobs{status="error"} <?=$recentjobs_failure_ct?> 

# HELP cometserver_device The online/offline status of each registered device
# TYPE cometserver_device gauge
<?php
    foreach($users as $username => $user) {
        foreach($user->Devices as $device_id => $device) {
            echo 'cometserver_device{'.
                'username=' . json_encode($username) . ', '.
                'device_id=' . json_encode($device_id) . ', '.
                'device_friendly_name=' . json_encode($device->FriendlyName) .
            '}=' . (
                array_key_exists($username . "\x00" . $device_id, $device_is_online_lookup)
                    ? '1'
                    : '0'
            ).
            "\n";
        }
    }
?> 
