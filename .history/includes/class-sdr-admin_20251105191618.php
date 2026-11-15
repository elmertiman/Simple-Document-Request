<?php
/**
 * Admin handling class
 */

if (!defined('ABSPATH')) {
    exit;
}

class SDR_Admin {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_product_pdf_field'));
        add_action('woocommerce_process_product_meta', array($this, 'save_product_pdf_field'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Document Requests', 'simple-document-request'),
            __('Document Requests', 'simple-document-request'),
            'manage_options',
            'sdr-requests',
            array($this, 'render_admin_page'),
            'dashicons-email-alt',
            56
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        $sdr_db = SDR_Database::get_instance();
        $requests = $sdr_db->get_all_requests(100);
        $total = $sdr_db->get_total_requests();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Authenticity Document Requests', 'simple-document-request'); ?></h1>
            <p><?php printf(__('Total Requests: %d', 'simple-document-request'), $total); ?></p>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'simple-document-request'); ?></th>
                        <th><?php _e('Product', 'simple-document-request'); ?></th>
                        <th><?php _e('Email', 'simple-document-request'); ?></th>
                        <th><?php _e('OTP Code', 'simple-document-request'); ?></th>
                        <th><?php _e('Status', 'simple-document-request'); ?></th>
                        <th><?php _e('Requested At', 'simple-document-request'); ?></th>
                        <th><?php _e('Verified At', 'simple-document-request'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)) : ?>
                        <tr>
                            <td colspan="7"><?php _e('No requests found.', 'simple-document-request'); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($requests as $request) : 
                            $product = wc_get_product($request->product_id);
                            $product_name = $product ? $product->get_name() : __('Product #', 'simple-document-request') . $request->product_id;
                        ?>
                            <tr>
                                <td><?php echo esc_html($request->id); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(get_edit_post_link($request->product_id)); ?>" target="_blank">
                                        <?php echo esc_html($product_name); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html($request->email); ?></td>
                                <td><code><?php echo esc_html($request->otp_code); ?></code></td>
                                <td>
                                    <?php if ($request->otp_used) : ?>
                                        <span style="color: green;"><?php _e('Verified', 'simple-document-request'); ?></span>
                                    <?php else : ?>
                                        <span style="color: orange;"><?php _e('Pending', 'simple-document-request'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($request->created_at); ?></td>
                                <td><?php echo $request->verified_at ? esc_html($request->verified_at) : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <style>
            .wp-list-table th, .wp-list-table td {
                padding: 10px;
            }
        </style>
        <?php
    }
    
    /**
     * Add PDF field to product edit page
     */
    public function add_product_pdf_field() {
        global $post;
        $pdf_url = get_post_meta($post->ID, '_sdr_authenticity_pdf', true);
        
        woocommerce_wp_text_input(array(
            'id' => '_sdr_authenticity_pdf',
            'label' => __('Authenticity PDF URL', 'simple-document-request'),
            'description' => __('Enter the URL to the PDF file that will be shown after OTP verification.', 'simple-document-request'),
            'value' => $pdf_url,
            'type' => 'url',
            'desc_tip' => true
        ));
    }
    
    /**
     * Save product PDF field
     */
    public function save_product_pdf_field($post_id) {
        if (isset($_POST['_sdr_authenticity_pdf'])) {
            update_post_meta($post_id, '_sdr_authenticity_pdf', esc_url_raw($_POST['_sdr_authenticity_pdf']));
        }
    }
}
