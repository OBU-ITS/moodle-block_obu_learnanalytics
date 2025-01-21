/* scripts for use with any dashboard with small block and show button
    tested with Tutor and SSC, TODO - make it works for students dashboard
    Written separate from common.js for easier development and testing
*/

$(document).ready(function () {
    //debugger;
    //var sideNode = checkColumn(anyNode);
    $.ajax({
        type: 'POST',
        url: "../blocks/obu_learnanalytics/check_connection.php",
        // NONE yet - data: data
    })
        .done(function (resp) {
            // TODO use a class or something to make enabling/disabling easier and refactor to not have both sml and med versions
            if (resp.ccStatus.Status == "OK") {
                // Shouldn't be needed but hide the error panels
                $('#obula_cc_errordiv').hide();
                $('#obula_show_pgm').prop("disabled",false);
                $('#obula-show-tf-med').prop("disabled",false);
                $('#obula_show_stud_no').prop("disabled",false);
                $('#obula_show_stud_pgm').prop("disabled",false);
                $('#obula_show_stud_pgm').show();
            } else {
                // OK to set/show both message panels as parent of 1 should be hidden
                // Popup is same for small and medium panels and is only populated for an admin (or me)
                $('#obula_cc_errordiv').html(resp.ccStatus.problemMessageSml + resp.ccStatus.popup);
                $('#obula_cc_errordiv').show();
                $('#obula_show_pgm').prop("disabled",true);
                $('#obula_show_stud_pgm').prop("disabled",true);
                $('#obula_show_stud_pgm').hide();         // To allow space for message
            }
        })
        .fail(function (jqXHR, textStatus, errorThrown) {
            alert('check_connection post failed:' + errorThrown);
        })
        // .always(function (resp) {
        //     // Code will always get executed after done or fail, like a try/catch finally
        //     alert('check_connection always:' + resp);
        // })
        ;           // End of .ajax 'line'
});

