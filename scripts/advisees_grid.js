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
//     // unClickStudent();
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
        _matrixDataCache = null;    // Clear down the cache after we are done.
    });
    popover.appendChild(closeButton);
    
    // Create the container for the matrix (content container)
    var matrixContainer = document.createElement('div');
    matrixContainer.id = 'attendanceMatrixContainer';
    matrixContainer.style.marginTop = "20px"; // optional inline style for spacing
    // Add a heading and an empty table that will be populated by our matrix builder.
    matrixContainer.innerHTML = "<h3>Attendance Matrix</h3><table id='attendanceMatrixTable'></table>";
    popover.appendChild(matrixContainer);
    // Grab the semester from the selected dropdown
    semester = document.getElementById('selSemester').value;
    // Append the popover to the overlay and the overlay to the document body.
    overlay.appendChild(popover);
    document.body.appendChild(overlay);
    $('#obula_launch_attendance_matrix').addClass('disabled');
    var wwwroot = M.cfg && M.cfg.wwwroot || '';
    var data = {
        "semester": semester
    };

    $.ajax({
        type: 'POST',
        url: wwwroot +'/blocks/obu_learnanalytics/advisees_matrix.php',
        data: data,
        dataType: 'json'
    })
    .done(function (res) {
        if (!res.success) {
            $('#attendanceMatrixContainer').html(res.html);
            $('#attendanceMatrixContainer').append('<div>There is currently no data available for this semester.</div>');
            $('#attendance-overlay').show();
            $('#obula_launch_attendance_matrix').removeClass('disabled');
            return;
        }
        _matrixDataCache = res.data;          // 🔹 cache raw data
        _matrixDetailsDataCache = res.matrixdetails;          // 🔹 cache details raw data
        AttendanceMatrix(); 
    })
    .fail(function (_, __, err) {
          console.error('HTTP', jqXHR.status, textStatus, errorThrown);
            console.error('Response:', jqXHR.responseText);
            alert('Advisees Matrix Failed: ' + errorThrown);
    });
    
}


/**
 * Build (or rebuild) the attendance matrix table.
 *
 * @param {object} studentData  matrix JSON from PHP
 *        shape: studentNo → studentName → programme → Week n { attendance_percent, modules_missed }
 */
