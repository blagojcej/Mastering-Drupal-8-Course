<?php

namespace Drupal\Tests\iai_pig\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Tests the image gallery block.
 *
 * @group iai_pig
 */
class ImageGalleryBlockFunctionalJavascriptTest extends JavascriptTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('iai_pig', 'iai_pig_product', 'block');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create and log in an administrative user.
    $adminUser = $this->drupalCreateUser(array(
      'administer blocks',
      'access administration pages',
    ));
    $this->drupalLogin($adminUser);

    // Place the block in the content area
    $block_url = 'admin/structure/block/add/iai_product_image_gallery/classy';
    $edit = [
      'region' => 'content',
    ];
    $this->drupalPostForm($block_url, $edit, 'Save block');
    $this->drupalLogout();
  }

  /**
   * Tests a product that does not have an image attached.
   */
  public function testProductWithoutImage() {

    // Create a product piece of content
    $node = $this->drupalCreateNode(array(
      'title' => t('Product without an image'),
      'type' => 'product',
    ));
    $node->save();

    $message = 'There were no product images to display.';
    $this->drupalGet('node/' . $node->id());
    $this->assertContains($message, $this->getTextContent());
  }

  /**
   * Tests a product that has an image attached.
   */
  public function testProductWithImage() {

    // Create a product piece of content
    $node = $this->drupalCreateNode(array(
      'title' => t('Product with an image'),
      'type' => 'product',
    ));
    $node->field_image->generateSampleItems();
    $node->save();

    $imageData = $node->field_image[0]->getValue();
    $altText = $imageData['alt'];
    $this->drupalGet('node/' . $node->id());
    $page = $this->getSession()->getPage();
    $targetLink = $page->findLink($altText);
    $this->assertSession()->responseContains('data-dialog-type="modal"');
    $this->assertSession()->LinkByHrefExists('/iai_pig/display_product_image/' . $node->id() . '/0');
    $this->assertNotEmpty($targetLink);
    $targetLink->click();
    $condition = "(jQuery('#drupal-modal').length > 0)";
    $this->assertJsCondition($condition);
    $this->assertSession()->assertWaitOnAjaxRequest();
    $condition = "(jQuery('#drupal-modal div img').length > 0)";
    $this->assertJsCondition($condition);
  }

}