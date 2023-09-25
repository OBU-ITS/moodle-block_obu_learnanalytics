/**
 * Stores values in a hidden input on the page for later use
 * Created so that events for change chart type and change week could access
 * the parameters required to post the ajax page
 * Called from tutor_grid.js
 * @param programme     The programme code
 * @param cohort        The cohort code (may be *)
 * @param studentNumber     The student number
 * @param studentName   The students name
 * 
 * @Note There is an equivalant PHP function that may need changing if you change this one
 */
function store_parameters(programme, cohort, studentNumber, studentName) {
    if (programme == null) {        // Don't overwrite
        programme = getProgrammeParameter();
    }
    if (cohort == null) {        // Don't overwrite
        cohort = getStudyStageParameter();
    }
    if (studentNumber == null) {        // Don't overwrite
        studentNumber = getStudentNumberParameter();
    }
    if (studentName == null) {        // Don't overwrite
        studentName = getStudentNameParameter();
    }
    var params = new Array(programme, cohort, studentNumber, studentName);
    // Don't think I need htmlspecialchars equiv, but if so https://stackoverflow.com/questions/1787322/htmlspecialchars-equivalent-in-javascript 
    $("#obula_parameters").val(JSON.stringify(params));
}

function getProgrammeParameter() {
    var result = "";
    var paramsStr = $("#obula_parameters").val();
    if (paramsStr != null && paramsStr != "") {
        var params = JSON.parse(paramsStr);
        result = params[0];
    }
    return result;
}

function getStudyStageParameter() {
    var result = "";
    var paramsStr = $("#obula_parameters").val();
    if (paramsStr != null && paramsStr != "") {
        var params = JSON.parse(paramsStr);
        result = params[1];
    }
    return result;
}

function getStudentNumberParameter() {
    var result = "";
    var paramsStr = $("#obula_parameters").val();
    if (paramsStr != null && paramsStr != "") {
        var params = JSON.parse(paramsStr);
        result = params[2];
    }
    return result;
}

function getStudentNameParameter() {
    var result = "";
    var paramsStr = $("#obula_parameters").val();
    if (paramsStr != null && paramsStr != "") {
        var params = JSON.parse(paramsStr);
        result = params[3];
    }
    return result;
}

function loadStudentGraph(chartType, chartNo, newDate = null, scrollIntoView = false, doneFunction = loadStudentGraphDone) {
    // TODO Cohort
    //debugger;
    var currentWeek = "";
    if (newDate == null) {
        currentWeek = $("#obula_currentweek").val();       // Don't parse the JSON
    } else {
        currentWeek = newDate;
    }
    if (currentWeek == null || currentWeek == undefined) {
        currentWeek = "";
    }
    var bandingCalc = $("#obula_banding_calc").val();
    var data = {
        "programme": getProgrammeParameter(), "studentNumber": getStudentNumberParameter()
        , "studentName": getStudentNameParameter(), "chartType": chartType
        , "currentWeek": currentWeek, "bandingCalc": bandingCalc, "sStage": getStudyStageParameter()
    };
    $.ajax({
        type: 'POST',
        url: "../blocks/obu_learnanalytics/students_graph.php",
        data: data,
        success: function (res) {
            //debugger;
            // So Learning Analytics error handling standard for pages that produce graph or other images
            // is to return a base64 encoded string if it works
            // or an array with error information if it didn't
            if (typeof res === 'object') {       // So actually a fail NOTE Array.isArray(res) returns false
                //debugger;
                // if I didn't catch it then
                //if (res.startsWith('<div display=')) {
                $('#obula_error_cell').html(res.consolehtml);
                $("#obula_error_row").show();       // Probably won't as consolehtml has display none in it
                alert('loadStudentGraph returned error, see console');
                doneFunction(false);
            } else {
                // Could still be an exception I didn't format, it seems they normally start <br />
                if (res.startsWith('<br />')) {
                    $('#obula_error_cell').html(res);
                    $("#obula_error_row").show();
                    alert('loadStudentGraph returned error, see #obula_error_cell');
                } else {
                    $("#obula_error_row").hide();
                    var imgID = '#obula_studentGraph_img_' + chartNo;
                    $(imgID).attr("src", res);       // Don't need .delay(2000);
                    //document.getElementById("obula_studradbuttons" + chartNo).style.display = "block";
                    // Now make sure the div is visible, just do it when any ready
                    document.getElementById("obula_studentGraphs_div").style.display = "block";
                    if (scrollIntoView) {
                        var imgElement = document.getElementById("obula_studentGraphs_div");
                        imgElement.scrollIntoView(false);           // true is going too far
                    }
                    // Now get the focus away from the student that was clicked because Moodle 3.10 upgrade outlines it
                    $('#obula_mod_eng').focus();
                    doneFunction(false);
                }
            }
        },
        error: function (errMsg) {
            alert('loadStudentGraph post failed:' + errMsg);
            doneFunction(false);
        }
    });
}

