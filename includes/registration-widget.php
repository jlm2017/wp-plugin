<?php

$jlm2017_form_errors = '';
$jlm2017_form_signup_email = '';
$jlm2017_form_signup_zipcode = '';

class FI_Registration_Widget extends WP_Widget {
    function __construct()
    {
        parent::__construct(
            'fi_registration_widget',
            esc_html__('Inscription FI'),
            ['description' => esc_html__('Formulaire d\'inscription')]
		);
    }

    public function widget($args, $instance)
    {
        global $jlm2017_form_signup_errors;
        global $jlm2017_form_signup_email;
        global $jlm2017_form_signup_zipcode;
        ?>
        <div class="fi-registration-widget">
          <h3 class="text-center">Je rejoins la France Insoumise</h3>
          <div class="row">
            <form method="POST" action="">
               <?php if (isset($jlm2017_form_signup_errors['form'])) {
                echo '<p>'.esc_html($jlm2017_form_signup_errors['form']).'</p>';
               } ?>
              <div class="form-group">
                <input type="hidden" name="action" value="jlm2017_signup_form">
              </div>
              <div class="col-sm-9 form-group <?= isset($jlm2017_form_signup_errors['email']) ? 'has-error has-feedback' : ''; ?>">
                <input required class="form-control input-lg" id="signup_email" name="jlm2017_form_signup_email" value="<?= esc_attr($jlm2017_form_signup_email) ?>" placeholder="Adresse email" type="email" />
                <?php if (isset($jlm2017_form_signup_errors['email'])) { ?>
                  <i class="fa fa-exclamation-triangle" aria-hidden="true" style="float: right; margin-top: -33px; margin-right: 10px; color: red;"></i>
                  <span class="help-block"><?= esc_html($jlm2017_form_signup_errors['email']) ?></span>
                <?php } ?>
              </div>
              <div class="col-sm-3 form-group <?= isset($jlm2017_form_signup_errors['zipcode']) ? 'has-error has-feedback' : '';   ?>">
                <input required class="form-control input-lg" id="signup_address_zip" name="jlm2017_form_signup_zipcode" value="<?= esc_attr($jlm2017_form_signup_zipcode) ?>" placeholder="Code Postal" type="text" />
                <?php if (isset($jlm2017_form_signup_errors['zipcode'])) { ?>
                  <i class="fa fa-exclamation-triangle" aria-hidden="true" style="float: right; margin-top: -33px; margin-right: 10px; color: red;"></i>
                  <span class="help-block"><?= esc_html($jlm2017_form_signup_errors['zipcode']) ?></span>
                <?php } ?>
              </div>
              <div class="col-sm-9">
                <p>En remplissant ce formulaire, j'accepte que la France Insoumise utilise ces données
                  pour m'envoyer des informations.</p>
                <p>
                  Si vous habitez à l'étranger, <a href="https://agir.lafranceinsoumise.fr/inscription/etranger/">cliquez ici</a>.
                </p>
              </div>
              <div class="col-sm-3">
                <button type="submit" class="btn btn-block btn-lg btn-primary">Je rejoins</button>
              </div>
            </form>
          </div>
        </div>
        <?php
        return;
    }
}
