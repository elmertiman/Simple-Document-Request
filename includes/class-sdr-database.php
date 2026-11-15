<?php
/**
 * Database handling class
 */

if (!defined('ABSPATH')) {
    exit;
}

class SDR_Database {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Constructor
    }
    
    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sdr_authenticity_requests';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            email varchar(255) NOT NULL,
            otp_code varchar(10) NOT NULL,
            otp_used tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            verified_at datetime NULL,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY email (email),
            KEY otp_code (otp_code),
            KEY otp_used (otp_used)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Save OTP request
     */
    public function save_otp_request($product_id, $email, $otp_code) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sdr_authenticity_requests';
        
        return $wpdb->insert(
            $table_name,
            array(
                'product_id' => $product_id,
                'email' => sanitize_email($email),
                'otp_code' => $otp_code,
                'otp_used' => 0,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%d', '%s')
        );
    }
    
    /**
     * Verify OTP code
     */
    public function verify_otp($product_id, $email, $otp_code) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sdr_authenticity_requests';
        
        // Check if OTP exists and is not used
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name 
            WHERE product_id = %d 
            AND email = %s 
            AND otp_code = %s 
            AND otp_used = 0
            ORDER BY created_at DESC
            LIMIT 1",
            $product_id,
            $email,
            $otp_code
        ));
        
        if ($result) {
            // Mark OTP as used
            $wpdb->update(
                $table_name,
                array(
                    'otp_used' => 1,
                    'verified_at' => current_time('mysql')
                ),
                array('id' => $result->id),
                array('%d', '%s'),
                array('%d')
            );
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get all requests for admin
     */
    public function get_all_requests($limit = 50, $offset = 0) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sdr_authenticity_requests';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
            ORDER BY created_at DESC 
            LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));
    }
    
    /**
     * Get filtered and sorted requests for admin
     */
    public function get_requests_filtered($args = array()) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sdr_authenticity_requests';
        
        $defaults = array(
            'search' => '',
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 50,
            'offset' => 0,
            'status' => '' // all, verified, pending
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Build WHERE clause
        $where = array('1=1');
        $where_values = array();
        
        // Search filter
        if (!empty($args['search'])) {
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $where[] = "(email LIKE %s OR otp_code LIKE %s OR product_id LIKE %s)";
            $where_values[] = $search;
            $where_values[] = $search;
            $where_values[] = $search;
        }
        
        // Status filter
        if ($args['status'] === 'verified') {
            $where[] = "otp_used = 1";
        } elseif ($args['status'] === 'pending') {
            $where[] = "otp_used = 0";
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Validate orderby
        $allowed_orderby = array('id', 'product_id', 'email', 'otp_code', 'otp_used', 'created_at', 'verified_at');
        if (!in_array($args['orderby'], $allowed_orderby)) {
            $args['orderby'] = 'created_at';
        }
        
        // Validate order
        $args['order'] = strtoupper($args['order']);
        if (!in_array($args['order'], array('ASC', 'DESC'))) {
            $args['order'] = 'DESC';
        }
        
        // Build query
        $sql = "SELECT * FROM $table_name WHERE $where_clause ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d";
        
        // Add limit and offset to values
        $where_values[] = $args['limit'];
        $where_values[] = $args['offset'];
        
        if (!empty($where_values)) {
            return $wpdb->get_results($wpdb->prepare($sql, $where_values));
        }
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get total count of filtered requests
     */
    public function get_requests_count_filtered($args = array()) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sdr_authenticity_requests';
        
        $defaults = array(
            'search' => '',
            'status' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Build WHERE clause
        $where = array('1=1');
        $where_values = array();
        
        // Search filter
        if (!empty($args['search'])) {
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $where[] = "(email LIKE %s OR otp_code LIKE %s OR product_id LIKE %s)";
            $where_values[] = $search;
            $where_values[] = $search;
            $where_values[] = $search;
        }
        
        // Status filter
        if ($args['status'] === 'verified') {
            $where[] = "otp_used = 1";
        } elseif ($args['status'] === 'pending') {
            $where[] = "otp_used = 0";
        }
        
        $where_clause = implode(' AND ', $where);
        $sql = "SELECT COUNT(*) FROM $table_name WHERE $where_clause";
        
        if (!empty($where_values)) {
            return $wpdb->get_var($wpdb->prepare($sql, $where_values));
        }
        
        return $wpdb->get_var($sql);
    }
    
    /**
     * Get total count of requests
     */
    public function get_total_requests() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sdr_authenticity_requests';
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    }
    
    /**
     * Delete a request by ID
     */
    public function delete_request($request_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sdr_authenticity_requests';
        
        return $wpdb->delete(
            $table_name,
            array('id' => $request_id),
            array('%d')
        );
    }
    
    /**
     * Delete multiple requests
     */
    public function delete_requests($request_ids) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sdr_authenticity_requests';
        
        if (empty($request_ids) || !is_array($request_ids)) {
            return false;
        }
        
        $ids = array_map('intval', $request_ids);
        $ids_string = implode(',', $ids);
        
        return $wpdb->query("DELETE FROM $table_name WHERE id IN ($ids_string)");
    }
}