function loadStudentGraphDone(state = false)
{
    // Nothing to do
}

/**
 * Handles click events for change of chart type
 * HAD to be changed when other events where changed to AJAX posts
 * @param {The type of chart, duration etc} chartType 
 * @param {The position on page 1 - 3} chartNo 
 */
function changeChartTypeRB(chartType, chartNo) {
    // Just need to pass it on to the common method
    loadStudentGraph(chartType, chartNo);
}

function showWeekControl(newDate = null) {
    // See if it's already visible, because if it's not it will need the control loaded
    // but if a new date has been passed it need's reloading anyway
    var invisible = (document.getElementById("obula_before_week_row").style.display == 'none');
    if (newDate != null || invisible) {
        var data = {
            "date": newDate
        };
        $.ajax({
            type: 'POST',
            url: "../blocks/obu_learnanalytics/week_control.php",
            data: data,
            success: function (res) {
                $('#obula_week_control_cell').html(res);            //.delay(1000);
                if (invisible) {
                    document.getElementById("obula_before_placeholders_row").style.display = "table-row";
                    document.getElementById("obula_before_week_row").style.display = "table-row";
                }
            },
            error: function (errMsg) {
                alert('showWeekControl post failed:' + errMsg);
            }
        });
    }
}

function showDateControls(newDate = null, studentDashboard = false, drawSemester = true) {
    // See if it's already loaded/visible, because if it's not it will need the control loaded
    // but if a new date has been passed it need's updating
    var title = document.getElementById("obula_weekdate");
    var invisible = (title == null) || (title.style.display == 'none');
    if (newDate != null || invisible) {
        var data = {
            "date": newDate,
            "weekControl": true,
            "semesterControl": drawSemester
        };
        $.ajax({
            type: 'POST',
            url: "../blocks/obu_learnanalytics/date_controls.php",
            data: data
            // beforeSend: function () {
            //     $("#obula_error_row").hide();
            //}
        })
            .done(function (resp) {
                if (resp != null && resp.success) {
                    $('#obula_week_control_cell').html(resp.weekControl);
                    if (invisible) {
                        if (studentDashboard) {
                            document.getElementById("obula_before_placeholders_row").style.display = "table-row";
                            document.getElementById("obula_before_week_row").style.display = "table-row";
                        }
                    }
                    if (drawSemester && !studentDashboard && resp.semesterControl != "") {
                        $('#obula_semester_control_cell').html(resp.semesterControl);
                    }
                }
            })
            .fail(function (jqXHR, textStatus, errorThrown) {
                // only way to trigger a fail is with a non 200 response, 404, 500 etc
                // but that seems extreme for a simple validation
                // So reserving this for exceptions
                alert('showDateControls post failed:' + errorThrown);
            })
            // .always(function(resp) {
            //         // Code will always get executed after done or fail, like a try/catch finally
            //     })
            ;           // End of .ajax 'line'
    }
}

