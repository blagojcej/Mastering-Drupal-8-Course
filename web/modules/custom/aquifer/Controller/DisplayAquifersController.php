<?php

/**
 * @file
 * Contains \Drupal\aquifer\DisplayAquifers
 */

namespace Drupal\aquifer\Controller;

use Drupal\aquifer\AquiferRetrievalServiceInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller methods for the DisplayAquifers controller
 *
 * This illustrates injecting a service with the service container and calling
 * a method of that service to retrieve a value. The various methods in this
 * controller build a render array to be displayed in a page.
 */
class DisplayAquifers extends ControllerBase {

  /**
   * Aquifer retrieval service.
   *
   * @var \Drupal\aquifer\AquiferRetrievalServiceInterface
   */
  protected $aquiferRetrievalService;

/******************************************************************************
 **                                                                          **
 ** This is an example of Dependency Injection. The necessary objects are    **
 ** being injected through the class's constructor.                          **
 **                                                                          **
 ******************************************************************************/
  /**
   * {@inheritdoc}
   */
  public function __construct(AquiferRetrievalServiceInterface $aquiferRetrievalService) {
    $this->aquiferRetrievalService = $aquiferRetrievalService;
  }

/******************************************************************************
 **                                                                          **
 ** To learn more about Symfony's service container visit:                   **
 **   http://symfony.com/doc/current/service_container.html                  **
 **                                                                          **
 ******************************************************************************/
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('aquifer.aquifer_retrieval_service')
    );
  }

  /**
   * Return the total number of aquifers in a region
   *
   * @param string $region
   *   The region for which the number of aquifers should be displayed
   *
   * @return array
   *   The render array
   */
  public function getTotalAquifers($region = 'ALL') {
    if ($region == 'ALL') {
      return [
        '#markup' => $this->t('The number of all aquifers tracked world wide is: @number', array('@number' => $this->aquiferRetrievalService->getTotalAquifers($region)))
      ];
    }
    else {
      return [
        '#markup' => $this->t('The number of aquifers in region @region is: @number', array('@region' => $region, '@number' => $this->aquiferRetrievalService->getTotalAquifers($region)))
      ];
    }
  }

  /**
   * Page title callback for aquifer count
   */
  public function totalAquifersTitleCallback($region = 'ALL') {
    return [
      '#markup' => $this->t('The number of aquifers in region @region', array('@region' => $region))
    ];
  }

  /**
   * Display the aquifer names
   *
   * In a real implementation we would probably do some type of paged output.
   * Since we have only three of them, we can just display them as an unordered
   * list.
   *
   * @return array
   *   The render array
   */
  public function getAquiferNames() {
    $aquifer_names = $this->aquiferRetrievalService->getAquiferNames();
    foreach ($aquifer_names as $aquifer) {
      $list[] = $aquifer;
    }

    $render_array['aquifer_names'] = array(
      '#theme' => 'item_list',
      '#items' => $list,
    );
    return $render_array;
  }

  /**
   * Display the data for an aquifer
   *
   * @param string $aquifer_name
   *   The name of the aquire for which to display the data
   *
   * @return array
   *   The render array
   */
  public function getAquiferData($aquifer_name) {
    $render_array = array();
    $aquifer_data = $this->aquiferRetrievalService->getAquiferData($aquifer_name);
    foreach ($aquifer_data as $property => $value) {
      $list[] = $this->t('@property = @value', array('@property' => $property, '@value' => $value));
    }

    $render_array['aquifer_data'] = array(
      '#theme' => 'item_list',
      '#items' => $list,
    );
    return $render_array;
  }

  /**
   * Page title callback for aquifer data
   */
  public function aquiferDataTitleCallback($aquifer_name) {
    return [
      '#markup' => $this->t('The data for aquifer @aquifer', array('@aquifer' => $aquifer_name))
    ];
  }

}