/* scripts for use with Tutor Summary dashboard
*/

$(document).ready(function () {
    var host = $("#obula_host").val();
    var anyNode = document.getElementById("obula_ts_heading_sml");
    var sideNode = checkColumn(anyNode);
    // Enabling/disabling of controls is done in check connection
    if (sideNode) {
        $("#obula_ts_heading_sml").show();
        $("#obula_ts_input_sml").show();
    } else {
        $("#obula_ts_heading_med").show();
        $("#obula_ts_input_med").show();
    }
    // var host = $("#obula_host").val();
    // if (host == "right") {
    //     $("#obula_ts_heading_sml").show();
    //     $("#obula_ts_input_sml").show();
    // } else {
    //     $("#obula_ts_heading_med").show();
    //     $("#obula_ts_input_med").show();
    // }
});

/**
 * Clears the selected dashboard and parameters, and goes back to original home
 */
function collapseTutor() {
    giveBackPage("ts");

    // Now log the event with an Ajax call, ignoring the response
    var data = {
        "dashboard": "Tutor"
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
 * Handles click event to show Tutors page
 */
function showTutorFull() {
    var tnode = event.target;
    // Ajax call re-written to use later .done/.fail functionality in case we need promises later
    $.ajax({
        type: 'POST',
        url: "../blocks/obu_learnanalytics/become_tutor.php",
        // data: data,
        beforeSend: function () {
            $("#obula_error_row").hide();
        }
    })
        .done(function (resp) {
            // So we can get errors and successes back
            if (resp.success) {
                takeOverPage(tnode);
                $("#obula_ts_heading_sml").hide();
                $("#obula_ts_input_sml").hide();
                $("#obula_ts_heading_med").hide();
                $("#obula_ts_input_med").hide();
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
