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
            'JLM 2017',
            'JLM 2017',
            'manage_options',
            'JLM2017',
            [$this, 'options_page']
        );
    }

    public function options_page()
    {
        ?>
        <h1>JLM 2017</h1>
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
            'jlm2017_nationbuilder_section',
            __('NationBuilder\'s settings', 'JLM2017'),
            [$this, 'nationbuilder_section_callback'],
            'jlm2017_settings_page'
        );

        add_settings_section(
            'jlm2017_api_section',
            __('api.jlm2017.fr settings', 'JLM2017'),
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

    public function nationbuilder_section_callback()
    {
        echo __('Please register your NationBuilder\'s settings', 'jlm2017');

        add_settings_field(
            'jlm2017_nb_api_key',
            __('API Key NationBuilder', 'JLM2017'),
            [$this, 'nb_api_key_render'],
            'jlm2017_settings_page',
            'jlm2017_nationbuilder_section'
        );

        add_settings_field(
            'jlm2017_nb_slug',
            __('NationBuilder slug', 'JLM2017'),
            [$this, 'nb_slug_render'],
            'jlm2017_settings_page',
            'jlm2017_nationbuilder_section'
        );
    }

    public function api_section_callback()
    {
        add_settings_field(
            'jlm2017_api_key',
            __('API Key api.jlm2017.fr', 'JLM2017'),
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

    public function nb_api_key_render()
    {
        $options = get_option('jlm2017_settings'); ?>

        <input type="text"
            name="jlm2017_settings[nb_api_key]"
            value="<?= $options['nb_api_key']; ?>">

        <?php
        try {
            $response = wp_remote_get('https://plp.nationbuilder.com/api/v1/sites?&access_token='.$options['nb_api_key'], [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'httpversion' => '1.1',
                'user-agent' => '',
            ]);
        } catch (Exception $e) {
            $error = true;
        }

        if (isset($error) || is_wp_error($response)) {
            ?> <p style="color: red;">La vérification de la clé API a échoué, veuillez réessayer plus tard.</p> <?php

            return;
        }

        if ($response['headers']['status'] === '401 Unauthorized') {
            ?> <p style="color: red;">Clé invalide.</p> <?php

        }
    }

    public function nb_slug_render()
    {
        $options = get_option('jlm2017_settings'); ?>
        <input type="text"
            name="jlm2017_settings[nb_slug]"
            value="<?= $options['nb_slug']; ?>">
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
            $url = 'https://api.jlm2017.fr/people';
            $response = wp_remote_get($url, [
                'timeout' => 300,
                'headers' => [
                    'Authorization' => 'Basic '.base64_encode($options['api_key'].':'),
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

        if ($response['response']['code'] === 401) {
            ?>
            <p style="color: red;">Clé invalide.</p>
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
        $options = get_option('jlm2017'); ?>
        <input type="text"
            name="jlm2017_settings[registration_mail_url]"
            value="<?= $options['registration_mail_url']; ?>">
        <?php

    }
}
