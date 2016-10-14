if (course_id === '') {
    alert("Nothing is going to work here. You are not enrolled in this course.\n\nIf you are not enrolled, the CourseID is not granted, and none of the functions work.\n\n\nEnroll yourself in this course...")
}

var __rl = $('#required-legend');
var __ol = $('#optional-legend');
var __ql = $('#request-legend');
var __nl = $('#note-legend');
var __vl = $('#views-legend');
var __hl = $('#history-legend');
var monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

$('.show-details').on('click', function (e) {
    e.preventDefault();
    $('#open-instructor-reading-' + this.getAttribute("href")).trigger('click');
});

/* bind legends specifically, instead of finding (much faster) */
__rl.on('click', function () {
    $(this).next('div.fieldset-content').slideToggle("slow");
    $(this).children('.lsit-caret').toggleClass('fa-chevron-down fa-chevron-right');
});
__ol.on('click', function () {
    $(this).next('div.fieldset-content').slideToggle("slow");
    $(this).children('.lsit-caret').toggleClass('fa-chevron-right fa-chevron-down');
});
__ql.on('click', function () {
    $(this).next('div.fieldset-content').slideToggle("slow");
    $(this).children('.lsit-caret').toggleClass('fa-chevron-right fa-chevron-down');
});
__nl.on('click', function () {
    $(this).next('div.fieldset-content').slideToggle("slow");
    $(this).children('.lsit-caret').toggleClass('fa-chevron-right fa-chevron-down');
});
__vl.on('click', function () {
    $(this).next('div.fieldset-content').slideToggle("slow");
    $(this).children('.lsit-caret').toggleClass('fa-chevron-right fa-chevron-down');
});
__hl.on('click', function () {
    $(this).next('div.fieldset-content').slideToggle("slow");
    $(this).children('.lsit-caret').toggleClass('fa-chevron-right fa-chevron-down');
});

function resetReadingPopupSaveButton() {
    $('#reading-save_button').removeClass('btn-success').removeClass('btn-danger').addClass('btn-info').text('Save');
}

