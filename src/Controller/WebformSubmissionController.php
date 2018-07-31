<?php

namespace Drupal\webform\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\webform\Ajax\WebformAnnounceCommand;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides route responses for webform submissions.
 */
class WebformSubmissionController extends ControllerBase {

  /**
   * Toggle webform submission sticky.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An Ajax response that toggle the sticky icon.
   */
  public function sticky(WebformSubmissionInterface $webform_submission) {
    // Toggle sticky.
    $webform_submission->setSticky(!$webform_submission->isSticky())->save();

    // Get selector.
    $selector = '#webform-submission-' . $webform_submission->id() . '-sticky';

    $response = new AjaxResponse();

    // Update sticky.
    $response->addCommand(new HtmlCommand($selector, static::buildSticky($webform_submission)));

    // Announce sticky status.
    $t_args = ['@label' => $webform_submission->label()];
    $text = $webform_submission->isSticky() ? $this->t('@label flagged/starred.', $t_args) : $this->t('@label unflagged/unstarred.', $t_args);
    $response->addCommand(new WebformAnnounceCommand($text));

    return $response;
  }

  /**
   * Toggle webform submission locked.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An Ajax response that toggle the lock icon.
   */
  public function locked(WebformSubmissionInterface $webform_submission) {
    // Toggle locked.
    $webform_submission->setLocked(!$webform_submission->isLocked())->save();

    // Get selector.
    $selector = '#webform-submission-' . $webform_submission->id() . '-locked';

    $response = new AjaxResponse();

    // Update lock.
    $response->addCommand(new HtmlCommand($selector, static::buildLocked($webform_submission)));

    // Announce lock status.
    $t_args = ['@label' => $webform_submission->label()];
    $text = $webform_submission->isLocked() ? $this->t('@label locked.', $t_args) : $this->t('@label unlocked.', $t_args);
    $response->addCommand(new WebformAnnounceCommand($text));
    return $response;
  }

  /**
   * Build sticky icon.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return \Drupal\Component\Render\FormattableMarkup
   *   Sticky icon.
   */
  public static function buildSticky(WebformSubmissionInterface $webform_submission) {
    $t_args = ['@label' => $webform_submission->label()];
    $args = [
      '@state' => $webform_submission->isSticky() ? 'on' : 'off',
      '@label' => $webform_submission->isSticky() ? t('Unstar/Unflag @label', $t_args) : t('Star/flag @label', $t_args),
    ];
    return new FormattableMarkup('<span class="webform-icon webform-icon-sticky webform-icon-sticky--@state"></span><span class="visually-hidden">@label</span>', $args);
  }

  /**
   * Build locked icon.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return \Drupal\Component\Render\FormattableMarkup
   *   Locked icon.
   */
  public static function buildLocked(WebformSubmissionInterface $webform_submission) {
    $t_args = ['@label' => $webform_submission->label()];
    $args = [
      '@state' => $webform_submission->isLocked() ? 'on' : 'off',
      '@label' => $webform_submission->isLocked() ? t('Unlock @label', $t_args) : t('Lock @label', $t_args),
    ];
    return new FormattableMarkup('<span class="webform-icon webform-icon-lock webform-icon-locked--@state"></span><span class="visually-hidden">@label</span>', $args);
  }

}
