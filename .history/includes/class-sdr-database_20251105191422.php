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
     * Get total count of requests
     */
    public function get_total_requests() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sdr_authenticity_requests';
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    }
}
