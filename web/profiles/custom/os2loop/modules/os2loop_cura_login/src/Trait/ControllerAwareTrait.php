<?php

namespace Drupal\os2loop_cura_login\Trait;

use Drupal\os2loop_cura_login\Controller\Os2loopCuraLoginController;
use Psr\Log\LoggerTrait;

/**
 * Controller aware trait to let services use the logger.
 */
trait ControllerAwareTrait {
  use LoggerTrait;

  /**
   * The controller.
   */
  private Os2loopCuraLoginController $controller;

  /**
   * Set controller.
   */
  public function setController(Os2loopCuraLoginController $controller) {
    $this->controller = $controller;
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, \Stringable|string $message, array $context = []): void {
    if ($this->controller) {
      $this->controller->log($level, $message, $context);
    }
  }

}
