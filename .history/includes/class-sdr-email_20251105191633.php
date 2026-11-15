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
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #f8f9fa; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background-color: #ffffff; padding: 30px; border: 1px solid #e0e0e0; }
                .otp-box { background-color: #f0f0f0; padding: 20px; text-align: center; margin: 20px 0; border-radius: 5px; }
                .otp-code { font-size: 32px; font-weight: bold; color: #2c3e50; letter-spacing: 5px; font-family: monospace; }
                .footer { background-color: #f8f9fa; padding: 15px; text-align: center; border-radius: 0 0 5px 5px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
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
     * Generate OTP code
     */
    public function generate_otp($length = 6) {
        return str_pad(wp_rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }
}
