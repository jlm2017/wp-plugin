<?php

class JLM2017_Plugin_Admin
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
            'FI plugin',
            'FI plugin',
            'manage_options',
            'JLM2017',
            [$this, 'options_page']
        );
    }

    public function options_page()
    {
        ?>
        <h1>France insoumise</h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('jlm2017_settings_page');
        do_settings_sections('jlm2017_settings_page');
        submit_button(); ?>
        </form>
        <?php

    }

    public function settings_init()
    {
        register_setting('jlm2017_settings_page', 'jlm2017_settings');

        add_settings_section(
            'jlm2017_api_section',
            __('api.lafranceinsoumise.fr settings', 'JLM2017'),
            [$this, 'api_section_callback'],
            'jlm2017_settings_page'
        );

        add_settings_section(
            'jlm2017_registration_section',
            __('Registration settings', 'JLM2017'),
            [$this, 'registration_section_callback'],
            'jlm2017_settings_page'
        );
    }

    public function api_section_callback()
    {
        add_settings_field(
            'jlm2017_api_id',
            __('Client id api.lafranceinsoumise.fr', 'JLM2017'),
            [$this, 'api_id_render'],
            'jlm2017_settings_page',
            'jlm2017_api_section'
        );

        add_settings_field(
            'jlm2017_api_key',
            __('Client secret api.lafranceinsoumise.fr', 'JLM2017'),
            [$this, 'api_key_render'],
            'jlm2017_settings_page',
            'jlm2017_api_section'
        );
    }

    public function registration_section_callback()
    {
        add_settings_field(
            'jlm2017_registration_redirect_url',
            __('URL de redirection après inscription', 'JLM2017'),
            [$this, 'registration_redirect_url_render'],
            'jlm2017_settings_page',
            'jlm2017_registration_section'
        );

        add_settings_field(
            'jlm2017_registration_mail_url',
            __('URL du mail de remerciement', 'JLM2017'),
            [$this, 'registration_mail_url_render'],
            'jlm2017_settings_page',
            'jlm2017_registration_section'
        );
    }

    public function api_id_render()
    {
        $options = get_option('jlm2017_settings'); ?>

        <input type="text"
            name="jlm2017_settings[api_id]"
            value="<?= isset($options['api_id']) ? $options['api_id'] : ''; ?>">

        <?php
    }

    public function api_key_render()
    {
        $options = get_option('jlm2017_settings'); ?>

        <input type="text"
            name="jlm2017_settings[api_key]"
            value="<?= $options['api_key']; ?>">

        <?php
        try {
            $options = get_option('jlm2017_settings');
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
        $options = get_option('jlm2017_settings'); ?>
        <input type="text"
            name="jlm2017_settings[registration_redirect_url]"
            value="<?= $options['registration_redirect_url']; ?>">
        <?php

    }

    public function registration_mail_url_render()
    {
        $options = get_option('jlm2017_settings'); ?>
        <input type="text"
            name="jlm2017_settings[registration_mail_url]"
            value="<?= $options['registration_mail_url']; ?>">
        <?php

    }
}
