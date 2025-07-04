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
        highlightStudentRow(studentNumber, '#obula_advisee_parent_grid');
        whileLoading(studentNumber);
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

/**
 * This function creates a modal overlay with a popover, and inside the popover
 * it creates a container for the attendance matrix. It then calls buildAttendanceMatrix()
 * passing the provided data.
 *
 */
function renderAttendanceMatrix() {
    // Create the overlay element (modal background)
    var overlay = document.createElement('div');
    overlay.id = 'attendance-overlay';
    overlay.style.display = 'none';
    // (Styling moved to styles.css)
    
    // Create the popover element
    var popover = document.createElement('div');
    popover.id = 'attendance-popover';
    // (Styling moved to styles.css)
    
    // Create and append the close button for the popover
    var closeButton = document.createElement('button');
    closeButton.innerHTML = '<i class="fa-solid fa fa-close" style="font-size:20px; "></i>';
    closeButton.addEventListener('click', function() {
        document.body.removeChild(overlay);
    });
    popover.appendChild(closeButton);
    
    // Create the container for the matrix (content container)
    var matrixContainer = document.createElement('div');
    matrixContainer.id = 'attendanceMatrixContainer';
    matrixContainer.style.marginTop = "20px"; // optional inline style for spacing
    // Add a heading and an empty table that will be populated by our matrix builder.
    matrixContainer.innerHTML = "<h3>Attendance Matrix</h3><table id='attendanceMatrixTable'></table>";
    popover.appendChild(matrixContainer);
    
    // Append the popover to the overlay and the overlay to the document body.
    overlay.appendChild(popover);
    document.body.appendChild(overlay);
    $('#obula_launch_attendance_matrix').addClass('disabled');


    var data = {
        "semester": '000000', "username": '1931312'
    };

    $.ajax({
        type: 'POST',
        url: '../blocks/obu_learnanalytics/advisees_matrix.php',
        data: data,
        dataType: 'json'
    })
    .done(function (res) {
        if (!res.success) {
            $('#attendanceMatrixContainer').html(res.html);
            return;
        }
    
        _matrixDataCache = res.data;          // 🔹 cache raw data
        buildAttendanceMatrix(res.data);                     // existing JS
    })
    .fail(function (_, __, err) {
        alert('Advisees Matrix Failed: ' + err);
    });
    
}


/**
 * This function builds an attendance matrix table in the container with
 * id "attendanceMatrixTable", using the provided studentData.
 *
 */
