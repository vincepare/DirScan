<?php
/**
 * This file is part of DirScan.
 *
 * PHPUnit bootstrap
 */

error_reporting(E_ALL);

// OS check
if (preg_match('/^(WIN32|WINNT|Windows)$/', PHP_OS)) {
    file_put_contents('php://stderr', "Please run tests on a Unix environment\n");
}

// Setup test environment
$wd = getcwd();
chdir(__DIR__);
if (!is_executable('setup.sh') && !chmod('setup.sh', 0555)) {
    trigger_error("Please make setup.sh executable", E_USER_ERROR);
}
exec('./setup.sh', $output, $status);
$success = is_dir('sandbox');
chdir($wd);
if (!$success || $status !== 0) {
    trigger_error("Environment setup failed", E_USER_ERROR);
}

// Set sandbox directory
$_ENV['DIRSCAN_TEST_SANDBOX'] = __DIR__ . DIRECTORY_SEPARATOR . 'sandbox';

// Load
require __DIR__.'/../src/DirScan.php';
require __DIR__.'/../src/Reporter.php';
require __DIR__.'/../src/TestReporter.php';