var _matrixDataCache = null;        // holds the raw matrix JSON
var _overallSortAsc  = true;        // current sort direction
function AttendanceMatrix() {

    if (!_matrixDataCache);          // nothing to build yet
    const studentData = _matrixDataCache;
    /* ─── 0. clear any previous table ─────────────────────────────── */
    const table = document.getElementById('attendanceMatrixTable');
    table.innerHTML = '';

    /* ─── 1. flatten for easy looping  ────────────────────────────── */
    const students = [];
    const allWeeks = new Set();

    for (const studentId in studentData) {
        if (!studentData.hasOwnProperty(studentId)) continue;

        const nameObj     = studentData[studentId] || {};
        const studentName = Object.keys(nameObj)[0] || 'Unknown';

        /* programme block could be missing */
        const programmeObj     = nameObj[studentName] || {};
        const studentProgramme = Object.keys(programmeObj)[0] || 'Unknown programme';

        /* weeks block could be missing */
        const weeksObj = programmeObj[studentProgramme] || {};   // ← never undefined

        /* overall % across available weeks */
        let sumPct = 0, weekCount = 0;
        for (const w in weeksObj) {
            if (!weeksObj.hasOwnProperty(w)) continue;
            sumPct += parseInt(weeksObj[w].attendance_percent, 10) || 0;
            weekCount++;
            allWeeks.add(w);                 // safe because weeksObj is now an object
        }
        const overallPct = weekCount ? Math.round(sumPct / weekCount) : 0;

        students.push({ studentId, studentName, studentProgramme, weeksObj, overallPct });
    }


    
    // ── NEW: sort by overallPct ───────────────────────────────────
    students.sort((a, b) =>
        _overallSortAsc ? b.overallPct - a.overallPct
                        : a.overallPct - b.overallPct);

    const weekLabels = Array.from(allWeeks).sort((a, b) =>
        parseInt(a.replace(/\D+/g, ''), 10) - parseInt(b.replace(/\D+/g, ''), 10)
    );

    /* ─── 2. header with legend + Overall column ──────────────────── */
    const thead = document.createElement('thead');
    const headerRow = document.createElement('tr');

    const cornerTh = document.createElement('th');
    cornerTh.innerHTML = `
        <div class="myLegend">
            <div class="legendItem"><span class="legendSquare" style="background:#9eab05"></span> &gt; 75%</div>
            <div class="legendItem"><span class="legendSquare" style="background:#db7c12"></span> 25–75%</div>
            <div class="legendItem"><span class="legendSquare" style="background:#c70540"></span> &lt; 25%</div>
        </div>`;
    headerRow.appendChild(cornerTh);

    const overallTh = document.createElement('th');
    /*  add the class that controls the gradient  */
    overallTh.classList.add(_overallSortAsc ? 'overall-sort-desc'
        : 'overall-sort-asc');

    
    const icon = document.createElement('i');
    icon.className = _overallSortAsc ? 'fa fa-caret-down'   // ▲
                                     : 'fa fa-caret-up';// ▼
    icon.style.marginRight = '4px';
    
    overallTh.append('Overall ', icon);
    
    /* toggle & rebuild on click */
    overallTh.onclick = () => {
        _overallSortAsc = !_overallSortAsc;   // flip direction
        AttendanceMatrix();              // rebuild table
    };
    
    headerRow.appendChild(overallTh);
    
    weekLabels.forEach(w => {
        const th = document.createElement('th');
    
        // split into ["Week", "1"], then join with a newline
        const [label, num] = w.split(' ');
        th.textContent = `${label}\n${num}`;
    
        headerRow.appendChild(th);
    });
    

    thead.appendChild(headerRow);
    table.appendChild(thead);

    /* ─── 3. body ─────────────────────────────────────────────────── */
    const tbody = document.createElement('tbody');

    students.forEach(st => {
        /* main row */
        const mainRow = document.createElement('tr');
        mainRow.className = `obula_att_matrix_row_${st.studentId}`;

        /* name / programme cell with arrow */
        const nameTd  = document.createElement('td');
        const nameDiv = document.createElement('div');
        nameDiv.className = 'attendanceMatrixCellContent_studentName';

        const nameSpan = document.createElement('span');
        nameSpan.textContent = st.studentName;
        nameSpan.style.fontWeight = 'bold';

        const progSpan = document.createElement('span');
        progSpan.textContent = `(${st.studentProgramme})`;
        progSpan.style.fontSize = '12px'
        progSpan.style.display = 'block';

        const arrowSpan = document.createElement('span');
        arrowSpan.innerHTML = '<i class="fa-solid fa fa-angle-down"></i>';

        nameDiv.append(nameSpan, progSpan, arrowSpan);
        nameTd.appendChild(nameDiv);
        mainRow.appendChild(nameTd);

        /* overall % cell */
        const overallTd  = document.createElement('td');
        const overallDiv = document.createElement('div');
        overallDiv.className = 'attendanceMatrixCellContent';
        overallDiv.textContent = st.overallPct + '%';
        overallDiv.style.setProperty('--tile-color', getAttendanceColor(st.overallPct));
        overallTd.appendChild(overallDiv);
        mainRow.appendChild(overallTd);

        /* week cells */
        weekLabels.forEach(week => {
            const td  = document.createElement('td');
            const div = document.createElement('div');
            div.className = 'attendanceMatrixCellContent';

            const entry = st.weeksObj[week];
            if (entry) {
                const pct = parseInt(entry.attendance_percent, 10) || 0;
                div.textContent = pct + '%';
                div.style.setProperty('--tile-color', getAttendanceColor(pct));

                const tip = document.createElement('div');
                tip.className = 'attendanceMatrixTooltip';

                const missed = entry.modules_missed || {};
                tip.textContent = Object.keys(missed).length
                    ? 'Lectures Not Attended:\n' + Object.entries(missed).map(([m,v]) => `${m} → ${v}`).join('\n')
                    : 'Full Attendance';

                div.appendChild(tip);
            } else {
                div.textContent = '--';
                div.style.background = '#eee';
            }

            td.appendChild(div);
            mainRow.appendChild(td);
        });

        tbody.appendChild(mainRow);

        /* details row (collapsed) */
        const detRow  = document.createElement('tr');
        detRow.className = `obula_att_matrix_row_details_${st.studentId}`;

        const detTd   = document.createElement('td');
        detTd.colSpan = weekLabels.length + 2;          // name + overall + weeks

        const detDiv  = document.createElement('div');
        detDiv.className = 'detailsContainer';

        detTd.appendChild(detDiv);
        detRow.appendChild(detTd);
        tbody.appendChild(detRow);

        /* expand / collapse logic (shared by name & tiles) */
        const toggleDetails = () => {
            const open = detDiv.style.maxHeight && detDiv.style.maxHeight !== '0px';
            if (open) {
                $(`.obula_att_matrix_row_details_${st.studentId} .detailsContainer`).empty();
                detDiv.style.maxHeight = '0';
                arrowSpan.innerHTML = '<i class="fa-solid fa fa-angle-down"></i>';
            } else {
                renderAttendanceMatrixDetails(st.studentId);
                detDiv.style.maxHeight = '80%';
                arrowSpan.innerHTML = '<i class="fa-solid fa fa-angle-up"></i>';
            }
        };

        nameDiv.onclick = toggleDetails;
        mainRow.querySelectorAll('.attendanceMatrixCellContent').forEach(div => {
            div.onclick = toggleDetails;
        });
    });

    table.appendChild(tbody);
    $('#attendance-overlay').show();
    $('#obula_launch_attendance_matrix').removeClass('disabled');

    /* helper */
    function getAttendanceColor(p) {
        if (p > 75) return '#9eab05';
        if (p < 25) return '#c70540';
        return '#db7c12';
    }
}

