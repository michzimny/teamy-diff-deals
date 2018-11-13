<?php

require_once('tdd-bootstrap.php');

// parsing URI parts from full request string
$uri = explode('b-', basename($_SERVER['REQUEST_URI']));
if (count($uri) < 2) {
    die('This script cannot be called directly!');
}

$board = (int)(array_pop($uri));
$roundPrefix = implode('b-', $uri);

try {
    $database = new BoardDB();
    // GET parameters pre-parsed by mod_rewrite are used for HTML fallback
    // in case {$prefix}{$round} combo is not matched against board DB
    $protocol = new Protocol($_GET['prefix'], $_GET['round'], $board);
    $html_filename = $protocol->get_filename();
    foreach ($database->getDB() as $prefix => $rounds) {
        foreach ($rounds as $round => $boards) {
            if ($prefix . $round === $roundPrefix) {
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
    readfile($html_filename);
} catch (Exception $e) {
    header('HTTP/1.0 404 Not Found');
    die();
}