$('.open-instructor-reading').on('click', function () {
    $('html').css('overflow', 'hidden');
    $('body').css('overflow', 'hidden');
    var
        data = this.dataset, //all the data
        str = '', //additional urls
        required_bibdata = '', //required bibdata
        optional_bibdata = '', // optional bibdata
        loanperiod = '', // loadperiod
        tag_str = '', // tags
        stud_note = '', // notes to students
        inst_note = '', // notes to instructors
        required_fields = [],
        field_titles = [],
        controlled_url_formats = ['pdf_general', 'pdf_article', 'pdf_chapter', 'pdf_other'],
        dateParts,
        realDateS,
        realDateE,
        rs,
        re,
        __s = $('#request_start_date'),
        __e = $('#request_end_date');

    if (data == null || typeof data === 'undefined') {

        data = {};
        data['trigger'] = this.getAttribute('data-trigger');//
        data['courseStart'] = this.getAttribute('data-course-start');//
        data['courseEnd'] = this.getAttribute('data-course-end');//
        data['requestStart'] = this.getAttribute('data-request-start');//
        data['requestEnd'] = this.getAttribute('data-request-end');//
        data['instance_id'] = this.getAttribute('data-instance_id');//
        data['required'] = this.getAttribute('data-required');//
        data['edit'] = this.getAttribute('data-edit');//
        data['get'] = this.getAttribute('data-get');//
        data['title'] = this.getAttribute('data-title');//
        data['author'] = this.getAttribute('data-author');//
        data['calln'] = this.getAttribute('data-calln');//
        data['loanp'] = this.getAttribute('data-loanp');//
        data['tags'] = this.getAttribute('data-tags');//
        data['note'] = this.getAttribute('data-note');//
        data['studentnote'] = this.getAttribute('data-studentnote');//
        data['urls'] = this.getAttribute('data-urls');//
        data['fields'] = this.getAttribute('data-fields');//
        data['fieldsreqd'] = this.getAttribute('data-fieldsreqd');//
        data['fieldtitles'] = this.getAttribute('data-fieldtitles');//
        data['format'] = this.getAttribute('data-format');//

    }

    $(".additional_url").remove();
    $('#insert-reading-get').attr('href', data.get);
    $('#insert-reading-title').text(data.title.replace(/"/g, '&quot;'));
    $('#insert-reading-author').text(data.author);
    $('#insert-reading-calln').text(data.calln);
    $('#insert-reading-loanp').text(data.loanp);
    $('#insert-reading-tags').text(data.tags);


    if (data.urls !== "") {
        $.each($.parseJSON(data.urls), function (key, value) {
            str += '&nbsp;<a href="' + value.url + '" class="btn btn-info additional_url remove-me" target="_blank">View Supplemental (' + value.description + ')&nbsp;<i class="fa fa-external-link-square"></i></a>';
        });
    }

    if (data.tags !== "") {
        $.each($.parseJSON(data.tags), function (key, value) {
            tag_str += '<div id="tag-container-' + key + '" class="outer-tag-div remove-me"><div class="input-append input-tag instructor-reading-tag" id="tag-wrapper-' + key + '"><button type="button" class="btn btn-tag btn-tag-management" data-tag="' + value.hash + '">' + value.name + '</button><button class="btn btn-tag btn-tag-danger btn-tag-append btn-delete-tag" data-tag="' + key + '" type="button">x</button></div>&nbsp;&nbsp;&nbsp;&nbsp;<strong>PURL: ' + value.shorturl + '</strong></div>';
        });
    }

    if (data.note !== "") {
        $.each($.parseJSON(data.note), function (key, note) {
            var now = new Date(note.timestamp);

            // Create an array with the current month, day and time
            var date = [now.getDate(), monthNames[now.getMonth()], now.getFullYear()];

            // Create an array with the current hour, minute and second
            var time = [now.getHours(), now.getMinutes()];

            // Determine AM or PM suffix based on the hour
            var suffix = ( time[0] < 12 ) ? "AM" : "PM";

            // Convert hour from military time
            time[0] = ( time[0] < 12 ) ? time[0] : time[0] - 12;

            // If hour is 0, set it to 12
            time[0] = time[0] || 12;

            // If seconds and minutes are less than 10, add a zero
            for (var i = 1; i < 2; i++) {
                if (time[i] < 10) {
                    time[i] = "0" + time[i];
                }
            }
            inst_note += '<p class="note-p" id="note-' + note.note_id + '"><span class="note-content">' + note.content + '</span><br>- <span class="note-author">' + note.firstname + ' ' + note.lastname + '</span>,<span class="note-date"> ' + date.join("-") + ' @ ' + time.join(":") + suffix + '</span></p>';
        });
    }


    var stud_note_id = -1;
    var stud_note_ct = "";
    var stud_note_pl = ' placeholder="Enter note here"';
    if (data.studentnote !== "") {
        $.each($.parseJSON(data.studentnote), function (key, value) {
            stud_note_id = key;
            stud_note_ct = value.content;
            stud_note_pl = '';
            itemBibdata[data.trigger].student_note = value.content;
        });
    }
    stud_note = '<p class="remove-me">You can enter a message below that will be shown to your students when they view this reading:</p><textarea style="width: 480px" rows="5" id="submit_student_note" class="remove-me" data-nid="' + stud_note_id + '"' + stud_note_pl + '>' + stud_note_ct + '</textarea><br>';

    required_fields = $.parseJSON(data.fieldsreqd);
    field_titles = $.parseJSON(data.fieldtitles);

    $.each($.parseJSON(data.fields), function (key, value) {
        /*
        console.log("Key: " + key);
        console.log(value);
        console.log(value.replace(/"/g, '&quot;'));
        console.log(JSON.stringify(value));
        console.log("\n\n");
        */

        value = value.replace(/"/g, '&quot;');

        if (key !== 'item_uri') {
            if (required_fields[key] === 1) {
                required_bibdata += '<div class="row-fluid" style="background-color: transparent;"><div class="span12"><label for="' + data.trigger + '_' + key + '">' + field_titles[key] + '</label><input class="span12 stored bibdata-input ' + key + '-bibdata" data-required="true" type="text" id="' + data.trigger + '_' + key + '" value="' + value + '" /></div></div>';
            } else {
                optional_bibdata += '<div class="row-fluid" style="background-color: transparent;"><div class="span12"><label for="' + data.trigger + '_' + key + '">' + field_titles[key] + '</label><input class="span12 stored bibdata-input ' + key + '-bibdata" data-required="false" type="text" id="' + data.trigger + '_' + key + '" value="' + value + '" /></div></div>';
            }
        }
        if (key === 'item_uri' && $.inArray(data.format, controlled_url_formats) === -1) {
            if (required_fields[key] === 1) {
                required_bibdata += '<div class="row-fluid"><div class="span12"><label class="control-label" for="' + data.trigger + '_' + key + '">' + field_titles[key] + ':</label><div class="controls"> <input class="span12 stored bibdata-input" type="text" id="' + data.trigger + '_' + key + '" value="' + value + '" /></div></div></div>';
            } else {
                optional_bibdata += '<div class="row-fluid"><div class="span12"><label class="control-label" for="' + data.trigger + '_' + key + '">' + field_titles[key] + ':</label><div class="controls"> <input class="span12 stored bibdata-input" type="text" id="' + data.trigger + '_' + key + '" value="' + value + '" /></div></div></div>';
            }
        }
    });

    /* loan period */
    if (data.loanp == 21) {
        loanperiod = '<select id="loanperiods"><option value="' + data.loanp + '" selected>' + loanperiods[data.loanp] + '</option></select>';
    } else {
        loanperiod = '<select id="loanperiods" onclick="resetReadingPopupSaveButton();">';
        $.each(loanperiods, function (key, value) {
            if (key != 21) {
                var __sltd = key === data.loanp ? 'selected' : '';
                loanperiod += '<option value="' + key + '" ' + __sltd + '>' + value + '</option>';
            }
        });
        loanperiod += '</select>';
    }

    /* dates */
    dateParts = (data.courseStart).match(/(\d+)/g);
    realDateS = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);//js course start
    dateParts = (data.courseEnd).match(/(\d+)/g);
    realDateE = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);//js course end
    dateParts = (data.requestStart).match(/(\d+)/g);
    rs = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);//js request start
    dateParts = (data.requestEnd).match(/(\d+)/g);
    re = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);//js request end
    __s.datepicker({
        dateFormat: 'yy-mm-dd',
        minDate: new Date(realDateS),
        maxDate: new Date(realDateE)
    });
    __s.datepicker("setDate", rs);

    __e.datepicker({
        dateFormat: 'yy-mm-dd',
        minDate: new Date(realDateS),
        maxDate: new Date(realDateE)
    });
    __e.datepicker("setDate", re);

    $('#instructor-reading-save').on('click', function () {
        startUpdate(course_id, data.trigger, puid);
    });

    $('#instructor-reading-reset').on('click', function () {
        resetBibdataFields(data.trigger);
    });

    $('#instructor-reading-cancel').on('click', function () {
        cancelItem(course_id, data.trigger);
    });

    var reqFieldInterval;

    var dialogWidth = "60%";
    if (window.innerWidth < 1000) {
        dialogWidth = "95%";
    }

    $('#instructor-reading-modal').dialog({

        modal: true,
        width: dialogWidth,
        maxHeight: window.innerHeight,
        draggable: false,
        resizable: false,
        open: function (event, ui) {
            $('.ui-widget-overlay').bind('click', function () {
                $(this).siblings('.ui-dialog').find('.ui-dialog-content').dialog('close');
            });

            if (str !== '') {
                $('#insert-reading-get').after(str);
            }
            if (tag_str !== '') {
                $('#instructor-reading-tag-header').after(tag_str);
            }
            if (required_bibdata !== '') {
                $('#instructor-reading-required-bibdata').append(required_bibdata);
            }
            if (optional_bibdata !== '') {
                $('#instructor-reading-optional-bibdata').append(optional_bibdata);
            }

            var potentialDocstoreURI = $('#' + data.trigger + '_item_uri');

            if(potentialDocstoreURI.val() !== undefined && data.format == 'pdf_chapter'){
                potentialDocstoreURI.prop('disabled', 'disabled')
            }

            $('#loanperiods-label').after(loanperiod);
            $('#instructor-reading-staff-note-area').append(inst_note);
            $('#instructor-reading-student-note-header').after(stud_note);

            setTimeout(function () {
                (function () {
                    if (data.required == "true") {
                        $('#required_reading_y').prop('checked', true);
                        $('#required_reading_n').prop('checked', false);
                    } else {
                        $('#required_reading_y').prop('checked', false);
                        $('#required_reading_n').prop('checked', true);
                    }
                    highlightFields();

                    $('.btn-delete-tag').on('click', function () {
                        var __tid = $(this).data('tag'), r = confirm('Are you sure you want to remove the tag [' + __tid + ']');
                        if (r == true) {
                            removeTag(__tid, data.trigger, course_id);
                        }
                    });
                })();
            }, 1);

            reqFieldInterval = setInterval(function () {
                checkRequiredFields()
            }, 125);

            // set what we want open and closed by default
            __rl.next('div.fieldset-content').show();
            __rl.children('.lsit-caret').removeClass('fa-chevron-right').addClass('fa-chevron-down');
            __ol.next('div.fieldset-content').hide();
            __ol.children('.lsit-caret').removeClass('fa-chevron-down').addClass('fa-chevron-right');
            __ql.next('div.fieldset-content').show();
            __ql.children('.lsit-caret').removeClass('fa-chevron-right').addClass('fa-chevron-down');
            __nl.next('div.fieldset-content').hide();
            __nl.children('.lsit-caret').removeClass('fa-chevron-down').addClass('fa-chevron-right');
            __vl.next('div.fieldset-content').hide();
            __vl.children('.lsit-caret').removeClass('fa-chevron-down').addClass('fa-chevron-right');
            __hl.next('div.fieldset-content').hide();
            __hl.children('.lsit-caret').removeClass('fa-chevron-down').addClass('fa-chevron-right');

            if(document.createEvent) {
                $('#instructor-reading-content').perfectScrollbar({
                    wheelSpeed: 50,
                    wheelPropagation: true,
                    suppressScrollX: true
                });
            } else{
                    submitForm.css('overflow-y','scroll');
                    submitForm.addClass('repaint').removeClass('repaint');
                $('#instructor-reading-content').css('overflow','scroll-y');
            }

            $.get(
                '/passtolicr.php',
                {
                    command: 'ItemReadsEnrolledSummary',
                    item_id: data.trigger,
                    course: course_id
                },
                function (data) {
                    var _readStr = '';
                    if (!data.success) {
                        _readStr = 'The system encountered an error and was unable to find views data for this Course Item.';
                    }
                    else {
                        $('#instructor-reading-views-count').text(data.data.total);
                        if (data.data.total == 0) {
                            _readStr = 'This item has not yet been viewed by any students';
                        } else {
                            $.each(data.data.perday, function () {
                                _readStr += '<tr><td>' + this.day + '</td><td>' + this.count + '</td></tr>';
                            });
                        }
                        $('#instructor-reading-load-reads-here').html(_readStr);
                    }
                },
                'json'
            );

            $.get(
                '/passtolicr.php',
                {
                    command: 'GetHistory',
                    id: data.instance_id,
                    table: 'course_item'
                },
                function (data) {
                    var _str;
                    var arrayLength;
                    if (!data.success) {
                        _str = 'The system encountered an error and was unable to find views data for this Course Item.';
                    }
                    else {
                        arrayLength = data.data.length;
                        for (var i = 0; i < arrayLength; i++) {
                            _str = _str + '<tr><td>' + (data.data[i].time) + '</td><td>' + (data.data[i].note) + '</td></tr>';
                        }
                    }
                    $('#instructor-reading-load-history-here').html(_str);
                },
                'json'
            );

        },
        close: function () {
            $("#loanperiods").remove();
            $("#instructor-reading-load-reads-here").empty();
            $("#instructor-reading-load-history-here").empty();
            $(".remove-me").remove();
            $('#instructor-reading-required-bibdata').empty();
            $('#instructor-reading-optional-bibdata').empty();
            $('#bibdata-success').empty();
            $('#instructor-reading-staff-note-area').empty();
            $('#instructor-reading-save').unbind();
            $('#instructor-reading-reset').unbind();
            $('#instructor-reading-cancel').unbind();
            if(document.createEvent) {
                $('#instructor-reading-content').perfectScrollbar('destroy');
            }
            clearInterval(reqFieldInterval);

            $('html').css('overflow', 'auto');
            $('body').css('overflow', 'auto');
            $("#content").css('display', 'block');
            if (instructor_home_reload) {
                location.reload();
            }
        }
    });

    if (navigator.userAgent.match(/(iPod|iPhone|iPad|Android)/)) {
        $("#content").css('display', 'none');
        $('.ui-dialog').css({'position': 'absolute', 'top': '50%', 'transform': 'translateY(-50%)'});
        $('.ui-dialog-titlebar-close').css({'width': '50px', 'height': '50px'});
    }


});

