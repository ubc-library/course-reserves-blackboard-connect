var crMenu = (function () {
    // Instance stores Singleton reference
    var instance, serverURI, debug;
    debug = false; // Todo SKK: Set back to false;

    if (debug) {
        console.log("Disable debug in js/addreserves/addreserves.js");
    }

    function init() {
        // Private
        if (debug) {
            console.log("Need to pass an init variable in here");
        }
        var uri = serverURI + "/js/addreserves/";


        function getInitJSON(type) {
            if (debug) {
                console.log("Getting json: " + type);
            }
            var jsonURL = uri + type + ".json";
            var json = {};
            $.ajax({
                    dataType: "json",
                    url: jsonURL,
                    async: false
                })
                .done(function (data) {
                    json = data;
                })
                .fail(function () {
                    alert("error");
                });
            return json;
        }

        function _getSearchForm(options) {
            this.html = undefined;
            this.form_type = undefined;
            this.type = undefined;
            this.disp = undefined;
            this.interstitial = undefined;
            this.noLoanPeriods = undefined;
            this.submit_object = undefined;

            var input_span = 'span9';

            this.fields = getInitJSON("search_fields");
            this.machine_names = getInitJSON("search_machine_names");
            this.placeholders = getInitJSON("search_placeholders");

            this.html = '';

            var key;


            if (options.groupdata && options.groupdata.length) {
                this.html = this.html.concat('<p>Use one of the search groups below to find an Item.</p>');
                var i = 0;
                for (i; i < options.groupdata.length; i += 1) {
                    if (Object.keys(options.groupdata[i].fields).length > 1) {
                        this.html = this.html.concat('<p><b>' + options.groupdata[i].title + '</b></p>');
                        this.html = this.html.concat('<div class="row-fluid"><div class="span12">');

                        for (key in options.groupdata[i].fields) {
                            if (options.groupdata[i].fields.hasOwnProperty(key)) {
                                this.fields[key] = 1;
                                this.html = this.html.concat('<input class="' + input_span + ' stored" type="text" id="ubc_id_' + this.machine_names[key] + '" name="' + this.machine_names[key] + '" value="" placeholder="' + (this.placeholders[key] || options.groupdata[i].fields[key]) + '">');
                            }
                        }
                        this.html = this.html.concat('</div></div>');
                    } else {
                        for (key in options.groupdata[i].fields) {
                            if (options.groupdata[i].fields.hasOwnProperty(key)) {
                                this.fields[key] = 1;
                                if (i === 0) {
                                    this.html = this.html.concat('<p><b>' + options.groupdata[i].fields[key] + '</b></p>');
                                } else {
                                    this.html = this.html.concat('<p><b>OR:  ' + options.groupdata[i].fields[key] + '</b></p>');
                                }
                                this.html = this.html.concat('<div class="row-fluid"><div class="span12"><input class="' + input_span + ' stored" type="text" id="ubc_id_' + this.machine_names[key] + '" name="' + this.machine_names[key] + '" value="" placeholder="' + (this.placeholders[key] || options.groupdata[i].fields[key]) + '"></div></div>');
                            }
                        }
                    }
                }
            } else {
                for (key in options.fields) {
                    if (options.fields.hasOwnProperty(key)) {
                        this.fields[key] = 1;
                        this.html = this.html.concat('<p><b>OR:  ' + options.fields[key] + '</b></p>');
                        this.html = this.html.concat('<div class="row-fluid"><div class="span12"><input class="' + input_span + ' stored" type="text" id="ubc_id_' + this.machine_names[key] + '" name="' + this.machine_names[key] + '" value="" placeholder="' + (this.placeholders[key] || options.fields[key]) + '"></div></div>');
                    }
                }
            }

            this.html = this.html.replace(/<b>OR<\/b>$/im, '<br/>');
            this.html = this.html.concat('<input type="hidden" id="ubc_id_type" name="type" value="' + options.type + '">');

            this.form_type = options.direct_type;
            this.type = options.type;

            return {
                html: this.html,
                form_type: this.form_type,
                type: this.type
            };
        }

        //public methods granted through init()
        return {

            destroy: function () {
                var prop;
                var props = ["html", "type", "disp", "interstitial", "noLoanPeriods", "submit_object"];
                for (prop in props) {
                    if (props.hasOwnProperty(prop)) {
                        try {
                            delete this.prop;
                        } catch (e) {
                            if (debug) {
                                console.log("could not delete: " + prop);
                            }
                            try {
                                delete this[prop];
                            } catch (f) {
                            }
                        }
                    }
                }
                this.html = undefined;
                this.form_type = undefined;
                this.type = undefined;
                this.disp = undefined;
                this.interstitial = undefined;
                this.noLoanPeriods = undefined;
                this.submit_object = undefined;
            },

            getSearchForm: function (constructor) {

                this.html = undefined;
                this.form_type = undefined;
                this.type = undefined;
                this.disp = undefined;
                this.interstitial = undefined;
                this.noLoanPeriods = undefined;
                this.submit_object = undefined;

                var x = _getSearchForm(constructor);

                if (debug) {
                    console.log("getSearchForm: ");
                    console.log(x);
                }

                this.html = x.html;
                this.form_type = x.form_type;
                this.type = x.type;
            },

            getResultHTML: function (data) {
                var _index = 0;
                var entry;
                var stripe;
                var j;
                var html = '';
                console.log("Result data");
                console.log(data);
                html = html.concat('<div class="content"><ul>');
                for (j = 0; j < data.length; j++) {
                    stripe = (j % 2 === 0) ? 'odd' : 'even';
                    entry = data[j];


                    var dataString = 'data-result-index="' + (_index++) + '" ';
                    html += '<li class="' + stripe + '">';

                    if (entry.form_type === 'pdf_chapter' || entry.form_type === 'ebook_chapter' || entry.form_type === 'book_chapter') {
                        // Collection Title
                        if (entry.collection_title !== null) {
                            html = html.concat('<h5>' + entry.collection_title + '</h5>');
                        }
                        html = html.concat('<p>');
                    }

                    // Title
                    if (entry.item_title !== null) {
                        html = html.concat('<h5>' + entry.item_title + '</h5>');
                    }
                    html = html.concat('<p>');

                    // Author
                    if (entry.item_author !== null) {
                        if (entry.item_author !== '') {
                            html = html.concat('Author(s): ' + entry.item_author);
                        }
                    }
                    html = html.concat('<br>');


                    if (entry.form_type === 'electronic_article') {
                        // Journal Info
                        if (entry.journal_title !== null) {
                            html = html.concat('Journal: ');
                            html = html.concat(entry.journal_title + ' ');
                        }

                        if (entry.journal_volume !== null) {
                            html = html.concat(entry.journal_volume);
                        }
                        if (entry.journal_issue !== null) {
                            html = html.concat(':' + entry.journal_issue + ' ');
                            //dataString = dataString.concat('data-journalissue="' + entry.journal_issue + '" ');
                        }
                        if (entry.item_incpages !== null) {
                            html = html.concat('Page(s): ' + entry.item_incpages);
                        }

                        html = html.concat('<br>');
                    }

                    // Dates Info
                    if (entry.item_pubdate !== null) {
                        if (entry.item_pubdate !== '') {
                            html = html.concat('Published: ' + entry.item_pubdate);
                        }
                    }

                    html = html.concat('<br>');

                    //Subject Terms
                    if (entry.subject_terms !== null) {
                        html = html.concat('Subject(s): ' + entry.subject_terms);
                    }
                    html = html.concat('<br>');


                    //Format
                    if (entry.form_type !== null) {
                        html = html.concat('Format: ' + entry.form_type_display);
                    }
                    html = html.concat('</p>');

                    if (entry.item_callnumber !== null && entry.form_type !== 'electronic-article') {
                        if (entry.item_callnumber !== '') {
                            html = html.concat('Call Number: ' + entry.item_callnumber);
                        }
                    }
                    //Abstract
                    if (entry.abstract !== null && entry.abstract !== '') {
                        html = html.concat('</p><a class="btn btn-info" onclick="$(this).siblings(\'.abstract\').slideToggle();">Show/Hide Abstract</a><div class="abstract" style="display:none">' + entry.abstract + '</div><p>');
                    }

                    //URI
                    if (entry.initial_uri !== null) {
                        html = html.concat('<a class="btn btn-info results-view-link" target="_blank" href="' + entry.initial_uri + '">View</a>');
                    }

                    //form_type in (electronic-article, ebook-general, book-general, ebook-chapter, book-chapter, electronic_article etc)
                    //form_type_display is to put in the submission form header is Book General, Ebook General etc
                    dataString = dataString.concat(' data-form_type="' + entry.form_type + '" data-displabel="' + entry.form_type_display + '"');

                    html = html.concat('<a class="btn btn-info results-select-link" ' + dataString + '>Select</a>');

                    html += '</li>';
                    entry = '';
                }

                html += '</ul></div>';

                return html;
            },

            getSubmitForm: function (data) {
                var prop, props = ["html", "type", "disp", "interstitial", "noLoanPeriods", "submit_object"], directEntry = false, parts, summon;
                for (prop in props) {
                    if (props.hasOwnProperty(prop)) {
                        if (this.hasOwnProperty(prop)) {
                            try {
                                delete this.prop;
                            } catch (e) {
                                if (debug) {
                                    console.log("could not delete: " + prop);
                                }
                                try {
                                    delete this[prop];
                                } catch (f) {
                                }
                            }
                        }
                    }
                }
                // highlightRequired();
                this.types = getInitJSON("submit_types");
                this.defaults = getInitJSON("submit_defaults");

                if (data.hasOwnProperty('is_direct_entry')) {
                    directEntry = data.is_direct_entry;
                }

                if (directEntry) {
                    // this.manual_entry = true;
                    if (data.form_type.indexOf('--') > -1) {
                        parts = data.form_type.split('--');
                        this.type = data.type = data.form_type = parts[0];
                        this.interstitial = data.interstitial = parts[1];
                        this.disp = data.disp = parts[0].replace('_', ' ').replace('pdf', 'PDF') + " (" + parts[1] + ")";
                    } else {
                        this.type = data.form_type;
                        this.interstitial = 'system-direct';
                        this.disp = data.form_type.replace('_', ' ').replace('pdf', 'PDF');
                    }
                    summon = "This item was not from summon. It was submitted as type: " + this.disp;
                    this.submit_object = {
                        abstract: "",
                        availability_id: "",
                        collection_title: "",
                        form_type: "",
                        form_type_display: "",
                        initial_uri: "",
                        item_author: "",
                        item_callnumber: "",
                        item_doi: "",
                        item_edition: "",
                        item_editor: "",
                        item_incpages: "",
                        item_isxn: "",
                        item_pubdate: "",
                        item_publisher: "",
                        item_pubplace: "",
                        item_title: "",
                        item_uri: "",
                        journal_issue: "",
                        journal_month: "",
                        journal_title: "",
                        journal_volume: "",
                        journal_year: "",
                        subject_terms: "",
                        summon: summon
                    };
                } else {
                    if (data.form_type.indexOf('__') > -1) {
                        parts = data.form_type.split('__');
                        this.type = data.type = data.form_type = parts[0];
                        this.interstitial = data.interstitial = parts[1];
                        this.disp = data.form_type.replace('_', ' ');
                    } else {
                        this.type = data.form_type;
                        this.interstitial = 'system';
                        this.disp = data.form_type_display;
                    }
                    this.submit_object = data;
                }
                this.submit_object.form_type = this.type;
                this.form_type = this.type;
                this.submit_object.request_interstitial = this.interstitial;
                this.submit_object.start_date = "";
                this.submit_object.end_date = "";
                this.submit_object.request_note_student = "";
                this.submit_object.request_note_staff = "";
                this.submit_object.tags = "";
                this.submit_object.request_required_reading = "";

                if (debug) {
                    console.log(this);
                }
            }
        };

    }

    //these are functions of crMenu
    return {
        // Get existing Singleton instance or create one if it doesn't
        init: function (options) {
            serverURI = options.uri;
        },

        getInstance: function () {
            if (!instance) {
                instance = init();
            }
            return instance;
        },

        setInstance: function (obj) {
            console.log('setting instance to obj. obj is: ');
            console.log(obj);
            instance = $.extend(true, {}, obj);
            return instance;
        }
    };

})();

