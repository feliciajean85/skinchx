<?php

if (!defined('ABSPATH')) {
    exit;
}

class AALS_API_Provider implements AALS_Appointments_Provider_Interface {
    
    private $api_key;
    private $api_url;
    
    public function __construct() {
        // Get Amelia API settings
        $this->api_key = get_option('amelia_api_key', '');
        $this->api_url = site_url('/wp-json/amelia/v1');
    }
    
    public function get_services() {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'services' => array(),
                'error' => 'API key not configured'
            );
        }
        
        $response = wp_remote_get($this->api_url . '/services', array(
            'headers' => array(
                'Amelia' => $this->api_key
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'services' => array(),
                'error' => $response->get_error_message()
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            return array(
                'success' => false,
                'services' => array(),
                'error' => 'API returned status code: ' . $status_code
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!isset($data['data']['services'])) {
            return array(
                'success' => false,
                'services' => array(),
                'error' => 'Invalid API response format'
            );
        }
        
        $services = array();
        foreach ($data['data']['services'] as $service) {
            $services[] = array(
                'id' => $service['id'],
                'name' => $service['name']
            );
        }
        
        return array(
            'success' => true,
            'services' => $services,
            'error' => null
        );
    }
    
    public function get_appointments($args) {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'appointments' => array(),
                'data_source' => 'api',
                'error' => 'API key not configured'
            );
        }
        
        $service_id = isset($args['service_id']) ? intval($args['service_id']) : 0;
        $status = isset($args['appointment_status']) ? $args['appointment_status'] : 'approved';
        $start = isset($args['range_start_utc']) ? $args['range_start_utc'] : null;
        $end = isset($args['range_end_utc']) ? $args['range_end_utc'] : null;
        
        // Build query parameters
        $query_params = array(
            'serviceId' => $service_id
        );
        
        // Only add status filter if not "all"
        if ($status !== 'all') {
            $query_params['status'] = $status;
        }
        
        if ($start) {
            $query_params['dates'] = array();
            $start_date = new DateTime($start);
            $end_date = $end ? new DateTime($end) : new DateTime('+1 year');
            $query_params['dates'][] = $start_date->format('Y-m-d');
            $query_params['dates'][] = $end_date->format('Y-m-d');
        }
        
        $url = add_query_arg($query_params, $this->api_url . '/appointments');
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Amelia' => $this->api_key
            ),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'appointments' => array(),
                'data_source' => 'api',
                'error' => $response->get_error_message()
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            return array(
                'success' => false,
                'appointments' => array(),
                'data_source' => 'api',
                'error' => 'API returned status code: ' . $status_code
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!isset($data['data']['appointments'])) {
            return array(
                'success' => false,
                'appointments' => array(),
                'data_source' => 'api',
                'error' => 'Invalid API response format'
            );
        }
        
        return array(
            'success' => true,
            'appointments' => $data['data']['appointments'],
            'data_source' => 'api',
            'error' => null
        );
    }
}
