/**
 * Created by skhanker on 4/1/14.
 */

/*$(document).on('reveal:open', '.reveal-modal', function () {
 //$('html').css('overflow', 'auto');
 //$('body').css('overflow', 'auto');
 });
 $(document).on('reveal:opened', '.reveal-modal', function () {
 //$('html').css('overflow', 'auto');
 //$('body').css('overflow', 'auto');
 });
 $(document).on('reveal:close', '.reveal-modal', function () {
 $('html').css('overflow', 'auto');
 $('body').css('overflow', 'auto');
 });*/

function updateProgressBar(barId) {
    var perc = parseInt(($(barId).css("width")).replace("%", ""), 10);
    $(barId).redraw();
    return (perc + 10).toString() + "%";
}

function resetSaveButton(course, item) {
    $('#' + course + '_' + item + '_save_button').removeClass('btn-success').removeClass('btn-danger').addClass('btn-info').text('Save');
}

function updateBibdata(item) {
    var ret = false;
    $.ajax({
        type: "POST",
        url: "/mediator.updatebibdata",
        data: {
            i: item, bibdata: JSON.stringify(itemBibdata[item])
        },
        dataType: 'json',
        async: false
    })
        .done(function (data) {
            if (!data.success) {
                alert("Error: " + data.message);
            }
            else {
                ret = true;
            }
        })
        .fail(function () {
            alert("Fatal Error: Could not connect to server to save changes");
        });
    return ret;
}

function clearFilters() {
    tag = '';
    tag_display = '';

    readingsTable.fnDraw();
    $('#filter-indicator-wrapper').css('display', 'none');
    $('.filter-table').each(function () {
        $(this).removeClass('active-tag');
    });
}

/*function setHeight (outerId, innerId) {
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
 }*/

/*
 function forceReflow(id){
 var element = document.getElementById(id);
 var n = document.createTextNode(' ');
 var disp = element.style.display;  // don't worry about previous display style

 element.appendChild(n);
 element.style.display = 'none';

 setTimeout(function () {
 element.style.display = disp;
 n.parentNode.removeChild(n);
 }, 5);
 }
 */

function batchedCopy(from, to, arrayOfItemIds, errString, iteration, maxlimit, perc, percInc) {
    var curlimit = 15, idString = '', _cloneBar = $('#clone-prog-bar');

    if (arrayOfItemIds.length < maxlimit) {
        curlimit = arrayOfItemIds.length;
    }

    for (var i = 0; i < curlimit; i++) {
        idString += arrayOfItemIds.shift() + ",";
    }

    idString = idString.slice(0, -1);

    iteration += 1;
    //console.log("Iteration : " + iteration);
    //console.log("Copying : " + idString);
    //console.log(" ");

    _cloneBar.css('width', perc + "%").removeClass('bar-success').addClass('bar-success');
    perc += percInc;

    setTimeout(function () {
        $.ajax({
            url: '/passtolicr.php',
            data: {
                command: 'CopyCourse',
                from: from,
                to: to,
                item_id_multi: idString
            },
            dataType: 'json',
            async: false
        }).done(function (data) {
            if (!data.success) {
                errString += idString;
            } else if (arrayOfItemIds.length > 0) {
                $('#clone-prog-items').append(" - " + idString + '<br>');
                _cloneBar.css('width', perc + "%").removeClass('bar-success').addClass('bar-success');
                perc += percInc;
                setTimeout(function () {
                    batchedCopy(from, to, arrayOfItemIds, errString, iteration, maxlimit, perc, percInc);
                }, 250);
            } else {
                if (errString !== 'The system was unable to import the following course items. Please contact LSIT.') {
                    alert(errString);
                    alert('Cloning Complete - Not all your course items may have been imported. Please contact LSIT. The web page will reload when you click OK.');
                } else {
                    _cloneBar.css('width', '100%').removeClass('bar-success').addClass('bar-success');
                    alert('Your Course Items have been Imported. The web page will reload when you click OK.');
                }
                $('#clone-item').trigger('reveal:close');
                location.reload();
            }
        });
    }, 250);
}

