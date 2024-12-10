/* scripts for use with Advisor Summary dashboard
NOTE the dashboard is the advisor dashboard, but the grid is the advisees grid
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
function collapseAdvisees() {
    giveBackPage("ts");

    // Now log the event with an Ajax call, ignoring the response
    var data = {
        "dashboard": "Advisees"
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

