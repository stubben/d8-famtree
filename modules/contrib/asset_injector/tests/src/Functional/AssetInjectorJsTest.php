<?php

namespace Drupal\Tests\asset_injector\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Class AssetInjectorJsTest.
 *
 * @package Drupal\Tests\asset_injector\Functional
 */
class AssetInjectorJsTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['asset_injector', 'toolbar', 'block'];

  /**
   * The account to be used to test access to both workflows.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $administrator;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests a user without permissions gets access denied.
   */
  public function testJsPermissionDenied() {
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);
    $this->drupalGet('admin/config/development/asset-injector/js');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests a user WITH permission has access.
   */
  public function testJsPermissionGranted() {
    $account = $this->drupalCreateUser(['administer js assets injector']);
    $this->drupalLogin($account);
    $this->drupalGet('admin/config/development/asset-injector/js');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test a created css injector is added to the page and the css file exists.
   *
   * @throws \Exception
   */
  public function testJsInjector() {
    $this->testJsPermissionGranted();
    $this->drupalGet('admin/config/development/asset-injector/js/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains(t('Code'));
    $this->submitForm([
      'label' => t('Blocks'),
      'id' => t('blocks'),
      'code' => '.block {border:1px solid black;}',
    ], t('Save'));

    $html = $this->getSession()->getPage()->getHtml();
    if (strpos($html, 'asset_injector/js/blocks') === FALSE) {
      throw new \Exception(t('Js not applied to page.'));
    }

    /** @var \Drupal\asset_injector\Entity\AssetInjectorJs $asset */
    foreach (asset_injector_get_assets(NULL, ['asset_injector_js']) as $asset) {
      $path = parse_url(file_create_url($asset->internalFileUri()), PHP_URL_PATH);
      $path = str_replace(base_path(), '/', $path);

      $this->drupalGet($path);
      $this->assertSession()->statusCodeEquals(200);
    }
  }

}