var timeout;
/*
 Data Verification
 */
function verifyFields() {

    var submitItemSuccess = $('#submit-item-success');
    var submitItemBtn = $('#submit-item-submit-btn');
    var uploadFileDocStore = $("#upload_file_docstore");

    submitItemSuccess.empty();

    var proceed = true, field = "Missing Required Information: ";
    $("div#submit-form").find("input").each(function () {
        if (this.getAttribute('data-required') === 'true' && $(this).val() === '') {
            $(this).addClass('requiredFieldBorder');
            field = field + $(this).prev('label').text() + ", ";
            proceed = false;
        } else {
            $(this).removeClass('requiredFieldBorder');
        }
    });
    // catch docstore files
    if (uploadFileDocStore !== null && uploadFileDocStore.length) {
        uploadFileDocStore.contents().find("input#ubc_id_uploadfile").each(function () {
            if (this.getAttribute('data-required') === 'true' && $(this).val() === '') {
                $(this).addClass('requiredFieldBorder');
                field = field + uploadFileDocStore.prev('label').text() + ", ";
                proceed = false;
            } else {
                $(this).removeClass('requiredFieldBorder');
            }
        });
    }

    field = field.replace(/^(,|\s)+|(,|\s)+$/g, '');

    if (proceed) {
        submitItemSuccess.empty().css('border-left', '3px solid #FFFFFF');
        submitItemBtn.removeClass('btn-dead-link').text('Submit Item').attr('disabled', false);
    } else {
        submitItemSuccess.text(field).css('border-left', '3px solid #FF3D3D');
        submitItemBtn.addClass('btn-dead-link').text('Cannot Submit Item.').attr('disabled', true);
    }
}

