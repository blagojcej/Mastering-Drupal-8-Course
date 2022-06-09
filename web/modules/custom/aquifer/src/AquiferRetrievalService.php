<?php

namespace Drupal\aquifer;

/**
 * An implementation of AquiferRetrievalServiceInterface.
 */

/******************************************************************************
 **                                                                          **
 ** This is just a mocked up service. It doesn't actually use any external   **
 ** service.                                                                 **
 **                                                                          **
 ******************************************************************************/
class AquiferRetrievalService implements AquiferRetrievalServiceInterface {

  /**
   * The base REST endpoint
   */
  protected $restEndpoint;

  /**
   * Constructs the Aquifer Retrieval Service object.
   *
   * @param string $rest_endpoint
   *   The base REST endpoint to call
   */
  public function __construct(string $rest_endpoint) {

/******************************************************************************
 **                                                                          **
 ** Symfony knows to pass the $rest_endpoint to our constructor because we   **
 ** specified it as an argument in our iai_aquifer.services.yml.             **
 **                                                                          **
 ******************************************************************************/
    $this->restEndpoint = $rest_endpoint;
  }

  /**
   * {@inheritdoc}
   */
  public function getRESTEndpoint() {
    return $this->restEndpoint;
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalAquifers($region = 'ALL') {

/******************************************************************************
 **                                                                          **
 ** We aren't really going to pay attention to the arguments. If we were     **
 ** creating an actual service, then we'd query some endpoint and use the    **
 ** arguments to help refine our query. For the purpose of this example, we  **
 ** are just going to return the same hard-coded value every time.           **
 **                                                                          **
 ******************************************************************************/
    return 100;
  }

  /**
   * {@inheritdoc}
   */
  public function getAquiferNames($region = 'ALL', $limit = -1, $offset = 0) {
    $aquifer_names = array();

/******************************************************************************
 **                                                                          **
 ** We aren't really going to pay attention to the arguments. If we were     **
 ** creating an actual service, then we'd query some endpoint and use the    **
 ** arguments to help refine our query. For the purpose of this example, we  **
 ** are just going to return the same hard-coded value every time.           **
 **                                                                          **
 ******************************************************************************/
    $aquifer_names = array(
      'bigBlue',
      'deepOcean',
      'vastSea',
    );

    return $aquifer_names;
  }

  /**
   * {@inheritdoc}
   */
  public function getAquiferData($name = NULL) {

/******************************************************************************
 **                                                                          **
 ** If we were creating an actual service, then we'd query an endpoint to    **
 ** get the data. For the purpose of this example, we are expecting only     **
 ** three possible aquifers.                                                 **
 **                                                                          **
 ******************************************************************************/
    $aquifer_data = array();
    switch ($name) {
      case 'bigBlue':
        $aquifer_data['coordinates'] = '47.7231° N, 86.9407° W';
        $aquifer_data['status'] = 'low';
        $aquifer_data['volume'] = 1000000;
        break;
      case 'deepOcean':
        $aquifer_data['coordinates'] = '14.5994° S, 28.6731° W';
        $aquifer_data['status'] = 'full';
        $aquifer_data['volume'] = 1000000000000;
        break;
      case 'vastSea':
        $aquifer_data['coordinates'] = '34.5531° N, 18.0480° E';
        $aquifer_data['status'] = 'adequate';
        $aquifer_data['volume'] = 1000000000;
        break;
      default:
        $aquifer_data['coordinates'] = 'unknown';
        $aquifer_data['status'] = 'N/A';
        $aquifer_data['volume'] = 'unknown';
    }
    return $aquifer_data;
  }
}