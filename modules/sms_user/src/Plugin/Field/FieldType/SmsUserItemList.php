<?php

/**
 * @file
 * Contains \Drupal\sms_user\Plugin\Field\FieldType\SmsUserItemList
 */

namespace Drupal\sms_user\Plugin\Field\FieldType;


use Drupal\Core\Field\FieldItemList;

/**
 * Represents the sms_user item list.
 *
 * Allows referencing of properties like arrays. It defaults to the corresponding
 * property of the first list item for non-numeric offsets.

 */
class SmsUserItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public function offsetExists($offset) {
    if (!is_numeric($offset)) {
      $value = $this->get(0)->get($offset);
      return isset($value);
    }
    return parent::offsetExists($offset);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetUnset($offset) {
    if (!is_numeric($offset)) {
      // @todo
      $this->get(0)->delete($offset);
    }
    parent::offsetUnset($offset);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetGet($offset) {
    if (!is_numeric($offset)) {
      return $this->get(0)->get($offset);
    }
    return parent::offsetGet($offset);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetSet($offset, $value) {
    if (!is_numeric($offset)) {
      return $this->get(0)->set($offset, $value);
    }
    parent::offsetSet($offset, $value);
  }

}