<?php

namespace Drupal\touch;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\TermStorageInterface;
use Exception;

/**
 * Class for implementing the submissions' helper service.
 */
class TouchSubmissionsHelper {

  use LoggerChannelTrait;
  use StringTranslationTrait;

  /**
   * The database connection.
   *
   * @var Connection
   */
  protected Connection $database;

  /**
   * The date formatter.
   *
   * @var DateFormatterInterface
   */
  protected DateFormatterInterface $dateFormatter;

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
   * Constructs a ContactFormSubmissionsListController object.
   *
   * @param Connection $database
   *   The database connection.
   * @param DateFormatterInterface $dateFormatter
   *   The date formatter.
   * @param EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param LanguageManagerInterface $languageManager
   *   The language manager.
   */
  public function __construct(Connection $database, DateFormatterInterface $dateFormatter, EntityTypeManagerInterface $entityTypeManager, LanguageManagerInterface $languageManager) {
    $this->database = $database;
    $this->dateFormatter = $dateFormatter;
    $this->entityTypeManager = $entityTypeManager;
    $this->languageManager = $languageManager;
  }

  /**
   * Get the submission key label.
   *
   * @param string $key
   *   The label key.
   *
   * @return string|null
   *   The submission key label if it exists or NULL if not.
   */
  public function getLabel(string $key): ?string {
    return match ($key) {
      'id' => $this->t('ID'),
      'name' => $this->t('Name'),
      'mail' => $this->t('Email address'),
      'subject' => $this->t('Subject'),
      'message' => $this->t('Message'),
      'newsletter' => $this->t('Newsletter subscription'),
      'language' => $this->t('Language'),
      'timestamp' => $this->t('Date'),
      'ip_address' => $this->t('IP address'),
      'ip_address_proxy' => $this->t('IP address (proxy)'),
      'browser' => $this->t('Browser'),
      'operating_system' => $this->t('Operating system'),
      'user_agent' => $this->t('User agent'),
      'operations' => $this->t('Operations'),
      'canonical_link' => $this->t('View'),
      'edit_link' => $this->t('Edit'),
      'delete_link' => $this->t('Delete'),
      default => NULL,
    };
  }

  /**
   * Get the submission key value.
   *
   * @param string $key
   *   The value key.
   * @param string|array $value
   *   The value.
   *
   * @return string|null
   *   The submission key translated label if it exists or NULL if not.
   */
  public function getValue(string $key, string|array $value): ?string {
    if ($key === 'subject' && !empty($value['id'])) {
      try {
        /** @var TermStorageInterface $termStorage */
        $termStorage = $this->entityTypeManager->getStorage('taxonomy_term');

        /** @var TermInterface $term */
        $term = $termStorage->load($value['id']);

        if ($term instanceof TermInterface) {
          $subject = Link::fromTextAndUrl($term->getName(), $term->toUrl())->toString();
        }
      }
      catch (InvalidPluginDefinitionException|PluginNotFoundException|EntityMalformedException $exception) {
        $this->getLogger('touch')->error($exception->getMessage());
      }
    }

    return match ($key) {
      'id', 'name', 'message', 'ip_address', 'ip_address_proxy', 'user_agent' => $value,
      'mail' => Link::fromTextAndUrl($value, Url::fromUri(Url::fromUri('mailto:' . $value)->toString()))->toString(),
      'subject' => ((!empty($subject)) ? $subject : ((!empty($value['name'])) ? $value['name'] : '')),
      'newsletter' => (($value === '1') ? $this->t('Yes') : (($value === '0') ? $this->t('No') : '')),
      'language' => $this->languageManager->getLanguage($value)->getName(),
      'timestamp' => $this->dateFormatter->format($value, 'short'),
      'browser', 'operating_system' => $this->t('This value is coming soon as a compatible library version is about to be released.'),
      default => NULL,
    };
  }

  /**
   * Get all submissions in the database.
   *
   * @param array $ids
   *   An optional list of submission ids, if not specified, all submissions are
   *   returned.
   *
   * @return array
   *   The submissions' details result.
   */
  public function selectSubmissions(array $ids = []): array {
    $results = [];

    try {
      // Select from the submissions' table.
      $query = $this->database->select('touch_contact_form_submissions');

      // Select all columns of the submissions' table.
      $query->fields('touch_contact_form_submissions');

      if (!empty($ids)) {
        $query->condition('id', $ids, 'IN');
      }

      // Execute the query and get results.
      $items = $query->execute()->fetchAll();

      foreach ($items as $item) {
        $results[] = (array) $item;
      }
    }
    catch (Exception $exception) {
      // Log the exception to the logger channel.
      $this->getLogger('touch')->error($exception->getMessage());
    }

    return $results;
  }

  /**
   * Insert multiple submissions in the database.
   *
   * @param array[] $submissions
   *   An array of submissions containing all required details to insert.
   *
   * @return int
   *   Return the last inserted id or zero if nothing was inserted.
   */
  public function insertSubmissions(array $submissions): int {
    try {
      // Insert in the submissions' table.
      $query = $this->database->insert('touch_contact_form_submissions');

      foreach ($submissions as $submission) {
        // Define the submission's keys to insert.
        $query->fields(array_keys($submission));

        // Define the submission's values to insert.
        $query->values($submission);
      }

      // Execute the query and get result.
      return (int) $query->execute();
    }
    catch (Exception $exception) {
      // Log the exception to the logger channel.
      $this->getLogger('touch')->error($exception->getMessage());
    }

    return 0;
  }

  /**
   * Update multiple submissions in the database.
   *
   * @param array[] $submissions
   *   An array of submissions containing all required details to update.
   *
   * @return int
   *   Return the number of updated rows or zero if nothing was updated.
   */
  public function updateSubmissions(array $submissions): int {
    $results = 0;

    try {
      foreach ($submissions as $submission) {
        // Update the submissions' table.
        $query = $this->database->update('touch_contact_form_submissions');

        $query->condition('id', $submission['id']);
        $query->fields($submission);

        // Execute the query and get results.
        $results += (int) $query->execute();
      }
    }
    catch (Exception $exception) {
      // Log the exception to the logger channel.
      $this->getLogger('touch')->error($exception->getMessage());
    }

    return $results;
  }

  /**
   * Delete multiple submissions from the database.
   *
   * @param string[] $ids
   *   A list of submissions' ids to delete from the database.
   *
   * @return int
   *   Return the number of deleted rows or zero if nothing was deleted.
   */
  public function deleteSubmissions(array $ids): int {
    try {
      // Delete from the submissions' table.
      $query = $this->database->delete('touch_contact_form_submissions');

      $query->condition('id', $ids, 'IN');

      // Execute the query and get results.
      return $query->execute();
    }
    catch (Exception $exception) {
      // Log the exception to the logger channel.
      $this->getLogger('touch')->error($exception->getMessage());
    }

    return 0;
  }

}
