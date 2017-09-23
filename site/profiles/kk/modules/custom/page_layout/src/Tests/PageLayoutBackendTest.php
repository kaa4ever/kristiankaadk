<?php

namespace Drupal\page_layout\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * @group page_layout
 */
class PageLayoutBackendTest extends WebTestBase {
  public static $modules = ['node', 'page_layout'];

  /**
   * Create a test for the flow.
   *
   * This test includes creating editing and deleting
   * a page with variants, conditions and blocks.
   */
  public function testBackend() {
    $admin_user = $this->drupalCreateUser(
      [
        'access administration pages',
        'administer blocks',
        'administer themes',
      ]
    );
    $this->drupalLogin($admin_user);

    // Create a new page.
    $this->drupalGet('/admin/structure/page-layout/add');
    $this->assertFieldByName('title');
    $this->assertFieldByName('path');
    $this->drupalPostForm('/admin/structure/page-layout/add', ['title' => 'New Page', 'path' => '/new-page'], t('Create page'));

    // Edit the default variant settings.
    $this->drupalGet('/admin/structure/page-layout/new_page/variants');
    $this->assertFieldByName('settings[admin_title]', 'Default');
    $this->assertFieldByName('settings[title]', 'New Page');
    $this->drupalPostForm('/admin/structure/page-layout/new_page/variants', ['settings[admin_title]' => 'Default Updated', 'settings[title]' => 'New Page Updated'], t('Save variant'));
    $this->drupalGet('/admin/structure/page-layout/new_page/variants');
    $this->assertFieldByName('settings[admin_title]', 'Default Updated');
    $this->assertFieldByName('settings[title]', 'New Page Updated');

    // Add a condition.
    $this->drupalGet('/admin/structure/page-layout/new_page/variants', ['fragment' => 'edit-conditions']);
    $this->clickLink(t('Add condition'));
    $this->drupalPostForm($this->getUrl(), ['condition' => 'request_path'], t('Add condition'));

    // Edit a condition.
    $this->drupalGet('/admin/structure/page-layout/new_page/variants', ['fragment' => 'edit-conditions']);
    $this->clickLink(t('Edit'));
    $this->drupalPostForm($this->getUrl(), ['condition' => 'request_path', 'configuration[pages]' => '/some-page'], t('Update condition'));

    // Delete the condition.
    $this->drupalGet('/admin/structure/page-layout/new_page/variants', ['fragment' => 'edit-conditions']);
    $this->clickLink(t('Remove'));
    $this->drupalPostForm($this->getUrl(), [], t('Confirm'));

    // Update the layout.
    $this->drupalGet('/admin/structure/page-layout/new_page/variants');
    $this->drupalPostAjaxForm(NULL, ['settings[layout]' => 'page_layout.two_column'], 'settings[layout]');

    // Place a block in the second region.
    $this->drupalGet('/admin/structure/page-layout/new_page/variants', ['fragment' => 'edit-content']);
    $this->clickLink(t('Place block'), 1);
    $this->clickLink(t('Place block'));

    // Remove the block.
    $this->drupalGet('/admin/structure/page-layout/new_page/variants', ['fragment' => 'edit-content']);
    $this->clickLink(t('Remove'));
    $this->drupalPostForm($this->getUrl(), [], t('Confirm'));

    // Add another variant.
    $this->drupalGet('/admin/structure/page-layout/new_page/variants/add');
    $this->assertFieldByName('admin_title');
    $this->assertFieldByName('title');
    $this->assertFieldByName('layout');
    $this->drupalPostForm('/admin/structure/page-layout/new_page/variants/add',
      [
        'admin_title' => 'New Variant',
        'title' => 'Second variant',
        'layout' => 'page_layout.one_column',
      ],
      t('Create variant')
    );

    // Delete a variant.
    $this->drupalGet('/admin/structure/page-layout/new_page/variants');
    $this->clickLink(t('Delete variant'));
    $this->drupalPostForm($this->getUrl(), [], t('Confirm'));

    // Edit page.
    $this->drupalGet('/admin/structure/page-layout');
    $this->assertRaw('<td>New Page</td>');
    $this->assertRaw('<td>new_page</td>');
    $this->assertRaw('<td>/new-page</td>');
    $this->drupalGet('/admin/structure/page-layout/new_page/edit');
    $this->assertFieldByName('title', 'New Page');
    $this->assertFieldByName('path', '/new-page');

    $this->drupalPostForm('/admin/structure/page-layout/new_page/edit', ['title' => 'New Page Updated', 'path' => '/new-page-updated'], t('Save changes'));
    $this->drupalGet('/admin/structure/page-layout');
    $this->assertRaw('<td>New Page Updated</td>');
    $this->assertRaw('<td>new_page</td>');
    $this->assertRaw('<td>/new-page-updated</td>');

    // Delete the page.
    $this->clickLink(t('Delete'));
    $this->drupalPostForm($this->getUrl(), [], t('Confirm'));
    $this->drupalGet('/admin/structure/page-layout');
    $this->assertRaw('<td colspan="4" class="empty message">No pages created yet.</td>');
  }

}
