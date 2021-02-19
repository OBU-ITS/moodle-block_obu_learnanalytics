/* scripts for use with student dashboard
*/

// $(document).ready(function () {
//     debugger; // NOT NEEDED YET
// });

/**
 * Handles click events for ideas lightbulb
 */
function ideasClicked(studentNumber) {
    var data = {
        "studentNumber": studentNumber
    };
    $.ajax({
        type: 'POST',
        url: "../blocks/obu_learnanalytics/student_ideas.php",
        data: data,
        success: function (res) {
            $('#obula_student_ideas_div').html(res).delay(1000);
            document.getElementById("obula_cohort_afterstatus_row").style.display = "table-row";
            document.getElementById("obula_student_ideas_row").style.display = "table-row";
            var element = document.getElementById("obula_student_ideas_div");
            element.scrollIntoView(false);           // true is going too far
        },
        error: function (errMsg) {
            alert('ideasClicked post failed:' + errMsg);
        }
    });
}

function showCohortComparison(duration = 'il') {
    var initialLoad = false;
    if (duration == 'il') {         // Initial load
        initialLoad = true;
        duration = '1wk'
        showWeekControl();          // It does spacer as well
    }
    var currentWeek = $("#obula_currentweek").val();       // Don't parse the JSON
    if (currentWeek == null || currentWeek == undefined) {
        currentWeek = "";
    }
    var data = {
        "programme": getProgrammeParameter()
        , "sStage": getStudyStageParameter()
        , "studentNumber": getStudentNumberParameter()
        // , "studentName": getStudentNameParameter()
        , "currentWeek": currentWeek
        , "duration": duration
    };
    $.ajax({
        type: 'POST',
        url: "../blocks/obu_learnanalytics/cohort_engagement.php",
        data: data,
        success: function (res, p1, p2) {
            $("#obula_cohort_comparison_img").attr("src", res);       // Don't need .delay(2000);
            if (initialLoad) {
                document.getElementById("obula_cohort_comparison_row").style.display = "table-row";
                //document.getElementById("obula_cohort_comparison_rbs").style.display = "block";
                var element = document.getElementById("obula_cohort_comparison_img");
                element.scrollIntoView(false);           // true is going too far
            }
        },
        error: function (errMsg) {
            alert(`showCohortReady Event post failed:${errMsg}`);
        }
    });
}

function changeElibHistRB(duration) {
    showELibraryHistory(duration);
}


function showELibraryHistory(duration = '1wk') {
    // Student page doesn't have a week control yet, but leave code for now in case we add it  // TODO check before release
    var currentWeek = $("#obula_currentweek").val();       // Don't parse the JSON
    if (currentWeek == null || currentWeek == undefined) {
        currentWeek = "";
    }
    showWeekControl();          // It does spacer as well
    var data = {
        "programme": getProgrammeParameter()
        , "studentNumber": getStudentNumberParameter()
        , "studentName": getStudentNameParameter()
        , "currentWeek": currentWeek
        , "width": 900
        , "ids2Chart": '*'
        , "duration": duration
    };
    $.ajax({
        type: 'POST',
        url: "../blocks/obu_learnanalytics/cohort_elibrary_history.php",
        data: data,
        success: function (res, p1, p2) {
            $("#obula_elibrary_history_img").attr("src", res);       // Don't need .delay(2000);
            document.getElementById("obula_elibrary_history_row").style.display = "table-row";
            var element = document.getElementById("obula_elibrary_history_img");
            element.scrollIntoView(true);           // false didn't do it very well
        },
        error: function (errMsg) {
            alert(`showELibraryHistory Event post failed:${errMsg}`);
        }
    });
}

