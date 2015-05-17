<?php
/**
 * This file is part of DirScan.
 *
 * Package the app into a phar archive
 * @package DirScan
 */

$srcRoot = __DIR__.'/../src';

$phar = new Phar(__DIR__.'/dirscan.phar', 0, 'dirscan.phar');
$phar->startBuffering();

// Removing shebang from dirscan
$dirscan = file_get_contents($srcRoot.'/dirscan');
$dirscan = preg_replace('/^#!.*?[\n\r]+/', '', $dirscan);

// Adding files
$phar['dirscan'] = $dirscan;
$phar['DirScan.php'] = file_get_contents($srcRoot.'/DirScan.php');
$phar['Reporter.php'] = file_get_contents($srcRoot.'/Reporter.php');
$phar['CliReporter.php'] = file_get_contents($srcRoot.'/CliReporter.php');

// Get the stub
$defaultStub = $phar->createDefaultStub('dirscan');
$stub = '#!/usr/bin/env php'."\n".$defaultStub;
$phar->setStub($stub);

$phar->stopBuffering();
echo "Build done (dirscan.phar)"."\n";