function cloneCourse(from, to) {
    var idString = '', msg = '';
    $('.' + from + '_' + to + '_copy_checkbox').filter(":checked").each(function () {
        idString += $(this).val() + ',';
    });
    if (idString !== '') {
        idString = idString.slice(0, -1);
        msg = ' The following items were selected to copy: ' + idString;
    } else {
        msg = ' All items will be cloned.';
        idString = '';
        $.ajax({
            url: '/passtolicr.php',
            data: {
                command: 'ListInstructorCIs',
                course: from
            },
            dataType: 'json',
            async: false
        }).done(function (data) {
            if (!data.success) {
                alert('The system was unable to import the request course items. Please contact LSIT.');
            } else {
                $.each(data.data, function (k, v) {
                    idString += k + ',';
                });
                idString = idString.slice(0, -1);
            }
        });
    }
    //console.log("IDs that will be cloned:");
    //console.log(idString);
    var response = confirm("Click to Confirm Import of Course Items." + msg);
    if (response == true) {
        var batchSize = 15;
        var perc = Math.floor(parseInt((100 / (idString.split(",").length / batchSize)) / 2));

        $('#clone-item').dialog({
            modal: true,
            width: dialogWidth,
            maxHeight: window.innerHeight,
            draggable: false,
            resizable: false,
            open: function () {
                $('.ui-widget-overlay').bind('click', function () {
                    $(this).siblings('.ui-dialog').find('.ui-dialog-content').dialog('close');
                });
                batchedCopy(from, to, idString.split(","), 'The system was unable to import the following course items. Please contact LSIT.', 0, batchSize, perc, perc);

            }
        });

        /*        $('#clone-item').reveal({
         animation: 'fade',
         animationspeed: 50,
         closeonbackgroundclick: false,
         dismissmodalclass: 'close-reveal-modal',
         open: function () {
         },
         opened: function () {
         batchedCopy(from, to, idString.split(","), 'The system was unable to import the following course items. Please contact LSIT.', 0, batchSize, perc, perc);
         },
         close: function () {
         }
         });*/

    } else {
        alert('Nothing was imported.');
    }
    return true;
}


function addTagtoItem(tag, tagId, key, course) {

    var ret = false;
    $.ajax({
        url: '/passtolicr.php',
        data: {
            command: 'AddItemToTag',
            item_id: key,
            tag: tagId,
            course: course
        },
        dataType: 'json',
        async: false
    }).done(function (data) {
        if (!data.success) {
            alert('The system was unable to add the requested tag.');
        }
        if (data.data == 1) {
            /*var str = '<div class="input-append input-tag" id="tag-wrapper-' + key + '-' + tagId + '"><button type="button" class="btn btn-tag btn-tag-management" data-tag="' + tag + '">' + tag + '</button><button class="btn btn-tag btn-tag-danger btn-tag-append btn-delete-tag" data-tag="' + tagId + '" type="button">x</button></div>',
             _astb_btn = $('#' + key + '_add_student_tag_button'),
             _text = _astb_btn.next('span').text();
             $('#' + key + '-tag-add-area').append(str);

             if (_text.indexOf("Saved") > -1) {
             _text = _text + ", " + tag;
             _astb_btn.next('span').text(_text);
             }
             else {
             _astb_btn.after('<span class="add-on-success">Saved ' + tag + '</span>');
             }
             */
            ret = true;
        } else {
            alert('The system was unable to add the requested tag.');
        }
    });

    return ret;
}

function addTag(tag, key, course) {

    var _tag = $.trim(tag.toString().replace(/^\s+|\s+$/g, '')), ret = false;
    if (_tag.length > 0) {
        $.ajax({
            url: '/passtolicr.php',
            data: {
                command: 'CreateTag',
                name: _tag,
                course: course
            },
            dataType: 'json',
            async: false
        }).done(function (data) {
            if (!data.success) {
                alert('The system was unable to make the requested tag [' + JSON.stringify(_tag) + '].');
            }
            if (data.data > 0) {
                ret = addTagtoItem(_tag, data.data, key, course);
            }
            else {
                alert('The system was unable to make the requested tag.');
            }
        });
    }

    return ret;
}

