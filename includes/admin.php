<?php

class FI_Plugin_Admin
{
    public function __construct()
    {
        // When initialized
        add_action('admin_init', [$this, 'settings_init']);

        // When menu load
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }

    public function add_admin_menu()
    {
        add_options_page(
            'FI add-ons',
            'FI add-ons',
            'manage_options',
            'FI',
            [$this, 'options_page']
        );
    }

    public function options_page()
    {
        ?>
        <h1>France insoumise</h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('fi_settings_page');
            do_settings_sections('fi_settings_page');
            submit_button(); ?>
        </form>
        <?php

    }

    public function settings_init()
    {
        register_setting('fi_settings_page', 'fi_settings', [$this, 'sanitize']);

        add_settings_section(
            'fi_api_section',
            __('Identifiants api.lafranceinsoumise.fr', 'FI'),
            [$this, 'api_section_callback'],
            'fi_settings_page'
        );

        add_settings_section(
            'fi_registration_section',
            __('Inscription', 'FI'),
            [$this, 'registration_section_callback'],
            'fi_settings_page'
        );

        add_settings_section(
            'fi_woocommerce_section',
            __('Woocommerce', 'FI'),
            [$this, 'woocommerce_section_callback'],
            'fi_settings_page'
        );
    }

    public function sanitize($data)
    {
        $old = get_option('fi_settings');

        $data['api_key'] = $data['api_key'] !== '' ? $data['api_key'] : $old['api_key'];
        $data['woocommerce_coupon_key'] = $data['woocommerce_coupon_key'] !== '' ? $data['woocommerce_coupon_key'] : $old['woocommerce_coupon_key'];

        return $data;
    }

    public function api_section_callback()
    {
        add_settings_field(
            'fi_api_id',
            __('Client id api.lafranceinsoumise.fr', 'FI'),
            [$this, 'api_id_render'],
            'fi_settings_page',
            'fi_api_section'
        );

        add_settings_field(
            'fi_api_key',
            __('Client secret api.lafranceinsoumise.fr', 'FI'),
            [$this, 'api_key_render'],
            'fi_settings_page',
            'fi_api_section'
        );
    }

    public function registration_section_callback()
    {
        add_settings_field(
            'fi_registration_redirect_url',
            __('URL de redirection après inscription', 'FI'),
            [$this, 'registration_redirect_url_render'],
            'fi_settings_page',
            'fi_registration_section'
        );

        add_settings_field(
            'fi_registration_mail_url',
            __('URL du mail de remerciement', 'FI'),
            [$this, 'registration_mail_url_render'],
            'fi_settings_page',
            'fi_registration_section'
        );
    }

    public function woocommerce_section_callback()
    {
        if (!class_exists('WooCommerce')) {
          ?>
          <p>
            WooCommerce n'est pas activé.
          </p>
          <?php

          return;
        }

        add_settings_field(
            'fi_woocommerce_notify_tag',
            __('Tag à donner lors de la complétion d\'une commande', 'FI'),
            [$this, 'woocommerce_notify_tag_render'],
            'fi_settings_page',
            'fi_woocommerce_section'
        );

        add_settings_field(
            'fi_woocommerce_coupon_key',
            __('Clé secrete pour la génération des coupons', 'FI'),
            [$this, 'woocommerce_coupon_key_render'],
            'fi_settings_page',
            'fi_woocommerce_section'
        );

        add_settings_field(
            'fi_woocommerce_coupon_code',
            __('Coupon à cloner', 'FI'),
            [$this, 'woocommerce_coupon_code_render'],
            'fi_settings_page',
            'fi_woocommerce_section'
        );
    }

    public function api_id_render()
    {
        $options = get_option('fi_settings'); ?>

        <input type="text"
            name="fi_settings[api_id]"
            value="<?= isset($options['api_id']) ? esc_attr($options['api_id']) : ''; ?>">

        <?php
    }

    public function api_key_render()
    {
        $options = get_option('fi_settings'); ?>

        <input type="password"
            name="fi_settings[api_key]">

        <?php
        try {
            $options = get_option('fi_settings');

            if (!is_array($options)) {
                return;
            }

            $url = 'https://api.lafranceinsoumise.fr/legacy/people/';
            $response = wp_remote_get($url, [
                'timeout' => 300,
                'headers' => [
                    'Authorization' => 'Basic '.base64_encode($options['api_id'].':'.$options['api_key']),
                ],
            ]);
        } catch (Exception $e) {
            $error = true;
        }

        if (isset($error) || is_wp_error($response)) {
            ?>
            <p style="color: red;">La vérification de la clé API a échouée.</p>
            <?php

            return;
        }

        if (in_array($response['response']['code'], [401, 403])) {
            ?>
            <p style="color: red;">L'authentification a échoué.</p>
            <?php

        }
    }

    public function registration_redirect_url_render()
    {
        $options = get_option('fi_settings'); ?>
        <input type="text"
            name="fi_settings[registration_redirect_url]"
            value="<?= isset($options['registration_redirect_url']) ? esc_attr($options['registration_redirect_url']) : ''; ?>">
        <?php

    }

    public function registration_mail_url_render()
    {
        $options = get_option('fi_settings'); ?>
        <input type="text"
            name="fi_settings[registration_mail_url]"
            value="<?= isset($options['registration_mail_url']) ? esc_attr($options['registration_mail_url']) : ''; ?>">
        <?php

    }

    public function woocommerce_notify_tag_render()
    {
        $options = get_option('fi_settings'); ?>
            <input type="text"
            name="fi_settings[woocommerce_notify_tag]"
            value="<?php echo isset($options['woocommerce_notify_tag']) ? esc_attr($options['woocommerce_notify_tag']) : ''; ?>">
            <?php
    }

    public function woocommerce_coupon_key_render()
    {
        $options = get_option('fi_settings'); ?>
            <input type="password"
            name="fi_settings[woocommerce_coupon_key]">
            <?php
    }

    public function woocommerce_coupon_code_render()
    {
        $options = get_option('fi_settings');
        if (is_array($options) && !wc_get_coupon_id_by_code($options['woocommerce_coupon_code'])) {
            $options['woocommerce_coupon_code'] = '';
        }

         ?>
            <input type="text"
            name="fi_settings[woocommerce_coupon_code]"
            value="<?php echo isset($options['woocommerce_coupon_code']) ? esc_attr($options['woocommerce_coupon_code']) : ''; ?>">
            <?php
    }
}
