<?php

namespace Drupal\touch\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Render\Element\Email;
use Drupal\taxonomy\TermInterface;
use Drupal\touch\TouchOptionsHelper;
use Drupal\touch\TouchSubmissionsHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for implementing the contact form.
 */
class TouchContactForm extends FormBase {

  /**
   * The contact subjects' taxonomy term entities.
   *
   * @var TermInterface[]
   */
  protected array $subjectEntities;

  /**
   * The form builder.
   *
   * @var FormBuilderInterface
   */
  protected FormBuilderInterface $formBuilder;

  /**
   * The language manager.
   *
   * @var LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * The mail manager.
   *
   * @var MailManagerInterface
   */
  protected MailManagerInterface $mailManager;

  /**
   * The module handler.
   *
   * @var ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

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
   * The time.
   *
   * @var TimeInterface
   */
  protected TimeInterface $time;

  /**
   * Constructs a TouchContactForm object.
   *
   * @param FormBuilderInterface $formBuilder
   *   The form builder.
   * @param LanguageManagerInterface $languageManager
   *   The language manager.
   * @param MailManagerInterface $mailManager
   *   The mail manager.
   * @param ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param TouchOptionsHelper $optionsHelper
   *   The options' helper.
   * @param TouchSubmissionsHelper $submissionsHelper
   *   The submissions' helper.
   * @param TimeInterface $time
   *   The time.
   */
  public function __construct(FormBuilderInterface $formBuilder, LanguageManagerInterface $languageManager, MailManagerInterface $mailManager, ModuleHandlerInterface $moduleHandler, TouchOptionsHelper $optionsHelper, TouchSubmissionsHelper $submissionsHelper, TimeInterface $time) {
    $this->formBuilder = $formBuilder;
    $this->languageManager = $languageManager;
    $this->mailManager = $mailManager;
    $this->moduleHandler = $moduleHandler;
    $this->optionsHelper = $optionsHelper;
    $this->submissionsHelper = $submissionsHelper;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): TouchContactForm {
    /** @noinspection PhpParamsInspection */
    return new static(
      $container->get('form_builder'),
      $container->get('language_manager'),
      $container->get('plugin.manager.mail'),
      $container->get('module_handler'),
      $container->get('touch.options_helper'),
      $container->get('touch.submissions_helper'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'touch_contact_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   The form.
   * @param FormStateInterface $form_state
   *   The current form state of the form.
   * @param TermInterface[] $subjectEntities
   *   The contact subjects' taxonomy term entities.
   *
   * @return array
   *   The form element to use for the ajax callback.
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $subjectEntities = []): array {
    $this->subjectEntities = $this->optionsHelper->getSubjectsEntities($subjectEntities);

    // Add the ajax page state as a clean key to the form state.
    $form_state->addCleanValueKey('ajax_page_state');

    // Use the appropriate form template.
    $form['#theme'] = 'touch_contact_form';

    // Generate a unique html attribute id for the ajax form.
    $globalWrapper = Html::getUniqueId('global-wrapper');

    // Wrapper element for ajax.
    $form['#prefix'] = '<div id="' . $globalWrapper . '">';
    $form['#suffix'] = '</div>';

    // Name element.
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('My name is'),
      '#placeholder' => $this->t('Complete name'),
      '#required' => TRUE,
      '#maxlength' => 255,
    ];

    // Email address element.
    $form['mail'] = [
      '#type' => 'email',
      '#title' => $this->t('I\'m reachable at'),
      '#placeholder' => $this->t('Email address'),
      '#required' => TRUE,
      '#maxlength' => Email::EMAIL_MAX_LENGTH,
    ];

    // Generate a unique html attribute id for the subject description ajax
    // element.
    $subjectDescriptionWrapper = Html::getUniqueId('subject-description-wrapper');

    $subjects = $this->optionsHelper->getSubjectsInformation($this->subjectEntities);
    $subjectNames = array_filter(array_combine(array_keys($subjects), array_column($subjects, 'name')));

    // If there is only (one or zero) "contact subjects" taxonomy term entity
    // available, it's automatically filled with the one available and the
    // subject element is hidden.
    $form['subjects'] = [
      '#type' => 'markup',
      '#access' => !(count($subjectNames) < 2),
    ];

    // Wrapper element for ajax.
    $form['subjects']['#prefix'] = '<div class="form-element--description" id="' . $subjectDescriptionWrapper . '">';
    $form['subjects']['#suffix'] = '</div>';

    // Subject element with ajax.
    $form['subjects']['subject'] = [
      '#type' => 'select',
      '#title' => $this->t('I write to you about'),
      '#options' => $subjectNames,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::ajaxChangeSubject',
        'wrapper' => $subjectDescriptionWrapper,
        'event' => 'change',
        'disable-refocus' => TRUE,
      ],
    ];

    // If there is only one "contact subjects" taxonomy term entity available,
    // it's automatically filled with the one available and the subject element
    // is hidden.
    if (count($subjectNames) === 1) {
      $form['subjects']['subject']['#value'] = (int) key($subjectNames);
    }

    // If there are zero "contact subjects" taxonomy term entity available,
    // it's automatically filled with the "0" value and the subject element
    // is hidden.
    if (count($subjectNames) === 0) {
      $form['subjects']['subject']['#value'] = 0;
    }

    // Subject' description element.
    $form['subjects']['description'] = [
      '#type' => 'markup',
    ];

    $this->displaySubjectDescription($form['subjects'], $form_state);

    // Message element.
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('I also have some details'),
      '#placeholder' => $this->t('Message'),
      '#required' => TRUE,
    ];

