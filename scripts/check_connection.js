/* scripts for use with any dashboard with small block and show button
    tested with Tutor and SSC, TODO - make it works for students dashboard
    Written separate from common.js for easier development and testing
*/

$(document).ready(function () {
    //debugger;
    // var anyNode = document.getElementById("obula_ssc_heading_sml");
    // Above line fails and I don't know why - it works in tutor_dashboard.js
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
                $('#obula_cc_errordiv_sml').hide();
                $('#obula_cc_errordiv_med').hide();
                // Enable the Tutor summary controls
                $('#obula-show-tf-sml').prop("disabled",false);
                $('#obula-show-tf-med').prop("disabled",false);
                // Now the SSC controls
                $('#obula_ssc_sid_sml').prop("disabled",false);
                $('#obula_ssc_sid_med').prop("disabled",false);
                $('#obula-show-sv-sml').prop("disabled",true);      // Not available yet
                $('#obula-show-sv-sml').show();
                $('#obula-show-tv-sml').prop("disabled",false);
                $('#obula-show-tv-sml').show();
                $('#obula-show-sv-med').prop("disabled",true);      // Not available yet
                $('#obula-show-sv-med').show();
                $('#obula-show-tv-med').prop("disabled",false);
                $('#obula-show-tv-med').show();
            } else {
                // OK to set/show both message panels as parent of 1 should be hidden
                // Popup is same for small and medium panels and is only populated for an admin (or me)
                $('#obula_cc_errordiv_sml').html(resp.ccStatus.problemMessageSml + resp.ccStatus.popup);
                $('#obula_cc_errordiv_med').html(resp.ccStatus.problemMessageMed + resp.ccStatus.popup);
                $('#obula_cc_errordiv_sml').show();
                $('#obula_cc_errordiv_med').show();
                // Now the Tutor summary controls
                $('#obula-show-tf-sml').prop("disabled",true);
                $('#obula-show-tf-med').prop("disabled",true);
                // Now the SSC controls
                $('#obula_ssc_sid-sml').prop("disabled",true);
                $('#obula-show-sv-sml').prop("disabled",true);
                $('#obula-show-sv-sml').hide();         // To allow space for message
                $('#obula-show-tv-sml').prop("disabled",true);
                $('#obula-show-tv-sml').hide();         // To allow space for message
                $('#obula_ssc_sid-med').prop("disabled",true);
                $('#obula-show-sv-med').prop("disabled",true);
                $('#obula-show-sv-med').hide();         // To allow space for message
                $('#obula-show-tv-med').prop("disabled",true);
                $('#obula-show-tv-med').hide();         // To allow space for message
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

