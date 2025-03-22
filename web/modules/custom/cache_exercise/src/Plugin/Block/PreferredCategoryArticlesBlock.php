<?php

namespace Drupal\cache_exercise\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Cache\Cache;

/**
 * Provides a block that displays articles from the user's preferred category.
 *
 * @Block(
 *   id = "preferred_category_articles",
 *   admin_label = @Translation("Preferred Category Articles"),
 *   category = @Translation("Custom")
 * )
 */
class PreferredCategoryArticlesBlock extends BlockBase implements ContainerFactoryPluginInterface {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected AccountInterface $currentUser;

  /**
   * Constructs the block.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, AccountInterface $currentUser) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Load the user entity.
    $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
    if (!$user || $user->get('field_preferred_category')->isEmpty()) {
      return [
        '#markup' => $this->t('You have not set a preferred category.'),
        '#cache' => ['contexts' => ['user']],
      ];
    }

    $category_id = $user->get('field_preferred_category')->target_id;

    // Load articles matching the preferred category.
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'article')
      ->condition('field_category', $category_id)
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->range(0, 5)
      ->accessCheck(TRUE);

    $nids = $query->execute();

    if (empty($nids)) {
      return [
        '#markup' => $this->t('No articles found in your preferred category.'),
        '#cache' => [
          'contexts' => ['user', 'preferred_category'],
        ],
      ];
    }

    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
    $items = [];

    foreach ($nodes as $node) {
      $items[] = [
        '#type' => 'link',
        '#title' => $node->label(),
        '#url' => $node->toUrl(),
      ];
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#cache' => [
        'contexts' => ['user', 'preferred_category'],
      ],
    ];
  }
}
