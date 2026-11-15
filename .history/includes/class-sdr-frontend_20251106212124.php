<?php
/**
 * Frontend handling class
 */

if (!defined('ABSPATH')) {
    exit;
}

class SDR_Frontend {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('woocommerce_single_product_summary', array($this, 'add_authenticity_button'), 25);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'add_modal_html'));
    }
    
    /**
     * Add authenticity button to product page
     */
    public function add_authenticity_button() {
        global $product;
        
        if (!$product) {
            error_log('SDR: No product found in add_authenticity_button');
            return;
        }
        
        $product_id = $product->get_id();
        
        // Check if product has authenticity PDF URL
        $pdf_url = get_post_meta($product_id, '_sdr_authenticity_pdf', true);
        
        if (empty($pdf_url)) {
            error_log('SDR: No PDF URL set for product ID: ' . $product_id);
            return; // Don't show button if no PDF is configured
        }
        
        error_log('SDR: Rendering button for product ID: ' . $product_id);
        
        ?>
        <div class="sdr-authenticity-section" style="margin: 20px 0;">
            <button type="button" class="button sdr-request-btn" data-product-id="<?php echo esc_attr($product_id); ?>" style="padding: 12px 24px; font-size: 16px;">
                <?php _e('Click Here For Proof of Authenticity', 'simple-document-request'); ?>
            </button>
            <!-- Debug: Product ID = <?php echo esc_html($product_id); ?> -->
        </div>
        <?php
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        if (!is_product()) {
            return;
        }
        
        wp_enqueue_style('sdr-frontend', SDR_PLUGIN_URL . 'assets/css/frontend.css', array(), SDR_VERSION);
        wp_enqueue_script('sdr-frontend', SDR_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), SDR_VERSION, true);
        
        wp_localize_script('sdr-frontend', 'sdrData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sdr_nonce')
        ));
    }
    
    /**
     * Add modal HTML
     */
    public function add_modal_html() {
        if (!is_product()) {
            return;
        }
        
        ?>
        <div id="sdr-modal" class="sdr-modal" style="display: none;">
            <div class="sdr-modal-overlay"></div>
            <div class="sdr-modal-content">
                <span class="sdr-modal-close">&times;</span>
                <div class="sdr-modal-body">
                    <h2><?php _e('Request Authenticity Document', 'simple-document-request'); ?></h2>
                    
                    <!-- Step 1: Email Input -->
                    <div id="sdr-step-email" class="sdr-step">
                        <p><?php _e('Enter your email address to receive the OTP code:', 'simple-document-request'); ?></p>
                        <form id="sdr-email-form">
                            <input type="email" id="sdr-email-input" placeholder="<?php esc_attr_e('your@email.com', 'simple-document-request'); ?>" required style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px;">
                            <button type="submit" class="button button-primary" style="width: 100%; padding: 12px; margin-top: 10px;">
                                <?php _e('Send OTP Code', 'simple-document-request'); ?>
                            </button>
                        </form>
                        <div id="sdr-email-message" class="sdr-message" style="margin-top: 15px;"></div>
                    </div>
                    
                    <!-- Step 2: OTP Verification -->
                    <div id="sdr-step-otp" class="sdr-step" style="display: none;">
                        <p><?php _e('Enter the OTP code sent to your email:', 'simple-document-request'); ?></p>
                        <form id="sdr-otp-form">
                            <input type="text" id="sdr-otp-input" placeholder="<?php esc_attr_e('Enter OTP Code', 'simple-document-request'); ?>" maxlength="6" required style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; text-align: center; font-size: 20px; letter-spacing: 5px;">
                            <button type="submit" class="button button-primary" style="width: 100%; padding: 12px; margin-top: 10px;">
                                <?php _e('Verify OTP', 'simple-document-request'); ?>
                            </button>
                        </form>
                        <div id="sdr-otp-message" class="sdr-message" style="margin-top: 15px;"></div>
                        <p style="margin-top: 15px; font-size: 12px; color: #666;">
                            <a href="#" id="sdr-resend-otp"><?php _e('Resend OTP Code', 'simple-document-request'); ?></a>
                        </p>
                    </div>
                    
                    <!-- Step 3: PDF Display -->
                    <div id="sdr-step-pdf" class="sdr-step" style="display: none;">
                        <h3><?php _e('Authenticity Document', 'simple-document-request'); ?></h3>
                        <div id="sdr-pdf-container" style="margin-top: 20px;">
                            <iframe id="sdr-pdf-viewer" style="width: 100%; height: 600px; border: 1px solid #ddd;"></iframe>
                        </div>
                        <button type="button" class="button" id="sdr-close-pdf" style="width: 100%; padding: 12px; margin-top: 15px;">
                            <?php _e('Close', 'simple-document-request'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
