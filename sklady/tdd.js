var TDD = {

    rowSelector: 'body > table > tbody > tr',
    rowHeaderClass: 'tdd-header',

    findTable: function(element) {
        var row = element.closest(TDD.rowSelector);
        var headerRow = row;
        while (headerRow.length && !headerRow.hasClass(TDD.rowHeaderClass)) {
            headerRow = headerRow.prev(TDD.rowSelector);
        }
        var rows = [];
        if (headerRow) {
            rows.push(headerRow[0]);
            headerRow = headerRow.next(TDD.rowSelector);
            while (headerRow.length && !headerRow.hasClass(TDD.rowHeaderClass)) {
                rows.push(headerRow[0]);
                headerRow = headerRow.next(TDD.rowSelector);
            }
        }
        return $(rows);
    },

    highlightTable: function(elem) {
        TDD.findTable(elem).addClass('specified');
    },

    hoverTable: function(ev) {
        TDD.findTable($(ev.currentTarget)).addClass('hovered');
    },

    unhoverTable: function(ev) {
        TDD.findTable($(ev.currentTarget)).removeClass('hovered');
    },

    switchTable: function(ev) {
        var header = TDD.findTable($(ev.currentTarget)).find('h4[id]');
        location.hash = header.attr('id');
        ev.stopPropagation();
    },

    lastRefererNumber: undefined,
    detectReferer: function() {
        var regex = document.referrer.match(/\d+t(\d+)-\d+\.htm/);
        if (regex) {
            if (regex[1] != TDD.lastRefererNumber) {
                TDD.lastRefererNumber = regex[1];
                return regex[1];
            }
        }
        return undefined;
    },

    detectTable: function(hash) {
        var regex = hash.match(/^#table-(\d+)$/);
        if (regex) {
            if (regex[1] === '0') {
                return $(hash);
            }
            var links = $('a.b');
            for (var link in links) {
                link = $(links[link]);
                var linkText = link.text().trim();
                if (linkText === regex[1]) {
                    return link.closest('tr').prevAll('.' + TDD.rowHeaderClass + ':first').find('h4[id]');
                }
            }
        }
        return [];
    },

    bindEvents: function() {
        $('tr').hover(TDD.hoverTable, TDD.unhoverTable);
        $('tr').click(TDD.switchTable);
        $(window).on('hashchange', function() {
            var table = TDD.detectTable(location.hash);
            if (table.length) {
                if (location.hash != '#' + $(table).attr('id')) {
                    location.hash = '#' + $(table).attr('id');
                }
                $('.specified').removeClass('specified');
                TDD.highlightTable(table);
            } else {
                var tableNo = TDD.detectReferer();
                if (tableNo) {
                    location.hash = '#table-' + tableNo;
                } else {
                    $('h4[id]').each(function() { TDD.highlightTable($(this)); });
                }
            }
        });
    }

};

$(document).ready(function() {
    TDD.bindEvents();
    $(window).trigger('hashchange');
});
