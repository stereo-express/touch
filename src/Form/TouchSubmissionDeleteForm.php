<?php

namespace Drupal\touch\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\touch\TouchSubmissionsHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for implementing the submission's delete form.
 *
 * @noinspection PhpUnused
 */
class TouchSubmissionDeleteForm extends ConfirmFormBase {

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
   * Constructs a TouchSubmissionDeleteForm object.
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
  public static function create(ContainerInterface $container): TouchSubmissionDeleteForm {
    /** @noinspection PhpParamsInspection */
    return new static(
      $container->get('touch.submissions_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'touch_submission_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, int $id = NULL): array {
    // Fetch the submission's details.
    $this->submission = current($this->submissionsHelper->selectSubmissions([$id]));

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->submissionsHelper->deleteSubmissions([$this->submission['id']]);

    $this->messenger()->addStatus($this->t('The submission by %name on %date has been deleted.', [
      '%name' => $this->submissionsHelper->getValue('name', $this->submission['name']),
      '%date' => $this->submissionsHelper->getValue('timestamp', $this->submission['timestamp']),
    ]));

    $this->getLogger('touch')->notice('The submission by %name on %date has been deleted.', [
      '%name' => $this->submissionsHelper->getValue('name', $this->submission['name']),
      '%date' => $this->submissionsHelper->getValue('timestamp', $this->submission['timestamp']),
    ]);

    // Redirect to the submissions' list page.
    $form_state->setRedirect('touch.contact_form_submissions_list');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Are you sure you want to delete the submission by %name on %date?', [
      '%name' => $this->submissionsHelper->getValue('name', $this->submission['name']),
      '%date' => $this->submissionsHelper->getValue('timestamp', $this->submission['timestamp']),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText(): TranslatableMarkup {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   *
   * @noinspection PhpPureAttributeCanBeAddedInspection
   */
  public function getCancelUrl(): Url {
    // Set the cancel url to the submission canonical page.
    return new Url('touch.contact_form_submission_canonical', ['id' => $this->submission['id']]);
  }

}
