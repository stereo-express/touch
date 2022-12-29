<?php

namespace Drupal\touch\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Utility\TableSort;
use Drupal\touch\TouchSubmissionsHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for implementing the submissions' list controller.
 *
 * @noinspection PhpUnused
 */
class TouchSubmissionsListController extends ControllerBase {

  /**
   * The submissions.
   *
   * @var array
   */
  protected array $submissions;

  /**
   * The submissions' helper.
   *
   * @var TouchSubmissionsHelper
   */
  protected TouchSubmissionsHelper $submissionsHelper;

  /**
   * Constructs a TouchSubmissionsListController object.
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
  public static function create(ContainerInterface $container): TouchSubmissionsListController {
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
   *
   * @return array
   *   The page render array.
   */
  public function display(Request $request): array {
    // Fetch the submissions' details.
    $this->submissions = $this->submissionsHelper->selectSubmissions();

    $rows = [];

    foreach ($this->submissions as $submission) {
      $rows[] = [
        'id' => [
          'data' => [
            '#type' => 'markup',
            '#markup' => $this->submissionsHelper->getValue('id', $submission['id']),
          ],
        ],
        'name' => [
          'data' => [
            '#type' => 'markup',
            '#markup' => $this->submissionsHelper->getValue('name', $submission['name']),
          ],
        ],
        'mail' => [
          'data' => [
            '#type' => 'markup',
            '#markup' => $this->submissionsHelper->getValue('mail', $submission['mail']),
          ],
        ],
        'subject' => [
          'data' => [
            '#type' => 'markup',
            '#markup' => $this->submissionsHelper->getValue('subject', ['id' => $submission['subject_id'], 'name' => $submission['subject_name']]),
          ],
        ],
        'language' => [
          'data' => [
            '#type' => 'markup',
            '#markup' => $this->submissionsHelper->getValue('language', $submission['language']),
          ],
        ],
        'timestamp' => [
          'data' => [
            '#type' => 'markup',
            '#markup' => $this->submissionsHelper->getValue('timestamp', $submission['timestamp']),
          ],
        ],
        'operations' => [
          'data' => [
            '#type' => 'operations',
            '#links' => [
              'canonical' => [
                'title' => $this->submissionsHelper->getLabel('canonical_link'),
                'url' => Url::fromRoute('touch.contact_form_submission_canonical', ['id' => $submission['id']]),
              ],
              'edit' => [
                'title' => $this->submissionsHelper->getLabel('edit_link'),
                'url' => Url::fromRoute('touch.contact_form_submission_edit', ['id' => $submission['id']]),
              ],
              'delete' => [
                'title' => $this->submissionsHelper->getLabel('delete_link'),
                'url' => Url::fromRoute('touch.contact_form_submission_delete', ['id' => $submission['id']]),
              ],
            ],
          ],
        ],
      ];
    }

    $header = [
      'id' => [
        'data' => $this->submissionsHelper->getLabel('id'),
      ],
      'name' => [
        'field' => 'name',
        'data' => $this->submissionsHelper->getLabel('name'),
      ],
      'mail' => [
        'field' => 'mail',
        'data' => $this->submissionsHelper->getLabel('mail'),
      ],
      'subject' => [
        'field' => 'subject',
        'data' => $this->submissionsHelper->getLabel('subject'),
      ],
      'language' => [
        'field' => 'language',
        'data' => $this->submissionsHelper->getLabel('language'),
      ],
      'timestamp' => [
        'field' => 'timestamp',
        'sort' => 'desc',
        'data' => $this->submissionsHelper->getLabel('timestamp'),
      ],
      'operations' => [
        'field' => 'operations',
        'data' => $this->submissionsHelper->getLabel('operations'),
      ],
    ];

    // Get the table order.
    $order = TableSort::getOrder($header, $request);

    // Get the table sort.
    $sort = TableSort::getSort($header, $request);

    // Field order and sort name.
    $sql = $order['sql'];

    // Sorting if desc.
    if ($sort === 'desc') {
      usort($rows, function ($a, $b) use ($sql) {
        return $a[$sql]['data']['#markup'] > $b[$sql]['data']['#markup'] ? -1 : 1;
      });
    }

    // Sorting if asc.
    if ($sort === 'asc') {
      usort($rows, function ($a, $b) use ($sql) {
        return $a[$sql]['data']['#markup'] < $b[$sql]['data']['#markup'] ? -1 : 1;
      });
    }

    return [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
  }

}
