/* scripts for use with tutor grid
*/

var gridLoading = studentLoading = chartLoading = marksLoading = false;

$(document).ready(function () {
    //debugger;
    set_gridLoading(true);

    /* Following was attempts to get bootstrap hover tips working ok and getting stuck
    $('[rel=tooltip]').tooltip({ trigger: "hover" });       // This doesn't work !!!
    $('[data-toggle="ztooltip"]').click(function () {
        $('[data-toggle="ztooltip"]').tooltip("hide");
    });
    $('[data-toggle="ztooltip"]').on("mouseleave", function(){
        $(this).tooltip("hide"); 
    });  global replace of data-toggle="ztooltip" to data-toggle="tooltip"
        when ready for next attempt*/

    var programme = document.getElementById("selProgramme").value;
    // Cohort dropdown won't even have been loaded yet
    var cohort = '*';
    var cohElement = document.getElementById("selStudyStage");
    if (cohElement != null) {
        var cohort = cohElement.value;
    }
    // But Study Mode/Type should have been
    var stElement = document.getElementById("selStudyType");
    var studyType = (stElement == null) ? '*' : stElement.value;

    showDateControls("getcurrent", false, true);
    //var currentWeek = $("#obula_currentweek").val();       // Don't parse the JSON
    // So is this a load from SSC dash, if so there is a hidden field of obula_ssc_student
    // if it is then set maxShow to *
    var maxShow = 10;
    var studentNumber = '';
    var sno = $("#obula_ssc_student").val();
    if (sno != null && sno != '?') {
        maxShow = '*';
        studentNumber = sno;
    }
    var data = {
        "programme": programme, "sStage": cohort, "maxShow": maxShow, "sStageSort": "down", "studentSort": "down"
        , "cohortfirst": 1, "currentWeek": "", "bandingCalc": "MED-20-4", "studyType": studyType
        , "myAdvisees": false, "semester": "", option: "getcurrent", "studentNumber": studentNumber
    };
    $.ajax({
        type: 'POST',
        url: "../blocks/obu_learnanalytics/tutor_grid.php",
        data: data
    })
        .done(function (res) {
            //debugger;
            var studentNumber = '';
            var sno = $("#obula_ssc_student").val();
            if (sno != null && sno != '?') {
                studentNumber = sno;
            }
            tutor_grid_done(true, res, programme, cohort, studentNumber);
        })
        .fail(function (errMsg) {
            alert('reloadGrid 1 post failed:' + errMsg);
        })
        ;           // End of .ajax 'line'
});             // End of inline function

function set_gridLoading(state) {
    gridLoading = state;
    set_somethingLoading(state);
}

function set_studentLoading(state) {
    studentLoading = state;
    set_somethingLoading(state);
}

function set_chartLoading(state) {
    chartLoading = state;
    set_somethingLoading(state);
}

function set_marksLoading(state) {
    marksLoading = state;
    set_somethingLoading(state);
}

function set_somethingLoading(state) {
    if (state) {
        $('#obula_dash_div').addClass('wait-cursor');
        //$('body').addClass('wait-cursor');
        // Despite the wait-cursor setting the cursor, it didn't work in chrome
        // even if I set it on the body, so next line is a solution (still do class as that greys page)
        $('body').css('cursor', 'wait');
    } else {
        if (!gridLoading && !studentLoading && !chartLoading && !marksLoading) {
            $('#obula_dash_div').removeClass('wait-cursor');
            $('body').css('cursor', '');
            //$('[data-toggle="ztooltip"]').tooltip("hide");
            //        document.body.style.cursor = "default";
        }
    }
}

