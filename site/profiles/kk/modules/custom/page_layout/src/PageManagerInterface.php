<?php

namespace Drupal\page_layout;

/**
 *
 */
interface PageManagerInterface {

  /**
   * Get all pages definitions.
   *
   * The page definitions is saved with configuration name
   * prefix as <module>.page.<id>,e.g. page_layout.page.node.
   *
   * @return array
   *   An array of page definitions from the config manager.
   */
  public function getPages();

  /**
   * Get a page with a specific ID.
   *
   * @param string $id
   *
   * @return bool|array
   *   Either the page definition array, or FALSE if none found.
   */
  public function getPage($id);

  /**
   * Load a page by a path.
   *
   * @param string $path
   *
   * @return bool|array
   *   Either the page definition array, or FALSE if none found.
   */
  public function getPageByPath($path);

  /**
   * Create a new page and save it in the configuration.
   *
   * @param string $title
   * @param string $path
   *
   * @return string
   *   ID of the new page.
   */
  public function createPage($title, $path);

  /**
   * Updates a page.
   *
   * @param array $page
   */
  public function updatePage(array $page);

  /**
   * Creates a new variant of a page.
   *
   * @param string $page_id
   * @param string $admin_title
   *   The title of the variant is only used in the administration.
   * @param string $title
   *   The title used on the page.
   * @param string $layout
   *
   * @return string
   *   The ID of the new variant.
   */
  public function createVariant($page_id, $admin_title, $title, $layout = 'page_layout.one_column');

  /**
   * Save and update a variant on a page.
   *
   * @param string $page_id
   * @param array $updated_variant
   */
  public function saveVariant($page_id, array $updated_variant);

  /**
   * Get a variant of a page.
   *
   * @param string $page_id
   * @param string|null $variant_id
   *   If no ID is specified,
   *   the first available variant of the page is returned.
   *
   * @return array|bool
   *   Either the variant definition, or FALSE.
   */
  public function getVariant($page_id, $variant_id = NULL);

  /**
   * Get a condition for a variant on a page.
   *
   * @param string $page_id
   * @param string $variant_id
   * @param string $condition_id
   *
   * @return array|bool
   *   Either the condition found, or FALSE.
   */
  public function getCondition($page_id, $variant_id, $condition_id);

  /**
   * Add a condition to a variant.
   *
   * @param string $page_id
   * @param string $variant_id
   * @param string $plugin_id
   *   The ID of the plugin selected.
   * @param array $configuration
   *   A configuration array to use when building the plugin.
   *   This configuration must include the context keys.
   * @param string|bool $condition_id
   *   Either an ID of a condition to update an existing condition,
   *   or FALSE to create a new condition.
   */
  public function saveCondition($page_id, $variant_id, $plugin_id, array $configuration = [], $condition_id = FALSE);

  /**
   * Remove a condition from a variant.
   *
   * @param string $page_id
   * @param string $variant_id
   * @param string $condition_id
   */
  public function removeCondition($page_id, $variant_id, $condition_id);

  /**
   * Iterate all variants for a page to find one that matches the request.
   *
   * This function depends on all context being set.
   *
   * @param string $page_id
   * @param bool $check_conditions
   *
   * @return bool|array
   *   Either FALSE if no variants matched the current request,
   *   or the variant definition.
   */
  public function getMatchingVariant($page_id, $check_conditions = TRUE);

}
