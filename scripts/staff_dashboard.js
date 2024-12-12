/* scripts for use with Tutor (and Staff) Summary dashboard
*/

$(document).ready(function () {
    var host = $("#obula_host").val();
    var anyNode = document.getElementById("obula_staff_heading");
    var sideNode = checkColumn(anyNode);
    //debugger;
    // Enabling/disabling of controls is done in check connection
    //if (sideNode) {
        $("#obula_staff_heading").show();
    //} else {
    //}
});

/**
 * Clears the selected dashboard and parameters, and goes back to original home
 */
function collapseTutor() {
    giveBackPage("staff");

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
