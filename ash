#!/usr/bin/env php
<?php

namespace Rpurinton\Ash;

@unlink(__DIR__ . '/debug-prompt.json');
@unlink(__DIR__ . '/debug-response.json');

require_once __DIR__ . '/vendor/autoload.php';
(new Ash())->start();
