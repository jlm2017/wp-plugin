<?php
/*
    Plugin Name: FI Addons

    Description: Fonctionnalités spécifiques à la FI. Dépend d'Elementor.
    Author: Jill Royer, Florian Simon
    License: GPL-3.0
*/

/**
 * Class FI_Plugin.
 */

require_once dirname(__FILE__).'/includes/yt-live-tchat-widget.php';
require_once dirname(__FILE__).'/includes/share-bar-widget.php';

class FI_Plugin
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('init', [$this, 'register_widgets']);
        add_action('init', [$this, 'admin_init']);
        add_action( 'elementor_pro/init', [$this, 'register_elementor_addons']);

        // Woocommerce
        add_action('woocommerce_loaded',[$this, 'remove_woocommerce_filters']);

        // When status goes from pending to processing
        add_action('woocommerce_order_status_pending_to_processing', [$this, 'hold_not_shipping_orders']);

        // When status got completed
        add_action('woocommerce_order_status_completed', [$this, 'tag_completed_orders'], 10, 2);

        // When itemm is added
        add_action('woocommerce_check_cart_items', [$this, 'check_cart_weight']);

        // Check code
        add_action('woocommerce_coupon_code', [$this, 'check_coupon_code'], 20);
    }

    public function register_widgets()
    {
        register_widget('FI_YT_Live_Tchat_Widget');
        register_widget('FI_Share_Bar');
    }

    public function register_elementor_addons()
    {
        require_once dirname(__FILE__).'/includes/registration-handler.php';
        $elementor_registration_action = new FI_Registration_Action();
        \ElementorPro\Plugin::instance()
            ->modules_manager->get_modules( 'forms' )
            ->add_form_action($elementor_registration_action->get_name(), $elementor_registration_action)
        ;
    }

    public function admin_init()
    {
        require_once dirname(__FILE__).'/includes/admin.php';

        new FI_Plugin_Admin();
    }

    public function remove_woocommerce_filters()
    {
        // Make Woocommerce code case sensitive
        remove_filter('woocommerce_coupon_code', 'wc_strtolower');
    }

    /**
     * When order status change to completed.
     *
     * @param WC_Order $order
     */
    public function tag_completed_orders($order_id)
    {
        $options = get_option('fi_settings');
        $order = wc_get_order($order_id);
        $url = 'https://api.lafranceinsoumise.fr/legacy/people/?email='.
            urlencode($order->billing_email);
        $response = wp_remote_get($url, ['headers' => [
            'Content-type' => 'application/json',
            'Authorization' => 'Basic '.base64_encode($options['api_id'].':'.$options['api_key']),
        ]]);

        if (is_wp_error($response)) {
          error_log($response->get_error_message());
          return;
        }

        if (json_decode($response['body'])->_meta->total === 0) {
            $response = wp_remote_post('https://api.lafranceinsoumise.fr/legacy/people', [
                'headers' => [
                    'Content-type' => 'application/json',
                    'Authorization' => 'Basic '.base64_encode($options['api_id'].':'.$options['api_key']),
                ],
                'body' => '{"email":"'.$order->billing_email.'", "tags": ["'.$options['woocommerce_notify_tag'].'"]}',
            ]);

            return;
        }

        wp_remote_request(json_decode($response['body'])->_items[0]->url, [
            'headers' => [
                'Content-type' => 'application/json',
                'Authorization' => 'Basic '.base64_encode($options['api_id'].':'.$options['api_key']),
            ],
            'method' => 'PATCH',
            'body' => '{"tags": ["'.$options['woocommerce_notify_tag'].'"]}',
        ]);
    }

    /**
     * Check cart weight is bellow 24
     *
     * @return [type] [description]
     */
    public function check_cart_weight() {
        global $woocommerce;
        $weight = $woocommerce->cart->cart_contents_weight;

        if ($weight > 26) {
            wc_add_notice(
                sprintf(__('Vous avez %s kg dans votre commande. Le maximum est de 26 kg.', 'woocommerce'), $weight),
                'error'
            );

            return false;
        }

        return true;
    }

    public function check_coupon_code($code)
    {
        $options = get_option('fi_settings');

        if (strlen($code) !== 14) {
            return $code;
        }

        if (wc_get_coupon_id_by_code($code)) {
            return $code;
        }

        if (!wc_get_coupon_id_by_code($options['woocommerce_coupon_code'])) {
            return $code;
        }

        // the expiration date is encoded in the first two chars of our code, using urlsafe base64.
        // to turn in into 'standard' base64, we need to replace - and _ by + and / and add 'A=' at the end
        $b64_date = strtr(substr($code, 0, 2), '-_', '+/') . 'A=';

        $bytes = base64_decode($b64_date);

        $days = ord($bytes[0]) + ord($bytes[1])*16;
        $date = (new Datetime('2017-01-01'))->add(new DateInterval('P'.$days.'D'));

        $partToSign = substr($code, 0, 8);
        $sig = substr(str_replace(['+', '/'], ['-', '_'], base64_encode(
            hash_hmac('sha1', substr($code, 0, 8), $options['woocommerce_coupon_key'], true)
        )), 0, 6);

        if (hash_equals($sig, substr($code, 8))) {
            $modelCouponId = (wc_get_coupon_id_by_code($options['woocommerce_coupon_code']));
            $meta = array_filter(get_post_meta($modelCouponId, '', true), function($key) {
                return ($key[0] !== '_');
            }, ARRAY_FILTER_USE_KEY);
            $meta = array_map(function($value) {
                if (is_array($value)) return maybe_unserialize($value[0]);

                return maybe_unserialize($value);
            }, $meta);

            $meta['date_expires'] = $date->getTimestamp();
            $meta['usage_count'] = '0';

            $newCoupon = [
                'post_author' => 1,
                'post_title' => $code,
                'post_excerpt' => __('Coupon de groupe d\'action autogénéré.'),
                'post_type' => 'shop_coupon',
                'post_status' => 'publish',
                'meta_input' => $meta,
            ];

            wp_insert_post($newCoupon);
        }

        return $code;
    }
}

new FI_Plugin();