function tutor_grid_done(fromReadyEvent, res, programme, cohort, studentNumber, refreshChart = false, updateDate = false, redrawSemester = false) {
    // Fix the Bootstrap Tooltip behavior (it wasn't closing if you clicked on the hovered control)
    $('[data-toggle="ztooltip"]').tooltip({
        trigger: 'hover'
    })
    $('#obula_tutor_grid_div').html(res.html).delay(100);
    //debugger;
    store_parameters(programme, cohort, null, null);
    // Now hide values that aren't in grid from study stage and type drop downs
    if (res.full_data_set == 1 && !res.success) {
        $("#selStudyStage").prop("disabled", true);
        $("#selStudyType").prop("disabled", true);
    } else {
        if (res.study_stages !== undefined && res.study_stages != '') {
            sstages = res.study_stages.split('|');
            sstages.pop();          // Last element is empty
            // Now hide/show them (Note there are some cross browser concerns)
            // But only hide if we have been sent a full dataset 
            // - see https://stackoverflow.com/questions/9234830/how-to-hide-a-option-in-a-select-menu-with-css
            $("#selStudyStage option").each(function () {
                if (($(this).val() == '*' && sstages.length > 1) || sstages.includes($(this).val())) {
                    $(this).show();
                } else {
                    if (res.full_data_set == 1) {
                        $(this).hide()
                    }
                }
                if (sstages.length == 1) {
                    $("#selStudyStage").val(sstages[0]);
                }
            });
            stypes = res.study_types.split('|');
            stypes.pop();          // Last element is empty
            $("#selStudyType option").each(function () {
                if (($(this).val() == '*' && stypes.length > 1) || stypes.includes($(this).val())) {
                    $(this).show();
                } else {
                    if (res.full_data_set == 1) {
                        $(this).hide()
                    }
                }
                if (stypes.length == 1) {
                    $("#selStudyType").val(stypes[0]);
                }
            });
        }
    }

    // Now show the advisees option if appropriate
    if (res.advisees_count > 0) {
        $("#obula_advisor").show();
    }
    // And the chart link
    if (res.students_count > 1) {
        $("#obula_chart_show").show();
    } else {
        $("#obula_chart_show").hide();
    }

    if (updateDate) {
        // And now the date controls
        // tutor_grid should only return a date if it changed it
        if (res.date != "") {
            showDateControls(res.date, false, redrawSemester);
        }
    }
    if (res.success) {
        if (fromReadyEvent) {
            if (studentNumber != '') {
                highlightStudentRow(studentNumber);
            }
        } else {
            checkRefreshStudentBits(res.date, redrawSemester);
        }
        if (refreshChart) {
            showChart();        // The cohort engagement chart
        }
    } else {
        unClickStudent();
    }
    set_gridLoading(false);
}

function highlightStudentRow(studentNumber) {
    var table = $("#obula_tutor_grid_table");
    if (table.length > 0) {        // Safety code - should not be zero
        //TODO$("#obula_tutor_grid_table").find('tr').removeClass('selected');
        $("tr.students").removeAttr('selected');
        // So now find row (would like to do it within table TODO)
        var rowsid = '#sid_' + studentNumber;
        $(rowsid).attr('selected', 'selected');
    }
}

function clickStudent(programme, studyStage, studentNumber, studentName, scrollIntoView = true, newDate = null) {
    set_studentLoading(true);
    //debugger;
    highlightStudentRow(studentNumber);
    store_parameters(programme, studyStage, studentNumber, studentName);
    // Now the graphs
    for (var i = 1; i <= 2; i++) {
        var lastOne = (i == 2);
        var imgElement = document.getElementById("obula_studentGraph_img_" + i);
        if (imgElement != null) {
            // Pick up the currently selected chart type, needs jquery see https://www.geeksforgeeks.org/how-to-know-which-radio-button-is-selected-using-jquery/ 
            var types = ["vle", "ez", "loans", "att"];
            var rbname = types[i - 1] + 'charttype';
            var selectedType = $('input[name=' + rbname + ']:checked', '#obula_studentGraphs_div').val();
            // Now we need the Banding
            loadStudentGraph(selectedType, i, newDate, scrollIntoView);
        }
    }
    // If marks or eng chart are visible then reload
    var finished = true;
    var chartDisplay = document.getElementById("obula_tutorsGraph_img").style.display;
    if (chartDisplay != "none") {
        showChart(true);
        finished = false;
    }
    var studentMarksDisplay = document.getElementById("obula_studentmarks_div").style.display;
    if (studentMarksDisplay != "none") {
        showStudentsMarks(studentNumber, false);
        finished = false;
    }
    if (finished) {
        set_studentLoading(false);
    }

    // Hide Module graph
    $("#obula_studentModule_img").hide();
    $('#obula_studentModule_row').hide();
}

function unClickStudent() {
    // Clear down student bits
    store_parameters(null, null, "", "");
    // Hide the student charts div 
    document.getElementById("obula_studentGraphs_div").style.display = "none";
    // And the student comparison chart
    hideCharts(true);
}

