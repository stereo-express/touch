<?php

namespace Drupal\touch\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\Element\Email;
use Drupal\Core\Url;
use Drupal\taxonomy\TermInterface;
use Drupal\touch\TouchOptionsHelper;
use Drupal\touch\TouchSubmissionsHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for implementing the submission's edit form.
 *
 * @noinspection PhpUnused
 */
class TouchSubmissionEditForm extends FormBase {

  /**
   * The submission.
   *
   * @var array
   */
  protected array $submission;

  /**
   * The contact subjects' taxonomy term entities.
   *
   * @var TermInterface[]
   */
  protected array $subjectEntities;

  /**
   * The entity type manager.
   *
   * @var EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The language manager.
   *
   * @var LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * The options' helper.
   *
   * @var TouchOptionsHelper
   */
  protected TouchOptionsHelper $optionsHelper;

  /**
   * The submissions' helper.
   *
   * @var TouchSubmissionsHelper
   */
  protected TouchSubmissionsHelper $submissionsHelper;

  /**
   * Constructs a TouchSubmissionEditForm object.
   *
   * @param EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param LanguageManagerInterface $languageManager
   *   The language manager.
   * @param TouchOptionsHelper $optionsHelper
   *   The options' helper.
   * @param TouchSubmissionsHelper $submissionsHelper
   *   The submissions' helper.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LanguageManagerInterface $languageManager, TouchOptionsHelper $optionsHelper, TouchSubmissionsHelper $submissionsHelper) {
    $this->entityTypeManager = $entityTypeManager;
    $this->languageManager = $languageManager;
    $this->optionsHelper = $optionsHelper;
    $this->submissionsHelper = $submissionsHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): TouchSubmissionEditForm {
    /** @noinspection PhpParamsInspection */
    return new static(
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('touch.options_helper'),
      $container->get('touch.submissions_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'touch_submission_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, int $id = NULL): array {
    // Fetch the submission's details.
    $this->submission = current($this->submissionsHelper->selectSubmissions([$id]));

    $this->subjectEntities = $this->optionsHelper->getSubjectsEntities();

    $form['#title'] = $this->t('Submission by %name on %date', [
      '%name' => $this->submissionsHelper->getValue('name', $this->submission['name']),
      '%date' => $this->submissionsHelper->getValue('timestamp', $this->submission['timestamp']),
    ]);

    // Name element.
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => (!empty($this->submission['name']) ? $this->submission['name'] : NULL),
      '#required' => TRUE,
      '#maxlength' => 255,
    ];

    // Email address element.
    $form['mail'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#default_value' => (!empty($this->submission['mail']) ? $this->submission['mail'] : NULL),
      '#required' => TRUE,
      '#maxlength' => Email::EMAIL_MAX_LENGTH,
    ];

    $subjects = $this->optionsHelper->getSubjectsInformation($this->subjectEntities);
    $subjectNames = array_filter(array_combine(array_keys($subjects), array_column($subjects, 'name')));

    // Subject id element.
    $form['subject_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Subject'),
      '#options' => $subjectNames,
      '#default_value' => (!empty($this->submission['subject_id']) ? $this->submission['subject_id'] : NULL),
      '#required' => TRUE,
    ];

