<?php

namespace Drupal\page_layout\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * @group page_layout
 */
class PageLayoutConditionTest extends WebTestBase {
  protected $adminUser;
  public static $modules = ['node', 'page_layout', 'page_layout_test_config'];

  /**
   *
   */
  public function setUp() {
    parent::setUp();
    $this->profile = 'standard';
    $this->adminUser = $this->drupalCreateUser(
      [
        'administer languages',
        'access administration pages',
        'administer themes',
      ]
    );
  }

  /**
   *
   */
  public function testDefaultVariant() {
    // Get a specific variant defined by path for a Page.
    $this->drupalGet('/articles/news');
    $this->assertTitle('News articles | Drupal');
  }

  /**
   *
   */
  public function testRequestPathCondition() {
    // Get the default variant for a Page.
    $this->drupalGet('/articles/' . $this->randomMachineName());
    $this->assertTitle('Articles | Drupal');
  }

  /**
   *
   */
  public function testLanguageCondition() {
    $this->drupalGet('/language');
    $this->assertTitle('English | Drupal');

    // Add Danish language.
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('admin/config/regional/language/add', ['predefined_langcode' => 'da'], t('Add language'));
    $this->drupalPostForm('admin/config/regional/language', ['site_default_language' => 'da'], t('Save configuration'));
    $this->drupalLogout();

    $this->drupalGet('/language');
    $this->assertTitle('Danish | Drupal');
  }

  /**
   *
   */
  public function testCurrentThemeCondition() {
    $this->drupalGet('/theme');
    $this->assertTitle('Bartik | Drupal');

    // Set another theme as default theme.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/appearance');
    $this->clickLink(t('Install and set as default'), 2);
    $this->drupalLogout();

    $this->drupalGet('/theme');
    $this->assertNoTitle('Bartik | Drupal');
  }

  /**
   *
   */
  public function testUserRoleCondition() {
    $this->drupalGet('/user-role-test');
    $this->assertTitle('Anonymous User | Drupal');

    // Set another theme as default theme.
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('/user-role-test');
    $this->assertTitle('Authenticated User | Drupal');
  }

  /**
   *
   */
  public function testNodeTypeCondition() {
    $this->drupalCreateContentType(['type' => 'letter', 'name' => 'Letter']);
    $page = $this->drupalCreateNode(['type' => 'page']);
    $article = $this->drupalCreateNode(['type' => 'article']);
    $letter = $this->drupalCreateNode(['type' => 'letter']);

    $this->drupalGet('/node/' . $page->id());
    $this->assertTitle('Node Type Test - Page | Drupal');

    $this->drupalGet('/node/' . $article->id());
    $this->assertTitle('Node Type Test - Article | Drupal');

    $this->drupalGet('/node/' . $letter->id());
    $this->assertTrue(strpos($this->getTitle(), 'Node Type Test - ') === FALSE);
  }

  /**
   * Return the title for the current page.
   *
   * Fails if no title found.
   *
   * @return string
   */
  private function getTitle() {
    // Don't use xpath as it messes with HTML escaping.
    preg_match('@<title>(.*)</title>@', $this->getRawContent(), $matches);
    if (isset($matches[1])) {
      $actual = $matches[1];
      $actual = $this->castSafeStrings($actual);

      return $actual;
    }
    $this->fail('No title found on page');
  }

  /**
   *
   */
  public function testMixedConditions() {
    $this->drupalGet('/mixed-conditions/show');
    $this->assertTitle('Mixed Conditions | Drupal');

    // Set invalid language.
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('admin/config/regional/language/add', ['predefined_langcode' => 'da'], t('Add language'));
    $this->drupalPostForm('admin/config/regional/language', ['site_default_language' => 'da'], t('Save configuration'));
    $this->drupalLogout();
    $this->drupalGet('/mixed-conditions/show');
    $this->assertNoTitle('Mixed Conditions | Drupal');

    // Set invalid path.
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('admin/config/regional/language', ['site_default_language' => 'en'], t('Save configuration'));
    $this->drupalLogout();
    $this->drupalGet('/mixed-conditions/nonshow');
    $this->assertNoTitle('Mixed Conditions | Drupal');

    // Set invalid user.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/mixed-conditions/show');
    $this->assertNoTitle('Mixed Conditions | Drupal');

    // Set invalid theme.
    $this->drupalGet('admin/appearance');
    $this->clickLink(t('Install and set as default'), 2);
    $this->drupalLogout();
    $this->drupalGet('/mixed-conditions/show');
    $this->assertNoTitle('Mixed Conditions | Drupal');
  }

}
