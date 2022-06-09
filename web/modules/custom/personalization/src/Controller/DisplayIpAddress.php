<?php

/**
 * @file
 * Contains \Drupal\personalization\DisplayIpAddress
 */

namespace Drupal\personalization\Controller;

use Drupal\personalization\PersonalizationIpServiceInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller methods for the Display Ip Address controller
 *
 * This illustrates injecting a service with the service container and calling
 * a method of that service to retrieve a value. It builds a simple page that
 * displays the value.
 */
class DisplayIpAddress extends ControllerBase {

  /**
   * Personalization Ip service.
   *
   * @var \Drupal\personalization\PersonalizationIpServiceInterface
   */
  protected $personalizationIpService;

  /**
   * {@inheritdoc}
   */
  public function __construct(PersonalizationIpServiceInterface $personalizationIpService) {
    $this->personalizationIpService = $personalizationIpService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('personalization.personalization_ip_service')
    );
  }

  /**
   * Return the current user's Ip Address
   */
  public function getIpAddress() {
    return [
      '#markup' => $this->t('Your Ip address is: @ipaddress', array('@ipaddress' => $this->personalizationIpService->getIpAddress()))
    ];
  }

}