    // If the "contact subjects" taxonomy term entity do not exist anymore, the
    // subject id element is hidden and the subject name is displayed instead.
    if (!array_key_exists($form['subject_id']['#default_value'], $form['subject_id']['#options'])) {
      $form['subject_id']['#access'] = FALSE;

      // Subject name element.
      $form['subject_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Subject'),
        '#default_value' => (!empty($this->submission['subject_name']) ? $this->submission['subject_name'] : NULL),
        '#required' => TRUE,
      ];
    }

    // Message element.
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#default_value' => (!empty($this->submission['message']) ? $this->submission['message'] : NULL),
      '#required' => TRUE,
    ];

    // Newsletter element.
    $form['newsletter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Newsletter subscription'),
      '#default_value' => (!empty($this->submission['newsletter']) ? $this->submission['newsletter'] : NULL),
    ];

    $languages = $this->optionsHelper->getLanguages();

    // Language element.
    $form['language'] = [
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#options' => $languages,
      '#default_value' => (!empty($this->submission['language']) ? $this->submission['language'] : NULL),
      '#required' => TRUE,
    ];

    // Date element.
    $form['datetime'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Date'),
      '#date_format' => 'Y-m-d H:i:s',
      '#default_value' => (!empty($this->submission['timestamp']) ? DrupalDateTime::createFromTimestamp($this->submission['timestamp']) : NULL),
      '#required' => TRUE,
    ];

    // IP address element.
    $form['ip_address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('IP address'),
      '#default_value' => (!empty($this->submission['ip_address']) ? $this->submission['ip_address'] : NULL),
      '#disabled' => TRUE,
    ];

    // IP address (proxy) element.
    $form['ip_address_proxy'] = [
      '#type' => 'textfield',
      '#title' => $this->t('IP address (proxy)'),
      '#default_value' => (!empty($this->submission['ip_address_proxy']) ? $this->submission['ip_address_proxy'] : NULL),
      '#disabled' => TRUE,
    ];

    // User agent element.
    $form['user_agent'] = [
      '#type' => 'textarea',
      '#title' => $this->t('User agent'),
      '#default_value' => (!empty($this->submission['user_agent']) ? $this->submission['user_agent'] : NULL),
      '#disabled' => TRUE,
    ];

    // Submit element.
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#submit' => ['::submitForm'],
    ];

    $form['actions']['delete'] = [
      '#type' => 'link',
      '#title' => $this->t('Delete'),
      '#url' => Url::fromRoute('touch.contact_form_submission_delete', ['id' => $this->submission['id']]),
      '#attributes' => [
        'class' => ['button', 'button--danger'],
      ],
    ];

    return $form;
  }

  /**
   * Form submit callback.
   *
   * @param array $form
   *   The form.
   * @param FormStateInterface $form_state
   *   The current form state of the form.
   *
   * @return array
   *   The form element to use for the ajax callback.
   *
   * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
   */
  public function submitForm(array &$form, FormStateInterface $form_state): array {
    // Get errors from the actual form state.
    $formErrors = $form_state->getErrors();

    // If there is no error, we do the final step and return an empty form with
    // an inline form success.
    if (empty($formErrors)) {
      // Clean the form values.
      $form_state->cleanValues();

      // Store the form submission in the database.
      $submissionStored = $this->storeFormSubmission($form_state);

      if ($submissionStored === TRUE) {
        $this->messenger()->addStatus($this->t('The submission by %name on %date has been successfully saved.', [
          '%name' => $this->submissionsHelper->getValue('name', $this->submission['name']),
          '%date' => $this->submissionsHelper->getValue('timestamp', $this->submission['timestamp']),
        ]));

        // Redirect to the submission's canonical page.
        $form_state->setRedirect('touch.contact_form_submission_canonical', ['id' => $this->submission['id']]);
      }
    }

    return $form;
  }

  /**
   * Store the form submission in the database.
   *
   * @param FormStateInterface $form_state
   *   The current form state of the form.
   *
   * @return bool
   *   Returns "TRUE" if the form submission was stored successfully in the
   *   database or "FALSE" if it failed.
   */
  private function storeFormSubmission(FormStateInterface $form_state): bool {
    $values = $form_state->getValues();

    // Prepare form values before updating them in the database.
    $this->prepareFormValues($values);

    // Update form submission.
    $result = $this->submissionsHelper->updateSubmissions([$values]);

    // If the submission was updated.
    if ($result >= 0) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Alter the values to be database ready.
   *
   * @param $values
   *   The values array to alter.
   *
   * @return void
   */
  private function prepareFormValues(&$values): void {
    // Set the submission id to be able to do the update on the right submission.
    $values['id'] = $this->submission['id'];

    // Set the timestamp from the date time.
    $values['timestamp'] = $values['datetime']->getTimestamp();

    // Unset the date time value.
    unset($values['datetime']);

    // Set the subject name to keep the submitted value even if the taxonomy
    // term entity is deleted in the future.
    if (empty($values['subject_name'])) {
      $values['subject_name'] = $this->optionsHelper->getSubjectsInformation($this->subjectEntities)[$values['subject_id']]['name'];
    }

    // Unset the default subject value.
    unset($values['subject']);
  }

}
