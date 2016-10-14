var f;
var isNew, _itemIdToRequestWith, _submitObjecttoCreateWith;

crMenu.init({
    uri: env_url
});
var postdata;
var startedSubmit = false;
var searchResults;
var searchEntries = {};

function clearVariables() {
    var _key;
    for (_key in searchResults) {
        if (searchResults.hasOwnProperty(_key)) {
            delete searchResults[_key];
        }
    }
}

var dialogWidth = "60%";
if (window.innerWidth < 1000) {
    dialogWidth = "95%";
}

var dialogHeight = window.innerHeight ? window.innerHeight : $(window).height();

dialogHeight -= 50;

function showSubmitForm(data, type, isManual, requestFormat) {
    console.log("Show submit form");
    var searchResultsDialog = $('#search-results');
    var submitForm = $('#submit-form');
    var noItemDialog = $('#no-item');
    
    if (searchResultsDialog.hasClass('ui-dialog-content') && searchResultsDialog.dialog('isOpen') === true) {
        searchResultsDialog.dialog('close');
    }

    if (noItemDialog.hasClass('ui-dialog-content') && noItemDialog.dialog('isOpen') === true) {
        noItemDialog.dialog('close');
    }

    //var form = crMenu.getInstance();
    console.log(data, type, isManual, requestFormat);
    _submitObjecttoCreateWith = null;
    isNew = true;

    submitForm.empty();
    console.log("/addreserves.getForm");

    $.ajax({
        type: "POST",
        data: {
            form_type: type,
            forced_manual: isManual,
            data: data
        },
        dataType: "json",
        url: '/addreserves.getForm'
    })
        .done(function (data) {
            console.log("/addreserves.getForm done, data: ");
            console.log(data);
            submitForm.empty().html(data.data.form).redraw();
            _submitObjecttoCreateWith = data.data.submit_object;
            if (data._new) {
                isNew = true;
                _itemIdToRequestWith = -3;
            } else {
                isNew = false;
                _itemIdToRequestWith = data.data.itemid;
            }
            console.log("IS NEW: ", isNew);
            bindLegend();

            // preselect option in request form if applicable
            if (type === 'request_general' && requestFormat) {
                $('#purchase_request_format').val(requestFormat);
            }


            var finalSubmitTypeId = $('#ubc_id_final_submit_typeid');

            //if DocStore, produce the file upload form
            if (finalSubmitTypeId.val() == 1) {
                setTimeout(
                    function () {
                        var idocument = document.getElementById('upload_file_docstore').contentWindow.document;
                        var _body = $('body', idocument);
                        _body.css("margin", "0").css("padding", "0");
                        document.getElementById('upload_file_docstore').contentWindow.document.write('<body style="margin: 0; padding: 0;"><form enctype="multipart/form-data" style="margin: 0; padding: 0;" action="' + docstore + '/docstore.create" method="POST" id="docstore-submission-form"><input type="hidden" name="actionlabel" value="docstore-submission" /><input type="hidden" name="course_id" value="" class="insert-course_id" /><input type="hidden" name="item_id" value="" class="insert-item_id" /><input type="hidden" name="puid" value="" class="insert-puid" /><input type="hidden" name="initiator" value="connect" /><input class="span10 stored" type="file" id="ubc_id_uploadfile" name="uploadfile" data-required="true" onclick="parent.verifyFields();" onchange="parent.verifyFields();" oninput="parent.verifyFields();" /></form></body>');
                    }, 1000
                )
            }

            console.log("/addreserves.getForm setTimeout() 1");
            setTimeout(function () {

                var _submit_item = $('#submit-item');

                _submit_item.dialog({
                    modal: true,
                    width: dialogWidth,
                    // maxHeight: dialogHeight,
                    draggable: false,
                    resizable: false,
                    title: "Submit Item: " + data.data.title,
                    open: function () {
                        console.log("/addreserves.getForm Dialog Opened");

                        dialogMobileFixes();
                        dialogBindings();

                        submitForm.css('overflow', 'hidden').css('overflow-y', 'scroll').redraw();

                        //attach tooltips
                        $('submit-form').find('.fieldset-content').find('.row-fluid').find('input').qtip({
                            show: 'focus',
                            hide: 'blur',
                            position: {
                                my: 'top left',  // Position my top left...
                                at: 'bottom left' // at the bottom right of...
                            },
                            style: {
                                classes: 'qtip-dark qtip-shadow'
                            }
                        });

                        setDates(querySDate, queryEDate); //course start and end date

                        var visitHref = $('#visit-item-url');
                        if (visitHref.length) {
                            visitHref.on('click', function (e) {
                                e.preventDefault();
                                var url = $('#ubc_id_item_uri').val();
                                window.open(url, '_blank');
                                return false;
                            })
                        }
                        if (document.createEvent) {
                            submitForm.perfectScrollbar({
                                wheelSpeed: 50,
                                wheelPropagation: true,
                                suppressScrollX: true
                            });
                        } else{
                            submitForm.css('overflow-y','scroll');
                            submitForm.addClass('repaint').removeClass('repaint');
                        }
                        //this adds an E to issn or isbn to make it eissn or eisbn
                        var isxn = $('#ubc_id_item_isxn');
                        var blockSubmit;

                        if (typeof isxn.val() !== 'undefined' && isxn.val().indexOf('e__') > -1) {
                            var prevText = 'E' + isxn.prev('span').text();
                            isxn.val(isxn.val().replace('e__', '')).prev('span').text(prevText);
                        }

                        $("div#submit-form").find("input").each(function () {
                            //bind a passive listener to all required fields
                            if (this.getAttribute('data-required') === 'true') {
                                $(this).bind("propertychange change click keyup input paste", function(e){
                                    verifyFields();
                                });
                            } else {
                                $(this).removeClass('requiredFieldBorder');
                            }
                        });

                        verifyFields();

                        /*
                        blockSubmit = setInterval(function () {
                            requiredFieldsFilled(isNew);
                        }, 150);
                        */

                        if ($.inArray($('#ubc_id_final_submit_format').val(), ['pdf_chapter', 'ebook_chapter', 'book_chapter']) !== -1) {
                            $('#ubc_id_item_title').removeAttr('disabled').attr("readonly", false);
                            $('#ubc_id_item_incpages').removeAttr('disabled').attr("readonly", false);
                            isNew = true;
                        }

                        $('#submit-item-submit-btn').on('click', function (e) {
                            console.log("/addreserves.getForm submit button hit");
                            $(this).off(e);
                            var progressBar = $("#submit-prog-bar");

                            //$("#submit-item").removeClass("xxlarge").addClass("large");
                            submitForm.css("width", "0").css("height", "0");
                            $("#submit-prog").css("width", "100%").css("height", "350");
                            progressBar.css("width", "1%");
                            $(document).addClass("dummyClass").removeClass("dummyClass");

                            setTimeout(function () {
                                (function () {
                                    console.log("/addreserves.getForm setTimeout() 2");

                                    if (!startedSubmit) {
                                        console.log("/addreserves.getForm setTimeout() !startedSubmit");

                                        _submit_item.addClass("dummyClass").removeClass("dummyClass");
                                        $(document).addClass("dummyClass").removeClass("dummyClass");

                                        clearInterval(blockSubmit); //you could only get here if submit is no longer being blocked by
                                        startedSubmit = true;
                                        var key, i, submit_bibdata, response_message;

                                        for (key in _submitObjecttoCreateWith) {
                                            if (_submitObjecttoCreateWith.hasOwnProperty(key)) {
                                                var this_id = 'input#ubc_id_' + key;
                                                if (typeof $(this_id).val() !== "undefined" && $(this_id).val() !== "")
                                                    _submitObjecttoCreateWith[key] = $(this_id).val();
                                            }
                                        }

                                        var _rri = $('#request-related-inputs');
                                        _rri.find('input:not([type=checkbox])').each(function () {
                                            _submitObjecttoCreateWith['request_' + $(this).attr("name")] = $(this).val();
                                        });
                                        _rri.find('input[type=checkbox]').each(function () {
                                            _submitObjecttoCreateWith['request_' + $(this).attr("name")] = $(this).prop('checked');
                                        });
                                        _rri.find('select').each(function () {
                                            _submitObjecttoCreateWith['request_' + $(this).attr("name")] = $(this).val();
                                        });

                                        progressBar.css("width", "5%");
                                        submit_bibdata = _submitObjecttoCreateWith; // {}
                                        submit_bibdata['engine'] = 'cr_system';

                                        if (debug) {
                                            console.log("typeid is: " + finalSubmitTypeId.val());
                                            console.log("format is: " + finalSubmitTypeId.val());
                                            console.log("isNew is: " + isNew.toString());
                                            console.log("submit data:");
                                            console.log(submit_bibdata);
                                        }

                                        response_message = "";

                                        _submit_item.addClass("dummyClass").removeClass("dummyClass");
                                        $(document).addClass("dummyClass").removeClass("dummyClass");
                                        if (isNew) {
                                            //console.log("/addreserves.getForm setTimeout() isNew");
                                            var _finalURI = (submit_bibdata.initial_uri == '' ? (submit_bibdata.item_uri == '' ? '' : submit_bibdata.item_uri) : submit_bibdata.initial_uri);
                                            //console.log("POSTing to addreserves.createItem");
                                            $.ajax({
                                                type: "POST",
                                                url: '/addreserves.createItem',
                                                data: {
                                                    title: submit_bibdata.item_title,
                                                    callnumber: submit_bibdata.item_callnumber || "",
                                                    bibdata: submit_bibdata,
                                                    uri: _finalURI,
                                                    type: finalSubmitTypeId.val(),
                                                    filelocation: _finalURI,
                                                    author: submit_bibdata.item_author,
                                                    physical_format: $('#purchase_request_format').val() ? $('#purchase_request_format').val() : $('#ubc_id_final_submit_format').val()
                                                },
                                                dataType: 'json',
                                                async: false,
                                                timeout: 120000
                                            }).done(function (res) {
                                                //console.log("POSTing done!");
                                                if (!res.success) {
                                                    // -4 error
                                                    response_message = "The system failed to initiate a connection to the Course Reserves Server. Please contact LSIT stating Item Error Code (-4)"; // Could not create item -- Item Error Code -4
                                                    $("#submit-prog-bar").addClass("bar-danger");
                                                    if (debug) {
                                                        //console.log("Could not create item (item needed to be created");
                                                    }
                                                }
                                                else {
                                                    _itemIdToRequestWith = res.data;
                                                    response_message = "The system created the item with ItemID: " + _itemIdToRequestWith;
                                                    if (debug) {
                                                        //console.log("Created item with id: " + _itemid);
                                                    }
                                                }
                                            }).fail(function () {
                                            });
                                        }

                                        //console.log("Attempting to request ItemID: " + _itemIdToRequestWith);

                                        $("#submit-item").addClass("dummyClass").removeClass("dummyClass");
                                        $(document).addClass("dummyClass").removeClass("dummyClass");
                                        progressBar.css("width", updateBar());

                                        if (_itemIdToRequestWith < -1) {
                                            if (_itemIdToRequestWith === -3) {
                                                response_message = "An error occurred while processing this request. We apologize for any inconvenience. Please try again in a few minutes. If the problem persists, please contact LSIT stating Item Error Code -3";
                                            }
                                            $("#submit-prog-bar").addClass("bar-danger");
                                        } else if (_itemIdToRequestWith > 0) {
                                            //console.log("Loanperiod (look in submit_object -> request_load_period): ");
                                            //console.log(form);
                                            //console.log("POSTing to passtolicr");
                                            $.ajax({
                                                url: '/passtolicr.php',
                                                data: {
                                                    command: 'RequestItem',
                                                    course: course_id,
                                                    item_id: _itemIdToRequestWith,
                                                    loanperiod: $('#ubc_id_loan_period').val(),
                                                    requestor: puid,
                                                    startdate: $('#ubc_id_start_date').val(),
                                                    enddate: $('#ubc_id_end_date').val()
                                                },
                                                dataType: 'json',
                                                async: false
                                            }).done(function (ridata) {
                                                //console.log("passtolicr is done");
                                                if (!ridata.success) {
                                                    response_message = '(' + ridata.code + ') ' + ridata.message;
                                                    if (ridata.code == 413) {
                                                        response_message = 'This physical item has already been requested for this course.';
                                                    }
                                                    progressBar.addClass("bar-danger");

                                                } else {


                                                    response_message = "Item created with ItemID: " + _itemIdToRequestWith;
                                                    progressBar.css("width", updateBar());
                                                    if ($('input[name=flag-required-reading]:checked').val() == 1) {
                                                        //console.log("POSTing to passtolicr (required-reading)");
                                                        $.ajax({
                                                            url: '/passtolicr.php',
                                                            data: {
                                                                command: 'SetCIRequired',
                                                                course: course_id,
                                                                item: _itemIdToRequestWith,
                                                                required: 1
                                                            },
                                                            dataType: 'json'
                                                        }).done(function () {
                                                            console.log("POSTing to passtolicr (required-reading) DONE");
                                                            $("#submit-prog-bar").css("width", updateBar());
                                                            if (debug) {
                                                                //console.log("Set item os required resding");
                                                            }
                                                        }).fail(function () {
                                                            alert('Failed to set item required status.');
                                                            if (debug) {
                                                                //console.log("Was not able to set item as required reading");
                                                            }
                                                        });
                                                    }
                                                    //console.log("POSTing to passtolicr (setCIField)");
                                                    $.ajax({
                                                        url: '/passtolicr.php',
                                                        data: {
                                                            command: 'SetCIField',
                                                            course: course_id,
                                                            item_id: _itemIdToRequestWith,
                                                            field: 'location',
                                                            value: submit_bibdata.availability_id
                                                        },
                                                        dataType: 'json'
                                                    }).done(function () {
                                                        //console.log("POSTing to passtolicr (setCIField) DONE");
                                                        if (debug) {
                                                        }
                                                    }).fail(function () {
                                                        //alert('Failed to set status to id ' + $('#ubc_id_final_status').val() + ' (this item was submitted without a url');
                                                    });

                                                    $('#ubc_id_final_status').each(function () {
                                                        if ($(this).val() > -1) {
                                                            //console.log("POSTing to passtolicr (SetCIStatus)");
                                                            $.ajax({
                                                                url: '/passtolicr.php',
                                                                data: {
                                                                    command: 'SetCIStatus',
                                                                    status: $(this).val(),
                                                                    course: course_id,
                                                                    item_id: _itemIdToRequestWith
                                                                },
                                                                dataType: 'json'
                                                            }).done(function () {
                                                                //console.log("POSTing to passtolicr (SetCIStatus) DONE");
                                                                if (debug) {
                                                                }
                                                            }).fail(function () {
                                                                //alert('Failed to set status to id ' + $('#ubc_id_final_status').val() + ' (this item was submitted without a url');
                                                            });
                                                        }
                                                    });

                                                    //set semicolon sep tags, if there are any in the field
                                                    //as this is in a for, it will not be async
                                                    var tags = ($('#ubc_id_tags').val()).split(";");
                                                    for (i = 0; i < tags.length; i++) {
                                                        (function () {
                                                            var tag = tags[i].replace(/^\s+|\s+$/g, '');
                                                            if (tag.length > 0) {
                                                                //console.log("POSTing to passtolicr (CreateTag)");
                                                                $.ajax({
                                                                    url: '/passtolicr.php',
                                                                    data: {
                                                                        command: 'CreateTag',
                                                                        course: course_id,
                                                                        name: tag
                                                                    },
                                                                    dataType: 'json'
                                                                }).done(function (res) {
                                                                    //console.log("POSTing to passtolicr (CreateTag) DONE");
                                                                    if (!res.success) {
                                                                        alert('The system was unable to make the requested tag.')
                                                                    }
                                                                    else {
                                                                        //console.log("POSTing to passtolicr (AddItemToTag)");
                                                                        $.ajax({
                                                                            url: '/passtolicr.php',
                                                                            data: {
                                                                                command: 'AddItemToTag',
                                                                                course: course_id,
                                                                                tag: res.data,
                                                                                item_id: _itemIdToRequestWith
                                                                            },
                                                                            dataType: 'json'
                                                                        }).done(function (res) {
                                                                            //console.log("POSTing to passtolicr (AddItemToTag) DONE");
                                                                            if (!res.success) {
                                                                                alert('The system was unable to add the item to the tag: ' + tag)
                                                                            }
                                                                        }).fail(function () {
                                                                            alert('Could not talk to the Course Reserves server. Please contact LSIT.');
                                                                        });
                                                                    }
                                                                }).fail(function () {
                                                                    alert('Failed to set item as required or not..');
                                                                }).always(function () {
                                                                    progressBar.css("width", updateBar());
                                                                });
                                                            }
                                                        })();
                                                    }


                                                    //set flag if there are notes to staff
                                                    $('#ubc_id_note_staff').each(function () {
                                                        if ($.trim($(this).val()) != "" && $(this).val() != "Enter note here") {
                                                            //console.log("POSTing to passtolicr (AddCINote)");
                                                            $.ajax({
                                                                url: '/passtolicr.php',
                                                                data: {
                                                                    command: 'AddCINote',
                                                                    author_puid: puid,
                                                                    content: JSON.stringify($(this).val()),
                                                                    roles_multi: '6,7',
                                                                    course: course_id,
                                                                    item_id: _itemIdToRequestWith
                                                                },
                                                                dataType: 'json'
                                                            }).done(function () {
                                                                //console.log("POSTing to passtolicr (AddCINote) DONE");
                                                                progressBar.css("width", updateBar());
                                                                if (debug) {
                                                                    //console.log("Set note to staff");
                                                                }
                                                            }).fail(function () {
                                                                alert('Failed to set note to staff.');
                                                            });
                                                        }
                                                    });

                                                    //set page range
                                                    $('#ubc_id_item_incpages').each(function () {
                                                        //console.log("POSTing to passtolicr (SetCIRange)");
                                                        // if ($('#ubc_id_note_staff').val().length > 0) {
                                                        $.ajax({
                                                            url: '/passtolicr.php',
                                                            data: {
                                                                command: 'SetCIRange',
                                                                course: course_id,
                                                                item_id: _itemIdToRequestWith,
                                                                range: JSON.stringify($(this).val())
                                                            },
                                                            dataType: 'json'
                                                        }).done(function () {
                                                            //console.log("POSTing to passtolicr (SetCIRange) DONE");
                                                            progressBar.css("width", updateBar());
                                                            if (debug) {
                                                                //console.log("Set note to staff");
                                                            }
                                                        }).fail(function () {
                                                            alert('Failed to set page range');
                                                        });
                                                    });


                                                    //set flag if there are notes to staff
                                                    $('#ubc_id_note_student').each(function () {
                                                        //console.log("POSTing to passtolicr (AddCINote)");
                                                        if ($.trim($(this).val()) != "" && $(this).val() != "Enter note here") {
                                                            $.ajax({
                                                                url: '/passtolicr.php',
                                                                data: {
                                                                    command: 'AddCINote',
                                                                    author_puid: puid,
                                                                    content: JSON.stringify($(this).val()),
                                                                    roles_multi: 9,
                                                                    course: course_id,
                                                                    item_id: _itemIdToRequestWith
                                                                },
                                                                dataType: 'json'
                                                            }).done(function () {
                                                                //console.log("POSTing to passtolicr (AddCINote) DONE");
                                                                progressBar.css("width", updateBar());
                                                                if (debug) {
                                                                    //console.log("Set note to students");
                                                                }
                                                            }).fail(function () {
                                                                alert('Failed to set note to student');
                                                            });
                                                        }
                                                    });
                                                    progressBar.css("width", "95%").addClass("bar-success");
                                                }
                                            }).fail(function () {
                                                response_message = "The system was able to create the item  with Item ID: " + _itemIdToRequestWith + ". " +
                                                    "However, the item could not be added to the course. Please contact LSIT stating Item " +
                                                    "Error Code (-5)";// Could not request item - Item Error Code -5
                                                progressBar.addClass("bar-danger");
                                            });
                                        }

                                        //add item to docstore, if pdf
                                        if (finalSubmitTypeId.val() == 1 && $('iframe#upload_file_docstore').length > 0) {
                                            var uploadFileDocStore = $('#upload_file_docstore');
                                            uploadFileDocStore.contents().find('.insert-course_id').each(function () {
                                                $(this).val(course_id)
                                            });
                                            uploadFileDocStore.contents().find('.insert-item_id').each(function () {
                                                $(this).val(_itemIdToRequestWith)
                                            });
                                            uploadFileDocStore.contents().find('.insert-puid').each(function () {
                                                $(this).val(puid)
                                            });
                                            uploadFileDocStore.contents().find('#docstore-submission-form').submit();
                                            progressBar.css("width", updateBar());

                                            uploadFileDocStore.load(function () {
                                                //alert('document has uploaded');
                                                console.log("setTimeout upload_file_docstore");
                                                setTimeout(function () {
                                                    //this is set to execute in the queue after all other items are queued
                                                    (function () {
                                                        $("#submit-prog-bar").css("width", "100%");
                                                        $('#submit-item').find('.close-reveal-modal').click();

                                                        $('#submit-results').dialog({
                                                            modal: true,
                                                            width: dialogWidth,
                                                            maxHeight: dialogHeight,
                                                            draggable: false,
                                                            resizable: false,
                                                            open: function () {
                                                                dialogMobileFixes();
                                                                dialogBindings();

                                                                $('#submit-results-success').text(response_message);
                                                                $('#submit-results-submit-btn').on('click', function () {
                                                                    $('#submit-results').trigger('reveal:close');
                                                                    location.reload();
                                                                });
                                                                submitForm.empty();
                                                            },
                                                            close: function () {
								location.reload();
                                                            }
                                                        });
                                                    })();//end of trigger submit complete timeout
                                                }, 1250);
                                            });
                                        } else {
                                            setTimeout(function () {
                                                //this is set to execute in the queue after all other items are queued
                                                (function () {
                                                    $("#submit-prog-bar").css("width", "100%");
                                                    // $('#submit-item').find('.close-reveal-modal').click();
                                                    $('#submit-item').dialog('close');
                                                    $('#submit-results').dialog({
                                                        modal: true,
                                                        width: dialogWidth,
                                                        maxHeight: dialogHeight,
                                                        draggable: false,
                                                        resizable: false,
                                                        open: function () {

                                                            dialogMobileFixes();
                                                            dialogBindings();

                                                            submitForm.empty();
                                                            $('#submit-results-success').text(response_message);
                                                            $('#submit-results-submit-btn').on('click', function () {
                                                                $('#submit-results').trigger('reveal:close');
                                                                location.reload();
                                                            });
                                                        },
                                                        close: function () {
							    location.reload();
                                                        }
                                                    });
                                                })();//end of trigger submit complete timeout
                                            }, 1250);
                                        }
                                    }//end if not started submit
                                })()
                            }, 50);//timeout on submit click
                        });//end onclick
                    },
                    close: function () {
                        if(document.createEvent) {
                            submitForm.perfectScrollbar('destroy');
                        }
                        submitForm.empty();
                        startedSubmit = false;
                    }
                });

            }, 250);


        })
        .fail(function () {
        })
        .always(function(){

        })
    ;
}