/**
 * Handles population and showing of help popup (that form needs to have already rendered)
 * @param studentNumber The student number
 * @param sname The student's name
 * @param advisor The advisor's p number 
 */
function showStudentInfo(studentNumber, sname, advisor) {
    var data = {
        "studentNumber": studentNumber
        , "sName": sname
        , "advisor": advisor
    };
    $.ajax({
        type: 'POST',
        url: "../blocks/obu_learnanalytics/get_student_info.php",
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
            alert('showStudentInfo exception ' + resp.responseText);
        })
        // .always(function(resp) {
        //         // Code will always get executed after done or fail, like a try/catch finally
        //     })
        ;           // End of .ajax 'line'
}

function showModuleEng() {
    var currentWeek = $("#obula_currentweek").val();       // Don't parse the JSON
    var studentNumber = getStudentNumberParameter();
    var studentName = getStudentNameParameter();
    var data = {
        "currentWeek": currentWeek, "studentNumber": studentNumber, "studentName": studentName
    };
    $.ajax({
        type: 'POST',
        url: "../blocks/obu_learnanalytics/student_module_eng.php",
        data: data,
        success: function (res) {
            $('#obula_studentModule_img').attr("src", res);       // Don't need .delay(2000);
            // Make sure it's visible
            $("#obula_studentModule_img").show();
            $('#obula_studentModule_row').show();
            var element = document.getElementById("obula_studentModule_row");
            element.scrollIntoView(true);
        },
        error: function (errMsg) {
            alert('showModuleEng Event post failed:' + errMsg);
        }
    });
}

function showStudentsMarks(studentNumber, scrollIntoView) {
    var data = { "studentNumber": studentNumber };
    set_marksLoading(true);
    $.ajax({
        type: 'POST',
        url: "../blocks/obu_learnanalytics/student_marks_grid.php",
        data: data,
        success: function (res) {
            if (typeof res === 'object') {       // So actually a fail NOTE Array.isArray(res) returns false
                $('#obula_error_cell').html(res.consolehtml);
                $("#obula_error_row").show();       // Probably won't as consolehtml has display none in it
                alert('showStudentsMarks returned error, see console');
            } else {
                $('#obula_studentmarks_div').html(res).delay(1000);
                var divElement = document.getElementById("obula_studentmarks_div");
                divElement.style.display = "block";
                if (scrollIntoView) {
                    divElement.scrollIntoView(true);
                }
            }
            set_marksLoading(false);
        },
        error: function (errMsg) {
            set_marksLoading(false);
            alert('clickStudentsMark Event post failed:' + errMsg);
        }
    });
}

function clickStudentsMark(studentNumber, studentName, studyStage) {
    showStudentsMarks(studentNumber, true);
    var studentGraphsDisplay = document.getElementById("obula_studentGraphs_div").style.display;
    if (studentGraphsDisplay != "none") {
        clickStudent(getProgrammeParameter(), studyStage, studentNumber, studentName, false);
    } else {
        highlightStudentRow(studentNumber);
    }
};

function clickChangeWeek(direction) {
    var changeDirection = (direction > 0) ? "nextweek" : "prevweek";
    wcDateChanged(changeDirection, null);
}

function semesterChanged() {
    if (gridLoading) { return };
    var element = document.getElementById("selSemester");
    if (element != null) {
        var semester = element.value;
        wcDateChanged(null, semester);
    }
}

function wcDateChanged(changeDirection, semester) {
    if (changeDirection != null) {
        reloadGrid(changeDirection);       // Also reloads total engagement chart
    } else {
        reloadGrid(semester);
    }
}

function checkRefreshStudentBits(newDate, semesterChanged) {
    // So we need to work out what's showing and if that student is still valid
    var sid = getStudentNumberParameter();
    var idsElement = document.getElementById('obula_ids2chart').value;
    if (idsElement == null || sid == "" || idsElement.includes(sid, 0) == false) {
        unClickStudent();
        return;
    }

    var studentGraphsDisplay = document.getElementById("obula_studentGraphs_div").style.display;
    if (studentGraphsDisplay != "none") {
        clickStudent(getProgrammeParameter(), getStudyStageParameter(), getStudentNumberParameter(), getStudentNameParameter(), false, newDate);
    }
    // And if the marks v eng scatter chart is visible, refresh or hide that
    var mveDisplayEle = document.getElementById("obula_marksveng_tbl");
    if (mveDisplayEle != null && mveDisplayEle.style.display != "none") {
        if (semesterChanged) {
            $("#obula_marksveng_tbl").hide();
        } else {
            showMarksvEng('rl', newDate);
        }
    }
}