function buildAttendanceMatrix() {

    // TEMP sample data.
    const studentData = {
        "0089128": {
            "Alice Walker": {
                "BSc Hons Psychology": {
                    "Week 1": { "attendance_percent": "75",  "modules_missed": { "CRIM5009 (202409:1)": "1/4" } },
                    "Week 2": { "attendance_percent": "25",  "modules_missed": { "PSYC6002 (202409:1)": "3/4" } },
                    "Week 3": { "attendance_percent": "90",  "modules_missed": {} },
                    "Week 4": { "attendance_percent": "40",  "modules_missed": { "CRIM5009 (202409:1)": "2/4" } },
                    "Week 5": { "attendance_percent": "60",  "modules_missed": { "PSYC6002 (202409:1)": "2/4" } },
                    "Week 6": { "attendance_percent": "100", "modules_missed": {} },
                    "Week 7": { "attendance_percent": "85",  "modules_missed": { "PSYC6002 (202409:1)": "1/4" } },
                    "Week 8": { "attendance_percent": "55",  "modules_missed": { "CRIM5009 (202409:1)": "2/4" } },
                    "Week 9": { "attendance_percent": "0",   "modules_missed": { "PSYC6002 (202409:1)": "4/4" } },
                    "Week 10": { "attendance_percent": "100", "modules_missed": {} },
                    "Week 11": { "attendance_percent": "65",  "modules_missed": { "CRIM5009 (202409:1)": "1/4" } },
                    "Week 12": { "attendance_percent": "75",  "modules_missed": {} }
                }
            }
        },
        "0011223": {
            "Bob Jones": {
                "BSc Hons Psychology": {
                    "Week 1": { "attendance_percent": "100", "modules_missed": {} },
                    "Week 2": { "attendance_percent": "50",  "modules_missed": { "CRIM5009 (202409:1)": "2/4" } },
                    "Week 3": { "attendance_percent": "35",  "modules_missed": { "PSYC6002 (202409:1)": "3/4" } },
                    "Week 4": { "attendance_percent": "20",  "modules_missed": { "CRIM5009 (202409:1)": "3/4" } },
                    "Week 5": { "attendance_percent": "40",  "modules_missed": { "PSYC6002 (202409:1)": "2/4" } },
                    "Week 6": { "attendance_percent": "60",  "modules_missed": {} },
                    "Week 7": { "attendance_percent": "80",  "modules_missed": {} },
                    "Week 8": { "attendance_percent": "85",  "modules_missed": { "CRIM5009 (202409:1)": "1/4" } },
                    "Week 9": { "attendance_percent": "75",  "modules_missed": { "PSYC6002 (202409:1)": "1/4" } },
                    "Week 10": { "attendance_percent": "30",  "modules_missed": { "CRIM5009 (202409:1)": "3/4" } },
                    "Week 11": { "attendance_percent": "55",  "modules_missed": { "PSYC6002 (202409:1)": "2/4" } },
                    "Week 12": { "attendance_percent": "100", "modules_missed": {} }
                }
            }
        },
        "0077665": {
            "Carol Smith": {
                "BSc Hons Psychology": {
                    "Week 1": { "attendance_percent": "10",  "modules_missed": { "CRIM5009 (202409:1)": "3/4" } },
                    "Week 2": { "attendance_percent": "30",  "modules_missed": { "PSYC6002 (202409:1)": "2/4" } },
                    "Week 3": { "attendance_percent": "100", "modules_missed": {} },
                    "Week 4": { "attendance_percent": "90",  "modules_missed": {} },
                    "Week 5": { "attendance_percent": "0",   "modules_missed": { "CRIM5009 (202409:1)": "4/4" } },
                    "Week 6": { "attendance_percent": "75",  "modules_missed": {} },
                    "Week 7": { "attendance_percent": "20",  "modules_missed": { "PSYC6002 (202409:1)": "3/4" } },
                    "Week 8": { "attendance_percent": "40",  "modules_missed": { "CRIM5009 (202409:1)": "2/4" } },
                    "Week 9": { "attendance_percent": "55",  "modules_missed": {} },
                    "Week 10": { "attendance_percent": "60",  "modules_missed": { "CRIM5009 (202409:1)": "1/4" } },
                    "Week 11": { "attendance_percent": "40",  "modules_missed": { "PSYC6002 (202409:1)": "2/4" } },
                    "Week 12": { "attendance_percent": "70",  "modules_missed": {} }
                }
            }
        }
    };
    
// Flatten data for table creation.
var students = [];
var allWeeks = new Set();

for (var studentId in studentData) {
    if (!studentData.hasOwnProperty(studentId)) continue;

    // Get the student name. In our structure, studentData[studentId] 
    // has only one key for the student name.
    var nameObj = studentData[studentId];
    var studentName = Object.keys(nameObj)[0];

    // Get the programme object.
    // This object should have a single key—the student programme.
    var programmeObj = nameObj[studentName];
    var studentProgramme = Object.keys(programmeObj)[0];

    // Now, the weeks object is inside the programme object.
    var weeksObj = programmeObj[studentProgramme];

    // Push the flattened student data, including studentProgramme.
    students.push({ 
        studentId: studentId, 
        studentName: studentName, 
        studentProgramme: studentProgramme,
        weeksObj: weeksObj 
    });

    // Add all week labels.
    for (var w in weeksObj) {
        if (weeksObj.hasOwnProperty(w)) {
            allWeeks.add(w);
        }
    }
}

    
    // Sort week labels numerically (assuming names like "Week 1", "Week 2", etc.)
    var weekLabels = Array.from(allWeeks).sort(function(a, b) {
        var numA = parseInt(a.replace(/\D+/g, ""), 10);
        var numB = parseInt(b.replace(/\D+/g, ""), 10);
        return numA - numB;
    });
    
    // Build table header with a legend in the top-left cell.
    var table = document.getElementById("attendanceMatrixTable");
    var thead = document.createElement("thead");
    var headerRow = document.createElement("tr");
    
    var cornerTh = document.createElement("th");
    cornerTh.innerHTML = `
        <div class="myLegend">
            <div class="legendItem">
                <span class="legendSquare" style="background-color:#9eab05;"></span> > 75%
            </div>
            <div class="legendItem">
                <span class="legendSquare" style="background-color:#db7c12;"></span> 25–75%
            </div>
            <div class="legendItem">
                <span class="legendSquare" style="background-color:#c70540;"></span> < 25%
            </div>
        </div>
    `;
    headerRow.appendChild(cornerTh);
    
    weekLabels.forEach(function(week) {
        var th = document.createElement("th");
        th.textContent = week;
        headerRow.appendChild(th);
    });
    thead.appendChild(headerRow);
    table.appendChild(thead);
    
    // Build table body.
    var tbody = document.createElement("tbody");
    
    students.forEach(function(stObj) {
        // Create the main row for student data.
        var mainRow = document.createElement("tr");
        mainRow.className = "obula_att_matrix_row_" + stObj.studentId;
        
        // Create left cell: Student Name and ID.
        var nameCell = document.createElement("td");
        var nameDiv = document.createElement("div");
        nameDiv.className = "attendanceMatrixCellContent_studentName";
        // Attach an onclick event on the name cell.
        nameDiv.onclick = function() {
            // Render HTML for details container, change arrow and expand
            if (detailsContainer.style.maxHeight === "0px" || detailsContainer.style.maxHeight === "") {
                renderDetailsContent(stObj.studentId);
                detailsContainer.style.maxHeight = "200px"; // Expand (adjust height as needed)
                infoSpan.innerHTML = '<i class="fa-solid fa fa-angle-up"></i>';
            } else {
                // Empty the details container, switch the arrow and collapse
                $(".obula_att_matrix_row_details_" + stObj.studentId + " .detailsContainer").empty();
                detailsContainer.style.maxHeight = "0";
                infoSpan.innerHTML = '<i class="fa-solid fa fa-angle-down"></i>';

            }
        };
        
        // Create student name and ID elements.
        var nameSpan = document.createElement("span");
        nameSpan.textContent = stObj.studentName;
        nameSpan.style.fontWeight = 'bold';
        
        var idSpan = document.createElement("span");
        idSpan.textContent = "(" + stObj.studentProgramme + ")";
        idSpan.style.display = "block"; // Force on new line
        
        // (Optional) Include an info icon inline if desired.
        var infoSpan = document.createElement("span");
        infoSpan.innerHTML = '<i class="fa-solid fa fa-angle-down"></i>';
        // You can style this further via CSS if needed.
        
        // Append the spans to the name container.
        nameDiv.appendChild(nameSpan);
        nameDiv.appendChild(idSpan);
        nameDiv.appendChild(infoSpan);
        
        nameCell.appendChild(nameDiv);
        mainRow.appendChild(nameCell);
        
        // Create cells for each week.
        weekLabels.forEach(function(week) {
            var cell = document.createElement("td");
            var cellDiv = document.createElement("div");
            cellDiv.className = "attendanceMatrixCellContent";
            // Attach an onclick event on the name cell.
            cellDiv.onclick = function() {
                // Render HTML for details container, change arrow and expand
                if (detailsContainer.style.maxHeight === "0px" || detailsContainer.style.maxHeight === "") {
                    renderDetailsContent(stObj.studentId);
                    detailsContainer.style.maxHeight = "200px"; // Expand (adjust height as needed)
                    infoSpan.innerHTML = '<i class="fa-solid fa fa-angle-up"></i>';

                } else {
                    // Empty the details container, switch the arrow and collapse
                    $(".obula_att_matrix_row_details_" + stObj.studentId + " .detailsContainer").empty();
                    detailsContainer.style.maxHeight = "0";
                    infoSpan.innerHTML = '<i class="fa-solid fa fa-angle-down"></i>';

                }
            };

            var entry = stObj.weeksObj[week];
            if (entry) {
                var attendanceNum = parseInt(entry.attendance_percent, 10) || 0;
                cellDiv.textContent = attendanceNum + "%";
                // Set cell color.
                cellDiv.style.setProperty("--tile-color", getAttendanceColor(attendanceNum));
                
                // Add tooltip with module details.
                var tooltip = document.createElement("div");
                tooltip.className = "attendanceMatrixTooltip";
                var missed = entry.modules_missed || {};
                if (Object.keys(missed).length === 0) {
                    tooltip.textContent = "No modules missed";
                } else {
                    var lines = ["Modules missed:\n"];
                    for (var mod in missed) {
                        if (missed.hasOwnProperty(mod)) {
                            lines.push(mod + " → " + missed[mod]);
                        }
                    }
                    tooltip.textContent = lines.join("\n");
                }
                cellDiv.appendChild(tooltip);
            } else {
                cellDiv.textContent = "--";
                cellDiv.style.backgroundColor = "#eee";
            }
            cell.appendChild(cellDiv);
            mainRow.appendChild(cell);
        });
        
        tbody.appendChild(mainRow);
        
        // Create a details row below the main row.
        var detailsRow = document.createElement("tr");
        detailsRow.className = "obula_att_matrix_row_details_" + stObj.studentId;
        var detailsCell = document.createElement("td");
        detailsCell.colSpan = weekLabels.length + 1;
        var detailsContainer = document.createElement("div");
        detailsContainer.className = "detailsContainer";
        detailsCell.appendChild(detailsContainer);
        detailsRow.appendChild(detailsCell);
        tbody.appendChild(detailsRow);
    });
    
    table.appendChild(tbody);
    console.log(_matrixDataCache);
    $('#attendance-overlay').show();
    $('#obula_launch_attendance_matrix').removeClass('disabled');

    /* helper */
    function getAttendanceColor(p) {
        p = Math.max(0, Math.min(100, p));
        if (p > 75) {
            return "#9eab05";   // > 75%
        } else if (p < 25) {
            return "#c70540";   // < 25%
        } else {
            return "#db7c12";   // 25–75%
        }
    }
}

function renderDetailsContent(student_id) {
    $(".obula_att_matrix_row_details_" + student_id + " .detailsContainer")
    .html("<p style='text-align:center;'>Hello " + student_id + "</p>");
}



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