function showSearchForm() {

    var obj = crMenu.getInstance();
    if (obj.hasOwnProperty('form_type') && (typeof obj.form_type !== 'undefined')) {
        //noinspection JSUnresolvedFunction
        // setHeight('#find-item', '#find-form');

        var skip = 'randomNameThatNeverOccurs';
        var _name;
        $('#find-item-submit-btn').show();
        $('#find-form').empty().html(obj.html).find("input[type!='hidden']").each(function () {
            //add specific keyups
            $(this).on('keyup', function () {

                //this is the field in focus triggering keyup
                _name = $(this).attr('name');
                //console.log("Input field: " + name);

                if (_name === 'jtitle' || _name === 'atitle') {
                    if (_name === 'jtitle') {
                        skip = 'atitle';
                    }
                    else {
                        skip = 'jtitle';
                    }
                }
                else {
                    skip = 'randomNameThatNeverOccurs';
                }
                $("#find-form").find("input[name!='" + _name + "']").not(':hidden').each(function () {
                    if ($(this).attr('name').toString() !== skip.toString()) {
                        //console.log('\'' + _name + '\' !== \'' + $(this).attr('name') + '\'');
                        $(this).val('');
                    }
                });
            });
        });
        for (var _id in searchEntries) {
            if (searchEntries.hasOwnProperty(_id)) {
                $("#" + _id).val(searchEntries[_id]).focus();
                delete searchEntries[_id];
            }
        }
        $('#find-item').dialog({
            modal: true,
            width: dialogWidth,
            maxHeight: dialogHeight,
            draggable: false,
            resizable: false,
            title: "Find Reserves Item: " + obj.type,
            open: function () {
                dialogMobileFixes();
                dialogBindings();

                $(document).keypress(function (e) {
                    if (e.which == 13) {
                        $("#find-item-submit-btn").click();
                    }
                });
                searchEntries.length = 0;
                searchEntries = {};
            },
            close: function () {
                $('#find-form').empty();
                $(document).unbind("keypress");
            }
        });
    }
}

