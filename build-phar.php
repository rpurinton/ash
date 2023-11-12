<?php
// The php.ini setting phar.readonly must be set to 0
ini_set('phar.readonly', 0);

// Define the name of the PHAR file
$pharFile = 'ash.phar';

// Create a new PHAR
$phar = new Phar($pharFile, 0, 'ash.phar');

// Start buffering. Mandatory to modify stub to add shebang
$phar->startBuffering();

// Create a default stub to run the application
$defaultStub = $phar->createDefaultStub('ash');

// Add a shebang to the stub
$stub = "#!/usr/bin/env php \n" . $defaultStub;

// Set the stub
$phar->setStub($stub);

// Add all files in the project
$dir = new RecursiveDirectoryIterator(__DIR__);
$iterator = new RecursiveIteratorIterator($dir);
$phar->buildFromIterator($iterator, __DIR__);

// Stop buffering
$phar->stopBuffering();

// Make the file executable
chmod($pharFile, 0755);

echo "PHAR created successfully: $pharFile";
