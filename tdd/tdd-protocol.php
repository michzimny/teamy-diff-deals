<?php

require_once('tdd-bootstrap.php');

// parsing URI parts from full request string
$uri = explode('b-', basename(strtok($_SERVER['REQUEST_URI'],'?')));
// if "b-" is not found in the string, it's a direct call, all mod_rewrite requests do have it
if (count($uri) < 2) {
    die('This script cannot be called directly!');
}
// last part of URI is board number
$board = (int)(array_pop($uri));
// the rest is compiled back to separate prefix from round later on
$roundPrefix = implode('b-', $uri);

$database = new BoardDB();
$nonTimedPrefixes = get_hide_prefixes();
$timedPrefixes = array_keys($database->getTimedDB());
$hidePrefixes = array_merge($nonTimedPrefixes, $timedPrefixes);

try {
    // GET parameters pre-parsed by mod_rewrite are used for HTML fallback
    // in case {$prefix}{$round} combo is not matched against board DB
    $protocol = new Protocol($_GET['prefix'], $_GET['round'], $board);
    $html_filename = $protocol->get_filename();
    foreach ($database->getDB() as $prefix => $rounds) {
        foreach ($rounds as $round => $boards) {
            // matching each prefix and round in DB to URI
            if (($prefix . $round === $roundPrefix)) {
                $protocol->set_hide_results(in_array($prefix, $nonTimedPrefixes));
                if (isset($boards[$board])) {
                    foreach($boards[$board] as $table => $deal) {
                        $protocol->set_deal($table, $deal);
                    }
                    echo $protocol->output();
                    exit(0);
                }
            }
        }
    }
    // Maybe prefix is just in result hide mode, without diff-deals
    foreach ($hidePrefixes as $prefix) {
        if (substr($roundPrefix, 0, strlen($prefix)) === $prefix) {
            // If it's in non-timed mode, force hide_results
            // Otherwise, timed hide will kick in
            $protocol->set_hide_results(in_array($prefix, $nonTimedPrefixes));
            echo $protocol->output();
            exit(0);
        }
    }
    // And here's the fallback if it's just a regular protocol
    readfile($html_filename);
} catch (Exception $e) {
    header('HTTP/1.0 404 Not Found');
    die();
}
