<?php

namespace Drupal\touch\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\touch\Form\TouchContactForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the "contact form" field formatter.
 *
 * @FieldFormatter(
 *   id = "touch_contact_form",
 *   label = @Translation("Contact form"),
 *   description = @Translation("A contact form diplaying subjects options from taxonomy term entity."),
 *   field_types = {
 *     "touch_contact_form"
 *   }
 * )
 *
 * @noinspection PhpUnused
 */
class TouchContactFormFormatter extends EntityReferenceFormatterBase {

  /**
   * The form builder.
   *
   * @var FormBuilderInterface
   */
  protected FormBuilderInterface $formBuilder;

  /**
   * Constructs a ContactFormFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param FormBuilderInterface $formBuilder
   *   The form builder.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, FormBuilderInterface $formBuilder) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): TouchContactFormFormatter {
    /** @noinspection PhpParamsInspection */
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @noinspection PhpParamsInspection
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    /** @var \Drupal\taxonomy\TermInterface[] $subjects */
    $subjects = $this->getEntitiesToView($items, $langcode);

    return $this->formBuilder->getForm(TouchContactForm::class, $subjects);
  }

}
