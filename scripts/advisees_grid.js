/* scripts for use with tutor grid
*/

var gridLoading = studentLoading = chartLoading = marksLoading = false;
var studentLoadingCount = 0;

$(document).ready(function () {
    //debugger;
    set_gridLoading(true);
    // showDateControls will call reloadAdvisorGrid
    showDateControls("getcurrent", "Advisor", "", true);
});             // End of inline function

function set_gridLoading(state) {
    gridLoading = state;
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

function advisees_grid_done(res) {
    //debugger;
    // Fix the Bootstrap Tooltip behavior (it wasn't closing if you clicked on the hovered control)
    $('[data-toggle="ztooltip"]').tooltip({
        trigger: 'hover'
    })
    $('#obula_advisee_grid_div').html(res.html).delay(100);
    $('#obula_advisee_grid2_div').html(res.html2).delay(100);

    //debugger;
//    store_parameters(programme, modLevel, null, null);

    set_gridLoading(false);
}

function clickStudentAdvisee(studentNumber) {
    //stolen from showBecomeView
    //debugger;
    var data = {
        "studentNumber": studentNumber,
        "type": "A"
    };
    $("#obula_ssc_student").val(studentNumber);
    //var tnode = event.target;
    var urlpage = "become_students_tutor";
    // Ajax call re-written to use later .done/.fail functionality in case we need promises later
    $.ajax({
        type: 'POST',
        url: "../blocks/obu_learnanalytics/" + urlpage + ".php",
        data: data,
        beforeSend: function () {
            $("#obula_error_row").hide();
        }
    })
        .done(function (resp) {
            // So we can get errors and successes back
            if (resp.success) {
                // Should already be taken over takeOverPage(tnode);
                $('#obula_summary_cell').html(resp.summaryhtml);
                $("#obula_summary_row").show();
                $('#obula_dash_div').html(resp.dashboardhtml);
                $("#obula_dash_row").show();
                // Now the data currency
                showDataCurrency();
            } else {
                $('#obula_error_cell').html(resp.message);
                $("#obula_error_row").show();
                $('#obula_footer').hide();
            }
        })
        .fail(function (jqXHR, textStatus, errorThrown) {
            // only way to trigger a fail is with a non 200 response, 404, 500 etc
            // but that seems extreme for a simple validation
            // So reserving this for exceptions
            alert('clickStudentAdvisee exception\\n' + errorThrown);
        })
        // .always(function(resp) {
        //         // Code will always get executed after done or fail, like a try/catch finally
        //     })
        ;           // End of .ajax 'line'
        
}


// function semesterChanged() {
//     if (gridLoading) { return };
//     unClickStudent();
//     var element = document.getElementById("selSemester");
//     if (element != null) {
//         var semester = element.value;
//         showDateControls('semester', "Tutor", semester, true);
// // done in showDateControls        reloadTutorGrid('semester', semester);
//     }
// }

function reloadAdvisorGrid(currentWeek = null) {
    if (gridLoading) { return };
    //debugger;
    set_gridLoading(true);

    //debugger;
    if (currentWeek == null) {
        var currentWeek = $("#obula_currentweek").val();       // Don't parse the JSON
    }
    var element = document.getElementById("selSemester");
    if (element == null) {
            alert('reloadGrid exception - No Semester found');
            return;
            //semester = '202409';    //TODO
        } else {
            semester = element.value;
        }
    var data = {
        "currentWeek": currentWeek, "semester": semester
    };
    $.ajax({
        type: 'POST',
        url: "../blocks/obu_learnanalytics/advisees_grid.php",
        data: data
    })
        .done(function (res) {
            //debugger;
            advisees_grid_done(res);
        })
        .fail(function (jqXHR, textStatus, errorThrown) {
            //debugger;
            alert('reloadAdvisorGrid 2 post failed:' + errorThrown);
        })
        ;           // End of .ajax 'line'
        
};
