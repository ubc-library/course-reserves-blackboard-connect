var student_home_reload = false;

if ((window.document.location.toString().indexOf("/open/") != -1)) {

    var res = window.document.location.toString().split("/open/");
    var id = 'reading-' + res[1];
    var testExists = document.getElementById(id);

    if (testExists === null) {
        alert('this reading is not available anymore');
    }
    else {
        id = '#'.concat(id);
        displayDialog(id)
    }
}

var readingsTable;
var tag = '';

function clearFilters() {
    tag = '';
    tag_display = '';

    readingsTable.fnDraw();
    $('#filter-indicator-wrapper').css('display', 'none');
    $('.filter-table').each(function () {
        $(this).removeClass('active-tag');
    });
}

$(document).ready(function () {
    //filterByTag(tag);
    invokeDataTable();

    $('#subscribe-toggle').on('click', function (e) {

        e.preventDefault();
        e.stopPropagation();

        var action = $(this).val();
        var disaction = 'Subscribe';

        if (action == 'Subscribe') {
            disaction = 'Unsubscribe';
        }

        $.get(
            '/passtolicr.php',
            {
                command: action,
                puid: puid,
                course: course
            },
            function (data) {
                if (!data.success) {
                    alert('You do not have an email address configured. Please contact Connect support to update your account.')
                }
                else {
                    if (data.data == 1) {
                        if (disaction == 'Subscribe') {
                            $('#subscribe-toggle').removeClass('btn-inverse').addClass('btn-warning').val('Subscribe').text('Subscribe');
                            $('#subscription-status').removeClass('label-success').addClass('label-important').text('UNSUBSCRIBED');
                            $('#subscription-not').text('not');
                        }
                        else {
                            $('#subscribe-toggle').removeClass('btn-warning').addClass('btn-inverse').val('Unsubscribe').text('Unsubscribe');
                            $('#subscription-status').removeClass('label-important').addClass('label-success').text('SUBSCRIBED');
                            $('#subscription-not').text('');
                        }
                    } else {
                        alert('Failed to ' + action + '.');
                    }
                }
            },
            'json');
        return true;
    });

    $('.show-details').on('click', function (e) {
        e.preventDefault();
        var id = '#reading-' + $(this).attr('href');
        displayDialog(id);
        return true;
    });

    if (showtag) {
        $('a[data-tag=' + showtag + ']').click();
    }

});

function displayDialog(id) {
    var dialogHeight = window.innerHeight ? window.innerHeight : $(window).height();
    dialogHeight -= 50;
    $(id).dialog({
        modal: true,
        width: "50%",
        maxHeight: dialogHeight,
        draggable: false,
        resizable: false,
        open: function (event, ui) {
            $('.ui-widget-overlay').bind('click', function () {
                $(this).siblings('.ui-dialog').find('.ui-dialog-content').dialog('close');
            });
            $('body').css('overflow-y', 'hidden');
        },
        close: function () {
            if (student_home_reload) {
                student_home_reload = false;
                location.reload();
            }
            $('body').css('overflow-y', 'visible');
        }
    });
}

function printReadingList() {
    var fr;
    $('#student-readings').css({width: '100%'});
    if (navigator.appName.indexOf('Internet Explorer') > 0) {
        ieprint();
    } else {
        if (window.frames['contentFrame']) {
            fr = window.frames['contentFrame'];
            fr.focus();
            fr = fr.contentDocument || fr;
            fr.print();
        } else {
            console.log("here");
            // readingsTable.destroy();
            window.print();
        }
    }
}

function ieprint() {
    window.open('/studenthome/id/' + course_id + '/print/1');
}

function ieprint2() {
    print();
    alert('Press OK to close this window.');
    window.close();
}

// function revokeDataTable() {
//     var tables = $.fn.dataTable.fnTables(true);
//     $(tables).each(function () {
//         $(this).dataTable().fnDestroy();
//     });
// }

function invokeDataTable() {
    readingsTable = $('#student-readings').DataTable({
        "bPaginate": false,
        "bLengthChange": false,
        "aoColumns": [
            {"bSortable": true},
            {"bSortable": true},
            {"bSortable": true},
            {"bSortable": true},
            {"bSortable": true},
            {"bSortable": true},
            {"bSortable": true},
            {"bSortable": true},
            {"bSortable": true}
        ],
        "bJQueryUI": true,
        "columnDefs": [
            { "aTargets": [ 5 ], "bSortable": false }
        ]
    });

    $('.filter-table').on('click', function () {
        clearFilters();
        // convert to html (table redraw scrapes html from <td> to do the compare)
        // remove all whitespaces, then make it lower case
        tag_display = this.getAttribute('data-tag');
        tag = ($('<div />').text(this.getAttribute('data-tag')).html()).replace(/\s+/g, '').toLowerCase();

        //console.log(tag);

        $(this).addClass('active-tag');
        $('#filter-indicator').text('#' + $(this).text());
        readingsTable.fnDraw();
        $('#filter-indicator-wrapper').css('display', 'block');

        $('.filter-table').each(function () {
            if ((($('<div />').text(this.getAttribute('data-tag')).html()).replace(/\s+/g, '').toLowerCase()) == tag) {
                $(this).addClass('active-tag');
            }
        });
    });
}


$.fn.dataTableExt.afnFiltering.push(
    function (oSettings, aData, iDataIndex) {
        var matched = false;
        var regex;
        var testString;
        var match;
        var tags;

        if (tag == '') {
            return true;
        }
        else {
            regex = new RegExp('data-tag="([a-zA-Z0-9]{0,})"', "gi");
            testString = aData[7].toString().replace(/\s+/g, '').toLowerCase();
            tags = '';
            while ((match = regex.exec(testString)) != null) {
                tags = tags.concat(match[1] + ",");
                if (match.index === regex.lastIndex) {
                    matched = true;
                    ++regex.lastIndex;
                }
            }
            tags = tags.replace(/,+$/, '');
            return (tags.indexOf(tag) != -1);
        }
    }
);

$('#sh-tag-cloud').click(function () {
    $('#tag-cloud').slideToggle();
});

$(function () {
    if (printBool) {
        ieprint2();
    }
});


$('#clear_filter_button').on('click', function (e) {
    e.preventDefault();
    clearFilters();
    return true;
});

function setHeight(outerId, innerId) {
    var myHeight = 0;
    if (typeof (window.innerWidth) == 'number') {
        myHeight = window.innerHeight;
    } else if (document.documentElement && (document.documentElement.clientWidth || document.documentElement.clientHeight)) {
        myHeight = document.documentElement.clientHeight;
    } else if (document.body && (document.body.clientWidth || document.body.clientHeight)) {
        myHeight = document.body.clientHeight;
    }
    $(outerId).css('height', myHeight - 100);
    $(innerId).css('height', myHeight - 186);
}
