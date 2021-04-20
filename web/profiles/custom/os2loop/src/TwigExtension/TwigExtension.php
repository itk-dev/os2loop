<?php

namespace Drupal\os2loop\TwigExtension;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Session\AccountInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension.
 */
class TwigExtension extends AbstractExtension {
  /**
   * The account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $account;

  /**
   * Constructor.
   */
  public function __construct(AccountInterface $account) {
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new TwigFunction('is_granted', [$this, 'isGranted']),
    ];
  }

  /**
   * Is granted.
   *
   * Heavily inspired by
   * https://symfony.com/doc/current/reference/twig_reference.html#is-granted.
   *
   * Examples:
   *
   *   Check for role:           is_granted('editor')
   *   Check for permission:     is_granted('administer nodes')
   *   Check for access on node: is_granted('update', node)
   */
  public function isGranted(string $role = NULL, $object = NULL) {
    if (NULL !== $role) {
      // If no object is passed we Check for permission or role.
      if (NULL === $object) {
        return $this->account->hasPermission($role)
          || in_array($role, $this->account->getRoles(), TRUE);
      }

      // Check access on object.
      if ($object instanceof ContentEntityBase) {
        return $object->access($role, $this->account);
      }
    }

    return FALSE;
  }

}
