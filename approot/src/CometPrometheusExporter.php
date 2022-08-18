<?php

// Mini standalone Prometheus exporter for Comet Server
// Copyright (2019) Comet Backup.com Ltd
// License: MIT

namespace CometPrometheusExporter;

class CometPrometheusExporter {

    /**
     * Special labels used to group job report status types in the Prometheus output format
     * 
     * @var string
     */
    const SUCCESS = "success";
    const RUNNING = "running";
    const WARNING = "warning";
    const QUOTAEXCEEDED = "quota_exceeded";
    const ERROR = "error";

    /**
     * The remote Comet Server that this exporter is connected to
     *
     * @var \Comet\Server
     */
    protected $cs;

    /**
     * Registry of Prometheus metrics
     *
     * @var \Prometheus\CollectorRegistry
     */
    protected $registry;

    /**
     * Construct a new CometPrometheusExporter instance
     */
    public function __construct(string $comet_server_url, string $comet_admin_username, string $comet_admin_password)
    { 
        $this->cs = new \Comet\Server($comet_server_url, $comet_admin_username, $comet_admin_password);
        $this->registry = new \Prometheus\CollectorRegistry(new \Prometheus\Storage\InMemory());
    }

    /**
     * Render information from the Comet Server in Prometheus format.
     *
     * @return string Prometheus metrics in text exposition format
     */
    public function metrics(): string {

        // Load some basic information using the Comet Server API

        $api_requests_start_time = microtime(true);
        {
            $serverinfo = $this->cs->AdminMetaVersion();
            if ($serverinfo->AuthenticationRole) {
                $users = $this->cs->AdminListUsersFull();
                $online_devices = $this->cs->AdminDispatcherListActive();
                $recentjobs = $this->cs->AdminGetJobsForDateRange(time() - 86400, time()); // Jobs with a runtime intersecting the last 24 hours
            }
            if ($serverinfo->StorageRole) {
                $storagerole_buckets = $this->cs->AdminStorageListBuckets();
            }
        }
        $api_requests_end_time = microtime(true);

        // Convert API responses to our Prometheus metrics

        $this->addRequestTimeMetric($api_requests_start_time, $api_requests_end_time);
        if ($serverinfo->AuthenticationRole) {
            $this->addTotalUsersMetric($users);
            $this->addStorageVaultMetrics($users);
            $this->addLastBackupMetrics($users);
            $this->addRecentJobsMetrics($recentjobs);
            $this->addOnlineStatusMetrics($users, $online_devices);
            $this->addDeviceIsCurrentMetrics($users, $online_devices, $serverinfo);
        }
        if ($serverinfo->StorageRole) {
            $this->addTotalSRBucketsMetric($storagerole_buckets);
        }
        
        // Render result

        $renderer = new \Prometheus\RenderTextFormat();
        return $renderer->render($this->registry->getMetricFamilySamples());
    }

    /**
     * Register metric
     * Time taken to request API data from the Comet Server
     *
     * @param float $api_requests_start_time
     * @param float $api_requests_end_time
     * @return void
     */
    public function addRequestTimeMetric(float $api_requests_start_time, float $api_requests_end_time):void {
        $api_duration_gauge = $this->registry->registerGauge(
            'cometserver',
            'api_lookup_duration',
            "The time required to retrieve data from the Comet Server (ms)"
        );
        $api_duration_gauge->set(($api_requests_end_time - $api_requests_start_time) * 1000);
    }

    /**
     * Register metric
     * Total number of users on the server
     *
     * @param \Comet\UserProfileConfig[] $users Result of AdminListUsersFull API call
     * @return void
     */
    public function addTotalUsersMetric(array $users): void { 

        $users_total_gauge = $this->registry->registerGauge(
            'cometserver',
            'users_total',
            'The total number of users on this Comet Server'
        );
        $users_total_gauge->set(count($users));
    }

    /**
     * Register metric
     * Total number of Storage Role buckets on the server
     *
     * @param \Comet\BucketProperties[] $storagerole_buckets Result of AdminListUsersFull API call
     * @return void
     */
    public function addTotalSRBucketsMetric(array $storagerole_buckets): void { 

        $storagerole_buckets_total_gauge = $this->registry->registerGauge(
            'cometserver',
            'storagerole_buckets_total',
            'The total number of Storage Role buckets on this Comet Server'
        );
        $storagerole_buckets_total_gauge->set(count($storagerole_buckets));
    }

