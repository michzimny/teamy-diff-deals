<?php

$html_filename = basename(strtok($_SERVER['REQUEST_URI'],'?'));
// if ".html" is not found at the end of the string, it's a direct call, all mod_rewrite requests do have it
if (substr($html_filename, -5) !== '.html') {
    die('This script cannot be called directly!');
}

require_once('tdd-bootstrap.php');

try {
    $url_parts = detect_protocol_url_parts($html_filename);
    if ($url_parts) {
        $prefix = $url_parts[0];
        $round = $url_parts[1];
        $board = $url_parts[2];
        $nonTimed = $url_parts[3];

        $protocol = new Protocol($prefix, $round, $board);
        $protocol->set_hide_results($nonTimed);

        $protocol->set_deals();

        echo $protocol->output();
        exit(0);
    }
    $html_filename = '..' . DIRECTORY_SEPARATOR . $html_filename;
    if (!file_exists($html_filename)) {
        throw new Exception();
    }
    // And here's the fallback if it's just a regular protocol
    readfile($html_filename);
} catch (Exception $e) {
    header('HTTP/1.0 404 Not Found');
    die();
}
