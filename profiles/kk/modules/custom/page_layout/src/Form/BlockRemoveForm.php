<?php

namespace Drupal\page_layout\Form;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\page_layout\PageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 */
class BlockRemoveForm extends FormBase {
  /**
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * @var \Drupal\page_layout\PageManagerInterface
   */
  protected $pageManager;

  /**
   * BlockRemoveForm constructor.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $blockManager
   * @param \Drupal\page_layout\PageManagerInterface $pageManager
   */
  public function __construct(BlockManagerInterface $blockManager, PageManagerInterface $pageManager) {
    $this->blockManager = $blockManager;
    $this->pageManager = $pageManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('page_layout.page_manager')

    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'page_layout_block_remove_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $plugin_id = NULL, $page_id = NULL, $variant_id = NULL, Request $request = NULL) {
    $plugin_info = $this->blockManager->getDefinition($plugin_id);

    // Check if a specific region is set to remove from.
    $region = $request->query->has('region') ? $request->query->get('region') : FALSE;
    // Check if a specific index should be removed.
    $weight = $request->query->has('weight') ? $request->query->get('weight') : FALSE;

    $form_state->set('plugin_id', $plugin_id);
    $form_state->set('page_id', $page_id);
    $form_state->set('variant_id', $variant_id);
    $form_state->set('region', $region);
    $form_state->set('weight', $weight);

    $form['warning'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('This will remove "@title". Are you sure?', ['@title' => $plugin_info['admin_label'], '@variant' => $variant_id]),
    ];

    $form['action']['confirm'] = [
      '#type' => 'submit',
      '#value' => $this->t('Confirm'),

    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $plugin_id = $form_state->get('plugin_id');
    $page_id = $form_state->get('page_id');
    $variant_id = $form_state->get('variant_id');
    $region = $form_state->get('region');
    $weight = $form_state->get('weight');

    $variant = $this->pageManager->getVariant($page_id, $variant_id);

    if ($variant) {
      $found = FALSE;
      foreach ($variant['blocks'] as $block_index => $block) {
        if ($block['plugin'] === $plugin_id && $block['region'] === $region) {
          // If no found block is yet set,
          // or the block from config has the right weight, mark it.
          if (!$found || $block['weight'] === $weight) {
            unset($variant['blocks'][$block_index]);
            $found = TRUE;
          }
        }
      }
    }
    $this->pageManager->saveVariant($page_id, $variant);

    drupal_set_message($this->t('Block removed from region.'), 'status');

    $form_state->setRedirect('page_layout.admin_variants_edit', ['page_id' => $page_id, 'variant_id' => $variant_id], ['fragment' => "edit-content"]);
  }

}
