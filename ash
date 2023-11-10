#!/usr/local/lib/php -f
<?php

namespace Rpurinton\Ash;

require_once(__DIR__ . '/../vendor/autoload.php');

$user_id = shell_exec("whoami");
$working_dir = shell_exec("pwd");