function cancelItem(course, item) {

    var response = confirm("Are you sure you want to cancel this item [" + item + "]?");
    if (response == true) {
        // status:29 -- request cancelled by instructor
        $.get(
            '/passtolicr.php',
            {
                command: 'SetCIStatus',
                course: course,
                item_id: item,
                status: 29
            },
            function (data) {
                if (!data.success) {
                    alert('The system was unable to cancel this item [' + item + '] (status change failed).');
                } else {
                    $.get(
                        '/passtolicr.php',
                        {
                            command: 'DerequestItem',
                            course: course,
                            item_id: item
                        },
                        function (data) {
                            if (!data.success) {
                                alert('The system was unable to cancel this item [' + item + '] (de-request failed).');
                            }
                            else {
                                alert('Item [' + item + '] cancelled successfully. Please click OK to reload the page.');
                                location.reload();
                            }
                        },
                        'json');
                }
            },
            'json');
    }
    return true;
}


function removeTag(id, item_id, course_id) {
    $.get(
        '/passtolicr.php',
        {
            command: 'DeleteItemFromTag',
            item_id: item_id,
            tag: id,
            course: course_id
        },
        function (data) {
            if (!data.success) {
                alert('The system was unable to remove the tag from this item [' + item_id + ']. Please try again in a few minutes.');
            }
            else {
                alert("Tag successfully deleted from this course item.");
                $('#tag-container-' + id).remove();
                window.instructor_home_reload = true;
            }
        },
        'json');
    return true;
}

var existingCourseTags = [];
$.get(
    '/passtolicr.php',
    {
        command: 'ListTags',
        course: course_id
    },
    function (data) {
        var i = 0;
        if (!data.success) {
            alert('Failed to retrieve existing course tags.');
        } else {
            for (i = 0; i < data.data.length; i++) {
                existingCourseTags.push(data.data[i].name);
            }
        }
    },
    'json'
);
$('body').on('keyup', '#add_student_tag,#ubc_id_tags', function () {
    console.log($(this).val());

    var $this = $(this),
        tags = $this.val().split(/\s*;\s*/),
        lastTag = tags.pop().toLowerCase(),
        i = 0,
        len = existingCourseTags.length,
    // html = '',
        maybe = [];
    console.log(tags);
    if (len > 0) {
        for (i = 0; i < len; i++) {
            if (lastTag && existingCourseTags[i].toLowerCase().indexOf(lastTag) === 0) {
                maybe.push('<a href="javascript:void(0)" class="btn btn-tag tag_adder_' + $this.attr('id') + '" data-tag-index="' + i + '">' + existingCourseTags[i] + '</a>');
            }
        }
    }
    if (maybe.length) {
        $('#tag_suggestions_' + $this.attr('id')).html('Suggestions: ' + maybe.join('&nbsp;'));
    } else {
        $('#tag_suggestions_' + $this.attr('id')).html('');
    }
    return true;
}).on('click', '.tag_adder_add_student_tag', function () {
    var id = $('#add_student_tag');
    var tags = $(id).val().split(/\s*;\s*/),
        tagIndex = $(this).data('tag-index');
    tags.pop();
    tags.push(existingCourseTags[tagIndex]);
    id.val(tags.join('; ') + '; ');
    id.focus();
    //id.keyup();
}).on('click', '.tag_adder_ubc_id_tags', function () {
    var id = $('#ubc_id_tags');
    var tags = $(id).val().split(/\s*;\s*/),
        tagIndex = $(this).data('tag-index');
    tags.pop();
    tags.push(existingCourseTags[tagIndex]);
    id.val(tags.join('; ') + '; ');
    id.focus();
    //id.keyup();
});

/*function updateTags(course_id, key) {
 var upStr = "";
 var _tagField = $('#add_student_tag'), tag = _tagField.val().split(/\s*;\s*!/), i = 0, _tag = '';
 console.log('updating tags', tag);
 for (i = 0; i < tag.length; i++) {
 _tag = tag[i].toString();
 if (_tag.length > 0) {
 addTag(_tag, key, course_id);
 upStr = "Tags";
 }
 window.instructor_home_reload = true;
 }
 _tagField.val('');
 return upStr;
 }*/

