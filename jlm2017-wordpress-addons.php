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
$jlm2017_form_signup_email = '';
$jlm2017_form_signup_zipcode = '';

class JLM2017_Plugin
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('init', [$this, 'handle_registration_form']);

        add_action('init', [$this, 'admin_init']);
    }

    public function admin_init()
    {
        require_once dirname(__FILE__).'/includes/admin.php';

        new JLM2017_Plugin_Admin();
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

        $options = get_option('jlm2017_settings');

        $url = 'https://'.$options['nb_slug'].
            '.nationbuilder.com/api/v1/people?access_token='.
            $options['nb_api_key'];

        $response = wp_remote_post($url, [
            'headers' => [
                'Accept' => 'application/json',
                'Content-type' => 'application/json',
            ],
            'httpversion' => '1.1',
            'user-agent' => '',
            'body' => '{"person":{"email":"'.$jlm2017_form_signup_email.'", "home_address":{"zip":"'.$jlm2017_form_signup_zipcode.'"}}}',
        ]);

        if (is_wp_error($response) || !in_array($response['headers']['status'], ['409 Conflict', '201 Created'])) {
            $jlm2017_form_signup_errors['form'] = 'Oups, une erreur est survenue, veuillez réessayer plus tard&nbsp;!';

            return;
        }

        if ($response['headers']['status'] === '409 Conflict') {
            $jlm2017_form_signup_errors['email'] = 'Adresse email déjà existante dans la base de donnée.';

            return;
        }

        $response = wp_remote_get(add_query_arg(
            [
                'EMAIL' => $_REQUEST['jlm2017_form_signup_email']
            ],
            $options['registration_mail_url']
        ));

        if (is_wp_error($response)) {
            error_log('Cannot get signup email content.');
        }

        wp_mail(
            $_REQUEST['jlm2017_form_signup_email'],
            'Merci pour votre appui !',
            $response['body'],
            ['From: Jean-Luc Mélenchon <nepasrepondre@jlm2017.fr>', 'Content-Type: text/html; charset=UTF-8']
        );

        if (wp_redirect($options['registration_redirect_url'])) {
            exit();
        } else {
            $jlm2017_form_signup_errors['form'] = 'Oups, une erreur est survenue, veuillez réessayer plus tard&nbsp;!';
        }
    }

    public static function get_people_count()
    {
        if (get_transient('jlm2017_people_count') && get_transient('jlm2017_people_count_date') &&
            (current_time('timestamp') - get_transient('jlm2017_people_count_date')) < 30) {

            return get_transient('jlm2017_people_count');
        }

        $options = get_option('jlm2017_settings');
        $url = 'https://api.jlm2017.fr/people';
        try {
            $response = wp_remote_get($url, [
                'timeout' => 300,
                'headers' => [
                    'Authorization' => 'Basic '.base64_encode($options['api_key'].':'),
                    'Accept' => 'application/json',
                ],
            ]);

            if (!is_wp_error($response) && isset(json_decode($response['body'])->_meta) && json_decode($response['body'])->_meta->total !== null && (current_time('timestamp') - self::get_saved_date()) > 300) {
                set_transient('jlm2017_people_count', json_decode($response['body'])->_meta->total, 0);
                set_transient('jlm2017_people_count_date', current_time('timestamp'), 0);

                return json_decode($response['body'])->_meta->total;
            } else {
                error_log('Error on request, please check API keys in the settings of JLM2017 wordpress plugin.');
            }
        } catch (Exception $e) {
            error_log('Error on request: '.$e->getMessage());
        }
    }

    public static function get_saved_date()
    {
        return get_transient('jlm2017_people_count_date');
    }
}

new JLM2017_Plugin();
