/* scripts for use with tutor grid
*/

var gridLoading = studentLoading = chartLoading = marksLoading = false;
var studentLoadingCount = 0;

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

    // var programme = document.getElementById("selProgramme").value;
    // var mlElement = document.getElementById("selModLevel");
    // var modLevel = (mlElement == null) ? '*' : mlElement.value;
    // // But Study Mode/Type should have been
    // var stElement = document.getElementById("selStudyType");
    // var studyType = (stElement == null) ? '*' : stElement.value;

    showDateControls("getcurrent", false, "", true);
    // It used to load the tutor grid here, but as the showDateControls calculates week, semester let it do it
});             // End of inline function

function set_gridLoading(state) {
    gridLoading = state;
    set_somethingLoading(state);
}

function set_studentLoading(state) {
    if (state) {
        // Test it before add for first time in
        if (studentLoadingCount == 0) {
            studentLoading = state;
            set_somethingLoading(state);
        }
        studentLoadingCount++;
    } else {
        studentLoadingCount--;
        // Test after subtract for all done
        if (studentLoadingCount == 0) {
            studentLoading = state;
            set_somethingLoading(state);
        }
    }
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

function tutor_grid_done(fromReadyEvent, res, programme, modLevel, studentNumber, refreshChart = false) {
    // Fix the Bootstrap Tooltip behavior (it wasn't closing if you clicked on the hovered control)
    $('[data-toggle="ztooltip"]').tooltip({
        trigger: 'hover'
    })
    $('#obula_tutor_grid_div').html(res.html).delay(100);
    // debugger;
    store_parameters(programme, modLevel, null, null);
    // Now hide values that aren't in grid from module level and type drop downs
    if (res.full_data_set == 1 && !res.success) {
        $("#selModLevel").prop("disabled", true);
        $("#selStudyType").prop("disabled", true);
        $("#selCampusCode").prop("disabled", true);
    } else {
        $("#selModLevel").prop("disabled", false);
        $("#selStudyType").prop("disabled", false);
        $("#selCampusCode").prop("disabled", false);
        if (res.mod_levels !== undefined && res.mod_levels != '') {
            mlevels = res.mod_levels.split('|');
            mlevels.pop();          // Last element is empty
            // Now hide/show them (Note there are some cross browser concerns)
            // But only hide if we have been sent a full dataset (not sure how a non full dataset happens now)
            // - see https://stackoverflow.com/questions/9234830/how-to-hide-a-option-in-a-select-menu-with-css
            $("#selModLevel option").each(function () {
                if (($(this).val() == '*' && mlevels.length > 1) || mlevels.includes($(this).val())) {
                    $(this).show();
                } else {
                    if (res.full_data_set == 1) {
                        $(this).hide()
                    }
                }
                if (mlevels.length == 1) {
                    $("#selModLevel").val(mlevels[0]);
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
            ccodes = res.campus_codes.split('|');
            ccodes.pop();          // Last element is empty
            $("#selCampusCode option").each(function () {
                if (($(this).val() == '*' && stypes.length > 1) || ccodes.includes($(this).val())) {
                    $(this).show();
                } else {
                    if (res.full_data_set == 1) {
                        $(this).hide()
                    }
                }
                if (ccodes.length == 1) {
                    $("#selCampusCode").val(ccodes[0]);
                }
            });
        }
    }

    // Now show the advisees option if appropriate
    if (res.advisees_count > 0) {
        $("#obula_advisor").show();
    }

    if (res.success) {
        if (fromReadyEvent) {
            if (studentNumber != '') {
                highlightStudentRow(studentNumber);
            }
        } else {
            checkRefreshStudentBits();
        }
        if (refreshChart) {
            showChart();        // The cohort engagement chart
        }
    } else {
        unClickStudent();
    }
    if (!refreshChart) {
        // And the chart link (note unClickStudent can show it, but only if it hides the graph)
        var chartDisplay = document.getElementById("obula_tutorsGraph_img").style.display;
        if (chartDisplay == "none") {
            if (res.students_count > 1) {
                $("#obula_chart_show").show();
            } else {
                $("#obula_chart_show").hide();
            }
        }
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
    //debugger;
    //$(this).blur();
    highlightStudentRow(studentNumber);
    store_parameters(programme, studyStage, studentNumber, studentName);
    // Now the graphs
    for (var i = 1; i <= 3; i++) {
        set_studentLoading(true);       // Yes inside the loop, it counts them started and done
        var lastOne = (i == 3);
        var imgElement = document.getElementById("obula_studentGraph_img_" + i);
        if (imgElement != null) {
            // Pick up the currently selected chart type, needs jquery see https://www.geeksforgeeks.org/how-to-know-which-radio-button-is-selected-using-jquery/ 
            var types = ["vle", "att", "ez", "loans"];
            var rbname = types[i - 1] + 'charttype';
            var selectedType = $('input[name=' + rbname + ']:checked', '#obula_studentGraphs_div').val();
            // Now we need the Banding
            loadStudentGraph(selectedType, i, newDate, scrollIntoView, set_studentLoading);
        }
    }
    // If marks or eng chart are visible then reload
    var chartDisplay = document.getElementById("obula_tutorsGraph_img").style.display;
    if (chartDisplay != "none") {
        showChart();
    }
    var studentMarksDisplay = document.getElementById("obula_studentmarks_div").style.display;
    if (studentMarksDisplay != "none") {
        showStudentsMarks(programme, studentNumber, false);
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
 * Handles population and showing of alerts popup (that form needs to have already rendered)
 * @param studentNumber The student number
 */
function showStudentAlerts(studentNumber) {
    //debugger;
    var element = document.getElementById("selSemester");
    var semester;
    if (element == null) {
            alert('showStudentInfo exception - No Semester found');
            return;
        } else {
            semester = element.value;
        }
        var data = {
            "studentNumber": studentNumber
            , "semester": semester
        };
        $.ajax({
            type: 'POST',
            url: "../blocks/obu_learnanalytics/get_student_alerts.php",
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
                alert('showStudentAlerts exception ' + resp.responseText);
            })
            // .always(function(resp) {
            //         // Code will always get executed after done or fail, like a try/catch finally
            //     })
            ;           // End of .ajax 'line'
    


}


/**
 * Handles population and showing of help popup (that form needs to have already rendered)
 * @param studentNumber The student number
 * @param sname The student's name
 * @param advisor The advisor's p number 
 */
function showStudentInfo(studentNumber, sname, advisor, estatus, wstatus) {
    var element = document.getElementById("selSemester");
    var semester;
    if (element == null) {
            alert('showStudentInfo exception - No Semester found');
            return;
        } else {
            semester = element.value;
        }

    var data = {
        "studentNumber": studentNumber
        , "sName": sname
        , "advisor": advisor
        , "semester": semester
        , "eStatus": estatus
        , "wStatus": wstatus
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
    //debugger;
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
            //debugger;
            $resType = typeof res;
            if ($resType === 'object') {    // Images actually come back as strings
                // So this is actually an error structure
                if (res.http_status == 204) {
                    alert('No Module Engagement for this Student and Time Period');
                } else {
                    alert('Error from post, HTTP Status: ' + res.http_status + '\n' + res.message);
                }
            } else {
                $('#obula_studentModule_img').attr("src", res);       // Don't need .delay(2000);
                // Make sure it's visible
                $("#obula_studentModule_img").show();
                $('#obula_studentModule_row').show();
                var element = document.getElementById("obula_studentModule_row");
                element.scrollIntoView(true);
            }
        },
        error: function (errMsg) {
            //debugger;
            alert('showModuleEng Event post failed:' + errMsg);
        }
    });
}

function showStudentsMarks(programme, studentNumber, scrollIntoView) {
    var data = { "studentNumber": studentNumber, "programme": programme };
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

function clickStudentsMark(studentNumber, studentName, studyStage, programme) {
    $(this).blur();
    showStudentsMarks(programme, studentNumber, true);
    var studentGraphsDisplay = document.getElementById("obula_studentGraphs_div").style.display;
    if (studentGraphsDisplay != "none") {
        clickStudent(getProgrammeParameter(), studyStage, studentNumber, studentName, false);
    } else {
        highlightStudentRow(studentNumber);
    }
};

function hideOtherMarksChanged() {
    var element = document.getElementById("obula_hideOtherMarks");
    // Now send it back to be saved
    var data = {
        "name": "obula_hide_othermarks", "value": element.checked.toString()
    };
    $.ajax({
        type: 'POST',
        url: "../blocks/obu_learnanalytics/set_user_preference.php",
        data: data
    })
        .done(function (res) {
        })
        .fail(function (jqXHR, textStatus, errorThrown) {
            alert('set_user_preference post failed:' + errorThrown);
        })
        ;           // End of .ajax 'line'

    // Now do hide/unhide
    if (element.checked) {
        $(".student-marks .other-mark").attr("class", "hidden-mark");
    } else {
        $(".student-marks .hidden-mark").attr("class", "other-mark");
    }
};

function semesterChanged() {
    if (gridLoading) { return };
    unClickStudent();
    var element = document.getElementById("selSemester");
    if (element != null) {
        var semester = element.value;
        showDateControls('semester', false, semester, true);
// done in showDateControls        reloadTutorGrid('semester', semester);
    }
}

function checkRefreshStudentBits() {
    // So we need to work out what's showing and if that student is still valid
    var sid = getStudentNumberParameter();
    var idsElement = document.getElementById('obula_ids2chart').value;
    if (idsElement == null || sid == "" || idsElement.includes(sid, 0) == false) {
        unClickStudent();
        return;
    }

    var studentGraphsDisplay = document.getElementById("obula_studentGraphs_div").style.display;
    if (studentGraphsDisplay != "none") {
        clickStudent(getProgrammeParameter(), getModLevelParameter(), getStudentNumberParameter(), getStudentNameParameter(), false);
    }
    // And if the marks v eng scatter chart is visible, refresh or hide that
    var mveDisplayEle = document.getElementById("obula_marksveng_tbl");
    if (mveDisplayEle != null && mveDisplayEle.style.display != "none") {
        // if (semesterChanged) {
        //     $("#obula_marksveng_tbl").hide();
        // } else {
            showMarksvEng('rl');
        // }
    }
}

function showChart() {
    // Let's hide some columns so we have more space
    // TODO see if we can just query table rather than whole dom
    alert("Unexpected use of showChart");
    $(".students-hideable").addClass('students-hidden');
    $(".students-hideable").removeClass('students-hideable');
    //debugger;

    // Now get and set the programme in a hidden field so it's available
    var programme = document.getElementById("selProgramme").value;
    var modLevel = document.getElementById("selModLevel").value;
    var sType = document.getElementById("selStudyType").value;
    if (programme != '') {
        set_chartLoading(true);
        //var imgElement = document.getElementById('obula_tutorsGraph_img');
        var idsElement = document.getElementById('obula_ids2chart').value;
        var currentWeek = $("#obula_currentweek").val();       // Don't parse the JSON
        var data = {
            "programme": programme
            , "modLevel": modLevel
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
            },
            error: function (errMsg) {
                alert('showChart Event post failed:' + errMsg);
                $("#obula_chart_show").show();
                set_chartLoading(false);
            }
        });
    }
}

// TODO remove after testing
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

function showMarksvEng(duration = 'il') {
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
    //var modLevel = getModLevelParameter();
    if (programme != '') {
        var idsElement = document.getElementById('obula_ids2chart').value;
        var currentWeek = $("#obula_currentweek").val();       // Don't parse the JSON
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
    // debugger;
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
    reloadTutorGrid('programme', oldProgramme);
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
                html += '<li title="More matches, enter more to refine">...</li>';
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

function modLevelChanged() {
    if (gridLoading) { return };
    unClickStudent();
    // Now reload
    $("#obula_myacc").prop("checked", false);
    reloadTutorGrid();
}


function studyTypeChanged() {
    if (gridLoading) { return };
    //debugger;
    var element = document.getElementById("selStudyType");
    if (element != null) {
        unClickStudent();
        reloadTutorGrid();
    }
    // TODO error if null
}

function campusCodeChanged() {
    if (gridLoading) { return };
    // debugger;
    var element = document.getElementById("selCampusCode");
    if (element != null) {
        unClickStudent();
        reloadTutorGrid();
    }
    // TODO error if null
}

// Check box to select my advisee's
function myaccChanged() {
    if (gridLoading) { return };
    unClickStudent();
    reloadTutorGrid();
}

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
    reloadTutorGrid();
};

function reloadTutorGrid(option = null, p2 = null, currentWeek = null) {
    if (gridLoading) { return };
    //debugger;
    set_gridLoading(true);
    var programme = document.getElementById("selProgramme").value;
    // Controls might not even have been loaded yet
    var mlElement = document.getElementById("selModLevel");
    var modLevel = (mlElement == null) ? '*' : mlElement.value;
    var stElement = document.getElementById("selStudyType");
    var studyType = (stElement == null) ? '*' : stElement.value;
    var ccElement = document.getElementById("selCampusCode");
    var campusCode = (ccElement == null) ? '*' : ccElement.value;
    var myaccElement = document.getElementById("obula_myacc");
    var cohortSort = 'down';
    var studentSort = 'down';
    if (option != null && option == 'programme') {
        // Assume loaded if we are called from programme changed
        mlElement.value = modLevel = '*';
        stElement.value = studyType = '*';
        ccElement.value = campusCode = '*';
        if (myaccElement != null) {     // It will be null if previous grid was not loaded because no activity
            myaccElement.checked = false;
        }
    } else {
        cohortSort = ($("#obula_cohort_sort").prop('name') == 'obula_cohort_up') ? 'up' : 'down';
        studentSort = ($("#obula_student_sort").prop('name') == 'obula_student_up') ? 'up' : 'down';
    }

    //debugger;
    var refreshChart = (document.getElementById("obula_tutorsGraph_img").style.display == "none") ? false : true;
    // or can use $("#obula_tutorsGraph_img").is(":visible")
    var bandingCalc = "MED-30-4";    //$("#obula_banding_calc").val();
    if (currentWeek == null) {
        var currentWeek = $("#obula_currentweek").val();       // Don't parse the JSON
    }
    var onlyMyAdvisees = "false";
    if (myaccElement != null && myaccElement.checked) {
        onlyMyAdvisees = "true";
    }
    var semester;
    if (option != null && option == "semester") {
        semester = p2;
    } else {
    var element = document.getElementById("selSemester");
    if (element == null) {
            alert('reloadGrid exception - No Semester found');
            return;
        } else {
            semester = element.value;
        }
    }
    var data = {
        "programme": programme, "modLevel": modLevel, "cohortSort": cohortSort, "studentSort": studentSort
        , "cohortfirst": 1, "currentWeek": currentWeek, "bandingCalc": bandingCalc, "studyType": studyType
        , "onlyMyAdvisees": onlyMyAdvisees, "semester": semester, "option": option, "oldProgramme": p2, "campusCode": campusCode
    };
    $.ajax({
        type: 'POST',
        url: "../blocks/obu_learnanalytics/tutor_grid.php",
        data: data
    })
        .done(function (res) {
            //debugger;
            tutor_grid_done(false, res, programme, modLevel, '', refreshChart);
        })
        .fail(function (jqXHR, textStatus, errorThrown) {
            //debugger;
            alert('reloadTutorGrid 2 post failed:' + errorThrown);
        })
        ;           // End of .ajax 'line'
};