function startUpdate(course_id, item_id, puid) {

    $('#instructor-reading-urls').hide();
    $('#instructor-reading-save-progress').show();
    $('#instructor-reading-content').css('z-index', -1).css('opacity', 0.2);
    $('#save-progress-bar').css('width', '1%');
    updateProgressBar("#save-progress-bar");


    $.ajax({
        url: '/passtolicr.php',
        data: {
            command: 'GetCIInfo',
            item_id: item_id,
            course: course_id
        },
        dataType: 'json',
        async: false
    }).done(function (data) {

        console.log('Global itemBibdata');
        console.log(itemBibdata);
        console.log('Request GetCIInfo');
        console.log(data);

        if (!data.success) {
            alert('The system was unable to start the update process. Please contact LSIT.');
        } else {
            var updateData = [], upStr = [], _ub, success, start, end, required, loanperiod, _tagField, tag, i, _tag, doBibUp, reqB, optB, prefix, tagsUp;
            while (updateData.length > 0) {
                updateData.pop();
            }

            //Start and End Dates
            start = $('#request_start_date').val();
            end = $('#request_end_date').val();

            //console.log("Stored Course Start Date: " + data.data.dates.course_item_start);
            //console.log("Submit Course Start Date: " + start);

            if (data.data.dates.course_item_start != start || data.data.dates.course_item_end != end) {
                //if (data.data.dates.course_item_start != start) { console.log("Update dates because start is different"); }
                //if (data.data.dates.course_item_end != end) { console.log("Update dates because end is different"); }
                updateData.push({
                    command: 'SetCIDates',
                    item_id: item_id,
                    course: course_id,
                    startdate: start,
                    enddate: end
                });
            }

            //Required Reading Flag
            required = $('input[name=update_required_reading]:checked').val();
            //console.log("Stored Required: " + data.data.required);
            //console.log("Submit Required: " + required);

            if (data.data.required != required) {
                //console.log("Update required as it is different");
                //__ur = updateRequired(course_id, key, required);
                updateData.push({
                    command: 'SetCIRequired',
                    item: item_id,
                    course: course_id,
                    required: required
                });
            }

            //Loan Period
            loanperiod = 'N/A'; //because if it is n/a the select below is disabled, and so you wouldn't get the na value

            $("#loanperiods").each(function () {
                loanperiod = $(this).find("option:selected").text();
            });

            //console.log("Stored Loan Period: " + data.data.loanperiod);
            //console.log("Submit Loan Period: " + loanperiod);

            if (data.data.loanperiod != loanperiod) {
                //console.log("Update loanperiod as it is different");
                updateData.push({
                    command: 'SetCILoanPeriod',
                    course: course_id,
                    item_id: item_id,
                    loanperiod: loanperiod
                });
            }

            //Student Notes
            $('#submit_student_note').each(function () {
                var nid = $(this).data('nid'), content = $.trim($(this).val()), oldSNote = itemBibdata[item_id].student_note;
                if (content.length > 0 && content != '""' && content != oldSNote && content != 'Enter note here') {
                    if (nid == -1) {
                        updateData.push({
                            command: 'AddCINote',
                            author_puid: puid,
                            content: content,
                            item_id: item_id,
                            course: course_id,
                            roles_multi: 'Student'
                        });
                    } else {
                        updateData.push({
                            command: 'UpdateNote',
                            note_id: nid,
                            content: content,
                            roles_multi: 'Student'
                        });
                    }
                }
            });

            $('#new-staff-note').each(function () {
                var content = $.trim($(this).val());
                if (content.length > 0 && content != '""' && content != 'Enter note here') {
                    updateData.push({
                        command: 'AddCINote',
                        author_puid: puid,
                        content: content,
                        item_id: item_id,
                        course: course_id,
                        roles_multi: 'Administrator,Library Staff,Instructor'
                    });
                }
            });

            //Course Item Tags
            _tagField = $('#add_student_tag');
            tag = _tagField.val().split(/\s*;\s*/);
            _tag = '';
            tagsUp = true;

            if (_tagField.val() == "" || tag.length == 0 || _tagField.val() == "Semicolon; separated; tags") {
                tagsUp = false;
            } else {
                for (i = 0; i < tag.length; i++) {
                    _tag = tag[i].toString();
                    if (_tag.length > 0) {
                        var ret = addTag(_tag, item_id, course_id);
                        if (ret == false) {
                            tagsUp = -1;
                        }
                    }
                }
                _tagField.val('');
            }

            //URI
            var currentUriSelector = '#' + item_id + '_item_uri'; //build the uri selector

            console.log(currentUriSelector);

            if($(currentUriSelector).val() !== undefined){
                console.log("Stored URI: " + data.data.uri);
                console.log("Submit URI: " + $(currentUriSelector).val());

                if (data.data.uri != $(currentUriSelector).val()) {
                    console.log("Update uri as it is different");
                    updateData.push({
                        command: 'SetItemURI',
                        item_id: item_id,
                        uri: $(currentUriSelector).val()
                    });
                }
            }

            //Bibdata
            doBibUp = false;
            reqB = $("#instructor-reading-required-bibdata");
            optB = $("#instructor-reading-optional-bibdata");
            prefix = item_id + '_';

            reqB.find('input').each(function () {
                var key;
                if ($(this).attr("id").indexOf(prefix) !== -1) {
                    key = $(this).attr("id").replace(prefix, '');
                    if (itemBibdata[item_id].hasOwnProperty(key)) {
                        if ($(this).val().toString() !== itemBibdata[item_id][key]) {
                            itemBibdata[item_id][key] = $(this).val();
                            doBibUp = true;
                        }
                    }

                }
            });

            optB.find('input').each(function () {
                var key;
                if ($(this).attr("id").indexOf(prefix) !== -1) {
                    key = $(this).attr("id").replace(prefix, '');
                    if (itemBibdata[item_id].hasOwnProperty(key)) {
                        if ($(this).val().toString() !== itemBibdata[item_id][key]) {
                            itemBibdata[item_id][key] = $(this).val();
                            doBibUp = true;
                        }
                    }
                }
            });

            if (doBibUp) {
                _ub = updateBibdata(item_id);
            }

            console.log(' ');
            console.log(' QueuedCommands ');
            console.log(updateData);
            console.log(' ');

            //Queued updates
            success = false;
            $.each(updateData, function (index, value) {
                console.log(value);
                $.ajax({
                    url: '/passtolicr.php',
                    data: value,
                    cache: false,
                    dataType: 'json'
                }).done(function (data) {
                    console.log(data);
                    if (!data.success) {
                        alert('An error occurred whilst trying to save changes. Please try again in a few minutes.');
                        //TODO SKK want to add a function that sends an email to us so we know it failed
                    } else if (data.success) {
                        success = true;
                        updateProgressBar("#save-progress-bar");
                        //TODO SKK maybe add div to modal of what was updated, or list?
                    }
                }).always(function (data) {
                    console.log(data);
                });

            });

            if (_ub) {
                upStr.push('Bibdata');
            }
            if (success) {
                upStr.push('Request');
            }
            if (tagsUp) {
                upStr.push('Tags');
            }

            setTimeout(function () {
                if (_ub || success || tagsUp) {
                    alert("Changes saved to: " + upStr.join() + "\nPlease close the popup to reload your readings.");
                    $('#save-progress-bar').css('width', '100%').addClass('bar-success');
                    window.location.reload();
                } else if (tagsUp == -1) {
                    alert("Tags could not be Saved\nPlease try again in a few minutes. If the issue persists, please contact LSIT");
                } else {
                    $('#instructor-reading-urls').show();
                    $('#instructor-reading-save-progress').hide();
                    $('#instructor-reading-content').css('z-index', 'auto').css('opacity', 1);
                }
            }, 125);
        }
    });

}

