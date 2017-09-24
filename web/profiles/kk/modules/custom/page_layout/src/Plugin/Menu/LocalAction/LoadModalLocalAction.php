<?php

namespace Drupal\page_layout\Plugin\Menu\LocalAction;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Defines a local action plugin with a dynamic title.
 */
class LoadModalLocalAction extends LocalActionDefault {

  /**
   * {@inheritdoc}
   */
  public function getOptions(RouteMatchInterface $route_match) {
    $options = parent::getOptions($route_match);
    // Add AJAX attributes.
    $options['attributes']['class'][] = 'use-ajax';
    $options['attributes']['data-dialog-type'] = 'modal';
    $options['attributes']['data-dialog-options'] = Json::encode(['width' => 700]);
    return $options;
  }

}
