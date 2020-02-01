<?php

$html_filename = basename(strtok($_SERVER['REQUEST_URI'],'?'));
// if ".html" is not found at the end of the string, it's a direct call, all mod_rewrite requests do have it
if (substr($html_filename, -5) !== '.html') {
    die('This script cannot be called directly!');
}

require_once('tdd-bootstrap.php');

try {
    $database = new BoardDB();
    $hidePrefixes = get_hide_prefixes();
    $prefixes = array_merge(
        array_keys($database->getDB()),
        $hidePrefixes
    );
    foreach ($prefixes as $prefix) {
        $uri_match = array();
        if (preg_match('/^(' . $prefix . ')(\d+)t(\d+)-(\d+)\.html$/', $html_filename, $uri_match)) {
            $round = intval($uri_match[2]);
            $table = intval($uri_match[3]);
            $segment = intval($uri_match[4]);
            $scoresheet = new Scoresheet($html_filename, $prefix, $table, $round, in_array($prefix, $hidePrefixes));
            $scoresheet->output();
            exit(0);
        }
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
