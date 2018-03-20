<?php
/*
    Plugin Name: FI Addons
    Description: Fonctionnalités spécifiques à la FI
    Author: Guillaume Royer, Florian Simon
    License: GPL-3.0
*/

/**
 * Class FI_Plugin.
 */
$jlm2017_form_errors = '';
$jlm2017_form_signup_email = '';
$jlm2017_form_signup_zipcode = '';

require_once dirname(__FILE__).'/includes/registration-widget.php';
require_once dirname(__FILE__).'/includes/yt-live-tchat-widget.php';
require_once dirname(__FILE__).'/includes/share-bar-widget.php';

class FI_Plugin
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('init', [$this, 'handle_registration_form']);
        add_action('init', [$this, 'register_widgets']);
        add_action('init', [$this, 'admin_init']);

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
        register_widget('FI_Registration_Widget');
        register_widget('FI_YT_Live_Tchat_Widget');
        register_widget('FI_Share_Bar');
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

    public function handle_registration_form()
    {
        global $jlm2017_form_signup_errors;
        global $jlm2017_form_signup_email;
        global $jlm2017_form_signup_zipcode;

        $jlm2017_form_signup_errors = array();

        $jlm2017_form_signup_email = (isset($_REQUEST['jlm2017_form_signup_email'])) ? $_REQUEST['jlm2017_form_signup_email'] : '';
        $jlm2017_form_signup_zipcode = (isset($_REQUEST['jlm2017_form_signup_zipcode'])) ? $_REQUEST['jlm2017_form_signup_zipcode'] : '';

        // Form validation
        if (!isset($_REQUEST['action']) || $_REQUEST['action'] !== 'jlm2017_signup_form') {
            return;
        }

        if (!isset($_REQUEST['jlm2017_form_signup_email'])) {
            $jlm2017_form_signup_errors['email'] = 'L\'email est obligatoire.';
        }

        if (isset($_REQUEST['jlm2017_form_signup_email']) && !is_email($_REQUEST['jlm2017_form_signup_email'])) {
            $jlm2017_form_signup_errors['email'] = 'Email invalide.';
        }

        if (!isset($_REQUEST['jlm2017_form_signup_zipcode'])) {
            $jlm2017_form_signup_errors['zipcode'] = 'Le code postal est obligatoire.';
        }

        if (isset($_REQUEST['jlm2017_form_signup_zipcode']) && !preg_match('/^[0-9]{5}$/', $_REQUEST['jlm2017_form_signup_zipcode'])) {
            $jlm2017_form_signup_errors['zipcode'] = 'Code postal invalide.';
        }

        if (count($jlm2017_form_signup_errors) > 0) {
            return;
        }

        $options = get_option('fi_settings');

        $url = 'https://api.lafranceinsoumise.fr/legacy/people/subscribe/';

        $body = '{"email":"'.$jlm2017_form_signup_email.'", "location":{"zip":"'.$jlm2017_form_signup_zipcode.'"}}';
        $response = wp_remote_post($url, [
            'headers' => [
                'Content-type' => 'application/json',
                'Authorization' => 'Basic '.base64_encode($options['api_id'].':'.$options['api_key']),
            ],
            'body' => $body
        ]);

        if (!is_wp_error($response) && $response['response']['code'] === 422) {
            if (strpos($response['body'], 'email')) {
                $jlm2017_form_signup_errors['email'] = 'Adresse email déjà existante dans la base de donnée.';
            } else {
                error_log('422 error while POSTing to API : '.$response['body']);
                $jlm2017_form_signup_errors['form'] = 'Oups, une erreur est survenue, veuillez réessayer plus tard&nbsp;!';
            }

            return;
        }

        if (is_wp_error($response) || $response['response']['code'] !== 201) {
            error_log('Error while POSTing new user to API.');
            $jlm2017_form_signup_errors['form'] = 'Oups, une erreur est survenue, veuillez réessayer plus tard&nbsp;!';

            return;
        }

        if (wp_redirect($options['registration_redirect_url'])) {
            exit();
        } else {
            $jlm2017_form_signup_errors['form'] = 'Oups, une erreur est survenue, veuillez réessayer plus tard&nbsp;!';
        }
    }

    /**
     * When an order is paid, change its status to onhold if it must not be shipped
     *
     * @param  WC_Order $order_id
     */
    public function hold_not_shipping_orders($order_id)
    {
        $order = wc_get_order($order_id);
        if ($order->has_shipping_method('local_pickup')) {
            $order->set_status('on-hold');
            $order->save();
        }
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

        $bytes = base64_decode(substr($code, 0, 2).'A=');
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
