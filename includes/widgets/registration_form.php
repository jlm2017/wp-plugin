<?php

/**
 * Adds Fi_Registration_Form widget.
 */
class Fi_Registration_Form extends WP_Widget {

  /**
   * Register widget with WordPress.
   */
  function __construct() {
    parent::__construct(
      'fi_registration_form', // Base ID
      esc_html__( 'JLM2017 inscription', 'jlm2017-wordpress-addons' ), // Name
      array( 'description' => esc_html__( 'Formulaire d\'inscription à la France Insoumise', 'jlm2017-wordpress-addons' ), ) // Args
    );
  }

  /**
   * Front-end display of widget.
   *
   * @see WP_Widget::widget()
   *
   * @param array $args     Widget arguments.
   * @param array $instance Saved values from database.
   */
  public function widget( $args, $instance ) {
    global $jlm2017_form_signup_errors;
    global $jlm2017_form_signup_email;
    global $jlm2017_form_signup_zipcode;
    ?>
    <h3 class="text-center">Je rejoins la France Insoumise</h3>
    <form method="POST" action="">
      <?php if (isset($jlm2017_form_signup_errors['form'])) {
        echo '<p>'.$jlm2017_form_signup_errors['form'].'</p>';
      } ?>
      <div class="form-group">
        <input type="hidden" name="action" value="jlm2017_signup_form">
      </div>
      <div class="col-sm-12 form-group <?= isset($jlm2017_form_signup_errors['email']) ? 'has-error has-feedback' : ''; ?>">
        <input required class="form-control input-lg" id="signup_email" name="jlm2017_form_signup_email" value="<?= $jlm2017_form_signup_email ?>" placeholder="Adresse email" type="email" />
        <?php if (isset($jlm2017_form_signup_errors['email'])) { ?>
          <i class="fa fa-exclamation-triangle" aria-hidden="true" style="float: right; margin-top: -33px; margin-right: 10px; color: red;"></i>
          <span class="help-block"><?= $jlm2017_form_signup_errors['email'] ?></span>
        <?php } ?>
      </div>
      <div class="form-group">
        <div class="col-sm-6 form-group <?= isset($jlm2017_form_signup_errors['zipcode']) ? 'has-error has-feedback' : ''; ?>">
          <input required class="form-control input-lg" id="signup_address_zip" name="jlm2017_form_signup_zipcode"  value="<?= $jlm2017_form_signup_zipcode ?>" placeholder="Code Postal" type="text" />
          <?php if (isset($jlm2017_form_signup_errors['zipcode'])) { ?>
            <i class="fa fa-exclamation-triangle" aria-hidden="true" style="float: right; margin-top: -33px; margin-right: 10px; color: red;"></i>
            <span class="help-block"><?= $jlm2017_form_signup_errors['zipcode'] ?></span>
          <?php } ?>
        </div>
        <div class="col-sm-6">
          <button type="submit" class="btn btn-block btn-lg btn-primary">J'appuie</button>
        </div>
      </div>
      <div class="col-xs-12 text-center">
        <p>Je recevrai des informations de la France Insoumise</p>
        <p class="text-right">
          <small>Si vous habitez à l'étranger, <a href="http://www.jlm2017.fr/inscription">cliquez ici</a>.</small>
        </p>
      </div>
    </form>
    <?php
  }

} // class Fi_Registration_Form
