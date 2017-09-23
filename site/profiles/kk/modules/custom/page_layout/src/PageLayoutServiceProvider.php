<?php

namespace Drupal\page_layout;

use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 *
 */
class PageLayoutServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   *
   * Override the route matcher service.
   */
  public function alter(ContainerBuilder $container) {
    // Extend the core title resolver.
    $definition = $container->getDefinition('title_resolver');
    $definition->setClass('Drupal\page_layout\Controller\TitleResolver');
    $definition->addArgument(new Reference('page_layout.page_manager'));
  }

}