function parseBulkEditCheckboxes(selectedCheckboxes, unselectedCheckboxes, uniqueTags, allTags){
    $('.batch-edit-checkbox').each(function () {
        var _id = $(this).val();
        var node, finalNode, _tags, _ta, _tagParts;
        if (this.checked) {
            node = document.querySelector('#item-checkbox-' + _id);
            finalNode = node.dataset; // cache this

            // process tags
            _tags = finalNode.tags;
            _ta = _tags.split('__END__');
            for (var i = 0; i < _ta.length; i++) {
                _tagParts = _ta[i].split('__HASHNAME__');
                if(_tagParts.length == 2){
                    uniqueTags[_tagParts[0]] = _tagParts[1];
                    allTags[_tagParts[0]] = _tagParts[1];
                    _tags[_tagParts[0]] = _tagParts[1];
                }
            }

            finalNode.tags = _tags;
            selectedCheckboxes.push(finalNode);
        } else {
            unselectedCheckboxes.push(_id);

            // process tags
            node = document.querySelector('#item-checkbox-' + _id);
            _tags = node.dataset.tags;
            _ta = _tags.split('__END__');
            for (var i = 0; i < _ta.length; i++) {
                _tagParts = _ta[i].split('__HASHNAME__');
                if(_tagParts.length == 2){
                    allTags[_tagParts[0]] = _tagParts[1];
                }
            }
        }
    });

    /*
    console.log('Selected Checkboxes:');
    console.log(selectedCheckboxes);

    console.log('Unselected Checkboxes:');
    console.log(unselectedCheckboxes);

    console.log('Globally Unique Tags:');
    console.log(uniqueTags);

    console.log('All Displayed Tags:');
    console.log(allTags);
    */
}

