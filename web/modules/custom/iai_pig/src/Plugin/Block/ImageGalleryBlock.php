<?php

namespace Drupal\iai_pig\Plugin\Block;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\iai_product\ProductManagerServiceInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/******************************************************************************
 **                                                                          **
 ** The context ensures that the block is present only on node pages.        **
 **                                                                          **
 ** Whenever a node is saved it invalidates its cache context and the block  **
 ** will be rebuilt. We make use of this context in our build method with the**
 **   <code>$node = $this->getContextValue('node');</code>                   **
 **                                                                          **
 ** This block is not intended to be an "all powerful" block to be reused    **
 ** elsewhere. We are making certain assumptions to keep the example         **
 ** relatively simple.                                                       **
 **                                                                          **
 ******************************************************************************/
/**
 * Provides an image gallery block
 * 
 * @Block(
 *      id = "iai_product_image_gallery",
 *      admin_label = @Translation("Product Image Gallery"),
 *      category = @Translation("Image Display"),
 *      context_definitions = {
 *          "node" = @ContextDefinition(
 *              "entity:node",
 *              label = @Translation("Node"),
 *              required = FALSE
 *          )
 *      }
 * )
 */
class ImageGalleryBlock extends BlockBase implements ContainerFactoryPluginInterface, ContextAwarePluginInterface
{
    /**
     * The Product Manager Service.
     *
     * @var \Drupal\iai_product\ProductManagerServiceInterface
     */
    protected $productManagerService;

    /**
     * The entity repository.
     *
     * @var \Drupal\Core\Entity\EntityRepositoryInterface
     */
    protected $entityRepository;

    protected $contextRepository;

