<?php

namespace Drupal\touch\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the "contact form" entity field type.
 *
 * @FieldType(
 *   id = "touch_contact_form",
 *   label = @Translation("Contact form"),
 *   description = @Translation("A contact form diplaying subjects options from taxonomy term entity."),
 *   category = @Translation("Reference"),
 *   default_widget = "options_buttons",
 *   default_formatter = "touch_contact_form",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 * )
 *
 * @noinspection PhpUnused
 */
class TouchContactFormItem extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings(): array {
    return [
      'target_type' => 'taxonomy_term',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings(): array {
    return [
      'handler' => 'default',
      'handler_settings' => [
        'target_bundles' => [
          'contact_subjects',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getPreconfiguredOptions(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data): array {
    $element['target_type'] = [
      '#type' => 'select',
      '#title' => t('Type of item to reference'),
      '#options' => ['taxonomy_term' => $this->t('Taxonomy term')],
      '#default_value' => $this->getSetting('target_type'),
      '#required' => TRUE,
      '#disabled' => $has_data,
      '#size' => 1,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::fieldSettingsForm($form, $form_state);

    if (!empty($form['handler']['handler_settings']['target_bundles']['#options']['contact_subjects'])) {
      $contactSubjectsLabel = $form['handler']['handler_settings']['target_bundles']['#options']['contact_subjects'];

      $form['handler']['handler_settings']['target_bundles']['#options'] = ['contact_subjects' => $contactSubjectsLabel];

      $form['handler']['handler_settings']['target_bundles']['#default_value'] = ['contact_subjects'];
    }

    return $form;
  }

}
