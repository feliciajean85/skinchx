<?php

if (!defined('ABSPATH')) {
    exit;
}

interface AALS_Appointments_Provider_Interface {
    
    /**
     * Fetch appointments from Amelia
     * 
     * @param array $args {
     *     @type int    $service_id          Service ID
     *     @type string $appointment_status  Appointment status (approved, pending, etc.)
     *     @type string $range_start_utc     Start date in UTC
     *     @type string $range_end_utc       End date in UTC
     * }
     * @return array {
     *     @type bool   $success      Whether the fetch was successful
     *     @type array  $appointments Array of appointment data
     *     @type string $data_source  'api' or 'db_fallback'
     *     @type string $error        Error message if failed
     * }
     */
    public function get_appointments($args);
    
    /**
     * Get all available services
     * 
     * @return array {
     *     @type bool  $success  Whether the fetch was successful
     *     @type array $services Array of services with id and name
     *     @type string $error   Error message if failed
     * }
     */
    public function get_services();
}