function showResultsForm(data, type) {

    var noItemDialog = $('#no-item');
    if (noItemDialog.hasClass('ui-dialog-content') && noItemDialog.dialog('isOpen') === true) {
        noItemDialog.dialog('close');
    }

    var findItemDialog = $('#find-item');
    if (findItemDialog.hasClass('ui-dialog-content') && findItemDialog.dialog('isOpen') === true) {
        findItemDialog.dialog('close');
    }

    var obj = crMenu.getInstance();
    if (obj.hasOwnProperty('form_type') && (typeof obj.form_type !== 'undefined')) {
        $('#results-form').empty().html(obj.getResultHTML(data));

        var _rsl = $('.results-select-link');

        _rsl.click(function () {
            //obj.getSubmitForm(data[this.getAttribute('data-result-index')]);
            //console.log("showSubmitForm(data[" + this.getAttribute('data-result-index') + "]," + data[this.getAttribute('data-result-index')].form_type + ",0);");
            //console.log(data);

            var selectedItemIndex = $(this).attr('data-result-index');

            //console.log("selectedItemIndex: ");
            //console.log(selectedItemIndex);
            setTimeout(function() {

                var selectedItem = data[selectedItemIndex];

                //console.log("selectedItem: ");
                //console.log(selectedItem);

                showSubmitForm(selectedItem, selectedItem.form_type, 0);
            }, 1);
        });
        if (data.length === 1) {
            //console.log("LOG:: length 1 ");
            _rsl.trigger('click');
        } else {
            //console.log("LOG:: Load Dialog");
            $('#search-results').dialog({

                modal: true,
                width: dialogWidth,
                maxHeight: dialogHeight,
                draggable: false,
                resizable: false,
                open: function () {
                    dialogMobileFixes();
                    dialogBindings();
                    // setHeight('#search-results', '#results-form');
                    if(document.createEvent) {
                        $('#results-form').perfectScrollbar({
                            wheelSpeed: 50,
                            wheelPropagation: true,
                            suppressScrollX: true
                        });
                    }else{
                        
                            submitForm.css('overflow-y','scroll');
                            submitForm.addClass('repaint').removeClass('repaint');
                    }
                    //if you have a manual entry button
                    $('#results-manual-entry').on('click', function () {
                        //console.log(obj.form_type);
                        /*obj.getSubmitForm({
                         form_type: obj.form_type,
                         is_direct_entry: true
                         });*/
                        setTimeout(function() {
                            showSubmitForm({}, obj.form_type, 1);
                        }, 1);
                        //var x = showDirectSubmitForm(constructor.direct_type);
                    });
                },
                close: function () {
                    if(document.createEvent) {
                        $('#results-form').perfectScrollbar('destroy');
                    }
                    //if you have a manual entry button
                    $('#results-manual-entry').on('click', function () {
                        return false;
                    });
                }
            });
        }
    }
    else {
        alert("No form type found");
    }
}