    /**
     * Register metrics
     * Storage Vault current sizes and quota enforcement for each user
     *
     * @param \Comet\UserProfileConfig[] $users Result of AdminListUsersFull API call
     * @return void
     */
    public function addStorageVaultMetrics(array $users): void {
        
        $vault_size_gauge = $this->registry->registerGauge(
            'cometserver',
            'storagevault_size_bytes',
            'The last measured size (in bytes) of each Storage Vault',
            ['username', 'vault_id', 'vault_name', 'vault_type']
        );
        $vault_quota_gauge = $this->registry->registerGauge(
            'cometserver',
            'storagevault_quota_bytes',
            'The quota limit for each Storage Vault, if one is set',
            ['username', 'vault_id', 'vault_name', 'vault_type']
        );

        foreach($users as $user) {
            foreach($user->Destinations as $storage_vault_id => $storage_vault) {
                $vault_size_gauge->set(
                    $storage_vault->Statistics->ClientProvidedSize->Size,
                    [
                        $user->Username,
                        $storage_vault_id,
                        $storage_vault->Description,
                        $storage_vault->DestinationType,
                    ]
                );

                if ($storage_vault->StorageLimitEnabled) {
                    $vault_quota_gauge->set(
                        $storage_vault->StorageLimitBytes,
                        [
                            $user->Username,
                            $storage_vault_id,
                            $storage_vault->Description,
                            $storage_vault->DestinationType,
                        ]
                    );
                }
            }
        }
    }

    /**
     * Register metrics
     * Last completed backup job for each Protected Item
     *
     * @param \Comet\UserProfileConfig[] $users Result of AdminListUsersFull API call
     * @return void
     */
    public function addLastBackupMetrics(array $users): void {

        $common_label_names = [
            'username', 'protected_item_id', 'protected_item_name', 'device_id', 'device_name'
        ];

        $lastbackup_start_time = $this->registry->registerGauge(
            'cometserver',
            'lastbackup_start_time',
            'The start time of the most recent completed backup job for this Protected Item',
            $common_label_names
        );
        $lastbackup_end_time = $this->registry->registerGauge(
            'cometserver',
            'lastbackup_end_time',
            'The end time of the most recent completed backup job for this Protected Item',
            $common_label_names
        );
        $lastbackup_file_count = $this->registry->registerGauge(
            'cometserver',
            'lastbackup_file_count',
            'The number of files in the most recent completed backup job for this Protected Item',
            $common_label_names
        );
        $lastbackup_file_size = $this->registry->registerGauge(
            'cometserver',
            'lastbackup_file_size_bytes',
            'The size (bytes) of the data selected for backup on disk, as of the most recent completed backup job for this Protected Item',
            $common_label_names
        );
        $lastbackup_upload_size = $this->registry->registerGauge(
            'cometserver',
            'lastbackup_upload_size_bytes',
            'The size (bytes) uploaded during most recent completed backup job for this Protected Item',
            $common_label_names
        );
        $lastbackup_download_size = $this->registry->registerGauge(
            'cometserver',
            'lastbackup_download_size_bytes',
            'The size (bytes) downloaded during most recent completed backup job for this Protected Item',
            $common_label_names
        );
        $lastbackup_status = $this->registry->registerGauge(
            'cometserver',
            'lastbackup_status',
            'The status of the most recent completed backup job for this Protected Item.',
            array_merge($common_label_names, ['status'])
        );
        foreach($users as $user) {
            foreach($user->Sources as $protected_item_id => $protected_item) {

                $has_completed_backup_job = ($protected_item->Statistics->LastBackupJob->StartTime > 0);
                
                if ($has_completed_backup_job) {
                    $common_label_values = [
                        $user->Username,
                        $protected_item_id,
                        $protected_item->Description,
                        $protected_item->OwnerDevice,
                        ($user->Devices[$protected_item->OwnerDevice]->FriendlyName ?? '')
                    ];

                    $lastbackup_start_time->set($protected_item->Statistics->LastBackupJob->StartTime, $common_label_values);
                    $lastbackup_end_time->set($protected_item->Statistics->LastBackupJob->EndTime, $common_label_values);
                    $lastbackup_file_count->set($protected_item->Statistics->LastBackupJob->TotalFiles, $common_label_values);
                    $lastbackup_file_size->set($protected_item->Statistics->LastBackupJob->TotalSize, $common_label_values);
                    $lastbackup_upload_size->set($protected_item->Statistics->LastBackupJob->UploadSize, $common_label_values);
                    $lastbackup_download_size->set($protected_item->Statistics->LastBackupJob->DownloadSize, $common_label_values);

                    $job_category = self::categoriseJobStatus($protected_item->Statistics->LastBackupJob);
                    foreach(self::jobStatusCategories() as $category) {
                        if ($category === $job_category) {
                            $lastbackup_status->set(1, array_merge($common_label_values, [$category]));

                        } else {
                            // Add a zero value for this job category
                            $lastbackup_status->set(0, array_merge($common_label_values, [$category]));
                        }
                    }
                }
            }
        }
    }

