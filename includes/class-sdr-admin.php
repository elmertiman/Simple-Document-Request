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
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_sdr_get_requests', array($this, 'ajax_get_requests'));
        add_action('wp_ajax_sdr_delete_request', array($this, 'ajax_delete_request'));
        add_action('wp_ajax_sdr_bulk_delete_requests', array($this, 'ajax_bulk_delete_requests'));
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
        $total = $sdr_db->get_total_requests();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Authenticity Document Requests', 'simple-document-request'); ?></h1>
            
            <div class="sdr-admin-toolbar">
                <div class="sdr-search-box">
                    <input type="search" id="sdr-search-input" placeholder="<?php esc_attr_e('Search by email, product ID, or OTP...', 'simple-document-request'); ?>" />
                    <button type="button" id="sdr-search-btn" class="button"><?php _e('Search', 'simple-document-request'); ?></button>
                    <button type="button" id="sdr-clear-search-btn" class="button" style="display:none;"><?php _e('Clear', 'simple-document-request'); ?></button>
                </div>
                
                <div class="sdr-filter-box">
                    <select id="sdr-status-filter">
                        <option value=""><?php _e('All Status', 'simple-document-request'); ?></option>
                        <option value="verified"><?php _e('Verified', 'simple-document-request'); ?></option>
                        <option value="pending"><?php _e('Pending', 'simple-document-request'); ?></option>
                    </select>
                </div>
            </div>
            
            <div class="sdr-bulk-actions" style="display:none;">
                <button type="button" id="sdr-bulk-delete-btn" class="button button-primary">
                    <i class="fas fa-trash-alt"></i> <?php _e('Delete Selected', 'simple-document-request'); ?> (<span id="sdr-selected-count">0</span>)
                </button>
            </div>
            
            <div class="sdr-results-info">
                <p><span id="sdr-showing-count">0</span> <?php _e('of', 'simple-document-request'); ?> <span id="sdr-total-count"><?php echo esc_html($total); ?></span> <?php _e('requests', 'simple-document-request'); ?></p>
            </div>
            
            <div id="sdr-loading" class="sdr-loading" style="display:none;">
                <span class="spinner is-active"></span> <?php _e('Loading...', 'simple-document-request'); ?>
            </div>
            
            <table class="wp-list-table widefat fixed striped" id="sdr-requests-table">
                <thead>
                    <tr>
                        <th class="check-column">
                            <input type="checkbox" id="sdr-select-all" />
                        </th>
                        <th class="sdr-sortable" data-column="id">
                            <span><?php _e('ID', 'simple-document-request'); ?></span>
                            <span class="sdr-sort-indicator"></span>
                        </th>
                        <th class="sdr-sortable" data-column="product_id">
                            <span><?php _e('Product', 'simple-document-request'); ?></span>
                            <span class="sdr-sort-indicator"></span>
                        </th>
                        <th class="sdr-sortable" data-column="email">
                            <span><?php _e('Email', 'simple-document-request'); ?></span>
                            <span class="sdr-sort-indicator"></span>
                        </th>
                        <th><?php _e('OTP Code', 'simple-document-request'); ?></th>
                        <th class="sdr-sortable" data-column="otp_used">
                            <span><?php _e('Status', 'simple-document-request'); ?></span>
                            <span class="sdr-sort-indicator"></span>
                        </th>
                        <th class="sdr-sortable sorted desc" data-column="created_at">
                            <span><?php _e('Requested At', 'simple-document-request'); ?></span>
                            <span class="sdr-sort-indicator">▼</span>
                        </th>
                        <th class="sdr-sortable" data-column="verified_at">
                            <span><?php _e('Verified At', 'simple-document-request'); ?></span>
                            <span class="sdr-sort-indicator"></span>
                        </th>
                        <th><?php _e('Actions', 'simple-document-request'); ?></th>
                    </tr>
                </thead>
                <tbody id="sdr-requests-tbody">
                    <tr>
                        <td colspan="9" class="sdr-loading-row"><?php _e('Loading...', 'simple-document-request'); ?></td>
                    </tr>
                </tbody>
            </table>
            
            <div class="sdr-no-results" style="display:none;">
                <p><?php _e('No requests found.', 'simple-document-request'); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        global $post;
        
        // Enqueue for product edit pages
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            if ($post && $post->post_type === 'product') {
                wp_enqueue_media();
                wp_enqueue_script('sdr-admin', SDR_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), SDR_VERSION, true);
            }
        }
        
        // Enqueue for requests page
        if ($hook === 'toplevel_page_sdr-requests') {
            // Enqueue Font Awesome
            wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0');
            
            wp_enqueue_script('sdr-admin-list', SDR_PLUGIN_URL . 'assets/js/admin-list.js', array('jquery'), SDR_VERSION, true);
            wp_enqueue_style('sdr-admin-list', SDR_PLUGIN_URL . 'assets/css/admin-list.css', array(), SDR_VERSION);
            
            wp_localize_script('sdr-admin-list', 'sdrAdminData', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sdr_admin_nonce')
            ));
        }
    }
    
    /**
     * Add PDF field to product edit page
     */
    public function add_product_pdf_field() {
        global $post;
        $pdf_url = get_post_meta($post->ID, '_sdr_authenticity_pdf', true);
        
        ?>
        <div class="options_group">
            <p class="form-field">
                <label for="_sdr_authenticity_pdf"><?php _e('Authenticity PDF', 'simple-document-request'); ?></label>
                <input type="text" id="_sdr_authenticity_pdf" name="_sdr_authenticity_pdf" value="<?php echo esc_attr($pdf_url); ?>" style="width: 70%; margin-right: 10px;" placeholder="<?php esc_attr_e('PDF URL', 'simple-document-request'); ?>">
                <button type="button" class="button button-secondary sdr-upload-pdf-btn"><?php _e('Upload PDF', 'simple-document-request'); ?></button>
                <button type="button" class="button button-secondary sdr-remove-pdf-btn" style="<?php echo empty($pdf_url) ? 'display:none;' : ''; ?>"><?php _e('Remove', 'simple-document-request'); ?></button>
                <span class="description" style="display: block; margin-top: 5px;">
                    <?php _e('Upload or enter the URL to the PDF file that will be sent after OTP verification.', 'simple-document-request'); ?>
                </span>
            </p>
        </div>
        <?php
    }
    
    /**
     * Save product PDF field
     */
    public function save_product_pdf_field($post_id) {
        if (isset($_POST['_sdr_authenticity_pdf'])) {
            update_post_meta($post_id, '_sdr_authenticity_pdf', esc_url_raw($_POST['_sdr_authenticity_pdf']));
        }
    }
    
    /**
     * AJAX handler to get requests
     */
    public function ajax_get_requests() {
        check_ajax_referer('sdr_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'simple-document-request')));
        }
        
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $orderby = isset($_POST['orderby']) ? sanitize_text_field($_POST['orderby']) : 'created_at';
        $order = isset($_POST['order']) ? sanitize_text_field($_POST['order']) : 'DESC';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 50;
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        
        $sdr_db = SDR_Database::get_instance();
        
        $args = array(
            'search' => $search,
            'orderby' => $orderby,
            'order' => $order,
            'status' => $status,
            'limit' => $limit,
            'offset' => $offset
        );
        
        $requests = $sdr_db->get_requests_filtered($args);
        $total = $sdr_db->get_requests_count_filtered($args);
        
        // Format the requests
        $formatted_requests = array();
        foreach ($requests as $request) {
            $product = wc_get_product($request->product_id);
            $product_name = $product ? $product->get_name() : __('Product #', 'simple-document-request') . $request->product_id;
            $product_edit_url = get_edit_post_link($request->product_id);
            
            $formatted_requests[] = array(
                'id' => $request->id,
                'product_id' => $request->product_id,
                'product_name' => $product_name,
                'product_edit_url' => $product_edit_url,
                'email' => $request->email,
                'otp_code' => $request->otp_code,
                'otp_used' => $request->otp_used,
                'status_label' => $request->otp_used ? __('Verified', 'simple-document-request') : __('Pending', 'simple-document-request'),
                'created_at' => $request->created_at,
                'verified_at' => $request->verified_at
            );
        }
        
        wp_send_json_success(array(
            'requests' => $formatted_requests,
            'total' => $total,
            'showing' => count($formatted_requests)
        ));
    }
    
    /**
     * AJAX handler to delete a request
     */
    public function ajax_delete_request() {
        check_ajax_referer('sdr_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'simple-document-request')));
        }
        
        $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
        
        if (empty($request_id)) {
            wp_send_json_error(array('message' => __('Invalid request ID', 'simple-document-request')));
        }
        
        $sdr_db = SDR_Database::get_instance();
        $deleted = $sdr_db->delete_request($request_id);
        
        if ($deleted) {
            wp_send_json_success(array(
                'message' => __('Request deleted successfully', 'simple-document-request')
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete request', 'simple-document-request')));
        }
    }
    
    /**
     * AJAX handler to bulk delete requests
     */
    public function ajax_bulk_delete_requests() {
        check_ajax_referer('sdr_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'simple-document-request')));
        }
        
        $request_ids = isset($_POST['request_ids']) ? $_POST['request_ids'] : array();
        
        if (empty($request_ids) || !is_array($request_ids)) {
            wp_send_json_error(array('message' => __('No requests selected', 'simple-document-request')));
        }
        
        // Sanitize IDs
        $request_ids = array_map('intval', $request_ids);
        
        $sdr_db = SDR_Database::get_instance();
        $deleted = $sdr_db->delete_requests($request_ids);
        
        if ($deleted) {
            $count = count($request_ids);
            wp_send_json_success(array(
                'message' => sprintf(
                    _n('%d request deleted successfully', '%d requests deleted successfully', $count, 'simple-document-request'),
                    $count
                )
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete requests', 'simple-document-request')));
        }
    }
}