$("#find-item-submit-btn").on('click', function () {

    //console.log("#find-item-submit-btn click()");
    var obj = crMenu.getInstance();

    var key;
    postdata = {};
    for (key in postdata) {
        if (postdata.hasOwnProperty(key)) {
            delete postdata[key];
        }
    }

    if (obj.hasOwnProperty('form_type') && (typeof obj.form_type !== 'undefined')) {

        $("div#find-form").find("input").each(function () {
            if ($(this).val() !== '') {
                postdata[$(this).attr("name")] = $(this).val();
                if ($(this).attr("name") !== "type" && $(this).attr("name") !== "locr_type") {
                    postdata.field = $(this).attr("name");
                }
                searchEntries[this.id] = $(this).val();
            }
        });

        if (postdata.jtitle && postdata.atitle) {
            postdata.field = 'title';
        }

        postdata['type'] = obj['type'];

        //console.log("Submitting search with: ");
        //console.log(postdata);

        var src = env_url + "/images/waiting.gif";

        $('#find-form').empty().html('<p style="width: 100%; text-align: center"><img src="' + src + '" width="48" height="48" /></p>');

        $('#find-item-submit-btn').hide();

        //console.log("Posting to /search");

        $.ajax({
            type: "POST",
            url: "/search",
            data: $.param(postdata),
            dataType: 'json'
        })
            .done(function (data) {
                //console.log("Posting to /search (DONE)");
                //console.log(data);
                if (typeof data.documents !== 'undefined' && data.documents.length > 0) {
                    //console.log("SHOW RESULTS FORM");
                    showResultsForm(data.documents, obj['type']);
                } else {
                    //console.log("NO ITEM");
                    noItemReveal();
                }
            })
            .fail(function () {
                noItemReveal();
            });
    }

    function noItemReveal() {
        var findItemDialog = $('#find-item');
        if (findItemDialog.hasClass('ui-dialog-content') && findItemDialog.dialog('isOpen') === true) {
            findItemDialog.dialog('close');
        }

        $('#no-item').dialog({
            modal: true,
            width: dialogWidth,
            maxHeight: dialogHeight,
            draggable: false,
            resizable: false,
            open: function () {

                dialogMobileFixes();
                dialogBindings();
                //console.log(obj)
                //init the no results link
                $('#no-results-manual-entry').one('click', function () {
                    /*crMenu.getInstance().getSubmitForm({
                     form_type: obj.form_type,
                     is_direct_entry: true
                     });*/
                    if (obj.form_type === 'electronic_article' || obj.form_type === 'book_chapter') {
                        setTimeout(function() {
                            showSubmitForm({}, obj.form_type, 1);
                        }, 1);
                    } else {
                        setTimeout(function() {
                            showSubmitForm({}, 'request_general', 1, obj.form_type); // redirect to 'request_general' form
                        }, 1);
                    }
                });
                $('#no-results-restart').on('click', function () {
                    $('#no-item').dialog('close');
                    var searchLink = "#" + crMenu.getInstance().type + "-submit";
                    $(searchLink).click();
                });
            },
            close: function () {
                $('#no-results-manual-entry').on('click', function () {
                    return false;
                });
                $('#no-results-restart').on('click', function () {
                    return false;
                });
            }
        });
    }
});


