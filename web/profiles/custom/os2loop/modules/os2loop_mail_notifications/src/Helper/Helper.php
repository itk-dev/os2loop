<?php

namespace Drupal\os2loop_mail_notifications\Helper;

/**
 * OS2Loop Mail notifications helper.
 */
class Helper {

  /**
   * Send notifications.
   */
  public function sendNotifications() {
  }

  /**
   * Implements hook_cron().
   */
  public function cron() {
    $this->sendNotifications();
  }

}