/**
 * Click event to show Student activity for the current week
 * 
 *
function showStudentConsistency(version) {
    var currentWeek = $("#obula_currentweek").val();       // Don't parse the JSON
    if (currentWeek == null || currentWeek == undefined) {
        currentWeek = "";
    }
    showWeekControl();          // It does spacer as well
    var data = {
        "studentNumber": getStudentNumberParameter()
        , "studentName": getStudentNameParameter()
        , "currentWeek": currentWeek
        , "width": 800
    };
    $.ajax({
        type: 'POST',
        // chart= parameter on url will not get used on server as it's a post, but we can get to it on the response
        url: "../blocks/obu_learnanalytics/student_consistency_" + version + ".php",
        data: data,
        success: function (res, p1, p2) {
            $("#obula_student_consistency_img").attr("src", res);       // Don't need .delay(2000);
            document.getElementById("obula_student_consistency_row").style.display = "table-row";
            var imgElement = document.getElementById("obula_student_consistency_img");
            imgElement.scrollIntoView(false);           // true is going too far
        },
        error: function (errMsg) {
            alert(`student consistency Event post failed:${errMsg}`);
        }
    });
}
*/

/*function showStudentRadar() {
    var currentWeek = $("#obula_currentweek").val();       // Don't parse the JSON
    if (currentWeek == null || currentWeek == undefined) {
        currentWeek = "";
    }
    showWeekControl();          // It does spacer as well
    var data = {
        "studentNumber": getStudentNumberParameter()
        , "studentName": getStudentNameParameter()
        , "currentWeek": currentWeek
    };
    $.ajax({
        type: 'POST',
        // chart= parameter on url will not get used on server as it's a post, but we can get to it on the response
        url: "../blocks/obu_learnanalytics/student_radar.php",
        data: data,
        success: function (res, p1, p2) {
            // Steal consistency spot for now
            $("#obula_student_consistency_img").attr("src", res);       // Don't need .delay(2000);
            document.getElementById("obula_student_consistency_row").style.display = "table-row";
            var imgElement = document.getElementById("obula_student_consistency_img");
            imgElement.scrollIntoView(false);           // true is going too far
        },
        error: function (errMsg) {
            alert(`student show Radar Event post failed:${errMsg}`);
        }
    });
}*/

function showStudentGraphs() {
    showWeekControl();          // It does spacer as well
    // Now the 3 graphs
    loadStudentGraph('vleduration', 1, null, true);
    loadStudentGraph('ezduration', 2, null, false);
    //loadStudentGraph('loansline', 3, null, false);
    //loadStudentGraph('attduration', 4, null, false);
}

function clickChangeWeek(direction) {
    var newWeek = (direction > 0) ? JSON.parse($("#obula_nextweek").val()) : JSON.parse($("#obula_prevweek").val());
    // Now put it back and reload grid and chart(s), reload of weekcontrol will recalculate next/prev
    $("#obula_currentweek").val(JSON.stringify(newWeek));
    var newDate = new Date(newWeek.first_day_week.date);
    var y = newDate.toString();
    // NO this uses jqueryUI var z = $.datepicker.formatDate('dd-M-yy', newDate);
    // And as I don't want to introduce another libary, do it manually
    // with no regard to i18n  - TODO put months in lang file 
    var year = newDate.getFullYear();
    var month = newDate.getMonth();
    var day = newDate.getDate();
    var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    // getMonth is 0 based so don't subtract 1
    var formattedDate = (day < 10 ? "0" + day : day) + "-" + months[(month)] + "-" + year;
    // Now put it back
    $("#obula_weekdate").text(formattedDate);
    showWeekControl(JSON.stringify(newWeek));
    // Now we just need to work out what to reload
    if (document.getElementById("obula_cohort_comparison_row").style.display == "table-row") {
        showCohortComparison();
    }
    //if (document.getElementById("obula_student_consistency_row").style.display == "table-row") {
    //    showStudentConsistency('v1');
    //}
    if (document.getElementById("obula_elibrary_history_row").style.display == "table-row") {
        showELibraryHistory();
    }
    if (document.getElementById("obula_studentGraphs_div").style.display == "block") {
        loadStudentGraph('vleduration', 1, null, true);
        loadStudentGraph('ezduration', 2, null, false);
        loadStudentGraph('loansline', 3, null, false);
        loadStudentGraph('attduration', 4, null, false);
    }
}