//-------------------------------------------------------------------
//----------------- Search Form Constructors ------------------------
//-------------------------------------------------------------------
$('#article-submit').click(function () {
    "use strict";
    clearVariables();
    crMenu.getInstance().getSearchForm({
        groupdata: [
            {
                title: "Journal and Article Title",
                fields: {
                    journaltitle: "Journal Title",
                    articletitle: "Article Title"
                }
            },
            {
                title: "DOI",
                fields: {
                    doi: "DOI"
                }
            },
            {
                title: "PubMed ID",
                fields: {
                    pubmedid: "PubMed ID"
                }
            }
        ],
        type: "article",
        direct_type: "electronic_article"
    });
    showSearchForm();
});

$('#book-submit').click(function () {
    "use strict";
    clearVariables();
    crMenu.getInstance().getSearchForm({
        form_type: "presubmit",
        groupdata: [
            {
                title: "Title",
                fields: {
                    title: "Title"
                }
            },
            {
                title: "Single Author Keyword (Lastname OR Firstname)",
                fields: {
                    author: "Single Author Keyword (Lastname OR Firstname)"
                }
            },
            {
                title: "ISBN",
                fields: {
                    isbn: "ISBN"
                }
            },
            {
                title: "Call Number",
                fields: {
                    callnumber: "Call Number"
                }
            },
            {
                title: "PURL",
                fields: {
                    purl: "PURL"
                }
            }
        ],
        type: "book",
        direct_type: "book_general"
    });
    showSearchForm();
});

