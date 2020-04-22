<?php

if (!defined('ABSPATH'))
    exit;

class AWCFE_Backend
{

    /**
     * @var    object
     * @access  private
     * @since    1.0.0
     */
    private static $_instance = null;

    /**
     * The version number.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $_version;

    /**
     * The token.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $_token;

    /**
     * The main plugin file.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $file;

    /**
     * The main plugin directory.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $dir;

    /**
     * The plugin assets directory.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $assets_dir;

    /**
     * Suffix for Javascripts.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $script_suffix;

    /**
     * The plugin assets URL.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $assets_url;
    public $hook_suffix = array();
    public $plugin_slug;

    /**
     * Constructor function.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function __construct($file = '', $version = '1.0.0')
    {
        $this->_version = $version;
        $this->_token = AWCFE_TOKEN;
        $this->file = $file;
        $this->dir = dirname($this->file);
        $this->assets_dir = trailingslashit($this->dir) . 'assets';
        $this->assets_url = esc_url(trailingslashit(plugins_url('/assets/', $this->file)));

        $this->plugin_slug = 'abc';

        $this->script_suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';


        register_activation_hook($this->file, array($this, 'install'));

        add_action('admin_menu', array($this, 'register_root_page'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'), 10, 1);
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_styles'), 10, 1);

        add_filter('woocommerce_admin_billing_fields', array($this, 'admin_billing_fields'), 10, 1);
        add_filter('woocommerce_admin_shipping_fields', array($this, 'admin_shipping_fields'), 10, 1);

        add_action( '‌woocommerce_before_order_object_save', array($this, 'before_order_object_save'), 10, 2);

        // add_action('pll_init', array($this, 'pll_init')); // poly lang inits

        $plugin = plugin_basename($this->file);
        add_filter("plugin_action_links_$plugin", array($this, 'add_settings_link'));

    }


    public function before_order_object_save($order, $ata_store){
        // $order
        $ata_store;
    }
    /**
     *
     *
     * Ensures only one instance of WCPA is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @see WordPress_Plugin_Template()
     * @return Main WCPA instance
     */
    public static function instance($file = '', $version = '1.0.0')
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self($file, $version);
        }
        return self::$_instance;
    }


    public function admin_billing_fields($billing_fields)
    {

        return $this->admin_order_fields($billing_fields, 'billing');
    }

    public function admin_order_fields($billing_fields, $section)
    {

        global $post;
        $order_id = $post->ID;
        $order   = $order_id ? wc_get_order( $order_id ) : null;
        $meta_data = get_post_meta($order_id, AWCFE_ORDER_META_KEY, true);

        if (isset($meta_data[$section]) && is_array($meta_data[$section])) {
            foreach ($meta_data[$section] as $v) {
                if (!in_array($v['type'], ['paragraph', 'header'])) {

                    $billing_fields[str_replace($section.'_','',$v['name'])] = array(
                        'label' => $v['label'],
//                        'id' => $v['name'],
                        'show' => true
//                        'value' => $v['value'],
                    );
                }
            }
        }
        return $billing_fields;
    }

    public function admin_shipping_fields($billing_fields)
    {

        return $this->admin_order_fields($billing_fields, 'shipping');
    }

    public function register_root_page()
    {

        $this->hook_suffix[] = add_submenu_page(
            'woocommerce',
            __('WooCommerce Checkout Fields Editor', 'checkout-field-editor-and-manager-for-woocommerce'),
            __('Checkout Fields', 'checkout-field-editor-and-manager-for-woocommerce'),
            'manage_woocommerce',
            'awcfe_admin_ui',
            array($this, 'admin_ui')
        );
    }

    public function admin_ui()
    {
        AWCFE_Backend::view('admin-root', []);
    }

    public function add_settings_link($links)
    {
        $settings = '<a href="' . admin_url('admin.php?page=awcfe_admin_ui#/') . '">' . __('Checkout Fields','checkout-field-editor-and-manager-for-woocommerce') . '</a>';
        array_push($links, $settings);
        return $links;
    }

    /**
     *    Create post type forms
     */

    static function view($view, $data = array())
    {
        extract($data);
        include(plugin_dir_path(__FILE__) . 'views/' . $view . '.php');
    }


    /**
     * Load admin CSS.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function admin_enqueue_styles($hook = '')
    {
        wp_register_style($this->_token . '-admin', esc_url($this->assets_url) . 'css/backend.css', array(), $this->_version);
        wp_enqueue_style($this->_token . '-admin');
    }

    /**
     * Load admin Javascript.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function admin_enqueue_scripts($hook = '')
    {
        if (!isset($this->hook_suffix) || empty($this->hook_suffix)) {
            return;
        }


        $screen = get_current_screen();

        wp_enqueue_script('jquery');

        if (in_array($screen->id, $this->hook_suffix)) {
            $ml = new AWCFE_Ml();
            if (!wp_script_is('wp-i18n', 'registered')) {
                wp_register_script('wp-i18n', esc_url($this->assets_url) . 'js/i18n.min.js', array('jquery'), $this->_version, true);
            }


            wp_enqueue_script($this->_token . '-backend', esc_url($this->assets_url) . 'js/backend.js', array('wp-i18n'), $this->_version, true);
            wp_localize_script($this->_token . '-backend', 'awcfe_object', array(
                    'api_nonce' => wp_create_nonce('wp_rest'),
                    'root' => rest_url('awcfe/v1/'),
                    'isMlActive' => $ml->is_active(),
                    'ml' => $ml->is_active() ? [
                        'currentLang' => $ml->current_language(),
                        'isDefault' => $ml->is_default_lan() ? $ml->is_default_lan() : (($ml->current_language() === 'all') ? true : false)
                    ] : false

                )
            );

        }


    }


    /**
     * Cloning is forbidden.
     *
     * @since 1.0.0
     */
    public function __clone()
    {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), $this->_version);
    }

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */
    public function __wakeup()
    {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), $this->_version);
    }

    /**
     * Installation. Runs on activation.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function install()
    {
        $this->_log_version_number();

    }

    /**
     * Log the plugin version number.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    private function _log_version_number()
    {
        update_option($this->_token . '_version', $this->_version);
    }

    // public function pll_init()
    // {
    //     $ml = new AWCFE_Ml();
    //     if ($ml->is_active()) {
    //         $ml->settings_to_ml_poly();
    //     }
    //
    // }

}