function showDataCurrency() {
    var data_currency = document.getElementById("obula_footer");
    // Don't repeat it if it's been done, it won't change that often
    if (data_currency != null && data_currency.innerHTML == 'Data Currency') {
        $.ajax({
            type: 'POST',
            url: "../blocks/obu_learnanalytics/data_currency.php",
            // beforeSend: function () {
            //     $("#obula_error_row").hide();
            //}
        })
            .done(function (resp) {
                if (resp != null && resp.success) {
                    $('#obula_footer').html(resp.footerhtml);
                    $('#obula_footer').show();
                }
            })
            .fail(function (jqXHR, textStatus, errorThrown) {
                // only way to trigger a fail is with a non 200 response, 404, 500 etc
                // but that seems extreme for a simple validation
                // So reserving this for exceptions
                alert('showDataCurrency post failed:' + errorThrown);
            })
            // .always(function(resp) {
            //         // Code will always get executed after done or fail, like a try/catch finally
            //     })
            ;           // End of .ajax 'line'
    } else {
        $('#obula_footer').show();
    }
}


/**
 * Hides nav bar and main panel (if it's not in main panel)
 * @param {*} tnode A node in the dashboard, like the button that was clicked
 */
function takeOverPage(tnode) {
    // Don't do it twice
    if ($("#obula_page_taken").val() != "Y") {
        $("#obula_page_taken").val("Y");
        // Next the Navigation Menu (it's got to go)
        // But only if it's not already, we could just try and click it but I want to store if I need restore
        // Now get the data-action for the right drawer, if they have already collapsed it then we will get undefined
        // I tried other ways of hiding it with pervious moodle, but it never collapsed
        // and/or they couldn't show it again with the correct button
        var rightDrawerDA = $(".drawer-right").find(".drawertoggle").attr("data-action");
        var rightDrawerCloseVisible =$(".drawer-right").find(".drawertoggle").is(":visible");
        // But we also need to check if it's visible as it remains in the dom, it's hidden by a class of hidden being added
        if (rightDrawerDA == "closedrawer" && rightDrawerCloseVisible == true) {
            $("button[data-action='closedrawer']").click();
            $("#obula_navbar_rightDrawerDA").val(rightDrawerDA);          // So we know if we should open it after
        } else {
            $("#obula_navbar_rightDrawerDA").val("No");
        }
        // Let's find where we are, because we need to put it back later
        var retValues = checkColumn2(tnode);
        var host = retValues[0];
        var laBlockId = retValues[1];
        var nextBlockId = retValues[2];
        var parentBlockId = retValues[3];
        // Store them
        $("#obula_host").val(host);          // So we know if we should open it after
        $("#obula_lablockid").val(laBlockId);          // So we know if we should open it after
        $("#obula_nextblockid").val(nextBlockId);          // So we know if we should open it after
        $("#obula_parentblockid").val(parentBlockId);      // For later

        // Used to only take over agressively from the right panel, but because Moodle 4
        // has wide margins around the main panel we need to it anyway
        // Let's get rid of the main block
        $("#topofscroll").hide();
        $("#topofscroll").attr("aria-hidden", "true");

        // Now append our block to main (it gets removed from the old parent by the method)
        // Tried with jquery because I hate mixing it, but failed as the jquery append despite the documentation isn't the same
        var newParent = document.getElementById("page");
        var myBlock = document.getElementById(laBlockId);
        newParent.appendChild(myBlock);
    }
}

