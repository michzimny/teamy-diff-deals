<?php

$prefix = $_GET['prefix'];
$round = (int)$_GET['round'];
$board = (int)$_GET['board'];

require_once('tdd-bootstrap.php');

$protocol = new Protocol($prefix, $round, $board);

// security check
$html_filename = $protocol->get_filename();
$len = strlen($html_filename);
$request_uri_ending = substr($_SERVER['REQUEST_URI'], -$len-1);
if($request_uri_ending != '/' . $html_filename) {
    die('This script cannot be called directly!');
}
//

$deals_by_tables = load_deals_for_tables($prefix, $round, $board);
if (count($deals_by_tables) > 0) {
    foreach($deals_by_tables as $table => $deal) {
        $protocol->set_deal($table, $deal);
    }
    echo $protocol->output();
}
else {
    readfile($html_filename);
}
