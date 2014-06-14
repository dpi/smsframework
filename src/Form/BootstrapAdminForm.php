<?php

/**
 * @file
 * Contains BootstrapAdminForm class
 */

namespace Drupal\sms\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Site\Settings;
use Drupal\Component\Utility\Xss;

/**
 * Provides a overview form for sms bootstrap options.
 *
 * @todo The approach used in implementing bootstrap in D7 will not work in D8
 *   since the inclusion of cache files has been removed from D8 bootstrap.
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
    $bootstrap_config = $this->config('sms.settings');
    // @todo See main comment above.
    $cache_backends = Settings::get('cache_backends', array());
  
    $bootstrap_configured = FALSE;
    foreach ($cache_backends as $inc) {
      if (substr($inc, -16) == 'sms_incoming.inc') {
        $bootstrap_configured = TRUE;
        break;
      }
    }
    
    $form['title']['#markup'] = '<h2>' . t('Incoming SMS bootstrap by-pass configuration') . '</h2>';
    $form['introduction']['#markup'] = '<p>' . t('For higher volume incoming SMS. Enable parsing and queuing for later processing.') . '</p>';
  
    if (! $bootstrap_configured) {
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
    
    $bootstrap_config = $this->config('sms.settings')->get('bootstrap');
    $form['sms_bootstrap_enabled'] = array(
      '#type' => 'checkbox',
      '#default_value' => ($bootstrap_config['enabled'] || Settings::get('sms_bootstrap_enabled', FALSE)),
      '#title' => t('Bootstrap by-pass enabled'),
      '#description' => t('If the variable sms_bootstrap_enabled is configured in your settings.php (advised) you will not be able to change it here.'),
      '#disabled' => Settings::get('sms_bootstrap_enabled', FALSE),
    );
    
    $form['sms_bootstrap_routes'] = array(
      '#type' => 'fieldset',
      '#title' => t('Routes'),
      '#description' => t('Routes can be defaulted by the SMS provider module, or defined in your settings.php file.'),
    );
    // Merge the routes specified in settings.php with the ones defined in config.
    $routes = Settings::get('sms_bootstrap_routes', array());
    if (is_array($bootstrap_config['routes'])) {
      $routes += $bootstrap_config['routes'];
    }
    $form['sms_bootstrap_routes']['vardump'] = array(
      '#type' => 'markup',
      '#markup' => '<pre>' . Xss::filterAdmin(print_r($routes, TRUE)) . '</pre>',
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
    $queue_config = (array) Settings::get('sms_bootstrap_queue', array());
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
      '#markup' => '<pre>' . Xss::filterAdmin(print_r($queue_config, TRUE) . "\nDrupal is using the $queue_class") . '</pre>',
    );
    
    // Get system setting form
    return parent::buildForm($form, $form_state);
    
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
     $this->config('sms.settings')->set('bootstrap.enabled', $form_state['values']['sms_bootstrap_enabled'])->save();
    parent::submitForm($form, $form_state);
  }
}
