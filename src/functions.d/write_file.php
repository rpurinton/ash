<?php
$this->functionHandlers['write_file'] = function ($args) {
    if ($this->ash->debug) echo ("debug: write_file(" . $args["path"] . ")\n");
    $path = $args['path'] ?? "";
    $content = $args['content'] ?? null;
    $append = $args['append'] ?? false;
    $owner = $args['owner'] ?? null;
    $group = $args['group'] ?? null;
    $chmod = $args['chmod'] ?? null;
    echo ("$path\n");
    if ($append) $result = file_put_contents($path, $content, FILE_APPEND);
    else $result = file_put_contents($path, $content);
    if (!is_null($owner)) $own_result = chown($path, $owner) ? "yes" : "failed";
    if (!is_null($group)) $grp_result = chgrp($path, $group) ? "yes" : "failed";
    if (!is_null($chmod)) $mod_result = chmod($path, $chmod) ? "yes" : "failed";
    if ($this->ash->debug) echo ("debug: write_file() bytes written: $result\n");
    $result = ["bytes_written" => $result, "owner_set" => $own_result, "group_set" => $grp_result, "chmod_set" => $mod_result];
    return $result;
};