function sortTags(allTags) {
    var keys = [], allTagsSort = {}, k, i, len;

    var re = /([^\W|\d]+)/g;
    var m;
    var __tag = '';

    for (k in allTags) {
        if (allTags.hasOwnProperty(k)) {
            __tag = '';
            while ((m = re.exec(allTags[k])) !== null) {
                if (m.index === re.lastIndex) {
                    re.lastIndex++;
                }
                if(typeof  m != undefined && m !== null) {
                    __tag += m[0];
                }
            }
            __tag = __tag.toLocaleLowerCase();
            allTagsSort[__tag] = k;
            keys.push(__tag);
        }
    }

    keys.sort();

    len = keys.length;

    var returnArray = {};

    for (i = 0; i < len; i++) {
        returnArray [keys[i]] = {
            tagID: allTagsSort[keys[i]],
            tag: allTags[allTagsSort[keys[i]]]
        };

    }

    return returnArray;
}

$('.batch-edit-checkbox').on('click', function () {

    var selectedCheckboxes = [];
    var unselectedCheckboxes = [];
    var uniqueTags = {};
    var allTags = {};

    parseBulkEditCheckboxes(selectedCheckboxes, unselectedCheckboxes, uniqueTags, allTags);

    var allTagsSorted = sortTags(allTags);
    var uniqueTagsSorted = sortTags(uniqueTags);

    /*
    console.log('Selected Checkboxes:');
    console.log(selectedCheckboxes);

    console.log('Unselected Checkboxes:');
    console.log(unselectedCheckboxes);

    console.log('Globally Unique Tags:');
    console.log(uniqueTags);

    console.log('All Displayed Tags:');
    console.log(allTags);
    */

    // empty the list of tags
    $('#AddItemToTag-value').empty();
    $('#DeleteItemFromTag-value').empty();

    $.each(allTagsSorted, function(key, value) {
        $('#AddItemToTag-value').append($("<option data-apiparam='tag' class='apiparam'></option>").attr("value",value.tagID).text(value.tag));
    });

    $.each(uniqueTagsSorted, function(key, value) {
        $('#DeleteItemFromTag-value').append($("<option data-apiparam='tag' class='apiparam'></option>").attr("value",value.tagID).text(value.tag));
    });

    var _sl = $('#bulk-edit-available-commands');

    if(selectedCheckboxes.length > 0){
        console.log('There is at least one selected checkbox, enable bulk actions');

        _sl.prop('disabled',false);
        var _selectedAction = _sl.find(":selected").val();
        initEditCommandForm(_selectedAction);
        $('#bulkEditTriggerCommand').prop('disabled',false).show();

    } else {
        console.log('No active checkboxes, disable bulk actions');

        $('.bulk-actions-form').each(function () {
            $(this).hide();
        });

        $('#bulkEditTriggerCommand').prop('disabled','disabled').hide();

        _sl.find('option:eq(0)').prop('selected', true);
        _sl.prop('disabled',true);

    }
});

