<?php
/**
 * DB Fallback Provider V2 - with limit support
 */

if (!defined('ABSPATH')) {
    exit;
}

class AALS_DB_Fallback_Provider implements AALS_Appointments_Provider_Interface {
    
    private $wpdb;
    private $tables = array();
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->detect_amelia_tables();
    }
    
    private function detect_amelia_tables() {
        $prefix = $this->wpdb->prefix;
        
        $possible_prefixes = array(
            $prefix . 'amelia_',
            $prefix . 'ameliabooking_'
        );
        
        foreach ($possible_prefixes as $test_prefix) {
            $appointments_table = $test_prefix . 'appointments';
            $services_table = $test_prefix . 'services';
            $users_table = $test_prefix . 'users';
            $customer_bookings_table = $test_prefix . 'customer_bookings';
            
            $table_exists = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                $appointments_table
            ));
            
            if ($table_exists) {
                $this->tables = array(
                    'appointments' => $appointments_table,
                    'services' => $services_table,
                    'users' => $users_table,
                    'customer_bookings' => $customer_bookings_table
                );
                break;
            }
        }
    }
    
    private function tables_available() {
        return !empty($this->tables);
    }
    
    public function get_services() {
        if (!$this->tables_available()) {
            return array(
                'success' => false,
                'services' => array(),
                'error' => 'Could not detect Amelia database tables'
            );
        }
        
        $services_table = $this->tables['services'];
        
        $results = $this->wpdb->get_results(
            "SELECT id, name FROM $services_table WHERE status = 'visible' ORDER BY name ASC"
        );
        
        if ($this->wpdb->last_error) {
            return array(
                'success' => false,
                'services' => array(),
                'error' => $this->wpdb->last_error
            );
        }
        
        $services = array();
        foreach ($results as $row) {
            $services[] = array(
                'id' => $row->id,
                'name' => $row->name
            );
        }
        
        return array(
            'success' => true,
            'services' => $services,
            'error' => null
        );
    }
    
    public function get_appointments($args) {
        if (!$this->tables_available()) {
            return array(
                'success' => false,
                'appointments' => array(),
                'data_source' => 'db_fallback',
                'error' => 'Could not detect Amelia database tables'
            );
        }
        
        $service_id = isset($args['service_id']) ? intval($args['service_id']) : 0;
        $status = isset($args['appointment_status']) ? $args['appointment_status'] : 'approved';
        $start = isset($args['range_start_utc']) ? $args['range_start_utc'] : null;
        $end = isset($args['range_end_utc']) ? $args['range_end_utc'] : null;
        $limit = isset($args['limit']) ? intval($args['limit']) : 50000;
        $order = isset($args['order']) && strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';
        
        $appointments_table = $this->tables['appointments'];
        $services_table = $this->tables['services'];
        $users_table = $this->tables['users'];
        $customer_bookings_table = $this->tables['customer_bookings'];
        
        $where_clauses = array();
        $where_clauses[] = $this->wpdb->prepare('a.serviceId = %d', $service_id);
        
        // Only add status filter if not "all"
        if ($status !== 'all') {
            $where_clauses[] = $this->wpdb->prepare('a.status = %s', $status);
        }
        
        // Default to future appointments
        $now_utc = current_time('mysql', true);
        if (empty($start)) {
            $start = $now_utc;
        }
        $where_clauses[] = $this->wpdb->prepare('a.bookingStart >= %s', $start);
        
        if ($end) {
            $where_clauses[] = $this->wpdb->prepare('a.bookingStart <= %s', $end);
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        $query = "
            SELECT 
                a.id,
                a.bookingStart,
                a.bookingEnd,
                a.status,
                a.internalNotes,
                s.name as service_name,
                customer.firstName as customer_first_name,
                customer.lastName as customer_last_name,
                customer.email as customer_email,
                customer.phone as customer_phone,
                provider.firstName as provider_first_name,
                provider.lastName as provider_last_name
            FROM $appointments_table a
            LEFT JOIN $services_table s ON a.serviceId = s.id
            LEFT JOIN $customer_bookings_table cb ON a.id = cb.appointmentId
            LEFT JOIN $users_table customer ON cb.customerId = customer.id
            LEFT JOIN $users_table provider ON a.providerId = provider.id
            WHERE $where_sql
            ORDER BY a.bookingStart $order
            LIMIT $limit
        ";
        
        $results = $this->wpdb->get_results($query, ARRAY_A);
        
        if ($this->wpdb->last_error) {
            return array(
                'success' => false,
                'appointments' => array(),
                'data_source' => 'db_fallback',
                'error' => $this->wpdb->last_error
            );
        }
        
        return array(
            'success' => true,
            'appointments' => $results,
            'data_source' => 'db_fallback',
            'error' => null
        );
    }
}
