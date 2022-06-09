<?php

namespace Drupal\Tests\aquifer\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Aquifer manager service.
 *
 * @group aquifer
 */
class AquiferManagerServiceFunctionalTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('aquifer');

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setup();

    // Create some aquifer pieces of content
    $this->drupalCreateNode(array(
      'title' => t('bigBlue'),
      'type' => 'aquifer',
      'field_aquifer_coordinates' => '47.7231° N, 86.9407° W',
      'field_aquifer_status' => 'low',
      'field_aquifer_volume' => 1000000,
    ));
  }

  /**
   * Tests that the service reads an aquifer.
   */
  public function testReadAquifer() {

    $aquiferManagerService = \Drupal::service('aquifer.aquifer_manager_service');
    $aquiferData = $aquiferManagerService->readAquifer('bigBlue');

    $this->assertEquals('47.7231° N, 86.9407° W', $aquiferData['coordinates']);
    $this->assertEquals('low', $aquiferData['status']);
    $this->assertEquals(1000000, $aquiferData['volume']);
  }

  /**
   * Tests that the service updates an existing aquifer.
   */
  public function testUpdateAquifer() {

    $aquiferData = array(
      'name' => 'bigBlue',
      'status' => 'adequate',
      'volume' => 123456789,
    );

    $aquiferManagerService = \Drupal::service('aquifer.aquifer_manager_service');
    $response = $aquiferManagerService->updateAquifer($aquiferData);
    $node = $response->object;

    $this->assertEquals('updated', $response->status);
    $this->assertEquals($aquiferData['status'], $node->field_aquifer_status->value);
    $this->assertEquals($aquiferData['volume'], $node->field_aquifer_volume->value);
  }

  /**
   * Tests that the service creates a new aquifer.
   */
  public function testCreateAquifer() {

    $aquiferData = array(
      'name' => 'vastSea',
      'coordinates' => '34.5531° N, 18.0480° E',
      'status' => 'aqequate',
      'volume' => 1000000000,
    );

    $aquiferManagerService = \Drupal::service('aquifer.aquifer_manager_service');
    $response = $aquiferManagerService->updateAquifer($aquiferData);
    $node = $response->object;

    $this->assertEquals('created', $response->status);
    $this->assertEquals($aquiferData['coordinates'], $node->field_aquifer_coordinates->value);
    $this->assertEquals($aquiferData['status'], $node->field_aquifer_status->value);
    $this->assertEquals($aquiferData['volume'], $node->field_aquifer_volume->value);
  }

}