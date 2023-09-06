<?php

namespace Drupal\issue_tracking_system\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block to display the last 3 issues assigned to the current user.
 *
 * @Block(
 *   id = "latest_issues",
 *   admin_label = @Translation("Latest 3 Assigned Issues"),
 *   category = @Translation("Custom Blocks"),
 * )
 */
class LatestIssuesBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The node storage service.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new CustomIssueBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\node\NodeStorageInterface $node_storage
   *   The node storage service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, NodeStorageInterface $node_storage, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->nodeStorage = $node_storage;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('node'),
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $current_user_id = $this->currentUser->id();

    $query = $this->nodeStorage->getQuery()
      ->condition('status', 1)
      ->condition('type', 'issue')
      ->condition('field_assignee.target_id', $current_user_id)
      ->sort('created', 'DESC')
      ->range(0, 3);

    $nids = $query->execute();

    $issues = $this->nodeStorage->loadMultiple($nids);
    $build = [];
    foreach ($issues as $issue) {
      $build = [
        $this->entityTypeManager->getViewBuilder('node')->view($issue, 'teaser'),
      ];
    }
    return $build;

  }

}
