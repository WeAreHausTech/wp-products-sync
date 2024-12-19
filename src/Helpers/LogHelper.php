<?php

namespace WeAreHausTech\WpProductSync\Helpers;

class LogHelper
{
    public static $logFolder = 'vendure-sync';

    public function __construct()
    {
    }

    // Log to files in uploads/vendure-sync folder
    public static function save_to_log_file($message)
    {
        $upload_dir = wp_upload_dir('basedir');

        $log_directory = trailingslashit($upload_dir['basedir']) . self::$logFolder;

        if (!file_exists($log_directory)) {
            $created = wp_mkdir_p($log_directory);
            if (!$created) {
                echo 'Failed to create log directory: ' . esc_html($log_directory) . PHP_EOL;
                return;
            }
        }

        $current_date = date('Y-m-d');
        $log_file = trailingslashit($log_directory) . "{$current_date}.log";

        $timestamp = date('Y-m-d H:i:s');
        $log_entry = '[' . $timestamp . '] ' . $message;

        $result = @file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);

        if ($result === false) {
            echo 'Failed to write to log file: ' . esc_html($log_file) . PHP_EOL;
        }

        self::cleanup_old_logs($log_directory);

    }

    //  Clean up log files older than $days
    public static function cleanup_old_logs($log_directory, $days = 7)
    {
        $files = glob($log_directory . '/*.log');
        $now = time();
        $expiry = $days * DAY_IN_SECONDS;

        foreach ($files as $file) {
            if (is_file($file)) {
                if (preg_match('/(\d{4}-\d{2}-\d{2})\.log$/', basename($file), $matches)) {
                    $file_date = strtotime($matches[1]);
                    if ($file_date && ($now - $file_date) > $expiry) {
                        unlink($file);
                    }
                }
            }
        }
    }
}
