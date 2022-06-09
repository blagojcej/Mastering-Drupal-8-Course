<?php

namespace Drupal\iai_product_guide\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Plugin\ContextAwarePluginTrait;
use Drupal\Core\Plugin\ContextAwarePluginAssignmentTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;

/******************************************************************************
 **                                                                          **
 ** The context ensures that the block is present only on node pages.        **
 **                                                                          **
 ** Whenever a node is saved it invalidates its cache context and the block  **
 ** will be rebuilt. We make use of this context in our build method with the**
 **   <code>$node = $this->getContextValue('node');</code>                   **
 **                                                                          **
 ******************************************************************************/
/**
 * Provides a block with a link to start the tour.
 * 
 * @Block(
 *      id = "iai_tour_link",
 *      admin_label = @Translation("Link for the IAI Product Guide tour"),
 *      category = @Translation("Links"),
 *      context_definitions = {
 *          "node" = @ContextDefinition(
 *              "entity:node",
 *              label = @Translation("Node"),
 *              required = FALSE
 *          )
 *      }
 * )
 */
class TourLinkBlock extends BlockBase implements ContainerFactoryPluginInterface, ContextAwarePluginInterface
{

    use ContextAwarePluginTrait;
    use ContextAwarePluginAssignmentTrait;

    /**
     * The current user.
     * 
     * @var \Drupal\Core\Session\AccountInterface
     */
    protected $currentUser;
    protected $contextRepository;

    /******************************************************************************
     **                                                                          **
     ** This is an example of Dependency Injection. The necessary objects are    **
     ** being injected through the class's constructor.                          **
     **                                                                          **
     ******************************************************************************/
    /**
     * Construct an TourLinkBlock object.
     * 
     * @param array $confguration
     *  A configuration array containing information about the plugin instance.
     * @param string $plugin_id
     *  The plugin_id for the plugin instance.
     * @param mixed $plugin_definition
     *  The plugin implementation definition.
     */
    public function __construct(array $confguration, $plugin_id, $plugin_definition, AccountInterface $current_user, ContextRepositoryInterface $contextRepository)
    {
        parent::__construct($confguration, $plugin_id, $plugin_definition);
        $this->currentUser = $current_user;
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
    public static function create(ContainerInterface $container, array $confguration, $plugin_id, $plugin_definition)
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
            $confguration,
            $plugin_id,
            $plugin_definition,
            $container->get('current_user'),
            $container->get('context.repository')
        );
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
        /******************************************************************************
         **                                                                          **
         ** @see:                                                                    **
         ** https://api.drupal.org/api/drupal/core!lib!Drupal!Component!Plugin!ContextAwarePluginBase.php/function/ContextAwarePluginBase%3A%3AgetContextValue/8.2.x
         **                                                                          **
         ******************************************************************************/
        $node = $this->getContextValue('node');
        if ($this->currentUser->hasPermission('access tour') && $node->getType() == 'book') {
            $url = Url::fromRoute('<current>', array(), array('query' => array('tour' => 1)));
            $build['tour_link'] = [
                '#type' => 'markup',
                '#markup' => Link::fromTextAndUrl($this->t('Take the tour!'), $url)->toString(),
            ];
            $build['#attached']['library'][] = 'tour/tour';
            return $build;
        }
    }
}
