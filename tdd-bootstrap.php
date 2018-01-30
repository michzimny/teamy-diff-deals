<?php

require_once('tdd-simple-html-dom.php');

class Protocol {

    function __construct($prefix, $round, $board) {
        $this->prefix = $prefix;
        $this->round = $round;
        $this->board = $board;
        $this->deals_by_tables = array();
        if(!file_exists($this->get_filename())) {
            throw new Exception('file not found: ' . $this->get_filename());
        }
    }

    function get_filename() {
        return $this->prefix . $this->round . 'b-' . $this->board . '.html';
    }

    function set_deal($table, $deal) {
        $this->deals_by_tables[$table] = $deal;
    }

    function output() {
        $content = file_get_contents($this->get_filename());
        $modified = 0;

        $dom = str_get_html($content);
        $header_td1 = $dom->find("/html/body/table/tr/td[class=\"bdcc12\"]", 0);
        $header_tr = $header_td1->parent;
        $tr = @$header_tr->next_sibling();
        while($tr) {
            $td = $tr->find('td/a', 0);
            $table = trim($td->innertext);
            $table = str_replace('&nbsp;', '', $table);
            $table = (int)$table;
            if($table && array_key_exists($table, $this->deals_by_tables)) {
                $contract1 = trim(str_replace('&nbsp;', '', $tr->find('td[class="bdc"]', 0)->innertext));
                $score1 = trim(str_replace('&nbsp;', '', end($tr->find('td'))->innertext));
                $contract2 = trim(str_replace('&nbsp;', '', $tr->next_sibling()->find('td[class="bdc"]', 0)->innertext));
                $score2 = trim(str_replace('&nbsp;', '', end($tr->next_sibling()->find('td'))->innertext));

                $deal = $this->deals_by_tables[$table];
                $insert = "<a href=\"#table-$table\"><h4 id=\"table-$table\">Stół $table &ndash; Rozdanie {$deal->deal_num}</h4></a>";
                // if is played on both tables of a match
                // note that the contract field for arbitral scores starts with 'A' (e.g. 'ARB' or 'AAA')
                if(($score1 !== '' || strpos($contract1, 'A') === 0)
                      && ($score2 !== '' || strpos($contract2, 'A') === 0)) {
                    $insert .= $deal->html();
                    $modified = 1;
                } else {
                    $insert .= '<p>...</p>';
                }

                $tr->outertext = '<tr class="tdd-header"><td colspan="7" style="border-bottom:1px solid #006;padding-top:30px;">' . $insert . '</td></tr>' . $tr->outertext;
            }
            $tr = @$tr->next_sibling();
        }

        if($modified) {
            $header_tr2 = $header_tr->next_sibling();
            $header_tr->outertext = '';
            $header_tr2->outertext = '';
            $dom->find('/html/body/table/tr', 0)->outertext = '';
        }

        $head = $dom->find('/html/head', 0);
        $head->innertext .= '<link rel="stylesheet" type="text/css" href="css/tdd.css" />';

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

    function __construct($filename, $num_in_pbn) {
        $this->deal_num = $num_in_pbn;
        $this->_parse($filename, $num_in_pbn);
    }

    function _parse($filename, $num_in_pbn) {
        $pbn = file_get_contents($filename);
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

function load_deals_for_tables($prefix, $round, $board_in_teamy) {
    $deals_by_tables = array();

    $prefix = preg_quote($prefix);
    $filename_regex = "/$prefix-r$round-t(\d+)-b(\d+).pbn/";
    foreach(scandir('.') as $filename) {
        if(preg_match($filename_regex, $filename, $match)) {
            $file_table = $match[1];
            $file_start_board = $match[2];

            // 1 in teamy -> 1 in pbn; 24 in teamy -> 1 in pbn; 25 in teamy -> 1 in pbn
            $num_in_pbn = $board_in_teamy - $file_start_board + 1;

            try {
                $deal = new Deal($filename, $num_in_pbn);
                $deals_by_tables[$file_table] = $deal;
            } catch (NoSuchDealNumber $e) {
                // ignore if the deal does not exist in the file
            }
        }
    }
    return $deals_by_tables;
}
