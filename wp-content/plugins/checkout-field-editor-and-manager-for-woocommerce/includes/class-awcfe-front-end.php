<?php

if (!defined('ABSPATH'))
    exit;

class AWCFE_Front_End
{


    private static $_instance = null;

    public $_version;

    /**
     * The token.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $_token;
    /**
     * The plugin assets URL.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $assets_url;
    /**
     * The main plugin file.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $file;

    function __construct($file = '', $version = '1.0.0')
    {
// Load frontend JS & CSS

        $this->_version = $version;
        $this->_token = AWCFE_TOKEN;

        /**
         * Check if WooCommerce is active
         * */
        if ($this->check_woocommerce_active()) {


            $this->file = $file;

            $this->assets_url = esc_url(trailingslashit(plugins_url('/assets/', $this->file)));

            add_filter('woocommerce_checkout_fields', array($this, 'get_checkout_fields'), 99999, 1);

            add_filter('woocommerce_get_country_locale_default', array($this, 'get_country_locale_default'), 10, 1);
		        add_filter('woocommerce_get_country_locale_base', array($this, 'get_country_locale_default'), 10, 1);
            add_filter('woocommerce_get_country_locale', array($this, 'get_country_locale_country'), 10, 1);

            add_action('woocommerce_checkout_update_order_meta', array($this, 'update_order_meta'), 10, 2);

            add_action('woocommerce_form_field', array($this, 'woocommerce_form_field'), 10, 4);

            add_action('woocommerce_order_details_after_order_table', array($this, 'order_details_after_order_table'), 10, 1);
            add_action('woocommerce_email_after_order_table', array($this, 'email_after_order_table'), 10, 1);

            //add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'fields_display_order_data_billing_in_admin'), 20, 1);
            add_action('woocommerce_admin_order_data_after_shipping_address', array($this, 'fields_display_order_data_shipping_in_admin'), 20, 1);

            add_action('updated_post_meta', array($this, 'updated_order_meta'), 20, 4);

            add_action('save_post', array($this, 'before_order_object_save'),10, 1);

            // add_action( 'wp_footer', array($this, 'awcfe_custom_footer_script'), 100 );

        }


    }

    public function check_woocommerce_active()
    {
        if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            return true;
        }
        if (is_multisite()) {
            $plugins = get_site_option('active_sitewide_plugins');
            if (isset($plugins['woocommerce/woocommerce.php']))
                return true;
        }
        return false;
    }

    public static function instance($parent)
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self($parent);
        }
        return self::$_instance;
    }

    /*
    public function awcfe_custom_footer_script(){
      ?>
      <script>
      jQuery(window).load(function(){
        jQuery(".checkout.woocommerce-checkout .form-row").each(function() {
            if(jQuery(this).is(":hidden")){
              jQuery(this).find('.woocommerce-input-wrapper input, .woocommerce-input-wrapper select, .woocommerce-input-wrapper textarea, .woocommerce-input-wrapper .input-text ').attr('disabled', true);
            }
        });
      });
      </script>
      <?php
    }
    */

    public function getSectionDefaultTitle($section) {

        $customSections = get_option(AWCFE_FIELDS_KEY);
        $sectionName = $customSections['fields'][$section]['extra']['name'];
        return $sectionName;

    }

    public function email_after_order_table($order)
    {
        $order_id = $order->get_id();
        $awcf_data = get_post_meta($order_id, AWCFE_ORDER_META_KEY, true);
        if( is_array($awcf_data) ){
          unset($awcf_data['account']);
        }

        if ($awcf_data) {
            echo '<table>';
            foreach ($awcf_data as $section => $fields) {

                $outString = $outString1 ='';
                $sectionName = $this->getSectionDefaultTitle($section);

                if ($fields) {
                    $outString1 .= '<tr class="awcfe-' . $section . '-extra-items" ><th colspan="2" >' . __(ucfirst($sectionName), 'woocommerce') . ' ' . __('Extra Fields', 'checkout-field-editor-and-manager-for-woocommerce') . ' </td></tr>';
                }
                uasort($fields, 'wc_checkout_fields_uasort_comparison');
                $row_template = '<tr class="awcfe-' . $section . '-extra-items" ><th>%1$s</th><td>%2$s</td></tr>';
                foreach ($fields as $key => $val) {
                    if (isset($val['show_in_email']) && $val['show_in_email'] === true) {

                        if($val['type'] == 'header' || $val['type'] == 'paragraph' ){
                            $outString .= sprintf($row_template, $val['label'], $val['value']);
                        }
                        if (!empty($val['value'])) {
                            if (is_array($val['value'])) {
                                $outString .= sprintf($row_template, $val['label'], esc_attr(implode(', ', $val['value'])));
                            } else {
                                $outString .= sprintf($row_template, $val['label'], $val['value']);
                            }
                        }
                        // echo sprintf($row_template, $val['label'], $val['value']);
                    }
                }
                if( $outString ){ echo $outString1.''.$outString; }

            }
            echo '</table>';
        }

    }

    public function order_details_after_order_table($order)
    {
        $order_id = $order->get_id();
        $awcf_data = get_post_meta($order_id, AWCFE_ORDER_META_KEY, true);
        if( is_array($awcf_data) ){
          unset($awcf_data['account']);
        }

        if ($awcf_data) {
            echo '<table class="woocommerce-table awcfe-order-extra-details">';
            foreach ($awcf_data as $section => $fields) {

                $outString = $outString1 ='';
                $sectionName = $this->getSectionDefaultTitle($section);

                if ($fields) {
                    $outString1 .= '<tr class="awcfe-' . $section . '-extra-items" ><th colspan="2" >' . __(ucfirst($sectionName), 'woocommerce') . ' ' . __('Extra Fields', 'checkout-field-editor-and-manager-for-woocommerce') . ' </td></tr>';
                }
                uasort($fields, 'wc_checkout_fields_uasort_comparison');
                $row_template = '<tr class="awcfe-' . $section . '-extra-items" ><th>%1$s</th><td>%2$s</td></tr>';
                foreach ($fields as $key => $val) {
                    if (isset($val['show_in_order_page']) && $val['show_in_order_page'] === true) {

                        if($val['type'] == 'header' || $val['type'] == 'paragraph' ){
                            $outString .= sprintf($row_template, $val['label'], $val['value']);
                        }
                        if (!empty($val['value'])) {
                            if (is_array($val['value'])) {
                                $outString .= sprintf($row_template, $val['label'], esc_attr(implode(', ', $val['value'])));
                            } else {
                                $outString .= sprintf($row_template, $val['label'], $val['value']);
                            }
                        }
                        // echo sprintf($row_template, $val['label'], $val['value']);
                    }
                }
                if( $outString ){ echo $outString1.''.$outString; }



            }
            echo '</table>';
        }


    }

    public function woocommerce_form_field($field, $key, $args, $value)
    {
        if ($args['type'] === 'paragraph') {
            $field .= '<p class="' . AWCFE_TOKEN . '_paragraph_field " >' . do_shortcode(nl2br($args['label'])) . '</p>';
            if (!empty($field)) {
                $field_html = '';

                $field_html .= $field;
                $container_class = esc_attr(implode(' ', $args['class']));
                $sort = $args['priority'] ? $args['priority'] : '';
                $field_container = '<div class="form-row %1$s"  data-priority="' . esc_attr($sort) . '">%2$s</div>';
                $field = sprintf($field_container, $container_class, $field_html);
            }
        }
        if ($args['type'] === 'header') {
            $field .= '<' . $args['subtype'] . ' class="' . AWCFE_TOKEN . '_paragraph_field " >' . do_shortcode(nl2br($args['label'])) . '</' . $args['subtype'] . '>';
            if (!empty($field)) {
                $field_html = '';

                $field_html .= $field;
                $container_class = esc_attr(implode(' ', $args['class']));
                $sort = $args['priority'] ? $args['priority'] : '';
                $field_container = '<div class="form-row %1$s"  data-priority="' . esc_attr($sort) . '">%2$s</div>';
                $field = sprintf($field_container, $container_class, $field_html);
            }
        }

        return $field;
    }



    public function update_order_meta($order_id, $postData)
    {

        $shipto_diff = isset($postData['ship_to_different_address']) ? $postData['ship_to_different_address'] : false;

        $checkout_fields = WC()->checkout()->get_checkout_fields();
        $fieldSchema = [];

        //$user_id = get_current_user_id();
        $order = $order_id ? wc_get_order($order_id) : null;
        foreach ($checkout_fields as $sekKey => $section) {

          if( $sekKey == 'shipping' && ( ! $shipto_diff || ! WC()->cart->needs_shipping_address() )){
            continue;
          }

            $fieldSchema[$sekKey] = [];
            foreach ($section as $key => $field) {
                if (isset($field['custom']) && $field['custom'] && isset($postData[$key])) {
                    $value = wc_clean($postData[$key]);

                    $meta_id = false;
                    if ($value) {
                        $meta_id = update_post_meta($order_id, '_' . $key, $value);
//                        $order->update_meta_data($key,$value);
                    }
                    if (!in_array($field['type'], ['paragraph', 'header'])
                        || (isset($field['show_in_email']) && $field['show_in_email'] === true)
                        || (isset($field['show_in_order_page']) && $field['show_in_order_page'] === true))
                        $fieldSchema[$sekKey][] = array(
                            'type' => $field['type'],
                            'meta_id' => $meta_id,
                            'name' => $field['name'],
                            'label' => (isset($field['label'])) ? (($field['label'] == '') ? AWCFE_EMPTY_LABEL : $field['label']) : AWCFE_EMPTY_LABEL,
                            'value' => $value,
                            'priority' => $field['priority'],
                            'col' => $field['col'],
                            'show_in_email' => isset($field['show_in_email']) ? $field['show_in_email'] : false,
                            'show_in_order_page' => isset($field['show_in_order_page']) ? $field['show_in_order_page'] : false,
                        );

                }
            }
        }

        if (!empty($fieldSchema)) {
            update_post_meta($order_id, AWCFE_ORDER_META_KEY, $fieldSchema);
            // $order->update_meta_data(AWCFE_ORDER_META_KEY, $fieldSchema);
        }

        /* $accountMeta = $fieldSchema['account'];
        if($user_id && $user_id != 0){
          if($accountMeta){
            foreach($accountMeta as $accountMetaDet){

              update_user_meta($user_id, $accountMetaDet['name'], $accountMetaDet['value']);

            }
          }
        } */


    }

    public function checkDefaultFieldAttr($field) {

        $customSections = get_option(AWCFE_FIELDS_KEY);
        $sectionDet = $customSections['fields']['billing']['fields'];
        if($sectionDet){
          foreach ($sectionDet as $key => $value) {
            foreach ($value as $skey => $svalue) {
              if($field == $svalue['name']){
                return @$svalue['required'];
              }
            }
          }
        }

    }


    function get_country_locale_country($fields)
    {
        if (is_array($fields)) {
            foreach ($fields as $key => $val) {
              foreach($val as $vkey => $vval){

                if (isset($vval['priority'])) {
                    unset($fields[$key][$vkey]['priority']);
                }
                if (isset($vval['label'])) {
                    unset($fields[$key][$vkey]['label']);
                }
                if (isset($vval['required'])) {
                    unset($fields[$key][$vkey]['required']);
                }
                // if (isset($vval['validate'])) {
                    // unset($fields[$key][$vkey]['validate']);
                // }

              }
            }
        }
        return $fields;
    }


    function get_country_locale_default($fields)
    {
        if (is_array($fields)) {
            foreach ($fields as $key => $val) {

                $retVal = $this->checkDefaultFieldAttr('billing_'. $key);

                if (isset($val['priority'])) {
                    unset($fields[$key]['priority']);
                }

                if (isset($val['label'])) {
                    unset($fields[$key]['label']);
                }

                if (isset($val['required'])) {
                  $fields[$key]['required'] = $retVal;
                }

                // if (isset($val['validate'])) {
                  // unset($fields[$key]['validate']);
                // }

            }
        }
        return $fields;
    }


    /**
     * @param $defaultFields
     * @return array|mixed|void
     */
    public function get_checkout_fields($defaultFields)
    {
        $fields = new AWCFE_Fields();
        return $fields->getFields($defaultFields);
    }


    /* 05 Aug 19 */

    public function fields_display_order_data_billing_in_admin($order)
    {
        // echo 'billing';
        $result = '';
        $order_id = $order->get_id();
        $awcf_data = get_post_meta($order_id, AWCFE_ORDER_META_KEY, true);
        if ($awcf_data) {
            $billing = $awcf_data['billing'];
            if ($billing) {
                $result .= '<div class="address" style="clear:left;" >';
                $result .= '<h3>' . __('Billing extra fields', 'checkout-field-editor-and-manager-for-woocommerce') . '</h3>';
                foreach ($billing as $billing_det) {
                    $result .= '<p><strong>' . $billing_det['label'] . ':</strong> ' . $billing_det['value'] . '</p>';
                }
               $result .= '</div>';
            }
        }
        echo $result;
    }

    public function fields_display_order_data_shipping_in_admin($order)
    {
        // echo 'shipping';
        $result = '';
        $order_id = $order->get_id();
        $awcf_data = get_post_meta($order_id, AWCFE_ORDER_META_KEY, true);

        if ($awcf_data) {
            $billing_order = $awcf_data['order'];
            if ($billing_order) {
              $result .= '<div class="address" style="clear:left;" >';
                $result .= '<h3>' . __('Order extra fields', 'checkout-field-editor-and-manager-for-woocommerce') . '</h3>';
                foreach ($billing_order as $billing_order_det) {
                    $result .= '<p><strong>' . $billing_order_det['label'] . ':</strong> ' . $billing_order_det['value'] . '</p>';
                }
              $result .= '</div>';


              $result .= '<div class="edit_address " style="clear:left;" >';
              ob_start();
                foreach ($billing_order as $billing_order_det) {

                  if( $billing_order_det['type'] == 'textarea' ){
                    woocommerce_wp_textarea_input( array(
              				'id' => '_'.$billing_order_det['name'],
              				'label' => $billing_order_det['label'],
              				'value' => $billing_order_det['value'],
              				'wrapper_class' => 'form-field-wide'
              			) );
                  } else {
                    woocommerce_wp_text_input( array(
                      'id' => '_'.$billing_order_det['name'],
                      'label' => $billing_order_det['label'],
                      'value' => $billing_order_det['value'],
                      'wrapper_class' => 'form-field'
                    ) );
                  }
                  /*woocommerce_wp_text_input( array(
                    'id' => '_'.$billing_order_det['name'],
                    'label' => $billing_order_det['label'],
                    'value' => $billing_order_det['value'],
                    'wrapper_class' => 'form-field'
                  ) );*/

                }

                $message = ob_get_contents();
                ob_end_clean();
                $result .= $message.'</div>';

            }
            /*
            $billing = $awcf_data['shipping'];
            if ($billing) {
              $result .= '<div class="address" style="clear:left;" >';
                $result .= '<h3>' . __('Shipping extra fields', 'checkout-field-editor-and-manager-for-woocommerce') . '</h3>';
                foreach ($billing as $billing_det) {
                    $result .= '<p><strong>' . $billing_det['label'] . ':</strong> ' . $billing_det['value'] . '</p>';
                }
              $result .= '</div>';
            }
            */
        }
        echo $result;
    }


    function updated_order_meta($meta_id, $object_id, $meta_key, $_meta_value)
    {


        $order = wc_get_order($object_id);
        if ($order === false)
            return false;

        $awcf_data = get_post_meta($object_id, AWCFE_ORDER_META_KEY, true);
        $fieldset = [];

        if ($awcf_data) {
            foreach ($awcf_data as $key => $field) {
                $fieldset[$key] = [];
                foreach ($field as $skey => $sfield) {
                    if ($sfield['meta_id'] == $meta_id && $sfield['name'] == $meta_key) {
                        $sfield['value'] = $_meta_value;
                        $fieldset[$key][] = $sfield;
                    } else {
                        $fieldset[$key][] = $sfield;
                    }

                }
            }
        }
        if (!empty($fieldset)) {
            update_post_meta($object_id, AWCFE_ORDER_META_KEY, $fieldset);
        }


    }



    public function before_order_object_save($arg=false){

        if ($arg) {
           if(get_post_type($arg)==='shop_order'){

             $awcf_data = get_post_meta($arg, AWCFE_ORDER_META_KEY, true);
             $fieldset = [];

             if ($awcf_data) {
                 foreach ($awcf_data as $key => $field) {
                     $fieldset[$key] = [];
                     foreach ($field as $skey => $sfield) {

                         $fieldname = '_'.$sfield['name'];
                         if( isset( $_POST[ $fieldname ] ) ){
                           $sfield['value'] = $_POST[ $fieldname ];
                         }
                         $fieldset[$key][] = $sfield;

                     }
                 }
             }

             if (!empty($fieldset)) {
                 update_post_meta($arg, AWCFE_ORDER_META_KEY, $fieldset);
             }


           }
        }
    }


// End enqueue_scripts ()
// End instance()
}
