/* scripts for use with student chart
*  AND tutor as this gets loaded for both
*/

$(document).ready(function () {
    // Because the Tutor grid also loads some student charts
    // This event will fire for both, but we can check the presence of the cohort placeholder
    //debugger;
    var imgID = $('#obula_cohort_comparison_img');
    if (imgID != null && imgID.length > 0) {
        if (imgID.length > 1) {
            alert('Multiple cohort engagement placeholders');
            return;
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
        };
        $.ajax({
                type: 'POST',
                url: "../blocks/obu_learnanalytics/cohort_engagement.php",
                data: data,
                success: function (res, p1, p2) {
                    $(imgID).attr("src", res);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    alert(`student ready Event post failed:${errorThrown}`);
                }
            });
        // Now the 3 graphs
        loadStudentGraph('vleduration', 1);
        loadStudentGraph('ezduration', 2);
        //loadStudentGraph('loansline', 3);
        //loadStudentGraph('attduration', 4);
        }
});


