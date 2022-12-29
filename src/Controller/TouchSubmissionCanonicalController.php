<?php

namespace Drupal\touch\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\touch\TouchSubmissionsHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for implementing the submission's canonical controller.
 *
 * @noinspection PhpUnused
 */
class TouchSubmissionCanonicalController extends ControllerBase {

  /**
   * The submission.
   *
   * @var array
   */
  protected array $submission;

  /**
   * The submissions' helper.
   *
   * @var TouchSubmissionsHelper
   */
  protected TouchSubmissionsHelper $submissionsHelper;

  /**
   * Constructs a TouchSubmissionCanonicalController object.
   *
   * @param TouchSubmissionsHelper $submissionsHelper
   *   The submissions' helper.
   */
  public function __construct(TouchSubmissionsHelper $submissionsHelper) {
    $this->submissionsHelper = $submissionsHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): TouchSubmissionCanonicalController {
    /** @noinspection PhpParamsInspection */
    return new static(
      $container->get('touch.submissions_helper')
    );
  }

  /**
   * Displays a list of all submissions.
   *
   * @param Request $request
   *   The request.
   * @param string $id
   *   The submission id.
   *
   * @return array
   *   The page render array.
   *
   * @noinspection PhpUnusedParameterInspection
   */
  public function display(Request $request, string $id): array {
    // Fetch the submission's details.
    $this->submission = current($this->submissionsHelper->selectSubmissions([$id]));

    // Set the formatted subject value.
    $this->submission = array_slice($this->submission, 0, 3) + ['subject' => ['id' => $this->submission['subject_id'], 'name' => $this->submission['subject_name']]] + array_slice($this->submission, 3);

    // Unset the subject id value as we use it in the formatted subject value.
    unset($this->submission['subject_id']);

    // Unset the subject name value as we use it in the formatted subject value.
    unset($this->submission['subject_name']);

    // Set the browser value.
    $this->submission = array_slice($this->submission, 0, -1) + ['browser' => $this->submission['user_agent']] + array_slice($this->submission, 0);

    // Set the operating system value.
    $this->submission = array_slice($this->submission, 0, -1) + ['operating_system' => $this->submission['user_agent']] + array_slice($this->submission, 0);

    $rows = [];

    foreach ($this->submission as $key => $value) {
      $rows[] = [
        'label' => [
          'data' => [
            '#markup' => $this->submissionsHelper->getLabel($key),
          ],
          'width' => '25%',
          'header' => TRUE,
        ],
        'value' => [
          'data' => [
            '#markup' => $this->submissionsHelper->getValue($key, $value),
          ],
          'width' => '75%',
        ],
      ];
    }

    return [
      '#title' => $this->t('Submission by %name on %date', [
        '%name' => $this->submissionsHelper->getValue('name', $this->submission['name']),
        '%date' => $this->submissionsHelper->getValue('timestamp', $this->submission['timestamp']),
      ]),
      '#theme' => 'table',
      '#rows' => $rows,
    ];
  }

}
