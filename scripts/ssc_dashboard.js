/* scripts for use with ssc (Student Support Coordinator) dashboard
*/

$(document).ready(function () { readySSC(); });     // Has to be called from inline function or it fails

function readySSC() {
    //debugger;
    var anyNode = document.getElementById("obula_ssc_heading_sml");
    var sideNode = checkColumn(anyNode);
    if (sideNode) {
        $("#obula_ssc_heading_sml").show();
        $("#obula_ssc_input_sml").show();
    } else {
        $("#obula_ssc_heading_med").show();
        $("#obula_ssc_input_med").show();
    }
}

/**
 * Clears the selected dashboard and parameters, ready to try again
 */
function clearSSC(sideNode = false) {
    $("#obula_ssc_sid").val('');
    $("#obula_summary_row").hide();
    $("#obula_dash_row").hide();
    $('#obula_footer').hide();

    if (sideNode) {
        $("#obula_ssc_heading_sml").show();
        $("#obula_ssc_input_sml").show();
    } else {
        $("#obula_ssc_heading_med").show();
        $("#obula_ssc_input_med").show();
    }
}

/**
 * Clears the selected dashboard and parameters, and goes back to original home
 */
function collapseSSC() {
    // So work out where we are going back to 
    var anyNode = document.getElementById("obula_ssc_heading_sml");
    var sideNode = checkColumn(anyNode);
    clearSSC(sideNode);
    if (sideNode) {
        giveBackPage(sideNode);
    }
    // Now log the event with an Ajax call, ignoring the response
    var data = {
        "dashboard": "Tutor"        //TODO determine which dashboard we are closing for students go-live
    };
$.ajax({
        type: 'POST',
        url: "../blocks/obu_learnanalytics/close_dashboard.php",
        data: data
    })
    .done(function (resp) {
        // No further action needed
    })
    .fail(function (jqXHR, textStatus, errorThrown) {
        alert('close_dashboard post failed:' + errorThrown);
    })
;           // End of .ajax 'line'
}

/**
 * Handles click event to show SSC a Students or Tutors eye view
 */
function showBecomeView(type, className) {
    // As there are 2 inputs with the same id we'll use the class
    var studentNumber = document.getElementsByClassName(className)[0].value;
    var data = {
        "studentNumber": studentNumber
    };
    $("#obula_ssc_student").val(studentNumber);
    var tnode = event.target;
    var urlpage = (type == "S") ? "become_student" : "become_students_tutor";
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
                takeOverPage(tnode);
                $("#obula_ssc_heading_sml").hide();
                $("#obula_ssc_heading_med").hide();
                $("#obula_ssc_input_sml").hide();
                $("#obula_ssc_input_med").hide();
                $('#obula_summary_cell').html(resp.summaryhtml);
                $("#obula_summary_row").show();
                $('#obula_dash_div').html(resp.dashboardhtml);
                $("#obula_dash_row").show();
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
            alert('showBecomeView exception\\n' + errorThrown);
        })
        // .always(function(resp) {
        //         // Code will always get executed after done or fail, like a try/catch finally
        //     })
        ;           // End of .ajax 'line'
        
    // Now the data currency
    showDataCurrency();
}
