<?php

namespace Drupal\cache_exercise\Cache\Context;

use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides a custom cache context for the preferred category.
 */
class PreferredCategoryCacheContext implements CacheContextInterface {

  protected AccountInterface $currentUser;
  protected EntityTypeManagerInterface $entityTypeManager;

  public function __construct(AccountInterface $currentUser, EntityTypeManagerInterface $entityTypeManager) {
    $this->currentUser = $currentUser;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {

    if ($this->currentUser->isAnonymous()) {
      return 'none';
    }

    // Load the user entity.
    $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
    if ($user && !$user->get('field_preferred_category')->isEmpty()) {
      return (string) $user->get('field_preferred_category')->target_id;
    }

    return 'none';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Preferred category');
  }
}
