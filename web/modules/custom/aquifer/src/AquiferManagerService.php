<?php

namespace Drupal\aquifer;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * An implementation of AquiferManagerServiceInterface.
 */
class AquiferManagerService extends AbstractAquiferManagerService implements AquiferManagerServiceInterface
{

    /**
     * Entity storage for node entities.
     *
     * @var \Drupal\Core\Entity\EntityStorageInterface
     */
    protected $nodeStorage;

    /**
     * Constructs the Aquifer Manager Service object.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
     *   Entity storage for node entities.
     */
    public function __construct(EntityTypeManagerInterface $entity_manager)
    {

        /******************************************************************************
         **                                                                          **
         ** Symfony knows to pass the $entity_manager to our constructor because we  **
         ** specified it as an argument in our iai_aquifer.services.yml.             **
         **                                                                          **
         ******************************************************************************/
        $this->nodeStorage = $entity_manager->getStorage('node');
    }

    /**
     * {@inheritdoc}
     */
    protected function createAquifer(array $aquifer_data)
    {
        $expected_fields = array('name', 'coordinates', 'status', 'volume');
        $operation = 'creating an aquifer';
        $this->validateExpectedFields($expected_fields, $operation, $aquifer_data);
        $this->validatePassedFields($expected_fields, $operation, $aquifer_data);
        $values = array(
            'title' => $aquifer_data['name'],
            'type' => 'aquifer',
            'uid' => 1,
        );
        unset($aquifer_data['name']);
        foreach ($aquifer_data as $property => $value) {
            $values['field_aquifer_' . $property] = $value;
        }
        $node = $this->nodeStorage->create($values);
        $this->nodeStorage->save($node);
        return (object) array('status' => 'created', 'object' => $node);
    }

    /**
     * {@inheritdoc}
     */
    public function readAquifer($aquifer_name)
    {
        $node = $this->getAquifer($aquifer_name);
        $aquifer_data = array(
            'name' => $node->title->value,
            'coordinates' => $node->field_aquifer_coordinates->value,
            'status' => $node->field_aquifer_status->value,
            'volume' => $node->field_aquifer_volume->value
        );
        return $aquifer_data;
    }

    /**
     * {@inheritdoc}
     */
    public function updateAquifer($aquifer_data)
    {
        // See if the aquifer already exists.
        $query = $this->nodeStorage->getQuery()
            ->condition('type', 'aquifer')
            ->condition('title', $aquifer_data['name'])
            ->count();

        $count_nodes = $query->execute();

        if ($count_nodes == 0) {
            $response = $this->createAquifer($aquifer_data);
            return $response;
        } elseif ($count_nodes == 1) {
            // Retrieve the aquifer
            $node = $this->getAquifer($aquifer_data['name']);

            unset($aquifer_data['name']); // remove the name attribute
            $expected_fields = array('coordinates', 'status', 'volume');
            $this->validatePassedFields($expected_fields, 'updating an aquifer', $aquifer_data);
            foreach ($aquifer_data as $property => $value) {
                $node->set("field_aquifer_{$property}", $value);
            }
            $this->nodeStorage->save($node);
            return (object) array('status' => 'updated', 'object' => $node);
        } else {
            // Do something about the fact that there is more than one aquifer with
            // this name.
        }
    }

    /**
     * Retrieve the specific node
     *
     * @param string $aquifer_name
     *   The name of the aquifer to retrieve
     *
     * @return NodeInterface $node
     *   The fully loaded node entity
     */
    private function getAquifer($aquifer_name)
    {
        $result = $this->nodeStorage->getQuery()
            ->condition('type', 'aquifer')
            ->condition('title', $aquifer_name)
            ->range(0, 1)
            ->execute();

        return $this->nodeStorage->load(reset($result));
    }

    /**
     * Validate the field data
     *
     * @param array $expected_fields
     *   The fields that are expected to be passed for the operation being
     *   performed
     * @param string $operation
     *   The operation being performed
     * @param array $aquifer_data
     *   The data being passed in.
     *
     * @throws \InvalidArgumentException
     */
    private function validateExpectedFields($expected_fields, $operation, $aquifer_data)
    {
        foreach ($expected_fields as $field_name) {
            if (!isset($aquifer_data[$field_name])) {
                throw new \InvalidArgumentException('Missing expected field: ' . $field_name . ' when ' . $operation . '.');
            }
        }
    }

    /**
     * Validate the field data
     *
     * @param array $expected_fields
     *   The fields that are expected to be passed for the operation being
     *   performed
     * @param string $operation
     *   The operation being performed
     * @param array $aquifer_data
     *   The data being passed in.
     *
     * @throws \UnexpectedValueException
     */
    private function validatePassedFields($expected_fields, $operation, $aquifer_data)
    {
        foreach ($aquifer_data as $field_name => $value) {
            if (!in_array($field_name, $expected_fields)) {
                throw new \UnexpectedValueException('Unexpected field: ' . $field_name . ' when ' . $operation . '.');
            }
        }
    }
}