function giveBackPage(type) {
    // So hide all the results before we re-arrange
    $("#obula_summary_row").hide();
    $("#obula_dash_row").hide();
    $('#obula_footer').hide();

    // Get the info we stored earlier for this
    var host = $("#obula_host").val();
    var laBlockId = $("#obula_lablockid").val();
    var nextBlockId = $("#obula_nextblockid").val();
    var parentBlockId = $("#obula_parentblockid").val();
    if (host == "right") {
        $("#obula_" + type + "_heading_sml").show();
        $("#obula_" + type + "_input_sml").show();
        $("#obula_" + type + "_heading_med").hide();
        $("#obula_" + type + "_input_med").hide();
    } else {
        $("#obula_" + type + "_heading_sml").hide();
        $("#obula_" + type + "_input_sml").hide();
        $("#obula_" + type + "_heading_med").show();
        $("#obula_" + type + "_input_med").show();
    }
    // So now work out where we are going back to 
    var newParent = document.getElementById(parentBlockId);
    var myBlock = document.getElementById(laBlockId);
    if (nextBlockId) {
        var sibling = document.getElementById(nextBlockId);
        newParent.insertBefore(myBlock, sibling);
    } else
    {
        // Must have been last one, so just append
        newParent.appendChild(myBlock);
    }

    // Now show the main page again
    $("#topofscroll").show();
    $("#topofscroll").attr("aria-hidden", "false");
    $("#obula_page_taken").val('N');

    // Now the Nav
    var rightDrawerDA = $("#obula_navbar_rightDrawerDA").val();
    if (rightDrawerDA == "closedrawer") {
        // Hope there is only one button inside righ drawer toggle div
        var rightDrawerDA = $(".drawer-right-toggle").find("button").click();
        $("#obula_navbar_rightDrawerDA").val(" ");
    }

}

/**
 * Checks to see if we are configured in the main column or side bar
 * @param {*} tnode A node in the dashboard, like the button that was clicked
 * returns null if main or column node if in sidebar
 */
function checkColumn(tnode) {
    var sidebar = false;        // Are we in the right panel
    var node = tnode;
    // Had trouble with jQuery, so use jscript instead
    while (node.parentNode) {
        node = node.parentNode;
        if (node.id == "block-region-side-pre") {
            sidebar = true;
            break;
        }
        if (node.id == "region-main") {
            break;
        }
    }
    // Now return the parent of node we need to change
    // Which doesn't have an ID !!
    return (sidebar) ? node.parentNode : null;
}

/**
 * Replaces checkColumn
 * Checks to see if we are currently in the main column or side bar
 * @param {*} tnode A node in the dashboard, like the button that was clicked
 * returns an array of values
 * [0] "right" or "main" to indicate if it is in the right sidebar
 * [1] the ID of the la block (to make moving it easier)
 * [2] the ID of the next block so we can slot back in front (can be null or ?)
 * [3] the parent ID
 */
function checkColumn2(tnode) {
    var host = "main";        // Are we in the right panel
    var laBlockId = "";
    var nextBlockId = "";
    var parentBlockId = "";
    var node = tnode;
    // Had trouble with jQuery, so use jscript instead
    while (node.parentNode) {
        node = node.parentNode;
        if (node.attributes && node.attributes["data-block"] && node.attributes["data-block"].nodeValue == "obu_learnanalytics")
        {
            laBlockId = node.id;
            // Now look to see if there is another block (with Moodle 4 they are sections)
            var nextNode = node;
            // We need a loop as there are spacers in between
            while (nextNode.nextElementSibling) {
                nextNode = nextNode.nextElementSibling;
                if (nextNode.tagName == "SECTION") {
                    nextBlockId = nextNode.id;
                    break;
                }
            }
        }
        if (node.id == "block-region-side-pre") {
            parentBlockId = node.id;
            host = "right";
            break;
        }
        if (node.id == "block-region-content") {     // That's the middle panel so might as well stop
            parentBlockId = node.id;
            break;
        }
    }
    // Now return what we have gathered on the way up
    return [host, laBlockId, nextBlockId, parentBlockId];
}

/**
 * Unsuccesful ?? attempt to copy to clipboard
 * But left as I want to crack it for when someone wants the data from the tutor grid copied.
 * Note navigator.clipboard.writeText is async so
 */
