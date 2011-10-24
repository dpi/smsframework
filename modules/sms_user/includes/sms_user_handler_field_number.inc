<?php

/**
 * @file
 * A handler to provide displays for users' mobile phone
 *
 */
class sms_user_handler_field_number extends views_handler_field {

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
  }

  function render($values) {
    return $values->{$this->field_alias};
  }
}