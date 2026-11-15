<?php
/**
 * AJAX handling class
 */

if (!defined('ABSPATH')) {
    exit;
}

class SDR_Ajax {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_ajax_sdr_request_otp', array($this, 'handle_request_otp'));
        add_action('wp_ajax_nopriv_sdr_request_otp', array($this, 'handle_request_otp'));
        
        add_action('wp_ajax_sdr_verify_otp', array($this, 'handle_verify_otp'));
        add_action('wp_ajax_nopriv_sdr_verify_otp', array($this, 'handle_verify_otp'));
    }
    
    /**
     * Handle OTP request
     */
    public function handle_request_otp() {
        check_ajax_referer('sdr_nonce', 'nonce');
        
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        
        // Debug logging
        error_log('SDR OTP Request - Product ID received: ' . $product_id);
        error_log('SDR OTP Request - Email: ' . $email);
        
        if (empty($email) || !is_email($email)) {
            wp_send_json_error(array('message' => __('Please enter a valid email address.', 'simple-document-request')));
        }
        
        if (empty($product_id)) {
            error_log('SDR OTP Request - Product ID is empty');
            wp_send_json_error(array('message' => __('Invalid product: Product ID is empty.', 'simple-document-request')));
        }
        
        $product = wc_get_product($product_id);
        if (!$product) {
            error_log('SDR OTP Request - Product not found for ID: ' . $product_id);
            wp_send_json_error(array('message' => __('Invalid product: Product not found.', 'simple-document-request')));
        }
        
        // Generate OTP
        $sdr_email = SDR_Email::get_instance();
        $otp_code = $sdr_email->generate_otp(6);
        
        // Save to database
        $sdr_db = SDR_Database::get_instance();
        $saved = $sdr_db->save_otp_request($product_id, $email, $otp_code);
        
        if (!$saved) {
            wp_send_json_error(array('message' => __('Failed to process request. Please try again.', 'simple-document-request')));
        }
        
        // Send email to customer
        $email_sent = $sdr_email->send_otp_email($email, $otp_code, $product_id);
        
        // Send admin notification (don't fail if this fails)
        $sdr_email->send_admin_notification($email, $product_id, $otp_code);
        
        if ($email_sent) {
            wp_send_json_success(array(
                'message' => __('OTP code has been sent to your email address.', 'simple-document-request')
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to send email. Please try again.', 'simple-document-request')));
        }
    }
    
    /**
     * Handle OTP verification
     */
    public function handle_verify_otp() {
        check_ajax_referer('sdr_nonce', 'nonce');
        
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $otp_code = isset($_POST['otp_code']) ? sanitize_text_field($_POST['otp_code']) : '';
        
        if (empty($email) || !is_email($email)) {
            wp_send_json_error(array('message' => __('Invalid email address.', 'simple-document-request')));
        }
        
        if (empty($product_id) || !wc_get_product($product_id)) {
            wp_send_json_error(array('message' => __('Invalid product.', 'simple-document-request')));
        }
        
        if (empty($otp_code)) {
            wp_send_json_error(array('message' => __('Please enter the OTP code.', 'simple-document-request')));
        }
        
        // Verify OTP
        $sdr_db = SDR_Database::get_instance();
        $verified = $sdr_db->verify_otp($product_id, $email, $otp_code);
        
        if ($verified) {
            // Get PDF URL for the product
            $pdf_url = $this->get_product_pdf_url($product_id);
            
            // Send authenticity document email with PDF attachment
            $sdr_email = SDR_Email::get_instance();
            if (!empty($pdf_url)) {
                $email_sent = $sdr_email->send_authenticity_document($email, $product_id, $pdf_url);
                
                if ($email_sent) {
                    error_log('SDR: Authenticity document email sent successfully to ' . $email);
                } else {
                    error_log('SDR: Failed to send authenticity document email to ' . $email);
                }
            } else {
                error_log('SDR: No PDF URL found for product ' . $product_id);
            }
            
            wp_send_json_success(array(
                'message' => __('✓ OTP verified successfully! The authenticity document has been sent to your email. Please check your inbox and junk/spam folder.', 'simple-document-request')
            ));
        } else {
            wp_send_json_error(array('message' => __('Invalid OTP code or code has already been used.', 'simple-document-request')));
        }
    }
    
    /**
     * Get product PDF URL
     * This assumes PDF is stored as product meta or custom field
     */
    private function get_product_pdf_url($product_id) {
        // Try to get PDF from custom field
        $pdf_url = get_post_meta($product_id, '_sdr_authenticity_pdf', true);
        
        if ($pdf_url) {
            return $pdf_url;
        }
        
        // Fallback: check if PDF is uploaded as attachment
        $attachments = get_posts(array(
            'post_type' => 'attachment',
            'posts_per_page' => 1,
            'post_parent' => $product_id,
            'post_mime_type' => 'application/pdf'
        ));
        
        if (!empty($attachments)) {
            return wp_get_attachment_url($attachments[0]->ID);
        }
        
        // Return empty if no PDF found
        return '';
    }
}