$('#chapter-submit').click(function () {
    "use strict";
    clearVariables();
    crMenu.getInstance().getSearchForm({
        form_type: "presubmit",
        fields: {
            title: "Title",
            isbn: "ISBN",
            author: "Single Author Keyword (Lastname OR Firstname)",
            callnumber: "Call Number",
            purl: "PURL"
        },
        type: "chapter",
        direct_type: "book_chapter"
    });
    showSearchForm();
});

$('#stream-submit').click(function () {
    clearVariables();
    crMenu.getInstance().getSearchForm({
        form_type: "presubmit",
        fields: {
            title: "Title",
            callnumber: "Call Number",
            purl: "PURL"
        },
        type: "stream",
        direct_type: "stream_general"
    });
    showSearchForm();
});

$('#web-submit').click(function () {
    clearVariables();
    setTimeout(function() {
        showSubmitForm({}, 'web_general', 1);
    }, 1);
});

$('#pdf-submit').click(function () {
    "use strict";
    clearVariables();
    $('#docstore-pre').dialog({
        modal: true,
        width: dialogWidth,
        maxHeight: dialogHeight,
        draggable: false,
        resizable: false,
        open: function () {

            $(this).parent().promise().done(function () {

                dialogMobileFixes();

                dialogBindings();

                $('#submit___pdf_chapter__docstore').on('click', function () {
                    $('#docstore-pre').parent().find('.ui-dialog-titlebar-close').click();
                    setTimeout(function(){
                        showSubmitForm({}, 'pdf_chapter__docstore', 1);
                    },1);
                });

                $('#submit___pdf_article__docstore').on('click', function () {
                    $('#docstore-pre').parent().find('.ui-dialog-titlebar-close').click();
                    setTimeout(function(){
                        showSubmitForm({}, 'pdf_article__docstore', 1);
                    },1);
                });

                $('#submit___pdf_other__docstore').on('click', function () {
                    $('#docstore-pre').parent().find('.ui-dialog-titlebar-close').click();
                    //console.log('closed base modal');
                    setTimeout(function(){
                        showSubmitForm({}, 'pdf_other__docstore', 1);
                    },1);
                });
            });
        },
        close: function () {

            $('#submit___pdf_chapter__docstore').unbind();

            $('#submit___pdf_article__docstore').unbind();

            $('#submit___pdf_other__docstore').unbind();
        }
    });

});

$('#physical-submit').click(function () {
    "use strict";
    clearVariables();
    setTimeout(function() {
        showSubmitForm({}, 'physical_general', 1);
    }, 1);
});

$('#request-submit').click(function () {
    "use strict";
    clearVariables();
    setTimeout(function() {
        showSubmitForm({}, 'request_general', 1);
    }, 1);
});

function dialogMobileFixes() {
    // Increase the size of the close icon for mobile to make it possible to hit.
    if (navigator.userAgent.match(/(iPod|iPhone|iPad|Android)/)) {
        $('.ui-dialog-titlebar-close').css({'width': '50px', 'height': '50px'});
    }
}
function dialogBindings() {
    // Allow closing the dialog via clicking the background.
    $('.ui-widget-overlay').bind('click', function () {
        $(this).siblings('.ui-dialog').find('.ui-dialog-content').dialog('close');
    });
}
