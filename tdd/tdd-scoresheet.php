<?php

$html_filename = basename(strtok($_SERVER['REQUEST_URI'],'?'));
// if ".html" is not found at the end of the string, it's a direct call, all mod_rewrite requests do have it
if (substr($html_filename, -5) !== '.html') {
    die('This script cannot be called directly!');
}

require_once('tdd-bootstrap.php');

try {
    $url_parts = detect_url_parts($html_filename);
    if ($url_parts) {
        $prefix = $url_parts[0];
        $round = $url_parts[1];
        $table = $url_parts[2];
        $segment = $url_parts[3];
        $nonTimed = $url_parts[4];
        $scoresheet = new Scoresheet($html_filename, $prefix, $table, $round, $nonTimed);
        $scoresheet->output();
        exit(0);
    }
    $html_filename = '..' . DIRECTORY_SEPARATOR . $html_filename;
    if (!file_exists($html_filename)) {
        throw new Exception();
    }
    // here's the fallback
    readfile($html_filename);
} catch (Exception $e) {
    header('HTTP/1.0 404 Not Found');
    die();
}
