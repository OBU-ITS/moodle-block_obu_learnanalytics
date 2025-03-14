//****
// Opted for each graph to have its own function as it allows for easier flexibility in the future
// Instead of having 1 master function
//****




// **************************************************************
// ************** VLE Engagement (MOODLE)
// **************************************************************
function studentVleMinutesLineChart(data, studentName) {
    var engagementCanvas = document.getElementById('studentChartVLEEngagement');
    showDataCurrency();
    engagementCanvas.style.display = 'block';
    unified_data = unifiedDataFormat(data);
    renderedEngagementChart = new Chart(engagementCanvas.getContext('2d'), {
        type: 'line',
        data: {
            labels: unified_data['first_day_week'],
            datasets: [
                {   // Student
                    label: studentName,
                    data: unified_data['vle_duration_minutes'],
                    backgroundColor: '#003896',
                    borderColor: '#003896',
                    borderWidth: 1,

                    // Smoothing
                    tension: 0.4,                // 0 -> straight lines, 1 -> big curves
                    cubicInterpolationMode: 'monotone'
                },
                {   // Cohort avg
                    label: 'Median Average',
                    data: unified_data['avg_vle_duration_minutes'],
                    backgroundColor: '#c70540',
                    borderColor: '#c70540',
                    borderWidth: 1,

                    // Smoothing
                    tension: 0.4,                // 0 -> straight lines, 1 -> big curves
                    cubicInterpolationMode: 'monotone'
                }
            ]
        },
        options: {
            plugins: {
                title: {
                  display: true,
                  text: 'Moodle Engagement - Duration (Minutes)'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            },
        }
    });
};

function studentVleVisitsLineChart(data, studentName) {
    var engagementCanvas = document.getElementById('studentChartVLEEngagement');
    showDataCurrency();
    engagementCanvas.style.display = 'block';
    unified_data = unifiedDataFormat(data);
    renderedEngagementChart = new Chart(engagementCanvas.getContext('2d'), {
        type: 'line',
        data: {
            labels: unified_data['first_day_week'],
            datasets: [
                {   // Student
                    label: studentName,
                    data: unified_data['vle_sessions'],
                    backgroundColor: '#003896',
                    borderColor: '#003896',
                    borderWidth: 1,

                    // Smoothing
                    tension: 0.4,                // 0 -> straight lines, 1 -> big curves
                    cubicInterpolationMode: 'monotone'
                },
                {   // Cohort avg
                    label: 'Median Average',
                    data: unified_data['avg_vle_sessions'],
                    backgroundColor: '#c70540',
                    borderColor: '#c70540',
                    borderWidth: 1,

                    // Smoothing
                    tension: 0.4,                // 0 -> straight lines, 1 -> big curves
                    cubicInterpolationMode: 'monotone'
                }
            ]
        },
        options: {
            plugins: {
                title: {
                  display: true,
                  text: 'Moodle Engagement - Visits (Sessions)'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            },
        }
    });
};

function studentVlePageViewsLineChart(data, studentName) {
    var engagementCanvas = document.getElementById('studentChartVLEEngagement');
    showDataCurrency();
    engagementCanvas.style.display = 'block';
    unified_data = unifiedDataFormat(data);
    renderedEngagementChart = new Chart(engagementCanvas.getContext('2d'), {
        type: 'line',
        data: {
            labels: unified_data['first_day_week'],
            datasets: [
                {   // Student
                    label: studentName,
                    data: unified_data['vle_page_hits'],
                    backgroundColor: '#003896',
                    borderColor: '#003896',
                    borderWidth: 1,

                    // Smoothing
                    tension: 0.4,                // 0 -> straight lines, 1 -> big curves
                    cubicInterpolationMode: 'monotone'
                },
                {   // Cohort avg
                    label: 'Median Average',
                    data: unified_data['avg_vle_page_hits'],
                    backgroundColor: '#c70540',
                    borderColor: '#c70540',
                    borderWidth: 1,

                    // Smoothing
                    tension: 0.4,                // 0 -> straight lines, 1 -> big curves
                    cubicInterpolationMode: 'monotone'
                }
            ]
        },
        options: {
            plugins: {
                title: {
                  display: true,
                  text: 'Moodle Engagement - Page Views'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            },
        }
    });
};


// **************************************************************
// ************** Attendance
// **************************************************************
function studentAttendanceLineChart(data, studentName) {
    // (1) Get your canvas
    var attendanceCanvas = document.getElementById('studentChartAttendance');

    // (2) Show the canvas, parse the data
    showDataCurrency();
    attendanceCanvas.style.display = 'block';
    unified_data = unifiedDataFormat(data);
    // (3) Build the Chart.js config
    renderedAttendanceChart = new Chart(attendanceCanvas.getContext('2d'), {
        type: 'line',
        data: {
            labels: unified_data['first_day_week'],
            datasets: [
                {
                    label: studentName,
                    data: unified_data['attendance_percentage'],
                    backgroundColor: '#003896',
                    borderColor: '#003896',
                    borderWidth: 1,
        
                    // Smoothing
                    tension: 0.4,                // 0 -> straight lines, 1 -> big curves
                    cubicInterpolationMode: 'monotone'
                },
                {
                    label: 'Median Average',
                    data: unified_data['avg_attendance_percentage'],
                    backgroundColor: '#c70540',
                    borderColor: '#c70540',
                    borderWidth: 1,
        
                    // Smoothing
                    tension: 0.4,               
                    cubicInterpolationMode: 'monotone'
                }
            ]
        },
        options: {
            plugins: {
                title: {
                  display: true,
                  text: 'Attendance Percentage of Lectures/Events Attended'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function studentAttendanceBarChart(data, studentName) {
    // (1) Get your canvas
    var attendanceCanvas = document.getElementById('studentChartAttendance');

    // (2) Show the canvas, parse the data
    showDataCurrency();
    attendanceCanvas.style.display = 'block';
    unified_data = unifiedDataFormat(data);
    // (3) Build the Chart.js config
    renderedAttendanceChart = new Chart(attendanceCanvas.getContext('2d'), {
        type: 'bar',
        data: {
            labels: unified_data['first_day_week'],
            datasets: [
                {
                    label: studentName,
                    data: unified_data['attendance_percentage'],
                    backgroundColor: '#003896',
                    borderColor: '#003896',
                    borderWidth: 1,
        
                    // Smoothing
                    tension: 0.4,                // 0 -> straight lines, 1 -> big curves
                    cubicInterpolationMode: 'monotone'
                },
                {
                    label: 'Median Average',
                    data: unified_data['avg_attendance_percentage'],
                    backgroundColor: '#c70540',
                    borderColor: '#c70540',
                    borderWidth: 1,
        
                    // Smoothing
                    tension: 0.4,               
                    cubicInterpolationMode: 'monotone'
                }
            ]
        },
        options: {
            plugins: {
                title: {
                  display: true,
                  text: 'Attendance Percentage of Lectures/Events Attended'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Version that is an area chart
function studentAttendanceAreaChart(data, studentName) {
    // (1) Get your canvas
    var attendanceCanvas = document.getElementById('studentChartAttendance');

    // (2) Show the canvas, parse the data
    showDataCurrency();
    attendanceCanvas.style.display = 'block';
    unified_data = unifiedDataFormat(data);
    // (3) Build the Chart.js config
    renderedAttendanceChart = new Chart(attendanceCanvas.getContext('2d'), {
        type: 'line',
        data: {
            labels: unified_data['first_day_week'],
            datasets: [{
                label: studentName,
                data: unified_data['attendance_percentage'],
    
                // Enable area fill
                fill: true,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1,
    
                // Smoothing
                tension: 0.4,                // 0 -> straight lines, 1 -> big curves
                cubicInterpolationMode: 'monotone'
            }]
        },
        options: {
            plugins: {
                title: {
                  display: true,
                  text: 'Attendance Percentage of Lectures/Events Attended'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}


// **************************************************************
// ************** eLibrary Engagement (EzProxy)
// **************************************************************
function eLibDurationLineChart(data, studentName) {
    // (1) Get your canvas
    var eLibCanvas = document.getElementById('studentChartELibEngagement');

    // (2) Show the canvas, parse the data
    showDataCurrency();
    eLibCanvas.style.display = 'block';
    unified_data = unifiedDataFormat(data);
    // (3) Build the Chart.js config
    renderedELibChart = new Chart(eLibCanvas.getContext('2d'), {
        type: 'line',
        data: {
            labels: unified_data['first_day_week'],
            datasets: [
                {
                    label: studentName,
                    data: unified_data['ez_duration_total'],
                    backgroundColor: '#003896',
                    borderColor: '#003896',
                    borderWidth: 1,
        
                    // Smoothing
                    tension: 0.4,                // 0 -> straight lines, 1 -> big curves
                    cubicInterpolationMode: 'monotone'
                },
                {
                    label: 'Median Average',
                    data: unified_data['avg_ez_duration_total'],
                    backgroundColor: '#c70540',
                    borderColor: '#c70540',
                    borderWidth: 1,
        
                    // Smoothing
                    tension: 0.4,               
                    cubicInterpolationMode: 'monotone'
                }
            ]
        },
        options: {
            plugins: {
                title: {
                  display: true,
                  text: 'Electronic Library Engagement - Duration (Minutes)'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function eLibPageVisitsLineChart(data, studentName) {
    // (1) Get your canvas
    var eLibCanvas = document.getElementById('studentChartELibEngagement');

    // (2) Show the canvas, parse the data
    showDataCurrency();
    eLibCanvas.style.display = 'block';
    unified_data = unifiedDataFormat(data);
    // (3) Build the Chart.js config
    renderedELibChart = new Chart(eLibCanvas.getContext('2d'), {
        type: 'line',
        data: {
            labels: unified_data['first_day_week'],
            datasets: [
                {
                    label: studentName,
                    data: unified_data['ez_sessions'],
                    backgroundColor: '#003896',
                    borderColor: '#003896',
                    borderWidth: 1,
        
                    // Smoothing
                    tension: 0.4,                // 0 -> straight lines, 1 -> big curves
                    cubicInterpolationMode: 'monotone'
                },
                {
                    label: 'Median Average',
                    data: unified_data['avg_ez_sessions'],
                    backgroundColor: '#c70540',
                    borderColor: '#c70540',
                    borderWidth: 1,
        
                    // Smoothing
                    tension: 0.4,               
                    cubicInterpolationMode: 'monotone'
                }
            ]
        },
        options: {
            plugins: {
                title: {
                    display: true,
                    text: 'Electronic Library Engagement - Page Views (Sessions)'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function eLibDownloadSizeLineChart(data, studentName) {
    // (1) Get your canvas
    var eLibCanvas = document.getElementById('studentChartELibEngagement');

    // (2) Show the canvas, parse the data
    showDataCurrency();
    eLibCanvas.style.display = 'block';
    unified_data = unifiedDataFormat(data);
    // (3) Build the Chart.js config
    renderedELibChart = new Chart(eLibCanvas.getContext('2d'), {
        type: 'line',
        data: {
            labels: unified_data['first_day_week'],
            datasets: [
                {
                    label: studentName,
                    data: unified_data['ez_size'],
                    backgroundColor: '#003896',
                    borderColor: '#003896',
                    borderWidth: 1,
        
                    // Smoothing
                    tension: 0.4,                // 0 -> straight lines, 1 -> big curves
                    cubicInterpolationMode: 'monotone'
                },
                {
                    label: 'Median Average',
                    data: unified_data['avg_ez_size'],
                    backgroundColor: '#c70540',
                    borderColor: '#c70540',
                    borderWidth: 1,
        
                    // Smoothing
                    tension: 0.4,               
                    cubicInterpolationMode: 'monotone'
                }
            ]
        },
        options: {
            plugins: {
                title: {
                    display: true,
                    text: 'Electronic Library Engagement - Download Size (MB)'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}



/**
 * Convert an object-of-objects into an object
 * whose keys are property names and values are arrays.
 *
 * @param {Object} data - Your raw data object (e.g., the JSON you posted).
 * @returns {Object} An object mapping each property to an array of its values.
 */
function unifiedDataFormat(data) {
    const unified_data = {};
    // Iterate over each outer key (e.g. "2024_10", "2024_11", ...)
    for (const outerKey in data) {
        if (!Object.prototype.hasOwnProperty.call(data, outerKey)) continue;
        
        const row = data[outerKey];   // e.g. {first_day_week: "30-Sep-2024", year: "2024", ...}
        // For each property in the "row" object
        for (const prop in row) {
            if (!Object.prototype.hasOwnProperty.call(row, prop)) continue;
            // Create an array for this property if it doesn't exist yet
            if (!unified_data[prop]) {
                unified_data[prop] = [];
            }
            let val = row[prop];
            // Attempt to parse numeric values so we get numbers instead of strings.
            // For example, "10" -> 10, "157.5" -> 157.5, etc.
            if (!isNaN(val) && val !== "" && val !== null) {
                val = parseFloat(val);
            }
            // Add this value to the array for that property
            unified_data[prop].push(val);
        }
    }
    return unified_data;
}



function chartHandler(res, chart_type, studentName, chart_style) {
    const studentChartVLEEngagement = Chart.getChart('studentChartVLEEngagement');
    const studentChartAttendance = Chart.getChart('studentChartAttendance');
    const studentChartELibEngagement = Chart.getChart('studentChartELibEngagement');

    // If there is a specific chart_style we want then adjust the chart_type
    if(chart_style) {
        chart_type = chart_type + '_' + chart_style
    }

    switch (chart_type) {
        // **** VLE (MOODLE) ****/

        case 'vleduration':
            if (studentChartVLEEngagement) {
                studentChartVLEEngagement.destroy();
            }
            studentVleMinutesLineChart(res, studentName);
            break;
        case 'vlesessions':
            if (studentChartVLEEngagement) {
                studentChartVLEEngagement.destroy();
            }
            studentVleVisitsLineChart(res, studentName);
            break;
        case 'vleviews':
            if (studentChartVLEEngagement) {
                studentChartVLEEngagement.destroy();
            }
            studentVlePageViewsLineChart(res, studentName);
            break;
            
        // **** ATTENDANCE ****/
        case 'attperc_linechart':
            // If there’s already a chart on this canvas, destroy it
            if (studentChartAttendance) {
                studentChartAttendance.destroy();
            }
            studentAttendanceLineChart(res, studentName);
            break;
        case 'attperc_barchart':
            // If there’s already a chart on this canvas, destroy it
            if (studentChartAttendance) {
                studentChartAttendance.destroy();
            }
            studentAttendanceBarChart(res, studentName);
            break;
            
        // **** eLib (EzProxy) ****/
        case 'ezduration':
            if (studentChartELibEngagement) {
                studentChartELibEngagement.destroy();
            }
            eLibDurationLineChart(res, studentName);
            break;
        case 'ezsessions':
            if (studentChartELibEngagement) {
                studentChartELibEngagement.destroy();
            }
            eLibPageVisitsLineChart(res, studentName);
            break;
        case 'ezsize':
            if (studentChartELibEngagement) {
                studentChartELibEngagement.destroy();
            }
            eLibDownloadSizeLineChart(res, studentName);
            break;
        default:
            console.warn(`Unknown chart_type: ${chart_type}`);
            break;
            
    }
}
