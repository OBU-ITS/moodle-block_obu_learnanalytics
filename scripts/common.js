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

function loadStudentGraph(chartType, chartNo, newDate = null, scrollIntoView = false) {
    // TODO Cohort
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
                }
            }
        },
        error: function (errMsg) {
            alert('loadStudentGraph post failed:' + errMsg);
        }
    });
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


/**
 * Hides nav bar and main panel (if it's not in main panel)
 * @param {*} tnode A node in the dashboard, like the button that was clicked
 */
function takeOverPage(tnode) {
    // Don't do it twice
    if ($("#obula_page_taken") != 'Y') {
        $("#obula_page_taken").val('Y');
        // First the Navigation Menu (it's got to go)
        // But only if it's not already
        var ariaHidden = $("#nav-drawer").attr("aria-hidden");
        $("#obula_navbar_ariahidden").val(ariaHidden);
        if (ariaHidden == "false") {
            $("button[data-action='toggle-drawer']").click();
        }
        // I tried other ways of hiding it, but it never collapsed
        // and/or they couldn't show it again with the correct button
        //navDiv.toggle();
        //$('#nav-drawer').collapse('hide');
        //$("#nav-drawer").attr("aria-hidden", "true");
        // That doesn't seem to set the closed class
        //var navDivClass = $("#nav-drawer").attr("class");
        //$("#nav-drawer").attr("class", navDivClass + " closed");

        // Next let's find where we are, because if we are on the sidebar we need to take over
        var sideNode = checkColumn(tnode);
        if (sideNode != null) {
            // Let's get rid of the main block
            $("#region-main").hide();
            $("#region-main").attr("aria-hidden", "true");
            // Now steal it's space
            var curClass = $("#region-main").attr("class");
            $("#region-main").attr("class", curClass + " obula-block-collapsed");
            // And make the sideNode take it
            sideNode.className += " obula-block-fullwidth";

            // Now that annoying customise button
            $("#page-header").hide();
            $("#page-header").attr("aria-hidden", "true");
            // Now steal it's space
            var curClass = $("#page-header").attr("class");
            $("#page-header").attr("class", curClass + " obula-block-collapsed");

            // Now let's hide all the other blocks in this column
            //debugger;
            var asideCol = document.getElementById("block-region-side-pre");
            var node = asideCol.firstChild;
            while (node) {
                if (node !== this && node.nodeType === Node.ELEMENT_NODE) {
                    var datablock = node.getAttribute('data-block');
                    // So if it's not me and it's a block and it's visible - hide it
                    if (datablock != null && datablock != 'obu_learnanalytics'
                        && node.offsetWidth > 0) {
                        node.className += " obula-block-hidden";        // Also so I can find the ones I hid
                    }
                }
                node = node.nextElementSibling || node.nextSibling;
            }
        }
    }
}

function giveBackPage(sideNode) {
    $("#region-main").show();
    $("#region-main").attr("aria-hidden", "false");
    var curClass = $("#region-main").attr("class");
    $("#region-main").attr("class", curClass.replace(" obula-block-collapsed", ""));
    curClass = sideNode.className;
    curClass = curClass.replace(" obula-block-fullwidth", "");
    sideNode.className = curClass;
    $("#obula_page_taken").val('N');

    // Now that annoying customise button (somebody might want it)
    $("#page-header").show();
    $("#page-header").attr("aria-hidden", "false");
    var curClass = $("#page-header").attr("class");
    curClass = curClass.replace("obula-block-collapsed", "");
    $("#page-header").attr("class", curClass);

    // Now let's unhide all the other blocks in this column
    //debugger;
    var asideCol = document.getElementById("block-region-side-pre");
    var node = asideCol.firstChild;
    while (node) {
        if (node !== this && node.nodeType === Node.ELEMENT_NODE) {
            var datablock = node.getAttribute('data-block');
            // So if it's not me and it's a block and it's hidden by me, unhide it
            if (datablock != null && datablock != 'obu_learnanalytics') {
                var myclass = node.className;
                if (myclass.indexOf('obula-block-hidden') >= 0) {
                    node.className = myclass.replace(' obula-block-hidden', '');
                }
            }
        }
        node = node.nextElementSibling || node.nextSibling;
    }

    // Now the Nav
    var ariaHidden = $("#nav-drawer").attr("aria-hidden");
    var ariaWasHidden = $("#obula_navbar_ariahidden").val();
    if (ariaWasHidden == "false" && ariaHidden == "true") {
        $("button[data-action='toggle-drawer']").click();
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
 * Unsuccesful ?? attempt to copy to clipboard
 * But left as I want to crack it for when someone wants the data from the tutor grid copied.
 * Note navigator.clipboard.writeText is async so
 */
function copyErrorTextToClipboard() {
    //debugger;
    // Get the text
    var copyText = document.getElementById("error-msg");

    if (navigator.clipboard) {
        navigator.clipboard.writeText(copyText.innerText).then(function () {
            // great nothing to do ok = true;
        }, function (err) {
            alert("Copy to clipboard failed");
        });
    } else {
        // As it's not an input we can't select it, but we have a hidden field we can use
        var hiddenText = document.getElementById("obula_copy2clip");
        hiddenText.value = copyText.innerText;

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

function gotoFeedback(type) {
    //  Hardcode for now https://docs.google.com/forms/d/1pClVblSvOXIcNLRv8fP4j_296nBSBM--n6bbdi789TU/edit?gxids=7628
    window.open('https://docs.google.com/forms/d/1pClVblSvOXIcNLRv8fP4j_296nBSBM--n6bbdi789TU/edit?gxids=7628');
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


