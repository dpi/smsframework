<?php

/**
 * @file
 * SMS Framework Message Tracking feature module: views
 *
 * @package sms
 * @subpackage sms_track
 */

/**
 * Implements hook_views_default_views().
 */
function sms_track_views_default_views() {
  $view = new view;
  $view->name = 'sms_track';
  $view->description = 'All archived SMS messages - in and out';
  $view->tag = 'sms';
  $view->view_php = '';
  $view->base_table = 'sms_track';
  $view->is_cacheable = FALSE;
  $view->api_version = 2;
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */
  $handler = $view->new_display('default', 'Default', 'default');
  $handler->override_option('fields', array(
    'dir' => array(
      'label' => 'Direction',
      'alter' => array(
        'alter_text' => 0,
        'text' => '',
        'make_link' => 0,
        'path' => '',
        'alt' => '',
        'prefix' => '',
        'suffix' => '',
        'help' => '',
        'trim' => 0,
        'max_length' => '',
        'word_boundary' => 1,
        'ellipsis' => 1,
        'html' => 0,
      ),
      'exclude' => 0,
      'id' => 'dir',
      'table' => 'sms_track',
      'field' => 'dir',
      'relationship' => 'none',
    ),
    'number' => array(
      'id' => 'number',
      'table' => 'sms_track',
      'field' => 'number',
    ),
    'message' => array(
      'label' => 'Message',
      'alter' => array(
        'alter_text' => 0,
        'text' => '',
        'make_link' => 0,
        'path' => '',
        'alt' => '',
        'prefix' => '',
        'suffix' => '',
        'help' => '',
        'trim' => 0,
        'max_length' => '',
        'word_boundary' => 1,
        'ellipsis' => 1,
        'html' => 0,
      ),
      'exclude' => 0,
      'id' => 'message',
      'table' => 'sms_track',
      'field' => 'message',
      'relationship' => 'none',
    ),
    'created' => array(
      'label' => 'Timestamp',
      'alter' => array(
        'alter_text' => 0,
        'text' => '',
        'make_link' => 0,
        'path' => '',
        'alt' => '',
        'prefix' => '',
        'suffix' => '',
        'help' => '',
        'trim' => 0,
        'max_length' => '',
        'word_boundary' => 1,
        'ellipsis' => 1,
        'html' => 0,
      ),
      'date_format' => 'small',
      'custom_date_format' => '',
      'exclude' => 0,
      'id' => 'created',
      'table' => 'sms_track',
      'field' => 'created',
      'relationship' => 'none',
    ),
    'local_number' => array(
      'label' => 'Local number',
      'alter' => array(
        'alter_text' => 0,
        'text' => '',
        'make_link' => 0,
        'path' => '',
        'alt' => '',
        'prefix' => '',
        'suffix' => '',
        'help' => '',
        'trim' => 0,
        'max_length' => '',
        'word_boundary' => 1,
        'ellipsis' => 1,
        'html' => 0,
      ),
      'exclude' => 0,
      'id' => 'local_number',
      'table' => 'sms_track',
      'field' => 'local_number',
      'relationship' => 'none',
    ),
    'gateway' => array(
      'label' => 'Gateway',
      'alter' => array(
        'alter_text' => 0,
        'text' => '',
        'make_link' => 0,
        'path' => '',
        'alt' => '',
        'prefix' => '',
        'suffix' => '',
        'help' => '',
        'trim' => 0,
        'max_length' => '',
        'word_boundary' => 1,
        'ellipsis' => 1,
        'html' => 0,
      ),
      'exclude' => 0,
      'id' => 'gateway',
      'table' => 'sms_track',
      'field' => 'gateway',
      'relationship' => 'none',
    ),
  ));
  $handler->override_option('access', array(
    'type' => 'role',
    'role' => array(
      '3' => 3,
    ),
  ));
  $handler->override_option('title', 'SMS Archive');
  $handler->override_option('empty', 'There are no SMS messages in the archive.');
  $handler->override_option('empty_format', '1');
  $handler->override_option('items_per_page', 30);
  $handler->override_option('use_pager', '1');
  $handler->override_option('style_plugin', 'table');
  $handler->override_option('style_options', array(
    'grouping' => '',
    'override' => 1,
    'sticky' => 0,
    'order' => 'desc',
    'columns' => array(
      'dir' => 'dir',
      'number' => 'number',
      'message' => 'message',
      'created' => 'created',
      'local_number' => 'local_number',
      'gateway' => 'gateway',
    ),
    'info' => array(
      'dir' => array(
        'sortable' => 1,
        'separator' => '',
      ),
      'number' => array(
        'sortable' => 1,
        'separator' => '',
      ),
      'message' => array(
        'sortable' => 1,
        'separator' => '',
      ),
      'created' => array(
        'sortable' => 1,
        'separator' => '',
      ),
      'local_number' => array(
        'sortable' => 1,
        'separator' => '',
      ),
      'gateway' => array(
        'sortable' => 1,
        'separator' => '',
      ),
    ),
    'default' => 'created',
  ));
  $views[$view->name] = $view;

  // View for sent and received messages
  $view = new view();
  $view->name = 'my_messages';
  $view->description = 'View the current user\'s sent and received messages.';
  $view->tag = 'sms';
  $view->base_table = 'sms_track';
  $view->human_name = 'My Messages';
  $view->core = 7;
  $view->api_version = '3.0';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['title'] = 'My Messages';
  $handler->display->display_options['use_more_always'] = FALSE;
  $handler->display->display_options['access']['type'] = 'perm';
  $handler->display->display_options['access']['perm'] = 'view own sms messages';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'full';
  $handler->display->display_options['pager']['options']['items_per_page'] = '10';
  $handler->display->display_options['style_plugin'] = 'table';
  $handler->display->display_options['style_options']['row_class'] = '[dir]bound';
  $handler->display->display_options['style_options']['columns'] = array(
    'message' => 'message',
    'author' => 'author',
    'recipient' => 'recipient',
    'created' => 'created',
    'dir' => 'dir',
    'gateway' => 'gateway',
    'local_number' => 'local_number',
    'number' => 'number',
  );
  $handler->display->display_options['style_options']['default'] = '-1';
  $handler->display->display_options['style_options']['info'] = array(
    'message' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
    'author' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
    'recipient' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
    'created' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
    'dir' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
    'gateway' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
    'local_number' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
    'number' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
  );
  /* Relationship: SMS Archive: SMS Author */
  $handler->display->display_options['relationships']['author']['id'] = 'author';
  $handler->display->display_options['relationships']['author']['table'] = 'sms_track';
  $handler->display->display_options['relationships']['author']['field'] = 'author';
  $handler->display->display_options['relationships']['author']['required'] = TRUE;
  /* Relationship: SMS Archive: SMS Recipient */
  $handler->display->display_options['relationships']['recipient']['id'] = 'recipient';
  $handler->display->display_options['relationships']['recipient']['table'] = 'sms_track';
  $handler->display->display_options['relationships']['recipient']['field'] = 'recipient';
  $handler->display->display_options['relationships']['recipient']['required'] = TRUE;
  /* Field: SMS Archive: Message */
  $handler->display->display_options['fields']['message']['id'] = 'message';
  $handler->display->display_options['fields']['message']['table'] = 'sms_track';
  $handler->display->display_options['fields']['message']['field'] = 'message';
  /* Field: User: Name */
  $handler->display->display_options['fields']['name']['id'] = 'name';
  $handler->display->display_options['fields']['name']['table'] = 'users';
  $handler->display->display_options['fields']['name']['field'] = 'name';
  $handler->display->display_options['fields']['name']['relationship'] = 'author';
  $handler->display->display_options['fields']['name']['label'] = 'Author';
  /* Field: User: Name */
  $handler->display->display_options['fields']['name_1']['id'] = 'name_1';
  $handler->display->display_options['fields']['name_1']['table'] = 'users';
  $handler->display->display_options['fields']['name_1']['field'] = 'name';
  $handler->display->display_options['fields']['name_1']['relationship'] = 'recipient';
  $handler->display->display_options['fields']['name_1']['label'] = 'Recipient';
  /* Field: SMS Archive: Timestamp */
  $handler->display->display_options['fields']['created']['id'] = 'created';
  $handler->display->display_options['fields']['created']['table'] = 'sms_track';
  $handler->display->display_options['fields']['created']['field'] = 'created';
  /* Field: SMS Archive: Direction */
  $handler->display->display_options['fields']['dir']['id'] = 'dir';
  $handler->display->display_options['fields']['dir']['table'] = 'sms_track';
  $handler->display->display_options['fields']['dir']['field'] = 'dir';
  /* Field: SMS Archive: Gateway */
  $handler->display->display_options['fields']['gateway']['id'] = 'gateway';
  $handler->display->display_options['fields']['gateway']['table'] = 'sms_track';
  $handler->display->display_options['fields']['gateway']['field'] = 'gateway';
  /* Field: SMS Archive: Local number */
  $handler->display->display_options['fields']['local_number']['id'] = 'local_number';
  $handler->display->display_options['fields']['local_number']['table'] = 'sms_track';
  $handler->display->display_options['fields']['local_number']['field'] = 'local_number';
  /* Field: SMS Archive: Remote number */
  $handler->display->display_options['fields']['number']['id'] = 'number';
  $handler->display->display_options['fields']['number']['table'] = 'sms_track';
  $handler->display->display_options['fields']['number']['field'] = 'number';
  /* Contextual filter: SMS Archive: Author */
  $handler->display->display_options['arguments']['author']['id'] = 'author';
  $handler->display->display_options['arguments']['author']['table'] = 'sms_track';
  $handler->display->display_options['arguments']['author']['field'] = 'author';
  $handler->display->display_options['arguments']['author']['default_action'] = 'default';
  $handler->display->display_options['arguments']['author']['default_argument_type'] = 'user';
  $handler->display->display_options['arguments']['author']['default_argument_options']['user'] = FALSE;
  $handler->display->display_options['arguments']['author']['summary']['number_of_records'] = '0';
  $handler->display->display_options['arguments']['author']['summary']['format'] = 'default_summary';
  $handler->display->display_options['arguments']['author']['summary_options']['items_per_page'] = '25';
  /* Contextual filter: SMS Archive: Recipient */
  $handler->display->display_options['arguments']['recipient']['id'] = 'recipient';
  $handler->display->display_options['arguments']['recipient']['table'] = 'sms_track';
  $handler->display->display_options['arguments']['recipient']['field'] = 'recipient';
  $handler->display->display_options['arguments']['recipient']['default_action'] = 'default';
  $handler->display->display_options['arguments']['recipient']['default_argument_type'] = 'user';
  $handler->display->display_options['arguments']['recipient']['default_argument_options']['user'] = FALSE;
  $handler->display->display_options['arguments']['recipient']['summary']['number_of_records'] = '0';
  $handler->display->display_options['arguments']['recipient']['summary']['format'] = 'default_summary';
  $handler->display->display_options['arguments']['recipient']['summary_options']['items_per_page'] = '25';

  /* Display: Page */
  $handler = $view->new_display('page', 'Page', 'page');
  $handler->display->display_options['path'] = 'user/%/my-messages';
  $handler->display->display_options['menu']['type'] = 'tab';
  $handler->display->display_options['menu']['title'] = 'My Messages';
  $handler->display->display_options['menu']['weight'] = '0';
  $handler->display->display_options['menu']['name'] = 'user-menu';
  $handler->display->display_options['menu']['context'] = 0;
  $handler->display->display_options['menu']['context_only_inline'] = 0;
  $handler->display->display_options['tab_options']['type'] = 'tab';
  $handler->display->display_options['tab_options']['title'] = 'Message Tracking';
  $handler->display->display_options['tab_options']['weight'] = '0';
  $views[$view->name] = $view;

  return $views;
}