$('.bulk-actions-form').each(function () {
    $(this).removeClass("hidden").hide();
});

$('#bulk-edit-available-commands').on('change', function (e) {
    // hide all forms
    $('.bulk-actions-form').each(function () {
        $(this).hide();
    });

    // show just this one
    var _selectedAction = $(this).find(":selected").val();
    initEditCommandForm(_selectedAction);

});

function initEditCommandForm(command) {
    $('#' + command + '-action').show(50, function () {
        if(command == 'SetCIDates'){
            $('#bulk-edit-startdate').datepicker({dateFormat: "yy-mm-dd", defaultDate: courseDateStart, minDate: courseDateStart, maxDate: courseDateEnd});
            $('#bulk-edit-enddate').datepicker({dateFormat: "yy-mm-dd",  defaultDate: courseDateEnd, minDate: courseDateStart, maxDate: courseDateEnd});
        }
    });
}

$('#bulkEditTriggerCommand').prop('disabled','disabled').hide();

$('#bulkEditTriggerCommand').on('click', function () {

    var _sl = $('#bulk-edit-available-commands');
    var _selectedAction = _sl.find(":selected").val();
    var _actionInputs = $('#' + _selectedAction + '-action');
    var selectedCheckboxes = [];
    var unselectedCheckboxes = [];
    var uniqueTags = {};
    var allTags = {};
    var payload = {};
    var doCommand = true;

    parseBulkEditCheckboxes(selectedCheckboxes, unselectedCheckboxes, uniqueTags, allTags);

    /*
    console.log('Selected Checkboxes:');
    console.log(selectedCheckboxes);
    console.log(' ');
    console.log('Action:' + _selectedAction);
    */

    payload.command = _selectedAction;

    if(_selectedAction == 'SetCIDates'){
        var r = confirm("Please press 'OK' to confirm you are bulk changing course item dates");
        if (r == true) {
            doCommand = true;
        } else {
            doCommand = false;
        }
    }

    _actionInputs.find('input.apiparam').each(function(){
        if($(this).data('apiparam') !== undefined) {
            payload[$(this).data('apiparam')] = $(this).val();
            console.log( $(this).data('apiparam') +': ' + $(this).val() );
        }
    });
    if(_actionInputs.find('option.apiparam').filter(":selected").data('apiparam') !== undefined){
        payload[_actionInputs.find('option.apiparam').filter(":selected").data('apiparam')] = _actionInputs.find('option.apiparam').filter(":selected").val();
        console.log(_actionInputs.find('option.apiparam').filter(":selected").data('apiparam') + ': ' + _actionInputs.find('option.apiparam').filter(":selected").val());
    }


    if(doCommand){

        $('#bulk-editing-modal').dialog({

            modal: true,
            width: dialogWidth,
            maxHeight: window.innerHeight,
            draggable: false,
            resizable: false,
            open: function (event, ui) {
                $('.ui-widget-overlay').bind('click', function () {
                    $(this).siblings('.ui-dialog').find('.ui-dialog-content').dialog('close');
                });

                // lolz
                $(this).css('overflow-x','hidden').css('overflow-y','hidden').css('overflow','hidden');

                $('#bulk-editing-content').css('overflow-x','hidden').css('overflow-y','scroll');

                // la Kristina McDavis edits for userExperience++
                var modalTitle = '';
                switch(payload.command) {
                    case 'SetCIRequired':
                        modalTitle += 'Set Course Items as ';
                        if(payload.required == 1) {
                            modalTitle += 'Required ';
                        } else {
                            modalTitle += 'Optional ';
                        }
                        break;
                    case 'SetCIDates':
                        modalTitle += 'Set Course Item Dates ';
                        break;
                    case 'AddItemToTag':
                        modalTitle += 'Add Existing Tag ';
                        break;
                    case 'DeleteItemFromTag':
                        modalTitle += 'Remove Existing Tag ';
                        break;
                    default:
                        modalTitle += 'Bulk Editing Progress';

                }

                modalTitle += '[' + selectedCheckboxes.length +  ' item(s)]';

                $('#bulk-editing-modal-title').empty().append(modalTitle);


                // le progress
                var contentArea = $('#bulk-editing-progress');

                contentArea.empty();

                for(var i = 0; i < selectedCheckboxes.length; i++){

                    payload.course = selectedCheckboxes[i].courseid;
                    payload.item_id = selectedCheckboxes[i].itemid;

                    if(payload.command == 'SetCIRequired') {
                        payload.item = payload.item_id;
                        delete payload.item_id;
                    }

                    console.log(' ');
                    console.log('----- ' + payload.course + '|' + selectedCheckboxes[i].itemid + ' -----');
                    console.log(payload);
                    console.log('----- ' + payload.course + '|' + selectedCheckboxes[i].itemid + ' -----');

                    $.get(
                        '/passtolicr.php',
                        payload,
                        function (data) {
                            console.log(data);
                            if (data.success) {
                                contentArea.append('<p class="alert">Success! ' + data.message + '</p>');
                                //response += 'Response: ' +  JSON.stringify(data);
                            }
                            else {
                                contentArea.append('<p class="alert alert-error">Error! ' + data.message + '</p>');
                            }
                        },
                        'json'
                    );
                }

            },
            close: function () {
                $('html').css('overflow', 'auto');
                $('body').css('overflow', 'auto');
                $("#content").css('display', 'block');
                location.reload();
            }
        });
    }

    /* bookmark */

    /*
    $.get(
        '/passtolicr.php',
        payload,
        function (data) {

            if (!data.success) {

            }
            else {

            }
        },
        'json'
    );
    */


});

