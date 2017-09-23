<?php

namespace Drupal\page_layout\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\page_layout\PageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class PageListController extends ControllerBase {
  /**
   * @var \Drupal\page_layout\PageManagerInterface
   */
  protected $pageManager;

  /**
   * PageListController constructor.
   *
   * @param \Drupal\page_layout\PageManagerInterface $pageManager
   */
  public function __construct(PageManagerInterface $pageManager) {
    $this->pageManager = $pageManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('page_layout.page_manager')
    );
  }

  /**
   * Build the list of pages handled by the Page Layout.
   *
   * @return array
   *   Renderable array.
   */
  public function listing() {
    $table = [
      '#type' => 'table',
      '#header' => [
        $this->t('Title'),
        $this->t('Machine name'),
        $this->t('Path'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('No pages created yet.'),
    ];

    foreach ($this->pageManager->getPages() as $id => $page) {
      // Some table columns containing raw markup.
      $table[$id]['label'] = [
        '#plain_text' => $page['title'],
      ];
      $table[$id]['id'] = [
        '#plain_text' => $id,
      ];
      $table[$id]['path'] = [
        '#plain_text' => $page['path'],
      ];
      $table[$id]['operations'] = [
        '#type' => 'operations',
        '#links' => [],
      ];
      $table[$id]['operations']['#links']['variants'] = [
        'title' => $this->t('Edit variants'),
        'url' => Url::fromRoute('page_layout.admin_variants_edit', ['page_id' => $id]),
      ];
      $table[$id]['operations']['#links']['edit'] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('page_layout.admin_page_edit', ['page_id' => $id]),
        'attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode(['width' => 700]),
        ],
      ];
      $table[$id]['operations']['#links']['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('page_layout.admin_page_delete', ['page_id' => $id]),
        'attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode(['width' => 700]),
        ],
      ];
    }

    return $table;
  }

}
