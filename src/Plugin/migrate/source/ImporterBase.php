<?php

namespace Drupal\fsrio_rpd_importer\Plugin\migrate\source;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for defining Importer Migrate plugins.
 */
abstract class ImporterBase extends SourcePluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  protected $skipCount = TRUE;

  /**
   * The DOMDocument we are encapsulating.
   *
   * @var \DOMDocument
   */
  protected $document;

  /**
   * The URLs of individual project info pages.
   *
   * @var array
   */
  protected $projectUrls = [];

  /**
   * The URL of the project page that is currently loaded in $this->document.
   *
   * @var string
   */
  protected $currentUrl;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);

    $this->document = new \DOMDocument();

    // Suppress errors during parsing, so we can pick them up after.
    libxml_use_internal_errors(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration
    );
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $this->discoverProjectUrls();
    return new \ArrayIterator($this->parseProjectPages());
  }

  /**
   * Opens the source project list and starts the list parser.
   */
  protected function discoverProjectUrls() {
    $this->document->loadHTMLFile($this->getSourceUrl());
    $this->projectUrls = $this->parseProjectListPage();
  }

  /**
   * Returns the URL of the source's lists of projects.
   *
   * @return string
   *   The source URL.
   */
  abstract protected function getSourceUrl();

  /**
   * Parses the project source list and extracts project page URLs.
   *
   * The plugin base will have already loaded the source list's HTML before this
   * method is called.
   *
   * @return string[]
   *   An array of project page URLs.
   */
  abstract protected function parseProjectListPage();

  /**
   * Parses project pages and inserts their metadata into an iterable array.
   *
   * @return string[][]
   *   An array of project metadata.
   */
  protected function parseProjectPages() {
    $projects = [];

    foreach ($this->projectUrls as $url) {
      $this->document->loadHTMLFile($url);
      $this->currentUrl = $url;
      $projects[] = ['source_url' => $url] + $this->parseProjectPage();
    }

    return $projects;
  }

  /**
   * Parses a single project page and extracts the project's metadata.
   *
   * The plugin base will have already loaded the project's HTML before this
   * method is called.
   *
   * The source_url will be automatically inserted into the project array by
   * parseProjectPages().  This method does not need to insert it unless it's
   * necessary to override the URL value for some reason.
   *
   * @return string[]
   *   An array of project metadata.
   */
  abstract protected function parseProjectPage();

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'accession_number' => $this->t('Accession Number (not valid for all sources)'),
      'end_year' => $this->t('End Year'),
      'institutions' => $this->t('Institutions'),
      'investigators' => $this->t('Investigators'),
      'objective' => $this->t('Objective'),
      'project_number' => $this->t('Project Number'),
      'source_url' => $this->t('Source URL'),
      'start_year' => $this->t('Start Year'),
      'title' => $this->t('Title'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return ['source_url' => ['type' => 'string', 'max_length' => 2048]];
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return get_class($this);
  }

}