function watchDocstoreFileField() {
    verifyFields();
}



// Ensures that required fields are not empty
function requiredFieldsFilled(isNewItem) {
    //"use strict";

    var submitItemSuccess = $('#submit-item-success');
    var submitItemBtn = $('#submit-item-submit-btn');
    var uploadFileDocStore = $("#upload_file_docstore");
    if (isNewItem) {
        submitItemSuccess.empty();
        var proceed = true, field = "Missing Required Information: ";
        $("div#submit-form").find("input").each(function () {
            if (this.getAttribute('data-required') === 'true' && $(this).val() === '') {
                $(this).addClass('requiredFieldBorder');
                field = field + $(this).prev('label').text() + ", ";
                proceed = false;
            } else {
                $(this).removeClass('requiredFieldBorder');
            }
        });
        // catch docstore files
        if (uploadFileDocStore !== null && uploadFileDocStore.length) {
            uploadFileDocStore.contents().find("input").each(function () {
                if (this.getAttribute('data-required') === 'true' && $(this).val() === '') {
                    $(this).addClass('requiredFieldBorder');
                    field = field + uploadFileDocStore.prev('label').text() + ", ";
                    proceed = false;
                } else {
                    $(this).removeClass('requiredFieldBorder');
                }
            });
        }

        field = field.replace(/^(,|\s)+|(,|\s)+$/g, '');

        if (proceed) {
            submitItemSuccess.empty().css('border-left', '3px solid #FFFFFF');
            submitItemBtn.removeClass('btn-dead-link').text('Submit Item').attr('disabled', false);
        } else {
            submitItemSuccess.text(field).css('border-left', '3px solid #FF3D3D');
            submitItemBtn.addClass('btn-dead-link').text('Cannot Submit Item.').attr('disabled', true);
        }
    } else {
        submitItemSuccess.empty().css('border-left', '3px solid #FFFFFF');
        submitItemBtn.removeClass('btn-dead-link').text('Submit Item').attr('disabled', false);
    }

}