$('#sh-tag-cloud').click(function () {
    $('#tag-cloud').slideToggle();
});

/*function bindCourseHomeLegend(key) {
 /!*
 var _p = $('#reading-'+key +'-content'), _l = $('legend.' + key + '-legend');
 _p.find(_l).on('click', function () {
 $(this).parent().find('div.fieldset-content').slideToggle("slow");
 $(this).children('.lsit-caret').toggle();
 });
 *!/
 }*/

function highlightFields() {
    var __parent = $('#instructor-reading-content');

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

/*function invokeUIHighlighting(id) {

 var __parent = $('#reading-' + id + '-content');

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
 }*/

function resetBibdataFields(_id) {
    var _replaceStr = _id + '_', key;
    $('#instructor-reading-required-bibdata').find('input.bibdata-input').each(function () {
        key = $(this).attr("id").replace(_replaceStr, '');
        if (key in itemBibdata[_id]) {
            $(this).val(itemBibdata[_id][key]);
        }
    });
    $('#instructor-reading-optional-bibdata').find('input.bibdata-input').each(function () {
        key = $(this).attr("id").replace(_replaceStr, '');
        if (key in itemBibdata[_id]) {
            $(this).val(itemBibdata[_id][key]);
        }
    });
}

function printReadingList() {
    var fr;
    $('#instructor-readings').css({width: '100%'});
    if (navigator.appName.indexOf('Internet Explorer') > 0) {
        ieprint();
    } else {
//        revokeDataTable();
        if (window.frames['contentFrame']) {
            fr = window.frames['contentFrame'];
            fr.focus();
            fr = fr.contentDocument || fr;
            fr.print();
        } else {
            window.print();
        }
//        invokeDataTable();
    }
}

function ieprint() {
    window.open('/instructorhome/id/' + course_id + '/print/1');
}

function ieprint2() {
//    revokeDataTable();
    print();
    alert('Press OK to close this window.');
    window.close();
}

function repaint() {
    $('body').addClass('dummyClass').removeClass('dummyClass');//force repaint
}

function updateBar() {
    var submitProgBar = $("#submit-prog-bar");
    var perc = parseInt((submitProgBar.css("width")).replace("%", ""));
    submitProgBar.redraw();
    return (perc + 10).toString() + "%";
}

/*function requiredFieldsFilledInstructor(key) {
 //"use strict";
 var id = '#' + key + '-bibdata', _id = $('#' + key + '-bibdata'), proceed = true, field = "Missing Required Information: ", btn = $('#' + key + '_save_button');

 $(id + '-success').empty();
 _id.find("input").each(function () {
 if (this.getAttribute('data-required') === 'true' && $(this).val() === '') {
 field = field + ($(this).prev('label').text()) + ", ";
 proceed = false;
 }
 });

 // catch docstore files
 if ($("#upload_file_docstore") !== null && $("#upload_file_docstore").length) {
 $("#upload_file_docstore").contents().find("input").each(function () {
 if (this.getAttribute('data-required') === 'true' && $(this).val() === '') {
 field = field + ($('#upload_file_docstore').prev('label').text()) + ", ";
 proceed = false;
 }
 });
 }

 field = field.replace(/^(,|\s)+|(,|\s)+$/g, '');
 if (proceed) {
 $(id + '-success').empty();
 btn.removeClass('btn-dead-link').text('Save Changes').attr('disabled', false);
 } else {
 $(id + '-success').text(field);
 btn.addClass('btn-dead-link').text('Cannot Save Item...').attr('disabled', true);
 }


 }*/

jQuery(document).ready(function ($) {
    $('#hometabs').tab();
    $('#helptabs').tab();
    $('body').css("overflow", "auto");

    $('#print_reading_list_button').on('click', function (e) {
        e.preventDefault();
        printReadingList();
        return true;
    });

    $('#clear_filter_button').on('click', function (e) {
        e.preventDefault();
        clearFilters();
        return true;
    });

    $("a[href^='mailto:']").on("click", function () {
        window.top.location = $(this).prop("href");
        return false;
    });
});


function checkRequiredFields() {
    //"use strict";
    var
        id = $("#instructor-reading-required-bibdata"),
        ds = $("#upload_file_docstore"),
        bs = $('#bibdata-success'),
        proceed = true,
        field = "Missing Information: ",
        btn = $('#instructor-reading-save');

    bs.empty();

    id.find("input").each(function () {
        if (this.getAttribute('data-required') === 'true' && $(this).val() === '') {
            field = field + ($(this).prev('label').text()) + ", ";
            proceed = false;
        }
    });

    // catch docstore files
    if (ds !== null && ds.length) {
        ds.contents().find("input").each(function () {
            if (this.getAttribute('data-required') === 'true' && $(this).val() === '') {
                field = field + (ds.prev('label').text()) + ", ";
                proceed = false;
            }
        });
    }

    field = field.replace(/^(,|\s)+|(,|\s)+$/g, '');
    if (proceed) {
        bs.empty();
        btn.removeClass('btn-dead-link').text('Save Changes').attr('disabled', false);
    } else {
        bs.text(field);
        btn.addClass('btn-dead-link').text('Cannot Save...').attr('disabled', true);
    }
}

$(function () {
    setTimeout(function () {
        if ((window.document.location.toString().indexOf("/open/") != -1)) {

            var res = window.document.location.toString().split("/open/");
            var id = 'reading-' + res[1];
            var testExists = document.getElementById(id);
            if (testExists === null) {
                alert('This reading is not available anymore.');
            }
            else {
                $('#' + id).find('a.show-details').click();
            }
        }
    }, 125);
});
