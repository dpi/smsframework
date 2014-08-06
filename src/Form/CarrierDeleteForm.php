<?php
/**
 * Contains Drupal\sms\Form\CarrierDeleteForm
 */

namespace Drupal\sms\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\ConfirmFormBase;

class CarrierDeleteForm extends ConfirmFormBase {
  /**
   * The carrier associated with this form
   *
   * @var array
   */
  protected $carrier;

  /**
   * The base redirect url from this form.
   *
   * @var \Drupal\Core\Url
   */
  protected $redirectUrl;

  /**
   * Constructor to provide the attached carrier
   */
  public function __construct() {
    $this->carrier = sms_carriers($this->getRequest()->get('domain'));
    $this->redirectUrl = new Url(array(
      'route_name' => 'sms.carrier_admin',
      'route_parameters' => array(),
    ));
  }

  /**
   * {@inheritdoc
   */
  public function getQuestion()
  {
    if ($this->carrier['type'] == SMS_CARRIER_OVERRIDDEN) {
      return $this->t('Are you sure you want revert %carrier?', array('%carrier' => $this->carrier['name']));
    }
    else if ($this->carrier['type'] == SMS_CARRIER_NORMAL) {
      return $this->t('Are you sure you want delete %carrier?', array('%carrier' => $this->carrier['name']));
    }
  }

  public function getDescription() {
    if ($this->carrier['type'] == SMS_CARRIER_OVERRIDDEN) {
      return $this->t('Reverting this carrier will delete it from the database. It will be replaced with the default carrier settings. This action cannot be undone.');
    }
    else if ($this->carrier['type'] == SMS_CARRIER_NORMAL) {
      return $this->t('This carrier will be removed from the database. This action cannot be undone.');
    }
  }

  public function getConfirmText() {
    if ($this->carrier['type'] == SMS_CARRIER_OVERRIDDEN) {
      return $this->t('Revert');
    }
    else if ($this->carrier['type'] == SMS_CARRIER_NORMAL) {
      return $this->t('Delete');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl()
  {
    return $this->redirectUrl;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'sms_carriers_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    carrier_delete($this->carrier['domain']);
    # XXX D7 porting issue: $carrier below never gets set ??
    # --- this is ALSO a bug in the D6 verion!
    #if ($carrier['type'] == SMS_CARRIER_OVERRIDDEN) {
    #  drupal_set_message(t('The carrier has been reverted.'));
    #}
    #if ($carrier['type'] == SMS_CARRIER_NORMAL) {
    #  drupal_set_message(t('The carrier has been deleted.'));
    #}

    $form_state->setRedirect($this->redirectUrl);
  }
}