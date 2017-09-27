<?php
/*
    Plugin Name: FI Plugin
    Description: additional fonctionalities for FI
    Author: Florian SIMON, Guillaume Royer
    License: GPL-3.0
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

        $url = 'https://api.lafranceinsoumise.fr/legacy/people/';

        $response = wp_remote_post($url, [
            'headers' => [
                'Content-type' => 'application/json',
                'Authorization' => 'Basic '.base64_encode($options['api_id'].':'.$options['api_key']),
            ],
            'body' => '{"email":"'.$jlm2017_form_signup_email.'", "location":{"zip":"'.$jlm2017_form_signup_zipcode.'"}}',
        ]);

        if (!is_wp_error($response) && $response['response']['code'] === 422) {
            if (strpos($response['body'], 'email')) {
                $jlm2017_form_signup_errors['email'] = 'Adresse email déjà existante dans la base de donnée.';
            }

            return;
        }

        if (is_wp_error($response) || !in_array($response['response']['code'], [422, 201])) {
            $jlm2017_form_signup_errors['form'] = 'Oups, une erreur est survenue, veuillez réessayer plus tard&nbsp;!';

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
            ['From: La France insoumise <nepasrepondre@lafranceinsoumise.fr>', 'Content-Type: text/html; charset=UTF-8']
        );

        if (wp_redirect($options['registration_redirect_url'])) {
            exit();
        } else {
            $jlm2017_form_signup_errors['form'] = 'Oups, une erreur est survenue, veuillez réessayer plus tard&nbsp;!';
        }
    }
}

new JLM2017_Plugin();
