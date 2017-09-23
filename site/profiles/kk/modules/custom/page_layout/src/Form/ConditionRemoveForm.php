<?php

namespace Drupal\page_layout\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\page_layout\PageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class ConditionRemoveForm extends FormBase {
  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\page_layout\PageManagerInterface
   */
  protected $pageManager;

  /**
   * ConditionRemoveForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Drupal\page_layout\PageManagerInterface $pageManager
   */
  public function __construct(ConfigFactoryInterface $configFactory, PageManagerInterface $pageManager) {
    $this->configFactory = $configFactory;
    $this->pageManager = $pageManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('page_layout.page_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'page_layout_condition_remove_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $page_id = NULL, $variant_id = NULL, $condition_id = NULL) {
    // Store the page and variant for re-use in submit.
    $form_state->set('page_id', $page_id);
    $form_state->set('variant_id', $variant_id);
    $form_state->set('condition_id', $condition_id);

    $form['warning'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Are you sure?'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['confirm'] = [
      '#type' => 'submit',
      '#value' => $this->t('Confirm'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $page_id = $form_state->get('page_id');
    $variant_id = $form_state->get('variant_id');

    $this->pageManager->removeCondition($page_id, $variant_id, $form_state->get('condition_id'));

    $form_state->setRedirect('page_layout.admin_variants_edit', ['page_id' => $page_id, 'variant_id' => $variant_id], ['fragment' => 'edit-conditions']);
  }

}