    /******************************************************************************
     **                                                                          **
     ** This is an example of Dependency Injection. The necessary objects are    **
     ** being injected through the class's constructor.                          **
     **                                                                          **
     ******************************************************************************/
    /**
     * Constructs Product Image Gallery block object.
     *
     * @param array $configuration
     *   A configuration array containing information about the plugin instance.
     * @param string $plugin_id
     *   The plugin_id for the plugin instance.
     * @param mixed $plugin_definition
     *   The plugin implementation definition.
     * @param \Drupal\iai_product\ProductManagerServiceInterface $product_manager_service
     *   The Product Manager Service.
     * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
     *   The entity repository.
     */
    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        ProductManagerServiceInterface $product_manager_service,
        EntityRepositoryInterface $entity_repository,
        ContextRepositoryInterface $contextRepository
    ) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
        $this->productManagerService = $product_manager_service;
        $this->entityRepository = $entity_repository;
        $this->contextRepository = $contextRepository;

        // set the defined contexts' values
        $this->setDefinedContextValues();
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
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {

        /******************************************************************************
         **                                                                          **
         ** The ContainerFactoryPluginInterface is what gave us access to Symfony's  **
         ** service container. Plugins don't get access to the service container if  **
         ** they don't implement the ContainerFactoryPluginInterface.                **
         **                                                                          **
         ** If we plan to do anything in our constructor we need to call the parent  **
         ** constructor explicitly. Therefore, we need to ensure we've got all the   **
         ** necessary objects to pass to our parent.                                 **
         **                                                                          **
         ******************************************************************************/
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('iai_product.product_manager_service'),
            $container->get('entity.repository'),
            $container->get('context.repository'),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function defaultConfiguration()
    {
        // By default, the block will display 5 thumbnails
        return array(
            'block_count' => 5,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function blockForm($form, FormStateInterface $form_state)
    {
        $range = range(2, 20);
        $form['block_count'] = array(
            '#type' => 'select',
            '#title' => $this->t('Number of product images in block'),
            '#default_value' => $this->configuration['block_count'],
            '#options' => array_combine($range, $range),
        );
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function blockSubmit($form, FormStateInterface $form_state)
    {
        $this->configuration['block_count'] = $form_state->getValue('block_count');
    }

    /**
     * {@inheritdoc}
     */
    public function label()
    {
        // Cast the label to a string since it is a TranslatableMarkup object.
        return (string) $this->pluginDefinition['label'];
    }

    /**
     * Set values for the defined contexts of this plugin
     * 
     */
    private function setDefinedContextValues()
    {
        // fetch the available contexts  
        $available_contexts = $this->contextRepository->getAvailableContexts();

        // ensure that the contexts have data by getting corresponding runtime contexts
        $available_runtime_contexts = $this->contextRepository->getRuntimeContexts(array_keys($available_contexts));
        $plugin_context_definitions = $this->getContextDefinitions();
        foreach ($plugin_context_definitions as $name => $plugin_context_definition) {

            // identify and fetch the matching runtime context, with the plugin's context definition
            $matches = $this->contextHandler()
                ->getMatchingContexts($available_runtime_contexts, $plugin_context_definition);
            $matching_context = reset($matches);

            // set the value to the plugin's context, from runtime context value
            $this->setContextValue($name, $matching_context->getContextValue());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function build()
    {
        $build = array();
        $product = null;

        /******************************************************************************
         **                                                                          **
         ** @see:                                                                    **
         ** https://api.drupal.org/api/drupal/core!lib!Drupal!Component!Plugin!ContextAwarePluginBase.php/function/ContextAwarePluginBase%3A%3AgetContextValue/8.2.x
         **                                                                          **
         ******************************************************************************/
        $node = $this->getContextValue('node');

        if ($node) {
            // Determine if we are on a page that points to a product.
            $product = $this->getProduct($node);
        }

        if ($product) {

            // Retrieve the product images
            $image_data = $this->productManagerService->retrieveProductImages($product);
            $block_count = $this->configuration['block_count'];
            $item_count = 0;
            $build['list'] = [
                '#theme' => 'item_list',
                '#items' => [],
            ];

            /******************************************************************************
             **                                                                          **
             ** This logic is just to give some positive feedback that the block is being**
             ** rendered. In reality, we'd likely just not have the block render anything**
             ** in this situation.                                                       **
             **                                                                          **
             ******************************************************************************/
            $build['list']['#items'][0] = [
                '#type' => 'markup',
                '#markup' => $this->t('There were no product images to display.')
            ];

            while ($item_count < $block_count && isset($image_data[$item_count])) {
                $file = File::load($image_data[$item_count]['target_id']);
                $link_text = [
                    '#theme' => 'image_style',
                    '#uri' => $file->getFileUri(),
                    '#style_name' => 'product_thumbnail',
                    '#alt' => $image_data[$item_count]['alt'],
                ];

                /******************************************************************************
                 **                                                                          **
                 ** This is the Modal API.                                                   **
                 ** @see: https://www.drupal.org/node/2488192 for more information.          **
                 **                                                                          **
                 ******************************************************************************/
                $options = array(
                    'attributes' => array(
                        'class' => array(
                            'use-ajax',
                        ),
                        'data-dialog-type' => 'modal',
                        'data-dialog-options' => Json::encode([
                            'width' => 700,
                        ]),
                    ),
                );
                $renderer = \Drupal::service('renderer');
                $url = Url::fromRoute('iai_pig.display_product_image', array('node' => $product->nid->value, 'delta' => $item_count));
                $url->setOptions($options);
                $build['list']['#items'][$item_count] = [
                    '#type' => 'markup',
                    '#markup' => Link::fromTextAndUrl($renderer->render($link_text), $url)
                        ->toString(),
                ];
                $item_count++;
            }
            $build['#attached']['library'][] = 'core/drupal.dialog.ajax';
        } else {

            /******************************************************************************
             **                                                                          **
             ** This logic is just to give some positive feedback that the block is being**
             ** rendered. In reality, we'd likely just not have the block render anything**
             ** in this situation.                                                       **
             **                                                                          **
             ******************************************************************************/
            $build['no_data'] = [
                '#type' => 'markup',
                '#markup' => $this->t('This page does not reference a product.'),
            ];
        }

        return $build;
    }

    /**
     * Get a product
     * 
     * @param \Drupal\node\NodeInterface $node
     *  The fully loaded node object
     * @return \Drupal\node\NodeInterface $product
     *  The fully loaded product
     */
    private function getProduct(NodeInterface $node)
    {
        /******************************************************************************
         **                                                                          **
         ** For this example block we are concerned only with nodes. Specifically, we**
         ** are operating under the assumption that this block should render only    **
         ** when it's being viewed on a Book page that references a Product or when  **
         ** it's being viewed directly on a Product page.                            **
         **                                                                          **
         ******************************************************************************/
        if ($node) {
            // Check if this is a Product node already
            if ($node->getType() == 'product') {
                return $node;
            }

            // Check if this node references a Product
            $product = $this->getReferencedProduct($node);

            if ($product) {
                return $this->entityRepository->getTranslationFromContext($product);
            }

            return NULL;
        } else {
            return NULL;
        }
    }

    /**
     * Get a referenced product
     *
     * @param \Drupal\node\Entity\Node $node
     *   The fully loaded node object.
     * @return \Drupal\node\Entity\Node $product
     *   The fully loaded referenced product
     */
    private function getReferencedProduct(Node $node)
    {

        /******************************************************************************
         **                                                                          **
         ** We are making an assumption about a particular field name that Book pages**
         ** use for the entity reference to products. We added this field to the book**
         **  pages ourselves.                                                        **
         **                                                                          **
         ******************************************************************************/
        if (isset($node->field_product)) {
            $referenced_entities = $node->field_product->referencedEntities();
            if (count($referenced_entities) > 0) {
                $product = $referenced_entities[0];
                return $product;
            }

            return NULL;
        } else {
            return NULL;
        }
    }
}