jQuery(document).ready(function ($) {
    var itemsShown = {};
    invokeDataTable();
    //countEventHandlers();
});

function revokeDataTable() {
    var tables = $.fn.dataTable.fnTables(true);
    $(tables).each(function () {
        $(this).dataTable().fnDestroy();
    });
}

function invokeDataTable() {

    readingsTable = $('#instructor-readings').dataTable({
        "bPaginate": false,
        "bLengthChange": false,
        "aoColumns": [
            {"bSortable": false},
            {"bSortable": false},
            {"bSortable": false},
            {"bSortable": false},
            {"bSortable": false},
            {"bSortable": false},
            {"bSortable": false},
            {"bSortable": false},
            {"bSortable": false},
            {"bSortable": false},
            {"bSortable": false},
            {"bSortable": false}
        ],
        "bJQueryUI": true
    });

    /*readingsTable.$('.filter-table').on('click', function () {*/
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


    var start;
    var end;
    var newIndex;
    var i = 0;

    $("#sortable").sortable({
        cursor: "move",
        placeholder: 'placeholder',
        forceHelperSize: true,
        forcePlaceholderSize: true,
        items: "> tr",
        helper: 'clone',
        handle: ".reading-handle",
        start: function (event, ui) {
            start = ui.item[0].rowIndex;
        },
        change: function (event, ui) {
            i++;
        },
        update: function (event, ui) {
        },
        over: function (event, ui) {
            newIndex = ui.item[0].rowIndex;
        },
        beforeStop: function (event, ui) {
        },
        stop: function (event, ui) {
            var _items = '';
            var reOrder, __ir = $("#instructor-readings");
            __ir.find("tr:even").removeClass('even').removeClass('odd').addClass('even');
            __ir.find("tr:odd").removeClass('odd').removeClass('even').addClass('odd');
            repaint();
            $('#sortable').find('tr').each(function () {
                _items = _items + $(this).attr('id').replace(/^reading-/, '') + ',';
            });
            _items = _items.substring(0, _items.length - 1);
            reOrder = $.get(
                '/passtolicr.php',
                {
                    command: 'SetCISequence',
                    course: course_id,
                    item_id_multi: _items
                },
                function (data) {
                    if (!data.success) {
                        alert('The system was unable to reorder the items.')
                    }
                },
                'json');
        }
        // end of drag
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

$(function () {
    if (print_bool) {
        ieprint2();
    }
});
