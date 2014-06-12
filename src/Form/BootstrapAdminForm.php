<?php

/**
 * @file
 * Contains BootstrapAdminForm class
 */

namespace Drupal\sms\Form;

use Drupal\Core\Form\ConfigFormBase;

/**
 * Provides a overview form for sms bootstrap options.
 *
 * @todo Migrate bootstrap settings to new config API
 */
class BootstrapAdminForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'sms_bootstrap_admin';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $op = NULL, $domain = NULL) {
    $config = $this->configFactory->get('sms.settings');
    // @todo Need to migrate this to config
    $cache_backends = variable_get('cache_backends', array());
  
    $configured = FALSE;
    foreach ($cache_backends as $inc) {
      if (substr($inc, -16) == 'sms_incoming.inc') {
        $configured = TRUE;
        break;
      }
    }
    
    $form['title']['#markup'] = '<h2>' . t('Incoming SMS bootstrap by-pass configuration') . '</h2>';
    $form['introduction']['#markup'] = '<p>' . t('For higher volume incoming SMS. Enable parsing and queuing for later processing.') . '</p>';
  
    if (! $configured) {
      $form['description'] = array(
        '#type' => 'fieldset',
        '#title' => t('Not enabled'),
      );
      $form['description']['description'] = array(
        '#type' => 'markup',
        '#markup' => '<p>' . t('The <em>cache_backend</em> for sms_incoming has not been set.') . '</p>'
        . '<p>' . t('See the smsframework module <code>bootstrap/README.txt</code> for more details.') . '</p>',
      );
  
      return $form;
    }
    
    $form['sms_bootstrap_enabled'] = array(
      '#type' => 'checkbox',
      '#default_value' => variable_get('sms_bootstrap_enabled', FALSE),
      '#title' => t('Bootstrap by-pass enabled'),
      '#description' => t('If the variable sms_bootstrap_enabled is configured in your settings.php (advised) you will not be able to change it here.'),
    );
    
    $form['sms_bootstrap_routes'] = array(
      '#type' => 'fieldset',
      '#title' => t('Routes'),
      '#description' => t('Routes can be defaulted by the SMS provider module, or defined in your settings.php file.'),
    );
    $form['sms_bootstrap_routes']['vardump'] = array(
      '#type' => 'markup',
      '#markup' => '<pre>' . filter_xss_admin(print_r(variable_get('sms_bootstrap_routes', array()), TRUE)) . '</pre>',
    );
    
    $form['sms_bootstrap_queue'] = array(
      '#type' => 'fieldset',
      '#title' => t('Queue'),
      '#description' => t('The queue used for parsed incoming SMS.'),
    );
    
    $queue_default = array(
      'name' => 'sms_incoming',
      'require db' => TRUE,
      'reliable' => TRUE,
    );
    $queue_config = (array) variable_get('sms_bootstrap_queue', array());
    $queue_config += $queue_default;
    if (!empty ($queue_config['inc'])) {
      // Maybe the include file isn't included. Plus if the path is wrong it will
      // give a warning here.
      include_once($queue_config['inc']);
    }
    // Now use DrupalQueue to retrieve whichever queue it would return.
    $queue = \Drupal::queue($queue_config['name'], $queue_config['reliable']);
    $queue_class = get_class($queue);
    
    $form['sms_bootstrap_queue']['vardump'] = array(
      '#type' => 'markup',
      '#markup' => '<pre>' . filter_xss_admin(print_r($queue_config, TRUE) . "\nDrupal is using the $queue_class") . '</pre>',
    );
    
    // Get system setting form
    return parent::buildForm($form, $form_state);
    
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
//     $this->configFactory->get('sms.settings')->set('bootstrap_enabled', $form_state['values']['sms_bootstrap_enabled'])->save();
    variable_set('sms_bootstrap_enabled', $form_state['values']['sms_bootstrap_enabled']);
    parent::submitForm($form, $form_state);
  }
}
