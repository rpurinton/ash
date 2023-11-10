#!/usr/local/bin/php -f
<?php

namespace Rpurinton\ash;

$options = getopt("", ["version", "help", "license", "credits", "debug"]);

if (isset($options["version"])) die("ash version 0.0.1 rpurinton 2023\n");
if (isset($options["help"])) die(shell_exec("cat " . __DIR__ . "/README.md"));
if (isset($options["license"])) die(shell_exec("cat " . __DIR__ . "/LICENSE"));
if (isset($options["credits"])) die(shell_exec("cat " . __DIR__ . "/CREDITS"));

$debug = isset($options["debug"]);

$uptime = trim(shell_exec("uptime"));
$host_fqdn = trim(shell_exec("hostname"));
$host_name = trim(shell_exec("hostname -s"));
$user_id = trim(shell_exec("whoami"));
$working_dir = trim(shell_exec("pwd"));
$working_folder = basename($working_dir);
$working_folder = $working_folder == "" ? "/" : $working_folder;

if ($debug) {
    echo "uptime: $uptime\nhost_fqdn: $host_fqdn\nhost_name: $host_name\nuser_id: $user_id\nworking_dir: $working_dir\nworking_folder: $working_folder\n";
}

$prompt = "ash# ";

while (true) {
    $input = readline($prompt);
    readline_add_history($input);
    $input = trim($input);
    if ($input == "exit") {
        break;
    }
    $output = shell_exec($input);
    echo $output;
}