function copyErrorTextToClipboard() {
    //debugger;
    // Get the text
    var copyText = document.getElementById("error-msg");
    var errorCell = document.getElementById("obula_error_cell");
    var clipText = copyText.innerText;
    if (errorCell.innerText != "") {
        clipText += "\r\n\r\n" + errorCell.innerText;
    }

    if (navigator.clipboard) {
        navigator.clipboard.writeText(clipText).then(function () {
            // great nothing to do ok = true;
        }, function (err) {
            alert("Copy to clipboard failed");
        });
    } else {
        // As it's not an input we can't select it, but we have a hidden field we can use
        var hiddenText = document.getElementById("obula_copy2clip");
        hiddenText.value = clipText;

        // Select the contents
        hiddenText.select();
        //hiddenText.show();
        //hiddenText.setSelectionRange(0, 99999); /* For mobile devices */

        // Copy the selected text
        // Note feature is deprecated, but it's working for now
        ok = document.execCommand("copy");
        if (!ok) {
            //alert("Copy to clipboard failed");
        }
    }
}

/**
 * Unsuccesful ?? attempt to copy to clipboard
 * But left as I want to crack it for when someone wants the data from the tutor grid copied.
 * Note navigator.clipboard.writeText is async so
 */
 function copyTextToClipboard(clipText) {
    //debugger;
    // if (navigator.clipboard) {
    //     navigator.clipboard.writeText(clipText).then(function () {
    //         // great nothing to do ok = true;
    //     }, function (err) {
    //         alert("Copy to clipboard failed");
    //     });
    // } else {
        // As it's not an input we can't select it, but we have a hidden field we can use
        var hiddenText = document.getElementById("obula_copy2clip");
        hiddenText.value = clipText;

        // Select the contents
        hiddenText.select();
        //hiddenText.show();
        //hiddenText.setSelectionRange(0, 99999); /* For mobile devices */

        // Copy the selected text
        // Note feature is deprecated, but it's working for now
        ok = document.execCommand("copy");
        if (!ok) {
            //alert("Copy to clipboard failed");
        // }
    }
}

function gotoFeedback(type) {
    //  Hardcode for now https://docs.google.com/forms/d/e/1FAIpQLScR2K62QEwoJyQJfYCuEt5W58ajYqIEbpMWcmNxzSmOoEmVeA/viewform?gxids=7628
    window.open('https://docs.google.com/forms/d/e/1FAIpQLScR2K62QEwoJyQJfYCuEt5W58ajYqIEbpMWcmNxzSmOoEmVeA/viewform?gxids=7628');
}

/**
 * Handles population and showing of help popup (that form needs to have already rendered)
 * @param {*} helpType (student, tutor or ssc) 
 */
function showHelp(helpType) {
    var data = {
        "helpType": helpType
    };
    $.ajax({
        type: 'POST',
        url: "../blocks/obu_learnanalytics/get_helptext.php",
        data: data,
        beforeSend: function () {
            $("#obula_error_row").hide();
        }
    })
        .done(function (resp) {
            if (resp != null && resp.success) {
                $('#obula_modal_popup_title').html(resp.title);
                $('.modal-body').html(resp.popupbodyhtml);
                // Display Modal, but make sure correct buttons will show
                $('#obula_modal_body').removeClass('popup-pgm-search');
                $('#obula_modal_close').show();
                $('#obula_modal_close').prop('disabled', false);
                $('#obula_modal_ok').hide();
                $('#obula_modal_ok').prop('disabled', true);
                $('#obula_modal_cancel').hide();
                $("#obula_modal_footer_text").text("");
                $('#obula_modal_footer_text').removeAttr('title');
                $('#obula_modal_popup').modal('show');
            }
        })
        .fail(function (resp) {
            // only way to trigger a fail is with a non 200 response, 404, 500 etc
            // but that seems extreme for a simple validation
            // So reserving this for exceptions
            alert('showHelp exception');
        })
        // .always(function(resp) {
        //         // Code will always get executed after done or fail, like a try/catch finally
        //     })
        ;           // End of .ajax 'line'
}