function showChart(fromStudentClick = false) {
    // Let's hide some columns so we have more space
    // TODO see if we can just query table rather than whole dom
    $(".students-hideable").addClass('students-hidden');
    $(".students-hideable").removeClass('students-hideable');
    //debugger;

    // Now get and set the programme in a hidden field so it's available
    var programme = document.getElementById("selProgramme").value;
    var sStage = document.getElementById("selStudyStage").value;
    var sType = document.getElementById("selStudyType").value;
    if (programme != '') {
        set_chartLoading(true);
        //var imgElement = document.getElementById('obula_tutorsGraph_img');
        var idsElement = document.getElementById('obula_ids2chart').value;
        var currentWeek = $("#obula_currentweek").val();       // Don't parse the JSON
        var data = {
            "programme": programme
            , "sStage": sStage
            , "sType": sType
            , "studentNumber": getStudentNumberParameter()
            , "studentName": getStudentNameParameter()
            , "currentWeek": currentWeek
            , "studentDashboard": false
        };
        $("#obula_chart_show").hide();
        $.ajax({
            type: 'POST',
            //url: "../blocks/obu_learnanalytics/tutor_graph.php",
            url: "../blocks/obu_learnanalytics/cohort_engagement.php",
            data: data,
            success: function (res) {
                $("#obula_chart_hide").show();                      // Hiding show done before ajax call see ^^^
                $("#obula_chart_expand").show();
                $('#obula_tutorsGraph_img').attr("src", res);       // Don't need .delay(2000);
                // Make sure it's visible
                $("#obula_tutorsGraph_img").show();
                set_chartLoading(false);
                if (fromStudentClick) {
                    set_studentLoading(false);
                }
            },
            error: function (errMsg) {
                alert('showChart Event post failed:' + errMsg);
                $("#obula_chart_show").show();
                set_chartLoading(false);
                if (fromStudentClick) {
                    set_studentLoading(false);
                }
            }
        });
    }
}

function expandChart() {
    if ($("#obula_tutor_grid_div").is(":visible")) {
        $("#obula_tutor_grid_div").hide();
        $("#obula_chart_expand").text('Collapse Chart');
    }
    else {
        $("#obula_tutor_grid_div").show();
        $("#obula_chart_expand").text('Expand Chart');
    }
}

function hideCharts(hideOthers = false) {
    $("#obula_tutorsGraph_img").hide();
    // And change it back to show
    $("#obula_chart_show").show();
    // And hide expand/hide
    $("#obula_chart_expand").hide();
    $("#obula_chart_hide").hide();
    // And put back table and columns
    $("#obula_tutor_grid_div").show();      // In case chart was expanded
    // TODO see if we can just query table rather than whole dom
    $(".students-hidden").addClass('students-hideable');
    $(".students-hidden").removeClass('students-hidden');
    if (hideOthers) {
        $("#obula_marksveng_tbl").hide();
        $("#obula_studentmarks_div").hide();
    }
}

function showMarksvEng(duration = 'il', newDate = null) {
    var initialLoad = false;
    if (duration == 'il') {         // Initial load
        initialLoad = true;
        duration = '1wk'
    }
    if (duration == 'rl') {         // Re-load
        var selectedDuration = $('input[name=mveradbuttons]:checked', '#obula_marksveng_rbs').val();
        if (selectedDuration != null) {
            duration = selectedDuration;
        }
    }
    // Now get and set the programme in a hidden field so it's available
    var programme = getProgrammeParameter();
    var cohort = getStudyStageParameter();
    if (programme != '') {
        var idsElement = document.getElementById('obula_ids2chart').value;
        var currentWeek = null;
        if (newDate == null) {
            currentWeek = $("#obula_currentweek").val();       // Don't parse the JSON
        } else {
            currentWeek = newDate;
        }
        var data = { "programme": programme, "ids2Chart": idsElement, "currentWeek": currentWeek, "duration": duration };
        $.ajax({
            type: 'POST',
            url: "../blocks/obu_learnanalytics/cohort_marks_v_engagement.php",
            data: data,
            success: function (res) {
                $('#obula_marksveng_img').attr("src", res);
                // Make sure it's visible
                $("#obula_marksveng_tbl").show();
            },
            error: function (errMsg) {
                alert('showMarksvEng Event post failed:' + errMsg);
            }
        });
    }
}

