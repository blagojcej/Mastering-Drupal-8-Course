<?php

namespace Drupal\Tests\aquifer\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;

/**
 * Tests the Aquifer manager service.
 *
 * @group aquifer
 */
class AquiferManagerServiceKernelTest extends EntityKernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'aquifer',
    'content_translation',
    'language',
    'menu_ui',
    'node',
    'options'
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setup();

    // We do need to install the config of node before aquifer or we will get
    // an error.
    $this->installConfig(['node', 'aquifer']);

    // Create an aquifer piece of content
    $node = Node::create(array(
      'title' => t('bigBlue'),
      'type' => 'aquifer',
      'language' => 'en',
      'field_aquifer_coordinates' => '47.7231° N, 86.9407° W',
      'field_aquifer_status' => 'low',
      'field_aquifer_volume' => 1000000,
    ));
    $node->save();
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

    $this->installSchema('node', ['node_access']);

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