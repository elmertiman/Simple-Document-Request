<?php
/**
 * Email handling class
 */

if (!defined('ABSPATH')) {
    exit;
}

class SDR_Email {
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
     * Get site logo URL
     */
    private function get_site_logo() {
        // Try to get custom logo
        $custom_logo_id = get_theme_mod('custom_logo');
        
        if ($custom_logo_id) {
            $logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
            if ($logo_url) {
                return $logo_url;
            }
        }
        
        // Fallback: Try to get site icon
        $site_icon_url = get_site_icon_url(200);
        if ($site_icon_url) {
            return $site_icon_url;
        }
        
        // No logo found
        return false;
    }
    
    /**
     * Get logo HTML for email
     */
    private function get_logo_html() {
        $logo_url = $this->get_site_logo();
        $site_name = get_bloginfo('name');
        
        if ($logo_url) {
            return '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr($site_name) . '" style="max-width: 200px; max-height: 80px; margin-bottom: 15px;">';
        }
        
        return '';
    }
    
    /**
     * Send OTP email
     */
    public function send_otp_email($email, $otp_code, $product_id) {
        $product = wc_get_product($product_id);
        $product_name = $product ? $product->get_name() : 'Product';
        
        $subject = sprintf(__('Your Authenticity Verification Code - %s', 'simple-document-request'), $product_name);
        
        $message = sprintf(
            __('Hello,

You have requested an authenticity verification document for the product: %s

Your OTP (One-Time Password) code is: %s

Please enter this code on the product page to view your authenticity document.

This code is valid for one-time use only and will expire after verification.

Best regards,
%s', 'simple-document-request'),
            $product_name,
            $otp_code,
            get_bloginfo('name')
        );
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        // Use HTML email
        $html_message = $this->get_email_template($product_name, $otp_code);
        
        return wp_mail($email, $subject, $html_message, $headers);
    }
    
    /**
     * Get email template
     */
    private function get_email_template($product_name, $otp_code) {
        $site_name = get_bloginfo('name');
        $site_url = home_url();
        $logo_html = $this->get_logo_html();
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #f8f9fa; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .logo { margin-bottom: 10px; }
                .content { background-color: #ffffff; padding: 30px; border: 1px solid #e0e0e0; }
                .otp-box { background-color: #f0f0f0; padding: 20px; text-align: center; margin: 20px 0; border-radius: 5px; }
                .otp-code { font-size: 32px; font-weight: bold; color: #2c3e50; letter-spacing: 5px; font-family: monospace; }
                .footer { background-color: #f8f9fa; padding: 15px; text-align: center; border-radius: 0 0 5px 5px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>" . $logo_html . "</div>
                    <h2>" . esc_html($site_name) . "</h2>
                </div>
                <div class='content'>
                    <h3>" . __('Authenticity Verification Code', 'simple-document-request') . "</h3>
                    <p>" . sprintf(__('You have requested an authenticity verification document for:', 'simple-document-request'), esc_html($product_name)) . "</p>
                    <p><strong>" . esc_html($product_name) . "</strong></p>
                    <div class='otp-box'>
                        <p>" . __('Your OTP Code:', 'simple-document-request') . "</p>
                        <div class='otp-code'>" . esc_html($otp_code) . "</div>
                    </div>
                    <p>" . __('Please enter this code on the product page to view your authenticity document.', 'simple-document-request') . "</p>
                    <p><em>" . __('This code is valid for one-time use only and will expire after verification.', 'simple-document-request') . "</em></p>
                </div>
                <div class='footer'>
                    <p>" . sprintf(__('This email was sent from %s', 'simple-document-request'), '<a href="' . esc_url($site_url) . '">' . esc_html($site_name) . '</a>') . "</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Send admin notification email
     */
    public function send_admin_notification($email, $product_id, $otp_code) {
        $admin_email = get_option('admin_email');
        
        if (empty($admin_email)) {
            return false;
        }
        
        $product = wc_get_product($product_id);
        $product_name = $product ? $product->get_name() : __('Product #', 'simple-document-request') . $product_id;
        $product_url = $product ? get_permalink($product_id) : '';
        $product_edit_url = admin_url('post.php?post=' . $product_id . '&action=edit');
        
        $subject = sprintf(__('New Authenticity Request - %s', 'simple-document-request'), $product_name);
        
        $html_message = $this->get_admin_notification_template($email, $product_name, $product_id, $product_url, $product_edit_url, $otp_code);
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        return wp_mail($admin_email, $subject, $html_message, $headers);
    }
    
    /**
     * Get admin notification email template
     */
    private function get_admin_notification_template($customer_email, $product_name, $product_id, $product_url, $product_edit_url, $otp_code) {
        $site_name = get_bloginfo('name');
        $site_url = home_url();
        $admin_url = admin_url('admin.php?page=sdr-requests');
        $current_time = current_time('mysql');
        $logo_html = $this->get_logo_html();
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #0073aa; color: #fff; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .logo { margin-bottom: 10px; }
                .logo img { background-color: #fff; padding: 10px; border-radius: 5px; }
                .content { background-color: #ffffff; padding: 30px; border: 1px solid #e0e0e0; }
                .info-box { background-color: #f8f9fa; padding: 15px; margin: 15px 0; border-left: 4px solid #0073aa; border-radius: 4px; }
                .info-row { margin: 10px 0; }
                .info-label { font-weight: bold; color: #555; display: inline-block; min-width: 120px; }
                .info-value { color: #333; }
                .button { display: inline-block; padding: 10px 20px; background-color: #0073aa; color: #fff !important; text-decoration: none; border-radius: 4px; margin: 10px 5px 10px 0; }
                .button:hover { background-color: #005a87; }
                .footer { background-color: #f8f9fa; padding: 15px; text-align: center; border-radius: 0 0 5px 5px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>" . $logo_html . "</div>
                    <h2>" . __('New Authenticity Request', 'simple-document-request') . "</h2>
                </div>
                <div class='content'>
                    <p>" . __('A new authenticity document request has been received:', 'simple-document-request') . "</p>
                    
                    <div class='info-box'>
                        <div class='info-row'>
                            <span class='info-label'>" . __('Customer Email:', 'simple-document-request') . "</span>
                            <span class='info-value'>" . esc_html($customer_email) . "</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>" . __('Product:', 'simple-document-request') . "</span>
                            <span class='info-value'>" . esc_html($product_name) . "</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>" . __('Product ID:', 'simple-document-request') . "</span>
                            <span class='info-value'>#" . esc_html($product_id) . "</span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>" . __('OTP Code:', 'simple-document-request') . "</span>
                            <span class='info-value'><code>" . esc_html($otp_code) . "</code></span>
                        </div>
                        <div class='info-row'>
                            <span class='info-label'>" . __('Request Time:', 'simple-document-request') . "</span>
                            <span class='info-value'>" . esc_html($current_time) . "</span>
                        </div>
                    </div>
                    
                    <p>" . __('Quick Actions:', 'simple-document-request') . "</p>
                    <a href='" . esc_url($product_edit_url) . "' class='button'>" . __('Edit Product', 'simple-document-request') . "</a>
                    " . ($product_url ? "<a href='" . esc_url($product_url) . "' class='button' target='_blank'>" . __('View Product', 'simple-document-request') . "</a>" : "") . "
                    <a href='" . esc_url($admin_url) . "' class='button'>" . __('View All Requests', 'simple-document-request') . "</a>
                </div>
                <div class='footer'>
                    <p>" . sprintf(__('This notification was sent from %s', 'simple-document-request'), '<a href="' . esc_url($site_url) . '">' . esc_html($site_name) . '</a>') . "</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Send authenticity document email with PDF attachment
     */
    public function send_authenticity_document($email, $product_id, $pdf_url) {
        $product = wc_get_product($product_id);
        $product_name = $product ? $product->get_name() : __('Product', 'simple-document-request');
        
        $subject = sprintf(__('Your Authenticity Document - %s', 'simple-document-request'), $product_name);
        
        $html_message = $this->get_authenticity_document_template($product_name);
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        // Get the PDF file path for attachment
        $attachments = array();
        $pdf_path = $this->get_pdf_file_path($pdf_url, $product_id);
        
        if ($pdf_path && file_exists($pdf_path)) {
            $attachments[] = $pdf_path;
        } else {
            error_log('SDR: PDF file not found for attachment: ' . $pdf_url);
            // Still send email without attachment if PDF not accessible
        }
        
        $result = wp_mail($email, $subject, $html_message, $headers, $attachments);
        
        // Clean up temporary file if it was downloaded
        if ($pdf_path && strpos($pdf_path, sys_get_temp_dir()) !== false && file_exists($pdf_path)) {
            @unlink($pdf_path);
        }
        
        return $result;
    }
    
    /**
     * Get PDF file path from URL
     */
    private function get_pdf_file_path($pdf_url, $product_id) {
        // Check if it's a local WordPress attachment
        $attachment_id = attachment_url_to_postid($pdf_url);
        
        if ($attachment_id) {
            // Get local file path
            return get_attached_file($attachment_id);
        }
        
        // Check if URL is from the local WordPress site
        $upload_dir = wp_upload_dir();
        if (strpos($pdf_url, $upload_dir['baseurl']) !== false) {
            // Convert URL to local file path
            $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $pdf_url);
            if (file_exists($file_path)) {
                return $file_path;
            }
        }
        
        // For external URLs, download temporarily
        return $this->download_pdf_temporarily($pdf_url, $product_id);
    }
    
    /**
     * Download PDF temporarily for email attachment
     */
    private function download_pdf_temporarily($pdf_url, $product_id) {
        // Use WordPress HTTP API to download the file
        $response = wp_remote_get($pdf_url, array(
            'timeout' => 30,
            'sslverify' => false
        ));
        
        if (is_wp_error($response)) {
            error_log('SDR: Failed to download PDF: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        
        if (empty($body)) {
            error_log('SDR: Downloaded PDF is empty');
            return false;
        }
        
        // Save to temporary file
        $temp_file = tempnam(sys_get_temp_dir(), 'sdr_pdf_' . $product_id . '_');
        $temp_file_with_ext = $temp_file . '.pdf';
        
        // Rename to add .pdf extension
        rename($temp_file, $temp_file_with_ext);
        
        if (file_put_contents($temp_file_with_ext, $body) === false) {
            error_log('SDR: Failed to save temporary PDF file');
            return false;
        }
        
        return $temp_file_with_ext;
    }
    
    /**
     * Get authenticity document email template
     */
    private function get_authenticity_document_template($product_name) {
        $site_name = get_bloginfo('name');
        $site_url = home_url();
        $logo_html = $this->get_logo_html();
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #4CAF50; color: #fff; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .logo { margin-bottom: 10px; }
                .logo img { background-color: #fff; padding: 10px; border-radius: 5px; }
                .content { background-color: #ffffff; padding: 30px; border: 1px solid #e0e0e0; }
                .success-box { background-color: #d4edda; padding: 20px; text-align: center; margin: 20px 0; border-radius: 5px; border: 1px solid #c3e6cb; }
                .footer { background-color: #f8f9fa; padding: 15px; text-align: center; border-radius: 0 0 5px 5px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>" . $logo_html . "</div>
                    <h2>" . __('Authenticity Document Verified', 'simple-document-request') . "</h2>
                </div>
                <div class='content'>
                    <div class='success-box'>
                        <h3 style='color: #155724; margin-top: 0;'>✓ " . __('Verification Successful', 'simple-document-request') . "</h3>
                    </div>
                    <p>" . __('Thank you for verifying your authenticity document request.', 'simple-document-request') . "</p>
                    <p><strong>" . __('Product:', 'simple-document-request') . "</strong> " . esc_html($product_name) . "</p>
                    <p>" . __('Your authenticity certificate is attached to this email as a PDF document.', 'simple-document-request') . "</p>
                    <p>" . __('Please keep this document for your records.', 'simple-document-request') . "</p>
                    <hr style='border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;'>
                    <p style='font-size: 14px; color: #666;'>" . __('If you have any questions about this document, please contact us.', 'simple-document-request') . "</p>
                </div>
                <div class='footer'>
                    <p>" . sprintf(__('This email was sent from %s', 'simple-document-request'), '<a href="' . esc_url($site_url) . '">' . esc_html($site_name) . '</a>') . "</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Generate OTP code
     */
    public function generate_otp($length = 6) {
        return str_pad(wp_rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }
}
