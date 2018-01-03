<?php

namespace Drupal\fsrio_rpd_importer\Plugin\migrate\source;

use Drupal\fsrio_rpd_importer\HtmlParserTrait;

/**
 * Source plugin for importing ARS research projects.
 *
 * @MigrateSource(
 *   id = "ars_project"
 * )
 */
class ArsImporter extends ImporterBase {

  use HtmlParserTrait;

  /**
   * The source site's base URL.
   *
   * Do not include a trailing slash so that it can be prepended to relative
   * URLs.
   */
  const BASE_URL = 'https://www.ars.usda.gov';

  /**
   * The URL of the source's list of projects.
   */
  const SOURCE_URL = 'https://www.ars.usda.gov/research/project-list-by-program/?npCode=108&filter=yes&projectStatus=A&projectType=&filterLocation=&showAllProjects=N&sortBy=L&submitFilter=Filter';

  /**
   * Override the projectUrls during testing for faster file access.
   *
   * @var string[]
   */
  protected $projectUrls = ['test.html'];

  /**
   * {@inheritdoc}
   */
  protected function getSourceUrl() {
    return self::SOURCE_URL;
  }

  /**
   * {@inheritdoc}
   */
  protected function parseProjectListPage() {
    $urls = [];

    /** @var \DOMNode $anchor */
    foreach ($this->document->getElementsByTagName('a') as $anchor) {
      $href = $anchor->attributes->getNamedItem('href')->nodeValue;
      if ($href !== NULL && substr($href, 0, 26) == '/research/project/?accnNo=') {
        $urls[] = self::BASE_URL . $href;
      }
    }

    // The ARS project list contains some duplicate entries.  Filter those out.
    return array_unique($urls);
  }

  /**
   * {@inheritdoc}
   */
  protected function parseProjectPage() {
    $project = [];

    // Most of the project content is contained in the main content div.
    /** @var \DOMNode $div */
    foreach ($this->document->getElementsByTagName('div') as $div) {
      $class = $div->attributes->getNamedItem('class')->nodeValue;
      $value = trim($div->nodeValue);
      if ($class == 'usa-width-three-fourths usa-layout-docs-main_content') {
        $project += $this->parseMainContent($div);
      }
    }

    var_dump($project);
    return $project;
  }

  /**
   * Parses the main content div of ARS project pages.
   *
   * @param \DOMNode $div
   *   The main content div.
   *
   * @return array
   *   An array of the parsed project data.
   */
  protected function parseMainContent(\DOMNode $div) {
    $data = [];

    $data['title'] = $this->parseChildNodes($div, [2, 2]);

    $project_number = $this->parseChildNodes($div, [4, 3, 0]);
    // Trim 'Project Number: ' from the front of the string.
    $data['project_number'] = substr($project_number, 16);

    $start_year = $this->parseChildNodes($div, [6, 1, 1, 0]);
    // Trim the year value from the end of the string.
    $data['start_date'] = substr($start_year, -4);

    $end_year = $this->parseChildNodes($div, [6, 1, 1, 2]);
    // Trim the year value from the end of the string.
    $data['end_date'] = substr($end_year, -4);

    $data['objective'] = $this->parseChildNodes($div, [8, 6]);

    return $data;
  }

}