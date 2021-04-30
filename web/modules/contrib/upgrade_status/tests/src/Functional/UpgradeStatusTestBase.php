<?php

namespace Drupal\Tests\upgrade_status\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Defines shared functions used by some of the functional tests.
 */
abstract class UpgradeStatusTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'upgrade_status',
    'upgrade_status_test_error',
    'upgrade_status_test_9_compatible',
    'upgrade_status_test_10_compatible',
    'upgrade_status_test_submodules_a',
    'upgrade_status_test_submodules_with_error',
    'upgrade_status_test_contrib_error',
    'upgrade_status_test_contrib_9_compatible',
    'upgrade_status_test_theme_functions',
    'upgrade_status_test_twig',
    'upgrade_status_test_library',
    'upgrade_status_test_library_exception',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->container->get('theme_installer')->install(['upgrade_status_test_theme']);
  }

  /**
   * Perform a full scan on all test modules.
   */
  protected function runFullScan() {
    $edit = [
      'scan[data][list][upgrade_status_test_error]' => TRUE,
      'scan[data][list][upgrade_status_test_9_compatible]' => TRUE,
      'scan[data][list][upgrade_status_test_10_compatible]' => TRUE,
      'scan[data][list][upgrade_status_test_submodules]' => TRUE,
      'scan[data][list][upgrade_status_test_submodules_with_error]' => TRUE,
      'scan[data][list][upgrade_status_test_twig]' => TRUE,
      'scan[data][list][upgrade_status_test_theme]' => TRUE,
      'scan[data][list][upgrade_status_test_theme_functions]' => TRUE,
      'scan[data][list][upgrade_status_test_library]' => TRUE,
      'scan[data][list][upgrade_status_test_library_exception]' => TRUE,
      'collaborate[data][list][upgrade_status_test_contrib_error]' => TRUE,
      ($this->getDrupalCoreMajorVersion() < 9 ? 'relax' : 'collaborate') . '[data][list][upgrade_status]' => TRUE,
      ($this->getDrupalCoreMajorVersion() < 9 ? 'relax' : 'collaborate') . '[data][list][upgrade_status_test_contrib_9_compatible]' => TRUE,
    ];
    $this->drupalPostForm('admin/reports/upgrade-status', $edit, 'Scan selected');
  }

  /**
   * Returns current core's major version.
   *
   * @return int
   *   Version converted to int.
   */
  protected function getDrupalCoreMajorVersion(): int {
    return (int) \Drupal::VERSION;
  }

}
