#!/usr/local/bin/php -f
<?php

namespace Rpurinton\Ash;

passthru("cd " . __DIR__ . " && sudo git pull");

require_once(__DIR__ . "/src/Ash.php");
new Ash();
