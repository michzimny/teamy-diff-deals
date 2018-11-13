<?php

require_once('tdd-simple-html-dom.php');

class Protocol {

    private static $translations = array();

    function __construct($prefix, $round, $board) {
        $this->prefix = $prefix;
        $this->round = $round;
        $this->board = $board;
        if (file_exists('translations.json')) {
            static::$translations = json_decode(file_get_contents('translations.json'), TRUE);
        }
        $this->deals_by_tables = array();
        if (!file_exists($this->get_filename())) {
            throw new Exception('file not found: ' . $this->get_filename());
        }
    }

    static function __($string) {
        if (isset(static::$translations[$string])) {
            return static::$translations[$string];
        }
        return $string;
    }

    function get_filename() {
        return '..' . DIRECTORY_SEPARATOR . $this->prefix . $this->round . 'b-' . $this->board . '.html';
    }

    function set_deal($table, $deal) {
        $this->deals_by_tables[$table] = $deal;
    }

    function output() {
        $content = file_get_contents($this->get_filename());

        $dom = str_get_html($content);
        $header_td1 = $dom->find('/html/body/table/tr/td[class="bdcc12"]', 0);
        $header_tr = $header_td1->parent;
        $tr = @$header_tr->next_sibling();
        while($tr) {
            $td = $tr->find('td/a', 0);
            if ($td) {
                $table = trim($td->innertext);
                $table = str_replace('&nbsp;', '', $table);
                $table = (int)$table;
                if($table && array_key_exists($table, $this->deals_by_tables)) {
                    $nextTr = $tr->next_sibling();
                    $contract1 = trim(str_replace('&nbsp;', '', $tr->find('td[class="bdc"]', 0)->innertext));
                    $score1 = trim(str_replace('&nbsp;', '', $tr->find('td', 6)->innertext));
                    $contract2 = trim(str_replace('&nbsp;', '', $tr->next_sibling()->find('td[class="bdc"]', 0)->innertext));
                    $score2 = trim(str_replace('&nbsp;', '', $nextTr->find('td', 5)->innertext));

                    $deal = $this->deals_by_tables[$table];
                    $insert = "<a href=\"#table-$table\"><h4 id=\"table-$table\">" . static::__("Stół") . " $table" . " &ndash; " . static::__("Rozdanie") . " {$deal->deal_num}</h4></a>";
                    // if is played on both tables of a match
                    // note that the contract field for arbitral scores starts with 'A' (e.g. 'ARB' or 'AAA')
                    if(($score1 !== '' || strpos($contract1, 'A') === 0)
                       && ($score2 !== '' || strpos($contract2, 'A') === 0)) {
                        $insert .= $deal->html();
                    } else {
                        $insert .= '<p>...</p>';
                    }

                    $tr->outertext = '<tr class="tdd-header"><td colspan="' . (count($tr->find('td'))-1) . '">' . $insert . '</td></tr>' . $tr->outertext;
                }
            }
            $tr = @$tr->next_sibling();
        }

        $header_tr2 = $header_tr->next_sibling();
        $header_tr->outertext = '';
        $header_tr2->outertext = '';
        $dom->find('/html/body/table/tr', 0)->outertext = '';

        $head = $dom->find('/html/head', 0);
        $head->innertext .= '<link rel="stylesheet" type="text/css" href="css/tdd.css" />'
                          . '<script src="https://code.jquery.com/jquery-3.3.1.min.js" type="text/javascript"></script>'
                          . '<script src="sklady/tdd.js" type="text/javascript"></script>';

        // replacing meta http-equiv refresh with a javascript refresh to preserve hash in the result page
        $meta = $head->find('meta');
        foreach ($meta as $metaTag) {
            if ($metaTag->hasAttribute('http-equiv') && strtolower($metaTag->getAttribute('http-equiv')) == 'refresh') {
                $head->innertext = str_replace($metaTag->outertext, '', $head->innertext) . '<script type="text/javascript">setTimeout(function() { location.reload(); }, ' . ($metaTag->getAttribute('content') * 1000) . ');</script>';
                break;
            }
        }

        print $dom->outertext;
    }

}

class NoSuchDealNumber extends Exception {
}

class Deal {

    function __construct($pbnfile, $num_in_pbn) {
        $this->deal_num = $num_in_pbn;
        $this->_parse($pbnfile, $num_in_pbn);
    }

    function _parse($pbn, $num_in_pbn) {
        $start = strpos($pbn, '[Board "' . $num_in_pbn . '"]');
        if($start === false) {
            throw new NoSuchDealNumber($num_in_pbn);
        }

        $pbn = substr($pbn, $start + 5);
        $stop = strpos($pbn,'[Board "');
        if($stop != false) {
            $pbn = substr($pbn, 0, $stop);
        }

        preg_match('|Dealer "([NESW])"|', $pbn, $m);
        $this->dealer = $m[1];

        preg_match('|Vulnerable "([^"]+)"|', $pbn, $m);
        $this->vuln = $m[1];
        if($this->vuln == 'None') {
            $this->vuln = '-';
        } else if($this->vuln == 'All') {
            $this->vuln = 'Obie';
        }

        preg_match('|Ability "([^"]+)"|', $pbn, $m);
        if($m[1]) {
            $this->ability = explode(' ',$m[1]);
        }

        preg_match('|Minimax "([^"]+)"|', $pbn, $m);
        $this->minimax = $m[1];

        preg_match('|Deal "(N:)?([^"]+)"|', $pbn, $m);
        $this->hands = explode(' ',$m[2]);
    }

