<?php

require_once('tdd-simple-html-dom.php');

function filename_from_url($url) {
    return '..' . DIRECTORY_SEPARATOR . $url;
}

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
        return filename_from_url($this->prefix . $this->round . 'b-' . $this->board . '.html');
    }

    function set_deal($table, $deal) {
        $this->deals_by_tables[$table] = $deal;
    }

    function findByID($id) {
        foreach ($this->deals_by_tables as $deal) {
            if ($deal->id === $id) {
                return $deal;
            }
        }
        return NULL;
    }

    function getTablesByID($id) {
        $tables = array();
        foreach ($this->deals_by_tables as $table => $deal) {
            if ($deal->id === $id) {
                $tables[] = $table;
            }
        }
        return $tables;
    }

    public static function areBoardsPlayed($boards) {
        // if somehow the default board hand record is not meant to be played on any table, don't reveal it
        if (!$boards) {
            return FALSE;
        }
        foreach ($boards as $board) {
            $dom = str_get_html($board);
            // score is the 6th cell for some rows, 7th cell for the others, depending if it's open room or closed
            $isFirstRow = count($dom->find('td/a'));
            $score = trim(str_replace('&nbsp;', '', $dom->find('td', 5 + $isFirstRow)->innertext));
            $contract = trim(str_replace('&nbsp;', '', $dom->find('td[class="bdc"]', 0)->innertext));
            // contract field for arbitral scores starts with 'A' (e.g. 'ARB' or 'AAA')
            if ($score == '' && (!strlen($contract) || $contract[0] != 'A')) {
                return FALSE;
            }
        }
        return TRUE;
    }

    function output() {
        $content = file_get_contents($this->get_filename());

        $dom = str_get_html($content);
        // if there's no hand record ("Don't send boards" or a hollow frame), just passthru the original file
        if (!count($dom->find('h4'))) {
            echo $content;
            return;
        }
        $header_td1 = $dom->find('/html/body/table/tr/td[class="bdcc12"]', 0);
        $header_tr = $header_td1->parent;
        $tr = @$header_tr->next_sibling();
        $columnCount = 0;
        $groupedBoards = array('default' => array());
        while ($tr) {
            $td = $tr->find('td/a', 0);
            if ($td) {
                $columnCount = max($columnCount, count($tr->find('td'))); // counting columns to set correct colspan later on
                $table = trim($td->innertext);
                $table = str_replace('&nbsp;', '', $table);
                $table = (int)$table;
                if ($table) {
                    if (array_key_exists($table, $this->deals_by_tables)) {
                        // add table rows to specific board record
                        if (!isset($groupedBoards[$this->deals_by_tables[$table]->id])) {
                            $groupedBoards[$this->deals_by_tables[$table]->id] = array();
                        }
                        $groupedBoards[$this->deals_by_tables[$table]->id][] = $tr->outertext;
                        $groupedBoards[$this->deals_by_tables[$table]->id][] = $tr->next_sibling()->outertext;
                        // remove these rows from the default board record protocol
                        $tr->outertext = '';
                        $tr->next_sibling()->outertext = '';
                    } else {
                        // add table rows to default board record
                        $groupedBoards['default'][] = $tr->outertext;
                        $groupedBoards['default'][] = $tr->next_sibling()->outertext;
                    }
                }
            }
            $tr = @$tr->next_sibling();
        }

        $table = $dom->find('/html/body/table', 0);
        $table->find('tr', 0)->class = 'tdd-header'; // marking default header as navigable header for JS
        foreach ($groupedBoards as $boardId => $groupedBoard) {
            if ($boardId === 'default') {
                // there are no tables for default hand record, clear the default table entirely (strip headers, footers etc.)
                if (!$groupedBoard) {
                    $table->innertext = '';
                    continue;
                }
                $innerTable = $table->find('td/table', 0);
                $rows = $innerTable->find('tr');
                $firstRow = array_shift($rows); // board record header (with the board number)
                $dealNumber = array();
                // replace board number header to make it consistent with other protocols
                // and mark it as hyperlink hash target
                if (preg_match('/#(\d+)/', $firstRow->find('h4', 0)->innertext, $dealNumber)) {
                    $firstRow->innertext = '<td><a href="#table-0"><h4 id="table-0">' . static::__("Rozdanie") . ' ' . $dealNumber[1] . '</h4></a></td>';
                }
                // remove all other rows (actual layout and DD data) if the default board has not been played on all tables
                if (!self::areBoardsPlayed($groupedBoard)) {
                    foreach ($rows as $row) {
                        $row->outertext = '';
                    }
                    $innerTable->innertext = trim($innerTable->innertext) . '<tr><td><p>...</p></td></tr>';
                }
            } else {
                $deal = $this->findByID($boardId);
                if ($deal) {
                    $tables = $this->getTablesByID($boardId);
                    sort($tables);
                    // compile header with tables numbers
                    $insert = '<a href="#table-' . $tables[0] . '"><h4 id="table-' . $tables[0] . '">';
                    if (count($tables) <= 5) {
                        $insert .= static::__("Stół") . ' ' . implode(', ', $tables);
                    } else {
                        $insert .= count($tables) . ' ' . static::__("stołów");
                    }
                    $insert .= ' &ndash; ';
                    $insert .= static::__("Rozdanie") . ' ' . $deal->deal_num . '</h4></a>';
                    // if the board has been played on all tables
                    if (self::areBoardsPlayed($groupedBoard)) {
                        $insert .= $deal->html();
                    } else {
                        $insert .= '<p>...</p>';
                    }
                    $table->innertext .= '<tr class="tdd-header"><td colspan="' . ($columnCount+1) . '">' . $insert . '</td></tr>';
                    $table->innertext .= implode('', $groupedBoard);
                }
            }
        }

        // append JS and CSS
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
        // identify deal by it's hash in case there are duplicate PBN files
        $this->id = md5($pbnfile);
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

class BoardDB {

    private $__timestampFile = '.tdd-timestamps.cache';
    private $__dbFile = '.tdd-records.cache';
    private $__database = array();

    public function __construct($timestampFile = '.tdd-timestamps.cache', $dbFile = '.tdd-records.cache') {
        $this->__timestampFile = $timestampFile;
        $this->__dbFile = $dbFile;
        if (file_exists($this->__dbFile)) {
            $this->__database = unserialize(file_get_contents($this->__dbFile));
        }
        $this->refreshBoardDatabase();
    }

    public function getDB() {
        return $this->__database;
    }

    private function __getRecordFiles($directory = '.') {
        return glob($directory . DIRECTORY_SEPARATOR . '*.pbn');
    }

    private function __getFilesTimestamps($files = array()) {
        // dictionary to keep track of PBN modification files
        return array_combine(
            $files,
            array_map('filemtime', $files)
        );
    }

    private function __compileRecordDatabase($files, $dbFile) {
        $this->__database = array();
        foreach ($files as $filename) {
            $filename = basename($filename);
            $fileParts = array();
            if (preg_match('/^(.*)-r(\d+)-t([0-9,-]+)-b(\d+)\.pbn$/', $filename, $fileParts)) {
                // tournament prefix
                $prefix = $fileParts[1];
                if (!isset($this->__database[$prefix])) {
                    $this->__database[$prefix] = array();
                }
                // round number
                $round = (int)($fileParts[2]);
                if (!isset($this->__database[$prefix][$round])) {
                    $this->__database[$prefix][$round] = array();
                }
                // interpret table numbers from possible ranges
                $tableString = $fileParts[3];
                $tables = array();
                // multiple ranges are separate by a comma
                foreach (explode(',', $tableString) as $tableSets) {
                    // each range may be a single value or actual range
                    $tableDelimiters = array_filter(explode('-', $tableSets));
                    // if it's a range, add every number from that range
                    if (count($tableDelimiters) > 1) {
                        for ($table = (int)($tableDelimiters[0]); $table <= (int)($tableDelimiters[1]); $table++) {
                            $tables[] = $table;
                        }
                    } else { // otherwise, add single value
                        $tables[] = (int)($tableDelimiters[0]);
                    }
                }
                $firstBoard = (int)($fileParts[4]);
                // split PBN file to single-board chunks
                $chunks = preg_split('/(\[Board "(\d+)"\])/', file_get_contents($filename), -1, PREG_SPLIT_DELIM_CAPTURE);
                $boardHeader = '';
                $boardNumber = 1;
                $firstBoardNumber = -1;
                foreach ($chunks as $chunk) {
                    $chunk = trim($chunk);
                    // PBN header (first chunk of the file) is ignored
                    if (strpos($chunk, '% PBN') > -1) {
                        continue;
                    }
                    // current chunk is a delimiter, store it to concatenate to board information
                    if (strpos($chunk, '[Board ') === 0) {
                        $boardHeader = $chunk;
                        continue;
                    }
                    // current chunk is proper board information
                    if (strpos($chunk, '[') === 0) {
                        try {
                            $deal = new Deal($boardHeader . $chunk, $boardNumber);
                            $boardNumberJFR = $boardNumber + $firstBoard - $firstBoardNumber;
                            if (!isset($this->__database[$prefix][$round][$boardNumberJFR])) {
                                $this->__database[$prefix][$round][$boardNumberJFR] = array();
                            }
                            foreach ($tables as $table) {
                                $this->__database[$prefix][$round][$boardNumberJFR][$table] = $deal;
                            }
                        } catch (NoSuchDealNumber $e) {
                            // ignore if the deal does not exist in the file
                        }
                    } else { // we've captured board number, store it until next iteration, when proper board chunk comes
                        $boardNumber = (int)($chunk);
                        // store first number of the file to calculate proper board number offset
                        if ($firstBoardNumber < 0) {
                            $firstBoardNumber = $boardNumber;
                        }
                    }
                }
            }
        }
        file_put_contents($this->__dbFile, serialize($this->__database));
    }

    public function refreshBoardDatabase() {
        $recordFiles = $this->__getRecordFiles();
        $savedTimestamps = file_exists($this->__timestampFile) ? json_decode(file_get_contents($this->__timestampFile), TRUE) : array();
        $timestamps = $this->__getFilesTimestamps($recordFiles);

        // if any of the files changed, regenerate board database
        if (array_diff_assoc($savedTimestamps, $timestamps) || array_diff_assoc($timestamps, $savedTimestamps)) {
            $this->__compileRecordDatabase($recordFiles, $this->__dbFile);
            file_put_contents($this->__timestampFile, json_encode($timestamps));
        }
    }
}
