<?php
/*
    Plugin Name: JLM 2017 plugin
    Description: additional fonctionalities for JLM 2017.
    Author: Florian SIMON
    License: GPL-2.0
*/

/**
 * Class JLM201_Plugin.
 *
 * @author    Florian SIMON <floian.simon fsimondev@gmail.com>
 */
$jlm2017_form_errors = '';
$jlm2017_form_user_email = '';
$jlm2017_form_user_zipcode = '';

class JLM2017_Plugin
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('init', [$this, 'jlm2017_registration_form']);

        // When initialized
        add_action('admin_init', [$this, 'jlm2017_registration_settings_init']);

        // When menu load
        add_action('admin_menu', [$this, 'jlm2017_registration_add_admin_menu']);
    }
    /**
     * Footer form fonctionality.
     */
    public function jlm2017_registration_add_admin_menu()
    {
        add_options_page(
            'JLM2017 registration',
            'JLM2017 registration',
            'manage_options',
            'JLM2017 registration',
            [$this, 'jlm2017_registration_options_page']
        );
    }

    public function jlm2017_registration_settings_init()
    {
        register_setting('jlm2017_registration_settings_page', 'jlm2017_registration_settings');

        add_settings_section(
            'jlm2017_registration_plugin_page_section',
            __('NationBuilder\'s settings', 'JLM2017 registration'),
            [$this, 'jlm2017_registration_settings_section_callback'],
            'jlm2017_registration_settings_page'
        );

        add_settings_field(
            'jlm2017_registration_api_key',
            __('API Key NationBuilder', 'JLM2017 registration'),
            [$this, 'jlm2017_registration_api_key_render'],
            'jlm2017_registration_settings_page',
            'jlm2017_registration_plugin_page_section'
        );

        add_settings_field(
            'jlm2017_registration_nation_slug',
            __('Nation slug', 'JLM2017 registration'),
            [$this, 'jlm2017_registration_nation_slug_render'],
            'jlm2017_registration_settings_page',
            'jlm2017_registration_plugin_page_section'
        );

        add_settings_field(
            'jlm2017_registration_url_redirect',
            __('URL redirect', 'JLM2017 registration'),
            [$this, 'jlm2017_registration_url_redirect_render'],
            'jlm2017_registration_settings_page',
            'jlm2017_registration_plugin_page_section'
        );
        add_settings_field(
            'jlm2017_registration_api_key_jlm2017',
            __('API Key api.jlm2017.fr', 'JLM2017 registration'),
            [$this, 'jlm2017_registration_api_key_jlm2017_render'],
            'jlm2017_registration_settings_page',
            'jlm2017_registration_plugin_page_section'
        );
    }

    public function jlm2017_registration_api_key_render()
    {
        $options = get_option('jlm2017_registration_settings'); ?>
            <input type="text"
            name="jlm2017_registration_settings[jlm2017_registration_api_key]"
            value="<?= $options['jlm2017_registration_api_key']; ?>">
        <?php
        try {
            $response = wp_remote_get('https://plp.nationbuilder.com/api/v1/sites/plp/pages/events?limit=10&access_token='.$options['jlm2017_registration_api_key'], [
          'headers' => [
          'Accept' => 'application/json',
          'Content-type' => 'application/json',
          ],
          'httpversion' => '1.1',
          'user-agent' => '',
          ]);
            if (is_wp_error($response)) {
                ?>
                <p style="color: red;">Vérification de l'API Key échouée, veuillez réessayer plus tard</p>
                <?php
            } elseif ($response['headers']['status'] === '401 Unauthorized') {
                ?>
                <p style="color: red;">API Key invalide</p>
                <?php
            }
        } catch (Exception $e) {
          ?>
              <p style="color: red;">Vérification de l'API Key échouée, veuillez réessayer plus tard</p>
          <?php
        }
    }

    public function jlm2017_registration_nation_slug_render()
    {
        $options = get_option('jlm2017_registration_settings');
        ?>
            <input type="text"
            name="jlm2017_registration_settings[jlm2017_registration_nation_slug]"
            value="<?= $options['jlm2017_registration_nation_slug']; ?>">
        <?php
    }

    public function jlm2017_registration_url_redirect_render()
    {
        $options = get_option('jlm2017_registration_settings');
        ?>
            <input type="text"
            name="jlm2017_registration_settings[jlm2017_registration_url_redirect]"
            value="<?= $options['jlm2017_registration_url_redirect']; ?>">
        <?php
    }

    public function jlm2017_registration_api_key_jlm2017_render()
    {
        $options = get_option('jlm2017_registration_settings'); ?>
            <input type="text"
            name="jlm2017_registration_settings[jlm2017_registration_api_key_jlm2017]"
            value="<?= $options['jlm2017_registration_api_key_jlm2017']; ?>">
        <?php
        try {
            $options = get_option('jlm2017_registration_settings');
            $url = 'https://api.jlm2017.fr/people';
            $response = wp_remote_get($url, [
              'timeout'     => 300,
              'headers' => [
                'Authorization' => 'Basic '.base64_encode($options['jlm2017_registration_api_key_jlm2017'].':'),
                'Accept' => 'application/json',
                'Content-type' => 'application/json',
              ],
              'httpversion' => '1.1',
              'user-agent' => '',
            ]);
            if (is_wp_error($response)) {
                ?>
            <p style="color: red;">Vérification de l'API Key échouée, veuillez réessayer plus tard</p>
            <?php
          } elseif ($response['response']['code'] === 401) {
                ?>
              <p style="color: red;">API Key invalide</p>
              <?php
            }
        } catch (Exception $e) {
          ?>
              <p style="color: red;">Vérification de l'API Key échouée, veuillez réessayer plus tard</p>
          <?php
        }
    }

    public function jlm2017_registration_settings_section_callback()
    {
        echo __('Please register your NationBuilder\'s settings', 'jlm2017');
    }

    public function jlm2017_registration_options_page()
    {
        ?>
      <form action='options.php' method='post'>

        <h2>JLM2017 registration</h2>

        <?php
            settings_fields('jlm2017_registration_settings_page');
        do_settings_sections('jlm2017_registration_settings_page');
        submit_button(); ?>

      </form>
      <?php

    }

    public function jlm2017_registration_form()
    {
        global $jlm2017_form_errors;
        global $jlm2017_form_user_email;
        global $jlm2017_form_user_zipcode;
        $jlm2017_form_errors = '';
        $jlm2017_form_user_email = (isset($_REQUEST['signup_email'])) ? $_REQUEST['signup_email'] : '';
        $jlm2017_form_user_zipcode = (isset($_REQUEST['signup_zipcode'])) ? $_REQUEST['signup_zipcode'] : '';
        $jlm2017_validation = (isset($_REQUEST['action'])) ? $_REQUEST['action'] : '';
        if ($jlm2017_form_user_email && $jlm2017_form_user_zipcode && $jlm2017_validation === 'jlm2017_registration_form_valid') {
            $options = get_option('jlm2017_registration_settings');

            $url = 'https://'.$options['jlm2017_registration_nation_slug'].
             '.nationbuilder.com/api/v1/people/match?email='.$jlm2017_form_user_email.'&access_token='.
             $options['jlm2017_registration_api_key'];

            $response = wp_remote_get($url, [
             'headers' => [
                 'Accept' => 'application/json',
                 'Content-type' => 'application/json',
             ],
             'httpversion' => '1.1',
             'user-agent' => '',
            ]);
            if (is_wp_error($response)) {
                $jlm2017_form_errors = $jlm2017_form_errors.'redirect,';
            } elseif ($response['headers']['status'] === '400 Bad Request'
                || $response['headers']['status'] === '200 OK') {
                if ($response['headers']['status'] !== '400 Bad Request') {
                    $jlm2017_form_errors = $jlm2017_form_errors.'email,';
                }
                if (!preg_match('/^[0-9]{5}$/', $jlm2017_form_user_zipcode)) {
                    $jlm2017_form_errors = $jlm2017_form_errors.'zipcode,';
                }
                if ($jlm2017_form_errors === '') {
                    $url = 'https://'.$options['jlm2017_registration_nation_slug'].
                  '.nationbuilder.com/api/v1/people?access_token='.
                  $options['jlm2017_registration_api_key'];

                    $response = wp_remote_post($url, [
                      'headers' => [
                          'Accept' => 'application/json',
                          'Content-type' => 'application/json',
                      ],
                      'httpversion' => '1.1',
                      'user-agent' => '',
                      'body' => '{"person":{"email":"'.$jlm2017_form_user_email.'", "home_address":{"zip":"'.$jlm2017_form_user_zipcode.'"}}}',
                  ]);
                    if (is_wp_error($response)) {
                        $jlm2017_form_errors = $jlm2017_form_errors.'redirect,';
                    } elseif (wp_redirect($options['jlm2017_registration_url_redirect'])) {
                        exit();
                    } else {
                        $jlm2017_form_errors = 'redirect';
                    }
                }
            }
        }
    }

    static function get_people_count() {
      if (!get_transient('people_count_jlm2017') || !get_transient('save_date_jlm2017')
          || (current_time('timestamp') - get_transient('save_date_jlm2017')) > 30) {
        $options = get_option('jlm2017_registration_settings');
        $url = 'https://api.jlm2017.fr/people';
        try {
          $response = wp_remote_get($url, [
            'timeout' => 300,
            'headers' => [
              'Authorization' => 'Basic '.base64_encode($options['jlm2017_registration_api_key_jlm2017'].':'),
              'Accept' => 'application/json',
              'Content-type' => 'application/json',
            ],
            'httpversion' => '1.1',
            'user-agent' => '',
          ]);
          if (!is_wp_error($response) && isset(json_decode($response['body'])->_meta) && json_decode($response['body'])->_meta->total !== NULL && (current_time('timestamp') - JLM2017_Plugin::get_saved_date()) > 300) {
            set_transient( 'people_count_jlm2017', json_decode($response['body'])->_meta->total, 0 );
            set_transient( 'save_date_jlm2017', current_time('timestamp'), 0 );

            return json_decode($response['body'])->_meta->total;
          }
          else {
            error_log('error on request, please check api key of bdd in the settings of jlm2017 wordpress plugin');
          }
        } catch (Exception $e) {
          error_log('error on request, here is the logs:\n'.$e->getMessage());
        }
      }
      return get_transient('people_count_jlm2017');
    }

    static function get_saved_date() {
      return get_transient('save_date_jlm2017');
    }
}

new JLM2017_Plugin();