    function html() {
        ob_start();
        include('tdd-handrecord-tpl.php');
        return ob_get_clean();
    }

    function format_hand($hand_num) {
        $hand = $this->hands[$hand_num];
        $hand = str_replace('T','10',$hand);
        $suits = explode('.',$hand);
        $str = '<img src="images/S.gif" alt="S" /> '.$suits[0].'<br />';
        $str .= '<img src="images/H.gif" alt="H" /> '.$suits[1].'<br />';
        $str .= '<img src="images/D.gif" alt="D" /> '.$suits[2].'<br />';
        $str .= '<img src="images/C.gif" alt="C" /> '.$suits[3];
        return $str;
    }

    function format_ability($ability_num) {
        $ability = $this->ability[$ability_num];
        $ab = array($ability[0], $ability[2], $ability[3], $ability[4], $ability[5], $ability[6]);
        foreach($ab as $k=>$v) {
            switch($v) {
                case 'A': $ab[$k] = '10'; break;
                case 'B': $ab[$k] = '11'; break;
                case 'C': $ab[$k] = '12'; break;
                case 'D': $ab[$k] = '13'; break;
            }
        }
        return "<td class='an4'>{$ab[0]}</td>
            <td class='an1'>{$ab[1]}</td>
            <td class='an1'>{$ab[2]}</td>
            <td class='an1'>{$ab[3]}</td>
            <td class='an1'>{$ab[4]}</td>
            <td class='an1'>{$ab[5]}</td>";
    }

    function format_minimax() {
        $minimax = $this->minimax;
        $minimax = preg_replace('|^(..)D(.+)|','$1x$2', $minimax);
        $minimax = preg_replace('|^(..)R(.+)|','$1xx$2', $minimax);
        $minimax = preg_replace('|^(.)N(.+)|','$1NT$2', $minimax);
        $minimax = preg_replace('/(\d)([SHDCN])(T?)(x*)([NESW])(.*)/','$1 <img src="images/$2.gif" alt="$2$3" /> $4 $5, $6', $minimax);
        return $minimax;
    }

}

define('TIMESTAMP_FILE', '.tdd-timestamps.cache');
define('RECORDS_FILE', '.tdd-records.cache');

$board_database = unserialize(file_get_contents(RECORDS_FILE));

function load_deals_for_tables($db, $prefix, $round, $board_in_teamy) {
    if (isset($db[$prefix])) {
        if (isset($db[$prefix][$round])) {
            if (isset($db[$prefix][$round][$board_in_teamy])) {
                return $db[$prefix][$round][$board_in_teamy];
            }
        }
    }
    return array();
}

function get_record_files($directory = '.') {
    return glob($directory . DIRECTORY_SEPARATOR . '*.pbn');
}

function get_files_timestamps($files = array()) {
    return array_combine(
        $files,
        array_map('filemtime', $files)
    );
}

function compile_record_database($files, $dbFile) {
    global $board_database;
    $db = array();
    foreach ($files as $filename) {
        $filename = basename($filename);
        $fileParts = array();
        if (preg_match('/^(.*)-r(\d+)-t(\d+)-b(\d+)\.pbn$/', $filename, $fileParts)) {
            $prefix = $fileParts[1];
            if (!isset($db[$prefix])) {
                $db[$prefix] = array();
            }
            $round = (int)($fileParts[2]);
            if (!isset($db[$prefix][$round])) {
                $db[$prefix][$round] = array();
            }
            $table = (int)($fileParts[3]);
            $firstBoard = (int)($fileParts[4]);
            $chunks = preg_split('/(\[Board "(\d+)"\])/', file_get_contents($filename), -1, PREG_SPLIT_DELIM_CAPTURE);
            $boardHeader = '';
            $boardNumber = 1;
            $firstBoardNumber = -1;
            foreach ($chunks as $chunk) {
                $chunk = trim($chunk);
                if (strpos($chunk, '% PBN') > -1) {
                    continue;
                }
                if (strpos($chunk, '[Board ') === 0) {
                    $boardHeader = $chunk;
                    continue;
                }
                if (strpos($chunk, '[') === 0) {
                    try {
                        $deal = new Deal($boardHeader . $chunk, $boardNumber);
                        $boardNumberJFR = $boardNumber + $firstBoard - $firstBoardNumber;
                        if (!isset($db[$prefix][$round][$boardNumberJFR])) {
                            $db[$prefix][$round][$boardNumberJFR] = array();
                        }
                        $db[$prefix][$round][$boardNumberJFR][$table] = $deal;
                    } catch (NoSuchDealNumber $e) {
                        // ignore if the deal does not exist in the file
                    }
                } else {
                    $boardNumber = (int)($chunk);
                    if ($firstBoardNumber < 0) {
                        $firstBoardNumber = $boardNumber;
                    }
                }
            }
        }
    }
    file_put_contents(RECORDS_FILE, serialize($db));
    $board_database = $db;
}

function refresh_board_database() {
    $recordFiles = get_record_files();
    $savedTimestamps = file_exists(TIMESTAMP_FILE) ? json_decode(file_get_contents('.tdd-timestamps.cache'), TRUE) : array();
    $timestamps = get_files_timestamps($recordFiles);

    if (array_diff_assoc($savedTimestamps, $timestamps) || array_diff_assoc($timestamps, $savedTimestamps)) {
        compile_record_database($recordFiles, RECORDS_FILE);
        file_put_contents(TIMESTAMP_FILE, json_encode($timestamps));
    }
}
