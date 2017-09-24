<?php

namespace Drupal\page_layout;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Component\Plugin\ContextAwarePluginInterface;
use Drupal\Component\Plugin\Exception\ContextException;

/**
 *
 */
class PageManager implements PageManagerInterface {
  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * PageManager constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Drupal\Core\Condition\ConditionManager $conditionManager
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $contextRepository
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $contextHandler
   */
  public function __construct(ConfigFactoryInterface $configFactory, ConditionManager $conditionManager, ContextRepositoryInterface $contextRepository, ContextHandlerInterface $contextHandler) {
    $this->configFactory = $configFactory;
    $this->conditionManager = $conditionManager;
    $this->contextRepository = $contextRepository;
    $this->contextHandler = $contextHandler;
  }

  /**
   * {@inheritdoc}
   */
  public function getPages() {
    $pages = [];
    foreach ($this->configFactory->listAll('page_layout.page') as $name) {
      $page = $this->configFactory->get($name)->getRawData();
      if (isset($page['id'])) {
        $pages[$page['id']] = $page;
      }
    }
    return $pages;
  }

  /**
   * {@inheritdoc}
   */
  public function getPage($id) {
    $pages = $this->getPages();
    return isset($pages[$id]) ? $pages[$id] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPageByPath($path) {
    foreach ($this->getPages() as $page) {
      if ($page['path'] === $path) {
        return $page;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function createPage($title, $path) {
    // Generate an unique machine name.
    $id = $this->generateId($title);
    $iteration = 0;
    while ($this->getPage($id)) {
      $iteration++;
      $id = $this->generateId($title, $iteration);
    }

    $configName = "page_layout.page.{$id}";
    $config = $this->configFactory->getEditable($configName);

    $config->set('id', $id);
    $config->set('title', $title);
    $config->set('path', $path);

    $config->save();

    return $id;
  }

  /**
   * Generate an ID from a title.
   *
   * @param string $title
   * @param bool|int $iterator
   *   To prefix with an iterater.
   *
   * @return string
   *   The ID.
   */
  private function generateId($title, $iterator = FALSE) {
    $id = strtolower($title);
    // Replace space with a underscore.
    $id = str_replace(' ', '_', strtolower($id));
    // Remove anything not a character or number.
    $id = preg_replace('/[^a-z0-9_]/', '', $id);
    // Add an iterator if specified.
    if ($iterator !== FALSE) {
      $id .= $iterator;
    }
    return $id;
  }

  /**
   * {@inheritdoc}
   */
  public function updatePage(array $page) {
    $config = $this->configFactory->getEditable("page_layout.page.{$page['id']}");
    $config->setData($page);
    $config->save();
  }

  /**
   * {@inheritdoc}
   */
  public function createVariant($page_id, $admin_title, $title, $layout = 'page_layout.one_column') {
    $configName = "page_layout.page.{$page_id}";
    $config = $this->configFactory->getEditable($configName);

    $id = uniqid();

    $variants = $config->get('variants') ?: [];
    $variants[] = [
      'id' => $id,
      'admin_title' => $admin_title,
      'title' => $title,
      'layout' => $layout,
      'conditions' => [],
      'blocks' => [],
    ];

    $config->set('variants', $variants);
    $config->save();

    return $id;
  }

  /**
   * {@inheritdoc}
   */
  public function saveVariant($page_id, array $updated_variant) {
    $page = $this->getPage($page_id);
    foreach ($page['variants'] as $key => $variant) {
      if ($variant['id'] === $updated_variant['id']) {
        $page['variants'][$key] = $updated_variant;
        break;
      }
    }
    $this->updatePage($page);
  }

  /**
   * {@inheritdoc}
   */
  public function getVariant($page_id, $variant_id = NULL) {
    $page = $this->getPage($page_id);
    if (!$page) {
      return FALSE;
    }

    foreach ($page['variants'] as $variant) {
      if (!$variant_id) {
        return $variant;
      }

      if ($variant['id'] === $variant_id) {
        return $variant;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCondition($page_id, $variant_id, $condition_id) {
    $page = $this->getPage($page_id);
    // Iterate variants and selection rules to find the right rule.
    foreach ($page['variants'] as $variant) {
      if ($variant['id'] === $variant_id) {
        foreach ($variant['conditions'] as $condition) {
          if ($condition['id'] === $condition_id) {
            return $condition;
          }
        }
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function saveCondition($page_id, $variant_id, $plugin_id, array $configuration = [], $condition_id = FALSE) {
    $variant = $this->getVariant($page_id, $variant_id);

    if ($variant) {
      // If an existing condition should be updated.
      if ($condition_id) {
        foreach ($variant['conditions'] as &$condition) {
          if ($condition['id'] === $condition_id) {
            $condition['plugin'] = $plugin_id;
            $condition['configuration'] = $configuration;
          }
        }
      }
      // Otherwise create a new one.
      else {
        $variant['conditions'][] = [
          'id' => uniqid(),
          'plugin' => $plugin_id,
          'configuration' => $configuration,
        ];
      }
    }

    $this->saveVariant($page_id, $variant);
  }

  /**
   * {@inheritdoc}
   */
  public function removeCondition($page_id, $variant_id, $condition_id) {
    $variant = $this->getVariant($page_id, $variant_id);
    // Iterate the conditions rules to unset the right one.
    foreach ($variant['conditions'] as $key => $condition) {
      if ($condition['id'] === $condition_id) {
        unset($variant['conditions'][$key]);
        break;
      }
    }

    $this->saveVariant($page_id, $variant);
  }

  /**
   * {@inheritdoc}
   */
  public function getMatchingVariant($page_id, $check_conditions = TRUE) {
    $page = $this->getPage($page_id);

    // Iterate variants from the page to see if one applies.
    $found = FALSE;
    foreach ($page['variants'] as $variant) {
      // If no variant has yet been found, and the current one
      // does not have any conditions to apply, use it for rendering.
      if (!$found['variant'] && (!$check_conditions || empty($variant['conditions']))) {
        $found = [
          'weight' => 0,
          'variant' => $variant,
        ];
      }
      // If the variant has condition, iterate them to see if the variant
      // apply to the current context.
      if ($check_conditions && !empty($variant['conditions'])) {
        $applies = TRUE;
        foreach ($variant['conditions'] as $condition) {
          $condition = $this->conditionManager->createInstance($condition['plugin'], $condition['configuration']);

          // Apply any required context.
          if ($condition instanceof ContextAwarePluginInterface) {
            try {
              $contexts = $this->contextRepository->getRuntimeContexts(array_values($condition->getContextMapping()));
              $this->contextHandler->applyContextMapping($condition, $contexts);
            } catch (ContextException $e) {
              $applies = FALSE;
            }
          }

          // Execute the condition to see if the condition is valid.
          if ($applies) {
            try {
              if (!$this->conditionManager->execute($condition)) {
                $applies = FALSE;
                break;
              }
            } catch (\Exception $e) {
              // If a condition is missing context, consider it a fail.
              $applies = FALSE;
              break;
            }
          }
        }
        // If all conditions defined applied, and the current variant has
        // defined more conditions than the previous found variant,
        // update the found variant.
        if ($applies && $found['weight'] < count($variant['conditions'])) {
          $found = [
            'weight' => count($variant['conditions']),
            'variant' => $variant,
          ];
        }
      }
    }

    // Only return the variant if one was found.
    // The weight entry was only for internal use.
    return $found ? $found['variant'] : FALSE;
  }

}