    // Newsletter element.
    $form['newsletter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('I would like to receive occasional news'),
    ];

    $messages = $this->messenger()->all();

    foreach ($messages as $class => $message) {
      // Submit message.
      $form['actions']['submit_message'] = [
        '#type' => 'markup',
        '#markup' => implode(' ', $message),
        '#prefix' => '<div class="form-element--messages form-element--messages--' . $class . '">',
        '#suffix' => '</div>',
      ];
    }

    // Submit element with ajax.
    $form['actions']['submit'] = [
      '#type' => 'button',
      '#value' => $this->t('Send'),
      '#ajax' => [
        'callback' => '::submitForm',
        'wrapper' => $globalWrapper,
        'event' => 'click',
        'disable-refocus' => TRUE,
      ],
    ];

    return $form;
  }

  /**
   * Form submit ajax callback to return it the right html element.
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
    // Delete all messages because the form is handled without them, with inline
    // form errors or with inline form success displayed.
    if ($this->moduleHandler->moduleExists('inline_form_errors')) {
      $this->messenger()->deleteAll();
    }

    // Get errors from the actual form state.
    $formErrors = $form_state->getErrors();

    // If there is no error, we do the final step and return an empty form with
    // an inline form success.
    if (empty($formErrors)) {
      // Clean the form values.
      $form_state->cleanValues();

      // Store the form submission in the database.
      $submissionStored = $this->storeFormSubmission($form_state);

      // Send the form submission by email.
      $submissionSent = $this->sendFormSubmission($form_state);

      if ($submissionStored === TRUE && $submissionSent === TRUE) {
        // Empty the form values.
        $this->emptyFormValues($form_state);

        // Display a success message.
        $this->messenger()->addStatus($this->t('Thank you for contacting me, I\'ll reach back to you shortly.'));

        // Rebuild the form as a brand new one except for the inline form success.
        return $this->formBuilder->rebuildForm($this->getFormId(), $form_state);
      }
    }

    return $form;
  }

  /**
   * Form ajax callback to return it the right html element.
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
   * @noinspection PhpUnused
   * @noinspection PhpUnusedParameterInspection
   */
  public function ajaxChangeSubject(array &$form, FormStateInterface $form_state): array {
    return $form['subjects'];
  }

  /**
   * Display the selected subject's description in the element.
   *
   * @param array $element
   *   The form element where.
   * @param FormStateInterface $form_state
   *   The current form state of the form.
   */
  private function displaySubjectDescription(array &$element, FormStateInterface $form_state) {
    // Remove the subject's description in the element.
    $element['description']['#markup'] = '';

    if (!empty($form_state->getValue('subject'))) {
      $subjectId = $form_state->getValue('subject');

      $subjects = $this->optionsHelper->getSubjectsInformation($this->subjectEntities);
      $subjectDescriptions = array_filter(array_combine(array_keys($subjects), array_column($subjects, 'description')));

      // Insert the new subject's description in the element.
      $element['description']['#markup'] = $subjectDescriptions[$subjectId];
    }
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

    // Prepare form values before inserting them in the database.
    $this->prepareFormValues($values);

    // Insert form submission.
    $result = $this->submissionsHelper->insertSubmissions([$values]);

    // If the submission was inserted.
    if ($result >= 0) {
      // Assign the id to the values.
      $form_state->setValue('id', (string) $result);

      return TRUE;
    }

    return FALSE;
  }

  /**
   * Send the form submission by email.
   *
   * @param FormStateInterface $form_state
   *   The current form state of the form.
   *
   * @return bool
   *   Returns "TRUE" if the form submission was sent successfully by email or
   *   "FALSE" if it failed.
   */
  private function sendFormSubmission(FormStateInterface $form_state): bool {
    $values = $form_state->getValues();

    // Prepare form values before sending them by email.
    $this->prepareFormValues($values);

    // Get the email address to send the submission.
    $subjects = $this->optionsHelper->getSubjectsInformation($this->subjectEntities);
    $subjectMails = array_filter(array_combine(array_keys($subjects), array_column($subjects, 'mail')));

    // If the contact subject has an email address.
    if (!empty($subjectMails[$values['subject_id']])) {
      // The contact subject email address is used.
      $emailAddress = $subjectMails[$values['subject_id']];
    }
    else {
      // Otherwise, the site email address is used.
      $emailAddress = $this->configFactory()->get('system.site')->get('mail');
    }

    // Send the email.
    $email = $this->mailManager->mail('touch', 'contact_form', $emailAddress, $values['language'], $values, $values['mail']);

    // Return the result.
    return $email['result'];
  }

  /**
   * Empty form values by unsetting user input values (except for the clean
   * keys) and by unsetting submitted and sanitized values.
   *
   * @param FormStateInterface $form_state
   *   The current form state of the form.
   *
   * @return void
   */
  private function emptyFormValues(FormStateInterface $form_state): void {
    // Get the clean value keys to ignore as user input keys.
    $cleanValueKeys = $form_state->getCleanValueKeys();

    // Get the actual user input values.
    $userInputValues = $form_state->getUserInput();

    foreach ($userInputValues as $key => $value) {
      // If the key in the user input values is not a clean key or if the key
      // starts with an underscore.
      if (!in_array($key, $cleanValueKeys) && !str_starts_with($key, '_')) {
        // Unset the user input value.
        unset($userInputValues[$key]);

        // Unset the submitted and sanitized value.
        $form_state->unsetValue($key);
      }
    }

    // Set the new emptied user input values.
    $form_state->setUserInput($userInputValues);
  }

  /**
   * Alter the values to be email ready.
   *
   * @param $values
   *   The values array to alter.
   *
   * @return void
   */
  private function prepareFormValues(&$values): void {
    // Set the subject id on a different key.
    $values['subject_id'] = $values['subject'];

    // Set the subject name to keep the submitted value even if the taxonomy
    // term entity is deleted in the future.
    $values['subject_name'] = (!empty($this->optionsHelper->getSubjectsInformation($this->subjectEntities)) ? $this->optionsHelper->getSubjectsInformation($this->subjectEntities)[$values['subject']]['name'] : $this->t('Undefined subject'));

    // Unset the default subject value.
    unset($values['subject']);

    // Add the language to the values.
    $values['language'] = $this->languageManager->getCurrentLanguage()->getId();

    // Add the timestamp to the values.
    $values['timestamp'] = $this->time->getRequestTime();

    // Add the ip address to the values.
    $values['ip_address'] = !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';

    // Add the ip address (proxy) to the values.
    $values['ip_address_proxy'] = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '';

    // Add the user agent to the values.
    $values['user_agent'] = !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
  }

}