function programmeChanged() {
    if (gridLoading) { return };
    // Get the old one
    var oldProgramme = getProgrammeParameter();
    // Store it, but clear student and cohort
    var programme = document.getElementById("selProgramme").value;
    store_parameters(programme, "*", "", "");
    unClickStudent();
    // Now reload
    $("#obula_myacc").prop("checked", false);
    $("#obula_title").text("Learning Analytics");     //In case this is/was the SSC dash
    $("#obula_title").removeClass('ssc-title');
    $("#obula_title").addClass('tutor-title');
    reloadGrid('programme', oldProgramme);
};

function clickSearchProgrammeOld() {
    if (gridLoading) { return };
    //debugger;
    var selProgElement = document.getElementById("selProgramme");
    var oldValue = selProgElement.value;
    if (selProgElement != null) {
        var progCode = prompt("Enter Programme Code", selProgElement.value);
        if (progCode != null && progCode != "") {
            selProgElement.value = progCode.toUpperCase();
            // Now validate it and popup error if not valid
            if (selProgElement.value == "") {
                // It wasn't in the list
                alert("Invalid Programme Code, try again");
                selProgElement.value = oldValue;
            } else {
                programmeChanged();
            }
        }
    }
}

function clickSearchProgramme() {
    if (gridLoading) { return };
    //debugger;
    var oldValue = $('#selProgramme').val();
    var oldName = $('#selProgramme option:selected').text();
    var data = {
        "oldValue": oldValue
        , "oldName": oldName
    };
    $.ajax({
        type: 'POST',
        url: "../blocks/obu_learnanalytics/programme_search.php",
        data: data,
        beforeSend: function () {
            $("#obula_error_row").hide();
        }
    })
        .done(function (resp) {
            if (resp != null && resp.success) {
                $('#obula_modal_popup_title').html(resp.title);
                $('.modal-body').html(resp.popupbodyhtml);
                // Display Modal
                $('#obula_modal_body').addClass('popup-pgm-search');
                $('#obula_modal_close').hide();
                $('#obula_modal_close').prop('disabled', true);
                $('#obula_modal_ok').prop('disabled', true);        // Till they've picked something
                $('#obula_modal_ok').show();
                $('#obula_modal_ok').on("click", clickSearchPGMOK);
                $('#obula_modal_cancel').show();
                $('#obula_modal_popup').on('shown.bs.modal', function () {
                    $('#obula_search_str').focus();
                });
                $('#obula_modal_popup').modal('show');
                // won't work for bootstrap modal popup, see above on event $('#obula_search_str').focus();
            }
        })
        .fail(function (resp) {
            // only way to trigger a fail is with a non 200 response, 404, 500 etc
            // but that seems extreme for a simple validation
            // So reserving this for exceptions
            alert('searchProgramme exception');
        })
        // .always(function(resp) {
        //         // Code will always get executed after done or fail, like a try/catch finally
        //     })
        ;           // End of .ajax 'line'
}

function clickSearchPGMOK() {
    //debugger;
    var selProgElement = document.getElementById("selProgramme");
    selProgElement.value = $('#obula_modal_footer_text').attr('title');
    // Now validate it and popup error if not valid
    if (selProgElement.value == "") {
        // It wasn't in the list
        alert("Invalid Programme Code, try again");
        selProgElement.value = oldValue;
    } else {
        programmeChanged();
    }
}

