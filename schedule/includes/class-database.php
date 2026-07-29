<?php
/**
 * Database Handler for V2 - Relative Time Scheduling
 */

if (!defined('ABSPATH')) {
    exit;
}

class AALS_Database {
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_prefix = $wpdb->prefix;
        
        // Schedules table - V3.2 with schedule_type and whatsapp support
        $schedules_table = $table_prefix . 'aals_schedules';
        $schedules_sql = "CREATE TABLE IF NOT EXISTS $schedules_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            schedule_type varchar(50) NOT NULL DEFAULT 'attendee_list' COMMENT 'attendee_list or group_report_reminder',
            source_marker varchar(100) DEFAULT NULL,
            service_id bigint(20) unsigned DEFAULT NULL,
            service_name_cache varchar(255) DEFAULT NULL,
            service_ids_json text DEFAULT NULL,
            service_names_cache text DEFAULT NULL,
            recipients text NOT NULL,
            time_value int(11) NOT NULL DEFAULT 24,
            time_unit varchar(20) NOT NULL DEFAULT 'hours',
            time_direction varchar(20) NOT NULL DEFAULT 'before' COMMENT 'before or after',
            enabled tinyint(1) NOT NULL DEFAULT 1,
            status varchar(20) NOT NULL DEFAULT 'Active',
            status_message text DEFAULT NULL,
            appointment_status varchar(50) DEFAULT 'approved',
            csv_fields_json text NOT NULL,
            email_subject varchar(255) DEFAULT NULL,
            email_body text DEFAULT NULL,
            whatsapp_enabled tinyint(1) NOT NULL DEFAULT 0,
            next_trigger_utc datetime DEFAULT NULL,
            next_appointment_id bigint(20) unsigned DEFAULT NULL,
            last_check_utc datetime DEFAULT NULL,
            last_run_utc datetime DEFAULT NULL,
            completed_utc datetime DEFAULT NULL,
            created_utc datetime NOT NULL,
            last_updated_utc datetime NOT NULL,
            PRIMARY KEY (id),
            KEY service_id (service_id),
            KEY status (status),
            KEY enabled (enabled),
            KEY next_trigger_utc (next_trigger_utc),
            KEY schedule_type (schedule_type),
            KEY source_marker (source_marker)
        ) $charset_collate;";
        
        // Logs table
        $logs_table = $table_prefix . 'aals_logs';
        $logs_sql = "CREATE TABLE IF NOT EXISTS $logs_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            schedule_id bigint(20) unsigned NOT NULL,
            attempt_utc datetime NOT NULL,
            action varchar(50) NOT NULL DEFAULT 'send',
            result varchar(20) NOT NULL,
            error_message text DEFAULT NULL,
            data_source varchar(20) DEFAULT NULL,
            rows_exported int(11) DEFAULT 0,
            recipients text DEFAULT NULL,
            subject varchar(255) DEFAULT NULL,
            attachment_name varchar(255) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY schedule_id (schedule_id),
            KEY result (result),
            KEY attempt_utc (attempt_utc)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($schedules_sql);
        dbDelta($logs_sql);
        
        // Run migration to add new columns to existing tables
        self::migrate_add_schedule_type_columns();
    }
    
    /**
     * Migration: Add schedule_type and time_direction columns if missing
     * This handles upgrading existing installations
     */
    public static function migrate_add_schedule_type_columns() {
        global $wpdb;
        $table = $wpdb->prefix . 'aals_schedules';
        
        // Check if schedule_type column exists
        $schedule_type_exists = $wpdb->get_var("SHOW COLUMNS FROM `$table` LIKE 'schedule_type'");
        if (!$schedule_type_exists) {
            $wpdb->query("ALTER TABLE `$table` ADD COLUMN `schedule_type` varchar(50) NOT NULL DEFAULT 'attendee_list' COMMENT 'attendee_list or group_report_reminder' AFTER `id`");
        }
        
        // Check if time_direction column exists
        $time_direction_exists = $wpdb->get_var("SHOW COLUMNS FROM `$table` LIKE 'time_direction'");
        if (!$time_direction_exists) {
            $wpdb->query("ALTER TABLE `$table` ADD COLUMN `time_direction` varchar(20) NOT NULL DEFAULT 'before' COMMENT 'before or after' AFTER `time_unit`");
        }
        
        // v9.7.2: source_marker lets external modules (e.g. the Booking Slot
        // Counter decay-date auto-reminder) tag schedules they own so they
        // can update / delete them safely without touching user-created ones.
        $source_marker_exists = $wpdb->get_var("SHOW COLUMNS FROM `$table` LIKE 'source_marker'");
        if (!$source_marker_exists) {
            $wpdb->query("ALTER TABLE `$table` ADD COLUMN `source_marker` varchar(100) DEFAULT NULL AFTER `schedule_type`");
            $wpdb->query("ALTER TABLE `$table` ADD INDEX `source_marker` (`source_marker`)");
        }
        
        // Check if whatsapp_enabled column exists
        $whatsapp_exists = $wpdb->get_var("SHOW COLUMNS FROM `$table` LIKE 'whatsapp_enabled'");
        if (!$whatsapp_exists) {
            $wpdb->query("ALTER TABLE `$table` ADD COLUMN `whatsapp_enabled` tinyint(1) NOT NULL DEFAULT 0 AFTER `email_body`");
        }
    }
    
    public static function get_schedule($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'aals_schedules';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }
    
    public static function get_all_schedules() {
        global $wpdb;
        $table = $wpdb->prefix . 'aals_schedules';
        return $wpdb->get_results("SELECT * FROM $table ORDER BY created_utc DESC");
    }
    
    public static function get_active_schedules() {
        global $wpdb;
        $table = $wpdb->prefix . 'aals_schedules';
        return $wpdb->get_results("SELECT * FROM $table WHERE enabled = 1 AND status IN ('Active', 'Scheduled') ORDER BY next_trigger_utc ASC");
    }
    
    public static function insert_schedule($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'aals_schedules';
        
        $defaults = array(
            'created_utc' => current_time('mysql', true),
            'last_updated_utc' => current_time('mysql', true),
            'enabled' => 1,
            'status' => 'Active',
            'appointment_status' => 'approved',
            'time_value' => 24,
            'time_unit' => 'hours',
            'time_direction' => 'before',
            'schedule_type' => 'attendee_list',
            'csv_fields_json' => '[]'
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Run migration to ensure columns exist before insert
        self::migrate_add_schedule_type_columns();
        
        $result = $wpdb->insert($table, $data);
        
        if ($result === false) {
            // Log error for debugging
            error_log('AALS Schedule Insert Error: ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    public static function update_schedule($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'aals_schedules';
        
        $data['last_updated_utc'] = current_time('mysql', true);
        
        return $wpdb->update($table, $data, array('id' => $id));
    }
    
    public static function delete_schedule($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'aals_schedules';
        return $wpdb->delete($table, array('id' => $id));
    }
    
    public static function update_status_atomic($id, $old_status, $new_status) {
        global $wpdb;
        $table = $wpdb->prefix . 'aals_schedules';
        
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE $table SET status = %s, last_updated_utc = %s WHERE id = %d AND status = %s",
            $new_status,
            current_time('mysql', true),
            $id,
            $old_status
        ));
        
        return $result > 0;
    }
    
    public static function insert_log($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'aals_logs';
        
        $defaults = array(
            'attempt_utc' => current_time('mysql', true),
            'action' => 'send'
        );
        
        $data = wp_parse_args($data, $defaults);
        
        $wpdb->insert($table, $data);
        return $wpdb->insert_id;
    }
    
    public static function get_logs($schedule_id, $limit = 20) {
        global $wpdb;
        $table = $wpdb->prefix . 'aals_logs';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE schedule_id = %d ORDER BY attempt_utc DESC LIMIT %d",
            $schedule_id,
            $limit
        ));
    }
}