    /**
     * List the available groups that categoriseJobStatus() will categorise into.
     *
     * @return string[]
     */
    protected static function jobStatusCategories(): array {
        return [
            self::SUCCESS,
            self::RUNNING,
            self::WARNING,
            self::QUOTAEXCEEDED,
            self::ERROR,
        ];
    }

    /**
     * Categorise a backup job into a fixed number of known types.
     *
     * @param \Comet\BackupJobDetail $job
     * @return string
     */
    protected static function categoriseJobStatus(\Comet\BackupJobDetail $job): string {
        if ($job->Status >= \Comet\Def::JOB_STATUS_STOP_SUCCESS__MIN && $job->Status <= \Comet\Def::JOB_STATUS_STOP_SUCCESS__MAX) {
            return self::SUCCESS;

        } else if ($job->Status >= \Comet\Def::JOB_STATUS_RUNNING__MIN && $job->Status <= \Comet\Def::JOB_STATUS_RUNNING__MAX) {
            return self::RUNNING;

        } else if ($job->Status == \Comet\Def::JOB_STATUS_FAILED_WARNING) {
            return self::WARNING;

        } else if ($job->Status == \Comet\Def::JOB_STATUS_FAILED_QUOTA) {
            return self::QUOTAEXCEEDED;

        } else {
            return self::ERROR;

        }
    }

    /**
     * Register metric
     * Categorise recent job counts, to report on them separately as well as in aggregate
     *
     * @param \Comet\BackupJobDetail[] $recentjobs
     * @return void
     */
    public function addRecentJobsMetrics(array $recentjobs): void {
        $recentjobs_gauge = $this->registry->registerGauge(
            "cometserver",
            "recentjobs_total",
            "Total number of jobs in the last 24 hours",
            ['status']
        );

        foreach(self::jobStatusCategories() as $category) {
            $recentjobs_gauge->set(0, [$category]);
        }
        
        foreach($recentjobs as $job) {
            $recentjobs_gauge->inc([ self::categoriseJobStatus($job) ]);
        }
    }

    /**
     * Register metric
     * Online/offline status of each device
     *
     * @param \Comet\UserProfileConfig[] $users Result of AdminListUsersFull API call
     * @param \Comet\LiveUserConnection[] $online_devices
     * @return void
     */
    public function addOnlineStatusMetrics(array $users, array $online_devices): void {

        $device_is_online_gauge = $this->registry->getOrRegisterGauge(
            'cometserver',
            'device_is_online',
            "The online/offline status of each registered device",
            ['username', 'device_id', 'device_name']
        );

        $device_is_online_lookup = []; // Build inverted index of online devices for traversal
        foreach($online_devices as $live_connection) {
            $key = $live_connection->Username . "\x00" . $live_connection->DeviceID;
            $device_is_online_lookup[$key] = true;
        }

        foreach($users as $username => $user) {
            foreach($user->Devices as $device_id => $device) {
                $is_online = array_key_exists($username . "\x00" . $device_id, $device_is_online_lookup);
                $device_is_online_gauge->set(
                    $is_online ? 1 : 0,
                    [$username, $device_id, $device->FriendlyName]
                );
            }
        }
    }

    /**
     * Register metric
     * Up-to-date status of each device
     *
     * @param \Comet\UserProfileConfig[] $users Result of AdminListUsersFull API call
     * @param \Comet\LiveUserConnection[] $online_devices
     * @param \Comet\ServerMetaVersionInfo $serverinfo
     * @return void
     */
    public function addDeviceIsCurrentMetrics(array $users, array $online_devices, \Comet\ServerMetaVersionInfo $serverinfo): void {

        $device_is_current_gauge = $this->registry->registerGauge(
            'cometserver',
            'device_is_current',
            "Whether each online device is running the current software version (" . $serverinfo->Version . ")",
            ['username', 'device_id', 'device_name']
        );

        foreach($online_devices as $live_connection) {

            // Try to find the friendly-name for this device
            $device_friendly_name = $users[$live_connection->Username]->Devices[$live_connection->DeviceID]->FriendlyName ?: ''; // (unknown)

            $device_is_current_gauge->set(
                ($live_connection->ReportedVersion == $serverinfo->Version) ? 1 : 0,
                [$live_connection->Username, $live_connection->DeviceID, $device_friendly_name]
            );
        }
    }

}