function showSearchProgrammeResults(str) {
    var results = null;
    if (str.length > 0) {
        // Look for exact match on code first
        results = searchProgrammeCode(str);
        if (results.length == 0) {
            if (str.length < 3 || str.slice(-1) == "-") {
                // Don't match yet
            } else {
                var results = searchProgrammes(str);
            }
        }
    }
    if (str.length == 0 || results.length == 0) {
        unpickPGMCode();
        document.getElementById("obula_results").innerHTML = str.length < 3 ? "" : "No Match";
    } else {
        // So create an unordered list
        var html = '<ul class="popup-pgm-ul">';
        results.forEach(function (item, index) {
            if (item == null) {
                html += '<li>...</li>';
            } else {
                html += '<li title="' + item["code"] + '" onclick="pickPGMCode(' + "'" + item["code"] + "', false)" + '"';
                html += ' ondblclick="pickPGMCode(' + "'" + item["code"] + "', true)" + '">';
                html += item["name"] + '</li>';
            }
        });
        html += '</ul>';
        document.getElementById("obula_results").innerHTML = html;
        if (results.length == 1) {
            pickPGMCode(results[0]["code"], false);
        }
    }
}

/**
 * Look for an exact match on programme code
 * @param  {} str
 */
function searchProgrammeCode(str) {
    var strupper = str.toUpperCase();
    var ddl = document.getElementById('selProgramme');
    var results = new Array();
    for (i = 0; i < ddl.options.length; i++) {
        if (ddl.options[i].value.toUpperCase() == strupper) {
            result = { code: ddl.options[i].value.toUpperCase(), name: ddl.options[i].text };
            results.push(result);
            break;      // Can only be one
        }
    }
    return results;
}
/**
 * Look to see if code or name contains search string
 * Return array of matched items
 * @param  {} str
 */
function searchProgrammes(str) {
    var strupper = str.toUpperCase();
    var ddl = document.getElementById('selProgramme');
    var results = new Array();
    var matched = 0;
    for (i = 0; i < ddl.options.length; i++) {
        if (ddl.options[i].text.toUpperCase().indexOf(strupper) != -1
            || ddl.options[i].value.toUpperCase().indexOf(strupper) != -1) {
            if (matched == 10) {
                results.push(null);
                break;
            }
            result = { code: ddl.options[i].value.toUpperCase(), name: ddl.options[i].text };
            results.push(result);
            matched++;
        }
    }
    return results;
}

function pickPGMCode(code, dblClick = false) {
    // Use search to get us the name back
    //debugger;
    var results = searchProgrammeCode(code);
    $('#obula_modal_footer_text').text(results[0]["name"]);
    $('#obula_modal_footer_text').attr('title', code);
    $('#obula_modal_ok').prop('disabled', false);
    //$('#obula_modal_ok').attr('default');
    if (dblClick) {
        clickSearchPGMOK();
        $('#obula_modal_popup').modal('hide');
    }
}

function unpickPGMCode() {
    $("#obula_modal_footer_text").text("");
    $('#obula_modal_footer_text').removeAttr('title');
    $('#obula_modal_ok').prop('disabled', true);
}

function studyStageChanged() {
    if (gridLoading) { return };
    unClickStudent();
    // Now reload
    $("#obula_myacc").prop("checked", false);
    reloadGrid();
}

function bandingChanged() {
    if (gridLoading) { return };
    var element = document.getElementById("selBanding");
    if (element != null) {
        $("#obula_banding_calc").val(element.value);
        // Now just reload, other charts can stay visible
        reloadGrid();
    }
    // TODO error if null
}

function studyTypeChanged() {
    if (gridLoading) { return };
    //debugger;
    var element = document.getElementById("selStudyType");
    if (element != null) {
        unClickStudent();
        reloadGrid();
    }
    // TODO error if null
}

// Check box to select my advisee's
function myaccChanged() {
    if (gridLoading) { return };
    unClickStudent();
    reloadGrid();
}

function maxShowChanged() {
    if (gridLoading) { return };
    // Just reload
    reloadGrid();
};

function clickCohortHeading() {
    if (gridLoading) { return };
    clickHeading('cohort');
};

function clickStudentHeading() {
    if (gridLoading) { return };
    clickHeading('student');
};