// Details drop down section where we use some chartjs graphs and show extra information
var _matrixDetailsDataCache = null;
window._attendanceCharts = window._attendanceCharts || {};
function renderAttendanceMatrixDetails (id) {
    const $container = $(`.obula_att_matrix_row_details_${id} .detailsContainer`);
    if (!$container.length || !_matrixDetailsDataCache) return;

    const d = _matrixDetailsDataCache[String(id)];
    if (!d) return;

    $container.html(`
        <div class="obula-details-grid" style="display:flex;max-width:900px;gap:20px;margin:0 auto;">
        <div class="obula-details-left" style="flex:0 0 30%;">
            <dl class="obula-kv" style="margin:0;">
            <dt>Student Number</dt><dd>${(d.student_number || id)}</dd>
            <dt>Name</dt><dd>${(d.name || 'Unknown')}</dd>
            <dt>Programme</dt><dd>${(d.programme || 'Unknown programme')}</dd>
            </dl>
        </div>

        <div class="obula-details-right" style="flex:1;">
            <div style="display:flex; align-items:center; justify-content:space-between;">
            <label for="attModFilter-${id}" style="display:flex; align-items:center; gap:8px;">
                <span style="font-weight:600;">Filter:</span>
                <select id="attModFilter-${id}" style="min-width:200px; padding:6px 8px;">
                <option value="module" selected>By Module</option>
                <option value="day">By Day</option>
                </select>
            </label>
            </div>

            <div id="studentAttendanceByModuleContainer-${id}"
                style="width:100%; height:320px; display:flex; justify-content:left; align-items:stretch;">
            <canvas id="studentChartAttendanceByModule-${id}" style="display:block; width:100%; height:100%;"></canvas>
            </div>
        </div>
        </div>
    `);
    chartHandler(null, 'attbymod', id, null)
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
