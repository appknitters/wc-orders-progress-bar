<?php
/**
 * Plugin Name: WooCommerce Orders Progress bar - Lite
 * Plugin URI: http://www.appknitters.com/
 * Description: Displays visual progress bar on orders and single order pages in customer's My Account.
 * Version: 1.5.0
 * Author: AppKnitters Co.
 * Author URI: http://www.appknitters.com/
 * Tested up to: 5.0.2
 *
 * Text Domain: woocommerce_order_progressbar
 * Domain Path: /lang/
 * 
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly


include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if (!is_plugin_active('woocommerce/woocommerce.php')) {
    return;
}
if (!defined('WCOPB_VER')) {
    define('WCOPB_VER', 1.5);
}
if (!class_exists('ANWC_Order_Progress_Bar')) {

    class ANWC_Order_Progress_Bar {

        private $options;

        public function __construct() {
            // Get defualt options
            $defaults = $this->get_default_options();
            // loading saved options.
            $options = get_option('anwc_order_progressbar');
            $this->options = wp_parse_args($options, $defaults);

            // adding settings page menu.
            add_action('admin_menu', array($this, 'admin_menu'));

            // rendering settings feilds 
            add_action('admin_init', array($this, 'admin_init'));
            // add css file for progress bar.
            add_action('wp_enqueue_scripts', array($this, 'scripts'));

            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));

            //Adding markup in my-account
            add_action('woocommerce_my_account_my_orders_column_order-status', array($this, 'my_orders_column'));
            add_action('woocommerce_view_order', array($this, 'my_orders_column'));
            add_action('manage_shop_order_posts_custom_column', array($this, 'shop_orders_column'), 10, 2);
        }

        public function scripts() {

            if (is_admin()) {
                
            } else {

                $selected_statuses = $this->options['wc_statuses'];
                $my_account_id = get_option('woocommerce_myaccount_page_id');
                $my_account = get_post_field('post_name', get_option('woocommerce_myaccount_page_id'));
                $checkout_page_id = woocommerce_get_page_id( 'checkout' );
                $checkout_page = get_post_field('post_name',$checkout_page_id);
                $is_orders_page = isset($this->options['is_orders_page']);
                $orders_page_slug = get_option('woocommerce_myaccount_orders_endpoint');
                $is_view_order_page = isset($this->options['is_view_order_page']);
                $view_order_page_slug = get_option('woocommerce_myaccount_view_order_endpoint');
                $style_options['theme'] = isset($this->options['theme']) ? $this->options['theme'] : '';
                $style_options['color'] = isset($this->options['color']) ? $this->options['color'] : '';
                $style_options['bgcolor'] = isset($this->options['bgcolor']) ? $this->options['bgcolor'] : '';
                $style_options['border'] = isset($this->options['border']) ? $this->options['border'] : '';
                $border_style = array('corners' => 0, 'rounded' => 10, 'circle' => 20);
                $style = ".meter:after {border-color:" . $this->darken_color($style_options['color']) . " !important;-moz-border-radius: {$border_style[$this->options['border']]}px;-webkit-border-radius: {$border_style[$this->options['border']]}px;border-radius: {$border_style[$this->options['border']]}px; } .meter span:after{background:" . $this->darken_color($style_options['color']) . " !important;-moz-border-radius: {$border_style[$this->options['border']]}px;-webkit-border-radius: {$border_style[$this->options['border']]}px;border-radius: {$border_style[$this->options['border']]}px; }.meter {-moz-border-radius: {$border_style[$this->options['border']]}px;-webkit-border-radius: {$border_style[$this->options['border']]}px;border-radius: {$border_style[$this->options['border']]}px; } .meter span{-moz-border-radius: {$border_style[$this->options['border']]}px;-webkit-border-radius: {$border_style[$this->options['border']]}px;border-radius: {$border_style[$this->options['border']]}px; }";
                
               
                $display_view_orders = false;
                if ($is_view_order_page && (strstr($_SERVER['REQUEST_URI'], $my_account . "/{$view_order_page_slug}/") || (isset($_REQUEST['page_id']) && $_REQUEST['page_id'] == $my_account_id && isset($_REQUEST['view-order'])))) {
                    $display_view_orders = true;
                } 
                if ($is_orders_page && (strstr($_SERVER['REQUEST_URI'], $my_account . "/{$orders_page_slug}/") || (isset($_REQUEST['page_id']) && $_REQUEST['page_id'] == $my_account_id && isset($_REQUEST['orders'])))) {
                    //Progress bar theme 
                    wp_enqueue_style ('anwc_progress_bar', plugin_dir_url (__FILE__) . 'css/progress-bar.css');
                    wp_add_inline_style ('anwc_progress_bar', $style);
                    // Script to trigger bar
                    wp_enqueue_script ('anwc_progress_bar_functions', plugin_dir_url (__FILE__) . 'js/functions.js', array ('jquery'));
                    wp_register_script ('anwc_progress_bar', plugin_dir_url (__FILE__) . 'js/progress_bar_orders.js', array ('jquery'));
                    wp_localize_script ('anwc_progress_bar', 'anwc_stages', $selected_statuses);
                    wp_localize_script ('anwc_progress_bar', 'anwc_style', $style_options);
                    wp_enqueue_script ('anwc_progress_bar');
                    } elseif ($display_view_orders) {
                    // Theme
                    wp_enqueue_style('anwc_progress_bar', plugin_dir_url(__FILE__) . 'css/progress-bar.css');
                    wp_add_inline_style('anwc_progress_bar', $style);
                    // Script to trigger bar
                    wp_enqueue_script('anwc_progress_bar_functions', plugin_dir_url(__FILE__) . 'js/functions.js', array('jquery'));
                    wp_register_script('anwc_progress_bar', plugin_dir_url(__FILE__) . 'js/progress_bar_view_order.js', array('jquery'));
                    wp_localize_script('anwc_progress_bar', 'anwc_stages', $selected_statuses);
                    wp_localize_script('anwc_progress_bar', 'anwc_style', $style_options);
                    wp_enqueue_script('anwc_progress_bar');
                }
            }
        }

        public function admin_scripts($page) {
            if (isset($_GET['page']) && $_GET['page'] == 'anwc_order_progressbar') {
                $wp_scripts = wp_scripts();
                wp_enqueue_style('anwc_progress_bar', plugin_dir_url(__FILE__) . 'css/progress-bar.css');
                wp_enqueue_style('spectrum', plugin_dir_url(__FILE__) . 'css/spectrum.css');
                wp_enqueue_style('jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/' . $wp_scripts->registered['jquery-ui-core']->ver . '/themes/smoothness/jquery-ui.css');
                wp_enqueue_style('anwc_progress_bar_options', plugin_dir_url(__FILE__) . 'css/admin_options.css');
                wp_enqueue_script('jquery-ui-core');
                wp_enqueue_script('jquery-ui-sortable');
                wp_enqueue_script('jquery-ui-tabs');
                wp_enqueue_script('spectrum', plugin_dir_url(__FILE__) . 'js/spectrum.js', array('jquery'));
                wp_enqueue_script('anwc_progress_bar_functions', plugin_dir_url(__FILE__) . 'js/functions.js', array('jquery'));
                wp_enqueue_script('anwc_progress_bar_options', plugin_dir_url(__FILE__) . 'js/admin_options.js', array('jquery', 'jquery-ui-sortable', 'jquery-ui-tabs'));
            }

            $selected_statuses = $this->options['wc_statuses'];


            $is_admin_orders_page = isset($this->options['is_admin_orders_page']);
            $style_options['theme'] = isset($this->options['theme']) ? $this->options['theme'] : '';
            $style_options['color'] = isset($this->options['color']) ? $this->options['color'] : '';
            $style_options['bgcolor'] = isset($this->options['bgcolor']) ? $this->options['bgcolor'] : '';
            $style_options['border'] = isset($this->options['border']) ? $this->options['border'] : '';
            $border_style = array('corners' => 0, 'rounded' => 10, 'circle' => 20);
            $style = ".meter:after {border-color:" . $this->darken_color($style_options['color']) . " !important;-moz-border-radius: {$border_style[$this->options['border']]}px;-webkit-border-radius: {$border_style[$this->options['border']]}px;border-radius: {$border_style[$this->options['border']]}px; } .meter span:after{background:" . $this->darken_color($style_options['color']) . " !important;-moz-border-radius: {$border_style[$this->options['border']]}px;-webkit-border-radius: {$border_style[$this->options['border']]}px;border-radius: {$border_style[$this->options['border']]}px; }.meter {-moz-border-radius: {$border_style[$this->options['border']]}px;-webkit-border-radius: {$border_style[$this->options['border']]}px;border-radius: {$border_style[$this->options['border']]}px; } .meter span{-moz-border-radius: {$border_style[$this->options['border']]}px;-webkit-border-radius: {$border_style[$this->options['border']]}px;border-radius: {$border_style[$this->options['border']]}px; }";

            if ($is_admin_orders_page && $page == 'edit.php' && $_GET['post_type'] == 'shop_order') {
                //Progress bar theme 
                wp_enqueue_style('anwc_progress_bar', plugin_dir_url(__FILE__) . 'css/progress-bar.css');
                wp_add_inline_style('anwc_progress_bar', $style);
                // Script to trigger bar
                wp_enqueue_script('anwc_progress_bar_functions', plugin_dir_url(__FILE__) . 'js/functions.js', array('jquery'));
                wp_register_script('anwc_progress_bar', plugin_dir_url(__FILE__) . 'js/admin-progress_bar_orders.js', array('jquery'));
                wp_localize_script('anwc_progress_bar', 'anwc_stages', $selected_statuses);
                wp_localize_script('anwc_progress_bar', 'anwc_style', $style_options);
                wp_enqueue_script('anwc_progress_bar');
            }
        }

        // adds custom menu to admin menu. we are adding our Order deadlines menu here 
        public function admin_menu() {

            add_submenu_page('woocommerce', 'Progress Bar', 'Orders Progress Bar', 'manage_woocommerce', 'anwc_order_progressbar', array($this, 'admin_menu_settings'));
        }

        public function admin_menu_settings() {
            $wc_statuses = wc_get_order_statuses();
            // Set class property
            ?>
            <div class="wrap">
                <h1>Progress bar settings</h1>
                <form method="post" action="options.php">
                    <?php
                    // This prints out all hidden setting fields
                    settings_fields('anwc_order_progressbar_options');
                    do_settings_sections('anwc_order_progressbar');
                    //do_settings_fields( 'anwc_order_progressbar',false );
                    submit_button();
                    ?>
                </form>
            </div>
            <?php
        }

        public function admin_init() {
            register_setting(
                    'anwc_order_progressbar_options', // Option group
                    'anwc_order_progressbar', // Option name
                    array($this, 'sanitize') // Sanitize
            );
            add_settings_section(
                    'anwc_order_progressbar_basic', // ID
                    'Basic', // Title
                    array($this, 'print_basic_info'), // Callback
                    'anwc_order_progressbar' // Page
            );
            add_settings_field(
                    'wc_statuses', // ID
                    'Statuses to use', // Title 
                    array($this, 'wc_statuses_callback'), // Callback
                    'anwc_order_progressbar', // Page
                    'anwc_order_progressbar_basic' // Section           
            );

            add_settings_section(
                    'anwc_order_progressbar_pages', // ID
                    'Pages', // Title
                    array($this, 'print_pages_info'), // Callback
                    'anwc_order_progressbar' // Page
            );

            add_settings_field(
                    'is_orders_page', // ID
                    '', // Title 
                    array($this, 'wc_is_orders_page_callback'), // Callback
                    'anwc_order_progressbar', // Page
                    'anwc_order_progressbar_pages' // Section           
            );

            add_settings_field(
                    'is_view_order_page', // ID
                    '', // Title 
                    array($this, 'wc_is_view_order_page_callback'), // Callback
                    'anwc_order_progressbar', // Page
                    'anwc_order_progressbar_pages' // Section           
            );
            add_settings_field(
                    'is_order_tracking_page', // ID
                    '', // Title 
                    array($this, 'wc_is_order_tracking_page_callback'), // Callback
                    'anwc_order_progressbar', // Page
                    'anwc_order_progressbar_pages' // Section           
            );
            add_settings_field(
                    'is_order_received_page', // ID
                    '', // Title 
                    array($this, 'wc_is_order_reveived_page_callback'), // Callback
                    'anwc_order_progressbar', // Page
                    'anwc_order_progressbar_pages' // Section           
            );

            add_settings_field(
                    'is_admin_orders_page', // ID
                    '', // Title 
                    array($this, 'wc_is_admin_orders_page_callback'), // Callback
                    'anwc_order_progressbar', // Page
                    'anwc_order_progressbar_pages' // Section           
            );

            add_settings_field(
                    'is_emails', // ID
                    '', // Title 
                    array($this, 'wc_is_emails_page_callback'), // Callback
                    'anwc_order_progressbar', // Page
                    'anwc_order_progressbar_pages' // Section           
            );

            add_settings_section(
                    'anwc_order_progressbar_style', // ID
                    'Style', // Title
                    array($this, 'print_style_info'), // Callback
                    'anwc_order_progressbar' // Page
            );
            add_settings_field(
                    'theme', // ID
                    'Theme', // Title 
                    array($this, 'wc_theme_callback'), // Callback
                    'anwc_order_progressbar', // Page
                    'anwc_order_progressbar_style' // Section           
            );

            add_settings_field(
                    'border', // ID
                    'Border', // Title 
                    array($this, 'wc_border_callback'), // Callback
                    'anwc_order_progressbar', // Page
                    'anwc_order_progressbar_style' // Section           
            );

            add_settings_field(
                    'preview', // ID
                    '', // Title 
                    array($this, 'wc_preview_callback'), // Callback
                    'anwc_order_progressbar', // Page
                    'anwc_order_progressbar_style' // Section           
            );
        }

        public function wc_statuses_callback() {
            $statuses = wc_get_order_statuses();
            $selected_arr = $this->options['wc_statuses'];
            if (!is_array($selected_arr)) {
                $selected_arr = array();
            }
            ?>

            <div id="wc_statuses" style="display:none">

                <?php foreach ($selected_arr as $key => $title): ?>
                    <input type="hidden" value="<?php echo $title; ?>" name="anwc_order_progressbar[wc_statuses][<?php echo $key; ?>]">
                <?php endforeach; ?>

            </div>
            <div id="statuses_selection_container">

                <div id="left">
                    <p>All Statuses</p>
                    <ul id="all_statuses" class="connected">
                        <?php foreach ($statuses as $slug => $title): ?>
                            <?php if (!in_array($title, $selected_arr)): ?>
                                <li data-id="<?php echo $slug; ?>" ><?php echo $title; ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div id="right">
                    <p>Selected: Success</p>
                    <ul id="selected_statuses" class="connected">
                        <?php foreach ($selected_arr as $slug => $title): ?>

                            <li data-id="<?php echo $slug; ?>"><?php echo $title; ?></li>

                        <?php endforeach; ?>
                    </ul>
                </div>
                <div id="right">
                    <p>Selected: Failed</p>
                    <ul id="failed_statuses" >
                        <li class="info">Please buy PRO version to use two different progress bars for failed and successfull tracks.</li>
                        <a class="button anwc_upgrade"  href="https://codecanyon.net/item/woocommerce-orders-progress-bar/20612363?ref=appknitters" >Buy PRO version</a>
            
                    </ul>
                </div>
            </div>
            <span> Please drop status of your choice from left panel into right panel. you can re-arrange status position on progress bar by dragging up and down in right(Selected) panel.</span>

            <?php
        }

        public function wc_is_orders_page_callback() {
            $checked = isset($this->options['is_orders_page']) ? $this->options['is_orders_page'] : '';
            $places = array('below' => 'Below order details', 'above' => 'Above order details');
            $selected = 'below';
            
            ?>
            <label for="is_orders_page" ><input class="is_page" type="checkbox" id="is_orders_page" name="anwc_order_progressbar[is_orders_page]" <?php checked($checked, 'on'); ?>/> Orders page</label>
            <div class="placements" style="display:none;">
            <select id="placement_orders" >
                <?php foreach ($places as $key => $title): ?>
                    <option disabled value="<?php echo $key; ?>" <?php selected($selected, $key);?>><?php echo $title ?> - PRO</option>
                <?php endforeach;?>
            </select>
            </div>
            <?php
        }

        public function wc_is_view_order_page_callback() {
            $checked = isset($this->options['is_view_order_page']) ? $this->options['is_view_order_page'] : '';
            $places = array('entry-header||above' => 'Above title', 'entry-header||below' => 'Below title', 'woocommerce-order-details||above' => 'Above order details', 'woocommerce-order-details||below' => 'Below order details', 'woocommerce-customer-details||above' => 'Above customer details', 'woocommerce-customer-details||below' => 'Below customer details');
            $selected = 'entry-header||below';
            
            ?>
            <label for="is_view_order_page" ><input class="is_page" type="checkbox" id="is_view_order_page" name="anwc_order_progressbar[is_view_order_page]" <?php checked($checked, 'on'); ?>/> View order page</label> 
            <div class="placements" style="display:none;">
            <select id="placement_order"   >

                <?php foreach ($places as $key => $title): ?>
                    <option disabled value="<?php echo $key; ?>" <?php selected($selected, $key);?>><?php echo $title ?> - PRO</option>
                <?php endforeach;?>
            </select>
            </div>
            <?php
        }

        public function wc_is_order_reveived_page_callback() {
            $checked = isset($this->options['is_order_received_page']) ? $this->options['is_order_received_page'] : '';
            ?>
            <label for="is_order_received_page" title="Availble in PRO version"><input disabled type="checkbox" id="is_order_received_page" name="anwc_order_progressbar[is_order_received_page]" <?php checked($checked, 'on'); ?>/> Order received page</label>    &nbsp; &nbsp;<a href="https://codecanyon.net/item/woocommerce-orders-progress-bar/20612363?ref=appknitters" class="anwc_buy_pro" >Buy PRO</a>

            <?php
        }

        public function wc_is_order_tracking_page_callback() {
            $checked = isset($this->options['is_order_tracking_page']) ? $this->options['is_order_tracking_page'] : '';
            ?>
            <label for="is_order_tracking_page" title="Availble in PRO version" ><input disabled type="checkbox" id="is_order_tracking_page" name="anwc_order_progressbar[is_order_tracking_page]" <?php checked($checked, 'on'); ?>/> Order tracking page</label>    &nbsp; &nbsp;<a href="https://codecanyon.net/item/woocommerce-orders-progress-bar/20612363?ref=appknitters" class="anwc_buy_pro" >Buy PRO</a>

            <?php
        }

        public function wc_is_admin_orders_page_callback() {
            $checked = isset($this->options['is_admin_orders_page']) ? $this->options['is_admin_orders_page'] : '';
            ?>
            <label for="is_admin_orders_page" title="Availble in PRO version" ><input disabled type="checkbox" id="is_admin_orders_page" name="anwc_order_progressbar[is_admin_orders_page]" <?php checked($checked, 'on'); ?>/> Admin orders listing page</label>    &nbsp; &nbsp;<a href="https://codecanyon.net/item/woocommerce-orders-progress-bar/20612363?ref=appknitters" class="anwc_buy_pro" >Buy PRO</a>

            <?php
        }

        public function wc_is_emails_page_callback() {
            $checked = isset($this->options['is_emails']) ? $this->options['is_emails'] : '';
            ?>
            <label for="is_emails" title="Availble in PRO version" ><input disabled type="checkbox" id="is_emails" name="anwc_order_progressbar[is_emails]" <?php checked($checked, 'on'); ?>/> Order emails</label>    &nbsp; &nbsp;<a href="https://codecanyon.net/item/woocommerce-orders-progress-bar/20612363?ref=appknitters" class="anwc_buy_pro" >Buy PRO</a>

            <?php
        }

        public function wc_theme_callback() {

            $selected = isset($this->options['theme']) ? $this->options['theme'] : '';
            $color = isset($this->options['color']) ? $this->options['color'] : '';
            $bgcolor = isset($this->options['bgcolor']) ? $this->options['bgcolor'] : 'lightgray';

            $themes = ['stripes' => 'Stripes', 'glow' => 'Glow', 'shine' => 'Shine'];

            $themes = apply_filters('an_orders_progress_bar_themes', $themes);
            ?>

            <select id="theme" name="anwc_order_progressbar[theme]"  >
                <option value="" <?php selected($selected, ''); ?>>Default</option>
                <?php foreach ($themes as $key => $title): ?>
                    <option value="<?php echo $key; ?>" <?php selected($selected, $key); ?>><?php echo $title ?></option>
                <?php endforeach; ?>
            </select>
            <p>
                <label for="color">
                    <input type="text" id="color" name="anwc_order_progressbar[color]" value="<?php echo $color; ?>"/>
                    <span>Progress color</span>
                </label>
            </p>
            <p>
                <label for="bgcolor">
                    <input type="text" id="bgcolor" name="anwc_order_progressbar[bgcolor]" value="<?php echo $bgcolor; ?>"/>
                    <span>Background color</span>
                </label>
            </p>


            <?php
        }

        public function wc_border_callback() {

            $selected = isset($this->options['border']) ? $this->options['border'] : '';
            ?>
            <select id="border" name="anwc_order_progressbar[border]" >
                <option value="corners" <?php selected($selected, 'corners'); ?>>Corners</option>
                <option value="rounded" <?php selected($selected, 'rounded'); ?>>Rounded</option>
                <option value="circle" <?php selected($selected, 'circle'); ?>>Circle</option>

            </select>
            <br><br>
            <label for="border_width" title="Available in PRO">
            <p>Width    &nbsp; &nbsp;<a href="https://codecanyon.net/item/woocommerce-orders-progress-bar/20612363?ref=appknitters" class="anwc_buy_pro" >Buy PRO</a></p>
            <input type="number" id="border_width"  value="0" disabled />px
            </label>
            <br/><br/>
            <label for="border_style" title="Available in PRO">
            <p>Style    &nbsp; &nbsp;<a href="https://codecanyon.net/item/woocommerce-orders-progress-bar/20612363?ref=appknitters" class="anwc_buy_pro" >Buy PRO</a></p>
            <select id="border_style" disabled  >
                <option value="solid" <?php selected($selected, 'solid');?>>Solid</option>
                <option value="dotted" <?php selected($selected, 'dotted');?>>Dotted</option>
                <option value="dashed" <?php selected($selected, 'dashed');?>>Dashed</option>
                <option value="double" <?php selected($selected, 'double');?>>Double</option>
                <option value="groove" <?php selected($selected, 'groove');?>>Groove</option>
                <option value="ridge" <?php selected($selected, 'ridge');?>>Ridge</option>
                <option value="inset" <?php selected($selected, 'inset');?>>Inset</option>
                <option value="outset" <?php selected($selected, 'outset');?>>Outset</option>
            </select>
            </label>
            <br/><br/>
            <label for="border_color" title="Available in PRO">
            <p>Color    &nbsp; &nbsp;<a href="https://codecanyon.net/item/woocommerce-orders-progress-bar/20612363?ref=appknitters" class="anwc_buy_pro" >Buy PRO</a></p>
            <input type="text" id="border_color"  value="" class='colors_picker' disabled />
            </label>
            <br/><br/>
            <label for="border_radius" title="Available in PRO">
            <p>Radius (Between 0px and 15px)    &nbsp; &nbsp;<a href="https://codecanyon.net/item/woocommerce-orders-progress-bar/20612363?ref=appknitters" class="anwc_buy_pro" >Buy PRO</a></p>
            <input disabled type="number" id="border_radius"  value="0" min="0" max="15" />px
            </label>

            <?php
        }

        public function wc_preview_callback() {
            ?>
            <a title="Hire us for Custom development" class="button button-primary anwc_hire sd "  href="https://www.appknitters.com" target="__blank"> Hire us</a>
            <div id="anwcpb_preview"></div>
            <?php
        }

        public function sanitize($input) {
            $new_input = array();
            // if (isset($input['wc_statuses']))
            //$input['wc_statuses'] = serialize($input['wc_statuses']);
            return $input;
        }

        public function print_basic_info() {
            print 'Please select woocommerce statuses to display in progress bar.';
        }

        public function print_pages_info() {
            print 'Please select pages on which you want to display progress bar.';
        }

        public function print_style_info() {
            print 'Please select style related options for your bar.';
        }

        

        public function my_orders_column($order) {
            if (is_object($order)) {
                $order_number = $order->get_id();
                $order_status = $order->get_status();
                echo wc_get_order_status_name($order_status);
            } else {
                $order_number = $order;
                $order = wc_get_order($order);
                $order_status = $order->get_status();
            }
            ?>

            <input type="hidden" id="opb_order_number" value="<?php echo $order_number; ?>" />
            <input type="hidden" id="opb_order_status" value="wc-<?php echo $order_status; ?>" />

            <?php
        }

        public function shop_orders_column($column, $order) {
            if ($column == 'order_status') {
                if (is_object($order)) {
                    $order_number = $order->get_id();
                    $order_status = $order->get_status();
                    echo wc_get_order_status_name($order_status);
                } else {
                    $order_number = $order;
                    $order = wc_get_order($order);
                    $order_status = $order->get_status();
                }
                ?>

                <input type="hidden" id="opb_order_number" value="<?php echo $order_number; ?>" />
                <input type="hidden" id="opb_order_status" value="wc-<?php echo $order_status; ?>" />

                <?php
            }
        }

        private function darken_color($rgb, $darker = 2) {

            $hash = (strpos($rgb, '#') !== false) ? '#' : '';
            $rgb = (strlen($rgb) == 7) ? str_replace('#', '', $rgb) : ((strlen($rgb) == 6) ? $rgb : false);
            if (strlen($rgb) != 6)
                return $hash . '000000';
            $darker = ($darker > 1) ? $darker : 1;

            list($R16, $G16, $B16) = str_split($rgb, 2);

            $R = sprintf("%02X", floor(hexdec($R16) / $darker));
            $G = sprintf("%02X", floor(hexdec($G16) / $darker));
            $B = sprintf("%02X", floor(hexdec($B16) / $darker));

            return $hash . $R . $G . $B;
        }

        private function get_default_options() {

            $defaults['wc_statuses'] = array(
                'wc-pending' => 'Pending payment',
                'wc-on-hold' => 'On hold',
                'wc-processing' => 'Processing',
                'wc-completed' => 'Completed'
            );
            $defaults['theme'] = 'stripes';
            $defaults['color'] = '#4db91b';
            $defaults['bgcolor'] = 'lightgray';
            $defaults['border'] = 'circle';

            return $defaults;
        }

    }

    new ANWC_Order_Progress_Bar();
}