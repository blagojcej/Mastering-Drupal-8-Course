<?php

/**
 * @file
 *
 * Contains \Drupal\Tests\personalization\Unit\PersonalizationIpServiceTest
 */

namespace Drupal\Tests\personalization\Unit;

use Drupal\personalization\PersonalizationIpService;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests all the methods in the Personalization Ip Service
 *
 * @coversDefaultClass \Drupal\personalization\PersonalizationIpService
 * @group personalization
 */
class PersonalizationIpServiceTest extends UnitTestCase {

  /**
   * The mocked request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $requestStack;

  /**
   * The tested Personalization Ip Service
   *
   * @var \Drupal\personalization\PersonalizationIpService
   */
  protected $personalizationIpService;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->requestStack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')->getMock();
    $request = Request::create('/test-path');
    $request->server->set('REMOTE_ADDR', '127.0.0.1');
    $this->requestStack->expects($this->any())
      ->method('getCurrentRequest')
      ->willReturn($request);
    $this->personalizationIpService = new PersonalizationIpService($this->requestStack);

  }

  /**
   * Test returning the Ip Address
   *
   * @covers ::getIpAddress
   */
  public function testGetIpAddress() {
    $this->assertEquals('127.0.0.1', $this->personalizationIpService->getIpAddress());
  }

  /**
   * Test mapping the Ip Address
   *
   * @covers ::mapIpAddress
   * @dataProvider provideIpAddresses
   */
  public function testMapIpAddress($mappedResult, $ipAddress) {
    $this->assertEquals($mappedResult, $this->personalizationIpService->mapIpAddress($ipAddress));
  }

  /**
   * Provides Ip Addresses to test
   *
   * @return array
   *   The data to test for Ip Addresses
   */
  public function provideIpAddresses () {
    return [
      ['39.7392° N, 104.9903° W', ''],
      ['39.7392° N, 104.9903° W', '127.0.0.1'],
      ['39.7392° N, 104.9903° W', '151.101.113.175']
    ];
  }

}