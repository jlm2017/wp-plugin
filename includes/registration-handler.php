<?php

class FI_Registration_Action extends \ElementorPro\Modules\Forms\Classes\Action_Base
{
    public function get_name()
    {
        return 'fi-registration';
    }

    public function get_label()
    {
        return "Inscription à la plateforme";
    }

    public function register_settings_section($widget)
    {
        // TODO: Implement register_settings_section() method.
    }

    public function on_export($element)
    {
        // TODO: Implement on_export() method.
    }

    public function run($record, $ajax_handler) {
        // Get submitetd Form data
        $raw_fields = $record->get('fields');

        // Normalize the Form Data
        $fields = [];
        foreach ($raw_fields as $id => $field) {
            $fields[$id]  = $field['value'];
        }

        if (empty($fields['email'])) {
            $ajax_handler->add_error("email", "L'email est obligatoire.");
        }

        if (!empty($fields['email']) && !is_email($fields['email'])) {
            $ajax_handler->add_error("email", "L'e-mail est invalide.");
        }

        if (empty($fields['zipcode'])) {
            $ajax_handler->add_error("zipcode", 'Le code postal est obligatoire.');
        }

        if (!empty($fields['zipcode']) && !preg_match('/^[0-9]{5}$/', $fields['zipcode'])) {
            $ajax_handler->add_error("zipcode", 'Le code postal est invalide.');
        }

        if (count($ajax_handler->errors) > 0) {
            return;
        }

        $email = sanitize_email($fields['email']);
        $zipcode = sanitize_text_field($fields['zipcode']);

        $options = get_option('fi_settings');

        $url = 'https://api.lafranceinsoumise.fr/legacy/people/subscribe/';

        $body = '{"email":"'.$email.'", "location_zip":"'.$zipcode.'"}';
        $response = wp_remote_post($url, [
            'headers' => [
                'Content-type' => 'application/json',
                'Authorization' => 'Basic '.base64_encode($options['api_id'].':'.$options['api_key']),
                'X-Wordpress-Client' => $_SERVER['REMOTE_ADDR']
            ],
            'body' => $body
        ]);

        if (!is_wp_error($response) && $response['response']['code'] === 422) {
            error_log('422 error while POSTing to API : '.$response['body']);
            $ajax_handler->add_error_message('Oups, une erreur est survenue, veuillez réessayer plus tard&nbsp;!');
            return;
        }

        if (is_wp_error($response) || $response['response']['code'] !== 201) {
            error_log('Error while POSTing new user to API.');
            $ajax_handler->add_error_message('Oups, une erreur est survenue, veuillez réessayer plus tard&nbsp;!');
            return;
        }
    }
}