function clickHeading(column) {
    if (gridLoading) { return };
    // Dismiss any hover tip see https://stackoverflow.com/questions/33584392/bootstraps-tooltip-doesnt-disappear-after-button-click-mouseleave
    $(this).tooltip('hide');
    unClickStudent();
    // So swap the sort
    //debugger;
    var imgName = $("#obula_" + column + "_sort").prop('name');
    var newName = '?';
    var newDirection = '?';
    if (imgName == "obula_" + column + "_down") {
        newName = "obula_" + column + "_up";
        newDirection = 'up';
    } else {
        newName = "obula_" + column + "_down";
        newDirection = 'down';
    }
    $("#obula_" + column + "_sort").prop('name', newName);
    // If the column was the left one, that make sure the right one is the same
    var swapName = $("#obula_swap_sort").prop('name');
    var swapOther = 'no';
    if (swapName == 'obula_cohortfirst_1' && column == 'cohort') {
        swapOther = 'student';
    }
    if (swapName == 'obula_cohortfirst_0' && column == 'student') {
        swapOther = 'cohort';
    }
    if (swapOther != 'no') {
        newName = 'obula_' + swapOther + '_' + newDirection;
        // Just change it, because it won't matter if it was already set to that
        $("#obula_" + swapOther + "_sort").prop('name', newName);
    }
    // And reload
    reloadGrid();
};

function clickSwapHeading() {
    if (gridLoading) { return };
    unClickStudent();
    // So swap the columns
    var imgName = $("#obula_swap_sort").prop('name');
    var newName = '?';
    if (imgName == "obula_cohortfirst_1") {
        newName = "obula_cohortfirst_0";
    } else {
        newName = "obula_cohortfirst_1";
    }
    $("#obula_swap_sort").prop('name', newName);
    // And reload
    reloadGrid();
};

function reloadGrid(option = null, oldProgramme = null) {
    if (gridLoading) { return };
    //debugger;
    set_gridLoading(true);
    var maxElement = document.getElementById("selMaxShow");     // Can be null grid not shown
    var maxShow = (maxElement == null) ? 10 : maxElement.value;
    var programme = document.getElementById("selProgramme").value;
    // Controls might not even have been loaded yet
    var cohElement = document.getElementById("selStudyStage");
    var cohort = (cohElement == null) ? '*' : cohElement.value;
    var stElement = document.getElementById("selStudyType");
    var studyType = (stElement == null) ? '*' : stElement.value;
    var myaccElement = document.getElementById("obula_myacc");
    if (option != null && option == 'programme') {
        // Assume loaded if we are called from programme changed
        cohElement.value = cohort = '*';
        stElement.value = studyType = '*';
        if (myaccElement != null) {     // It will be null if previous grid was not loaded because no activity
            myaccElement.checked = false;
        }
        maxShow = 10;
    }

    //debugger;
    var refreshChart = (document.getElementById("obula_tutorsGraph_img").style.display == "none") ? false : true;
    // or can use $("#obula_tutorsGraph_img").is(":visible")
    var sStageSort = ($("#obula_cohort_sort").prop('name') == 'obula_cohort_down') ? 'down' : 'up';
    var studentSort = ($("#obula_student_sort").prop('name') == 'obula_student_down') ? 'down' : 'up';
    var cohortfirst = ($("#obula_swap_sort").prop('name') == 'obula_cohortfirst_1') ? 1 : 0;
    var bandingCalc = $("#obula_banding_calc").val();
    var currentWeek = $("#obula_currentweek").val();       // Don't parse the JSON
    var myAdvisees = false;
    if (myaccElement != null && myaccElement.checked) {
        myAdvisees = true;
    }
    var semester = null;
    var redrawSemester = true;
    if (option != null && option != "programme" && option != "nextweek" && option != "prevweek") {
        // Must be a semester
        semester = option;
        option = "semester";
        redrawSemester = false;
    }
    //TODO see if we can common up the similar logic on ready event
    var data = {
        "programme": programme, "sStage": cohort, "maxShow": maxShow, "sStageSort": sStageSort, "studentSort": studentSort
        , "cohortfirst": cohortfirst, "currentWeek": currentWeek, "bandingCalc": bandingCalc, "studyType": studyType
        , "myAdvisees": myAdvisees, "semester": semester, "option": option, "oldProgramme": oldProgramme
    };
    $.ajax({
        type: 'POST',
        url: "../blocks/obu_learnanalytics/tutor_grid.php",
        data: data
    })
        .done(function (res) {
            tutor_grid_done(false, res, programme, cohort, '', refreshChart, true, redrawSemester);
        })
        .fail(function (jqXHR, textStatus, errorThrown) {
            alert('reloadGrid 2 post failed:' + errorThrown);
        })
        ;           // End of .ajax 'line'
};
