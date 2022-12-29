<?php

namespace Drupal\touch;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\TermStorageInterface;

/**
 * Class for implementing the contact subjects' helper service.
 */
class TouchOptionsHelper {

  use LoggerChannelTrait;

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
   * Constructs a TouchOptionsHelper object.
   *
   * @param EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param LanguageManagerInterface $languageManager
   *   The language manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LanguageManagerInterface $languageManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->languageManager = $languageManager;
  }

  /**
   * Retrieve an array of all languages.
   *
   * @return array
   *   The array of languages.
   */
  public function getLanguages(): array {
    $availableLanguages = $this->languageManager->getLanguages();

    $languages = [];

    foreach ($availableLanguages as $availableLanguage) {
      $languages[$availableLanguage->getId()] = $availableLanguage->getName();
    }

    return $languages;
  }

  /**
   * Retrieve "contact subjects" taxonomy term entities of those provided or all
   * if empty.
   *
   * @param TermInterface[] $subjectEntities
   *   The "contact subjects" taxonomy term entities.
   *
   * @return TermInterface[]
   *   The subject's information array.
   */
  public function getSubjectsEntities(array $subjectEntities = []): array {
    // The current language id.
    $currentLanguageId = $this->languageManager->getCurrentLanguage()->getId();

    /** @var TermInterface[] $subjects */
    $subjects = [];

    foreach ($subjectEntities as $subjectEntity) {
      if ($subjectEntity instanceof TermInterface && $subjectEntity->bundle() === 'contact_subjects' && $subjectEntity->isPublished()) {
        $subjects[] = $subjectEntity;
      }
    }

    if (empty($subjects)) {
      try {
        /** @var TermStorageInterface $termStorage */
        $termStorage = $this->entityTypeManager->getStorage('taxonomy_term');

        // Query the taxonomy term entities.
        $termQuery = $termStorage->getQuery();

        // Where the bundle is the "contact subjects".
        $termQuery->condition('vid', 'contact_subjects');

        // Where their status is published.
        $termQuery->condition('status', TRUE);

        /** @var TermInterface[] $subjectsEntities */
        $subjects = $termStorage->loadMultiple($termQuery->execute());
      }
      catch (InvalidPluginDefinitionException|PluginNotFoundException $exception) {
        $this->getLogger('touch')->error($exception->getMessage());
      }
    }

    foreach ($subjects as &$subject) {
      // Use the current language translation of the "contact subjects"
      // taxonomy term entity.
      if ($subject->hasTranslation($currentLanguageId)) {
        $subject = $subject->getTranslation($currentLanguageId);
      }
    }

    return $subjects;
  }

  /**
   * Retrieve subject's information array of all subject.
   *
   * @param TermInterface[] $subjectEntities
   *   The "contact subjects" taxonomy term entities.
   *
   * @return array
   *   The subject's information array of all subject.
   */
  public function getSubjectsInformation(array $subjectEntities): array {
    $subjects = [];

    foreach ($subjectEntities as $subjectEntity) {
      if ($subjectEntity instanceof TermInterface && $subjectEntity->hasField('mail')) {
        $subjects[] = [
          'id' => $subjectEntity->id(),
          'name' => $subjectEntity->getName(),
          'description' => $subjectEntity->getDescription(),
          'weight' => $subjectEntity->getWeight(),
          'mail' => $subjectEntity->get('mail')->getString(),
        ];
      }
    }

    // First, sort "contact subjects" taxonomy term entities by "name".
    usort($subjects, function($first, $second) {
      return strtolower($first['name']) <=> strtolower($second['name']);
    });

    // Second, sort "contact subjects" taxonomy term entities by "weight".
    usort($subjects, function($first, $second) {
      return $first['weight'] <=> $second['weight'];
    });

    $keyedSubjects = [];

    // Add the taxonomy term id as the array key of each subject.
    for ($index = 0; $index < count($subjects); $index++) {
      $keyedSubjects[$subjects[$index]['id']] = $subjects[$index];
    }

    return $keyedSubjects;
  }

}
