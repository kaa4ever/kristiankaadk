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
class VariantsDeleteForm extends FormBase {
  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\page_layout\PageManagerInterface
   */
  protected $pageManager;

  /**
   * AddPageForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
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
    return 'page_layout_delete_variant';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $page_id = NULL, $variant_id = NULL) {
    // Store the page and variant for re-use in submit.
    $form_state->set('page_id', $page_id);
    $form_state->set('variant_id', $variant_id);

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

    $config = $this->configFactory->getEditable("page_layout.page.$page_id");

    $variants = $config->get('variants');
    foreach ($variants as $key => &$variant) {
      if ($variant['id'] === $variant_id) {
        unset($variants[$key]);
      }
    }

    $config->set('variants', $variants);
    $config->save();

    drupal_set_message($this->t('Variant successfully deleted'));

    $form_state->setRedirect('page_layout.admin_variants_edit', ['page_id' => $page_id]);
  }

}