function validateItemTitle(e) {
    //please do not comment out this function, this function is needed to prevent Safari breaking on a missing onKeyUp




    //console.log($(e).val());

}

function bindLegend() {
    var __parent = $('#submit-form');
    __parent.find('legend').on('click', function () {
        $(this).parent().find('.fieldset-content').slideToggle("slow");
        $(this).children('.lsit-caret').toggle();
    });
    __parent.find('.fieldset-content').find('.row-fluid').find('input').each(function () {
        var el = $(this);
        el.on('focus', function () {
            el.parent().parent().css('background-color', 'rgb(255, 235, 197)');
            __parent.find('.fieldset-content').find('.row-fluid').find('input').not(el).each(function () {
                $(this).parent().parent().css('background-color', 'transparent');
            });
        });
    });
    __parent.find('.fieldset-content').find('.row-fluid').each(function () {
        $(this).on('hover', function () {
            var el = $(this);
            if (!(el.find('input').is(":focus"))) {
                el.css('background-color', 'rgb(255, 249, 240)');
            }
            __parent.find('.fieldset-content').find('.row-fluid').not(el).each(function () {
                if (!($(this).find('input').is(":focus"))) {
                    $(this).css('background-color', 'transparent');
                }
            });
        });
    });
}

function setDates(start, end) {
    var dateParts, realDateS, realDateE;
    dateParts = start.match(/(\d+)/g);
    realDateS = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);
    dateParts = end.match(/(\d+)/g);
    realDateE = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);

    var startDate = $('#ubc_id_start_date');
    var endDate = $('#ubc_id_end_date');

    startDate.datepicker({
        dateFormat: 'yy-mm-dd',
        minDate: new Date(realDateS),
        maxDate: new Date(realDateE)
    });
    startDate.datepicker("setDate", realDateS);


    endDate.datepicker({
        dateFormat: 'yy-mm-dd',
        minDate: new Date(realDateS),
        maxDate: new Date(realDateE)
    });
    endDate.datepicker("setDate", realDateE);
}