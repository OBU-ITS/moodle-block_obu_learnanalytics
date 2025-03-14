async function renderChart(chart_type, studentName) {
    // Because the Tutor grid also loads some student charts
    // This event will fire for both, but we can check the presence of the cohort placeholder
    //debugger;
    var currentWeek = $("#obula_currentweek").val();       // Don't parse the JSON
    if (currentWeek == null || currentWeek == undefined) {
        currentWeek = "";
    }
    var chart_style = null;
    // Check if there is a specifi chart_style for that chart_type
    if (chart_type.includes('_')) {
        // Split on underscore
        chart_style = chart_type.split('_')[1];
        chart_type = chart_type.split('_')[0];
      }
      
    
    var data = {
        programme: getProgrammeParameter(),
        studentNumber: getStudentNumberParameter(),       
        sStage: getModLevelParameter(),     
        currentWeek: currentWeek,                   
        chartType: chart_type
    };
    $.ajax({
            type: 'POST',
            url: "../blocks/obu_learnanalytics/students_graph_v2.php",
            data: data,
            dataType: 'json',
            success: function (res) {
                if (Array.isArray(res) && res.length) {
                    chartHandler(res[0], chart_type, studentName, chart_style);
                 } else {
                    chartHandler(res, chart_type, studentName, chart_style);
                 }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                alert(`student ready Event post failed:${errorThrown}`);
            }
        });
        set_studentLoading(false);    
    }