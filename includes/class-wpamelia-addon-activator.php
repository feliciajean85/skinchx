<?php

/**
 * Fired during plugin activation
 *
 * @link       https://encoderit.com
 * @since      1.0.0
 *
 * @package    Wpamelia_Addon
 * @subpackage Wpamelia_Addon/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Wpamelia_Addon
 * @subpackage Wpamelia_Addon/includes
 * @author     Encoder It <nadim@encoderit.net>
 */
class Wpamelia_Addon_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
      global $wpdb;
        $table_name = $wpdb->prefix . 'amelia_body_chart';
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        // SQL for the custom table
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            data LONGTEXT NOT NULL,
            appoinment_id bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

      
        dbDelta($sql);
        $table_name2 = $wpdb->prefix . 'amelia_service_chart';
        

        // SQL for the custom table
        $sql = "CREATE TABLE $table_name2 (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            data LONGTEXT NOT NULL,
            service_id bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

       
        dbDelta($sql); 
         $table_name2 = $wpdb->prefix . 'amelia_program_meterials';
        

        // SQL for the custom table
        $sql = "CREATE TABLE $table_name2 (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            data LONGTEXT NOT NULL,
            service_id bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

       
        dbDelta($sql); 
        $table_name2 = $wpdb->prefix . 'amelia_body_chart_ref';
        

        // SQL for the custom table
        $sql = "CREATE TABLE $table_name2 (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            data LONGTEXT NOT NULL,
            appointment_id bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

       
        dbDelta($sql);
        
   
        $table_name=$wpdb->prefix . 'amelia_report_comments';
        $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name text NOT NULL,
        description text NOT NULL,
        fields text NOT NULL,
        PRIMARY KEY (id)
      ) $charset_collate;";

 
     dbDelta($sql);
     
     // Create table for saved combined reports
     $table_name = $wpdb->prefix . 'amelia_combined_reports';
     $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        report_name varchar(255) NOT NULL,
        service_ids text NOT NULL,
        service_names text NOT NULL,
        customer_ids text DEFAULT NULL,
        customer_names text DEFAULT NULL,
        coupon_codes text DEFAULT NULL,
        report_data LONGTEXT NOT NULL,
        form_data LONGTEXT DEFAULT NULL,
        pdf_url varchar(500) DEFAULT NULL,
        created_by bigint(20) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY created_by (created_by),
        KEY created_at (created_at)
      ) $charset_collate;";
      
     dbDelta($sql);
	}

}
