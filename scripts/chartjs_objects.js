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
    unified_data = pgmgraphdataUnifiedDataFormat(data);
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
            interaction: {
                mode: 'index',      // <-- Show all data for that index
                intersect: false    // <-- Even if the cursor isn't exactly on a bar
            },
            plugins: {
                title: {
                  display: true,
                  text: 'Moodle Engagement - Duration (Minutes)'
                },
                tooltip: {
                    callbacks: {
                      label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                          label += ': ';
                        }
                        let value = context.parsed.y;
                        if (!isNaN(value)) {
                          value = value + ' minutes';
                        }
                        return label + value;
                      }
                    }
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
    unified_data = pgmgraphdataUnifiedDataFormat(data);
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
            interaction: {
                mode: 'index',      // <-- Show all data for that index
                intersect: false    // <-- Even if the cursor isn't exactly on a bar
            },
            plugins: {
                title: {
                  display: true,
                  text: 'Moodle Engagement - Visits (Sessions)'
                },
                tooltip: {
                    callbacks: {
                      label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                          label += ': ';
                        }
                        let value = context.parsed.y;
                        if (!isNaN(value)) {
                          value = value + ' visits';
                        }
                        return label + value;
                      }
                    }
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
    unified_data = pgmgraphdataUnifiedDataFormat(data);
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
            interaction: {
                mode: 'index',      // <-- Show all data for that index
                intersect: false    // <-- Even if the cursor isn't exactly on a bar
            },
            plugins: {
                title: {
                  display: true,
                  text: 'Moodle Engagement - Page Views'
                },
                tooltip: {
                    callbacks: {
                      label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                          label += ': ';
                        }
                        let value = context.parsed.y;
                        if (!isNaN(value)) {
                          value = value + ' page views';
                        }
                        return label + value;
                      }
                    }
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
// ************** BY MODULE ENGAGEMENT
// **************************************************************

function studentVLEDurationByModule(data) {
    // (1) Get your canvas
    var engageByModuleCanvas = document.getElementById('studentChartEngagementByModule');

    // In your AJAX success:
    if (!data || data === "null") {
        console.error("No data returned from server or invalid data");
        return;
    }
    // We'll also need the original data object so we can get module shortnames:
    data = JSON.parse(data);  // if still a JSON string at this point

    // (2) Show the canvas
    showDataCurrency();
    engageByModuleCanvas.style.display = 'block';
    // (3) Convert your raw data into a "unified_data" structure for modules
    const unified_data = modgraphdataUnifiedDataFormat(data);
    // => e.g. { first_day_week: [...], "75786_duration_total": [...], ... }

    const modulesData = data.Modules;
    
    // Prepare an array of datasets
    const allDatasets = [];
    
    for (const key in unified_data) {
      if (key.endsWith('_duration_total')) {
        // The module ID is whatever precedes '_duration_total'
        const moduleID = key.split('_')[0];
    
        // Look up the shortname from data.Modules
        const shortname = modulesData[moduleID]?.course_shortname 
                          || `Module ${moduleID}`;
    
        // Build a dataset for this module
        allDatasets.push({
          label: shortname,
          data: unified_data[key],
          borderWidth: 1
        });
      }
    }

    // (5) Create the stacked bar chart
    renderedEngagementByModuleCanvas = new Chart(engageByModuleCanvas.getContext('2d'), {
        type: 'bar',
        data: {
            // The x-axis labels come from first_day_week
            labels: unified_data['first_day_week'],
            datasets: allDatasets
        },
        options: {
            interaction: {
                mode: 'index',      // <-- Show all data for that index
                intersect: false    // <-- Even if the cursor isn't exactly on a bar
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Student Duration by Module (Minutes)'
                },
                tooltip: {
                    callbacks: {
                      label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                          label += ': ';
                        }
                        let value = context.parsed.y;
                        if (!isNaN(value)) {
                          value = value + ' minutes';
                        }
                        return label + value;
                      }
                    }
                }
            },
            scales: {
                x: {
                    stacked: true
                },
                y: {
                    beginAtZero: true,
                    stacked: true
                }
            }
        }
    });
}




// **************************************************************
// ************** Attendance
// **************************************************************
function studentAttendanceLineChart(data, studentName) {
    // (1) Get your canvas
    var attendanceCanvas = document.getElementById('studentChartAttendance');

    // (2) Show the canvas, parse the data
    showDataCurrency();
    attendanceCanvas.style.display = 'block';
    unified_data = pgmgraphdataUnifiedDataFormat(data);

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
            interaction: {
                mode: 'index',      // <-- Show all data for that index
                intersect: false    // <-- Even if the cursor isn't exactly on a bar
            },
            plugins: {
                title: {
                  display: true,
                  text: 'Attendance Percentage of Lectures/Events Attended'
                },
                tooltip: {
                    callbacks: {
                      label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                          label += ': ';
                        }
                        let value = context.parsed.y;
                        if (!isNaN(value)) {
                          value = value + '%';
                        }
                        return label + value;
                      }
                    }
                }
            },

            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                      // Append '%' to each tick label
                      callback: function (value) {
                        return value + '%';
                      }
                    }
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
    unified_data = pgmgraphdataUnifiedDataFormat(data);
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
            interaction: {
                mode: 'index',      // <-- Show all data for that index
                intersect: false    // <-- Even if the cursor isn't exactly on a bar
            },
            plugins: {
                title: {
                  display: true,
                  text: 'Attendance Percentage of Lectures/Events Attended'
                },
                tooltip: {
                    callbacks: {
                      label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                          label += ': ';
                        }
                        let value = context.parsed.y;
                        if (!isNaN(value)) {
                          value = value + '%';
                        }
                        return label + value;
                      }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                      // Append '%' to each tick label
                      callback: function (value) {
                        return value + '%';
                      }
                    }
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
    unified_data = pgmgraphdataUnifiedDataFormat(data);
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
    unified_data = pgmgraphdataUnifiedDataFormat(data);
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
            interaction: {
                mode: 'index',      // <-- Show all data for that index
                intersect: false    // <-- Even if the cursor isn't exactly on a bar
            },
            plugins: {
                title: {
                  display: true,
                  text: 'Electronic Library Engagement - Duration (Minutes)'
                },
                tooltip: {
                    callbacks: {
                      label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                          label += ': ';
                        }
                        let value = context.parsed.y;
                        if (!isNaN(value)) {
                          value = value + ' minutes';
                        }
                        return label + value;
                      }
                    }
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
    unified_data = pgmgraphdataUnifiedDataFormat(data);
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
                interaction: {
                    mode: 'index',      // <-- Show all data for that index
                    intersect: false    // <-- Even if the cursor isn't exactly on a bar
                },
                title: {
                    display: true,
                    text: 'Electronic Library Engagement - Page Views (Sessions)'
                },
                tooltip: {
                    callbacks: {
                      label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                          label += ': ';
                        }
                        let value = context.parsed.y;
                        if (!isNaN(value)) {
                          value = value + ' visits';
                        }
                        return label + value;
                      }
                    }
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
    unified_data = pgmgraphdataUnifiedDataFormat(data);
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
            interaction: {
                mode: 'index',      // <-- Show all data for that index
                intersect: false    // <-- Even if the cursor isn't exactly on a bar
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Electronic Library Engagement - Download Size (MB)'
                },
                tooltip: {
                    callbacks: {
                      label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                          label += ': ';
                        }
                        let value = context.parsed.y;
                        if (!isNaN(value)) {
                          value = value + ' mb downloaded';
                        }
                        return label + value;
                      }
                    }
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
 * Flatten "Modules" + "GraphData" into an object-of-arrays for easy Chart.js usage.
 * 
 * @param {Object} data - The entire object containing { Modules, GraphData }.
 * @returns {Object} 
 *   An object like:
 *   {
 *     first_day_week: [...],
 *     year: [...],
 *     week_number: [...],
 *     "70709_duration_total": [...],
 *     "70009_duration_total": [...],
 *     ...
 *   }
 */
function modgraphdataUnifiedDataFormat(data) {
    // Separate the two main parts
    const modulesData = data.Modules;   // e.g. { "70709": {course_fullname, ...}, ... }
    const graphData = data.GraphData;   // e.g. { "2023_24": { first_day_week, modules: {...} }, ... }
    // Our flattened result
    const unified_data = {
        first_day_week: [],
        year: [],
        week_number: []
    };
    // Get all module IDs from the "Modules" object
    // We'll create one array per module for "duration_total" values
    const moduleIDs = Object.keys(modulesData);

    // For each module ID, define a property in `unified_data`
    // e.g. "70709_duration_total" => [values...]
    moduleIDs.forEach(mid => {
        unified_data[`${mid}_duration_total`] = [];
    });

    // Sort the keys of GraphData (e.g. "2023_24", "2023_25", etc.)
    // so we push arrays in consistent date/week order
    const sortedKeys = Object.keys(graphData).sort();

    // Iterate each week's data
    sortedKeys.forEach(outerKey => {
        const row = graphData[outerKey];
        
        // Push the standard properties
        unified_data.first_day_week.push(row.first_day_week || null);
        unified_data.year.push(row.year || null);
        unified_data.week_number.push(row.week_number || null);

        // For each module in the global modules list,
        // see if there's a matching "duration_total" in this week's row.
        // If not present, use 0 (or null, if you prefer).
        moduleIDs.forEach(mid => {
            let val = 0;
            const moduleEntry = row.modules ? row.modules[mid] : null;
            if (moduleEntry && moduleEntry.duration_total) {
                // Parse as float so Chart.js sees a numeric value
                val = parseFloat(moduleEntry.duration_total) || 0;
            }
            unified_data[`${mid}_duration_total`].push(val);
        });
    });

    return unified_data;
}




/**
 * Convert an object-of-objects into an object
 * whose keys are property names and values are arrays.
 *
 * @param {Object} data - Your raw data object (pgm graph data only)
 * @returns {Object} An object mapping each property to an array of its values.
 */
function pgmgraphdataUnifiedDataFormat(data) {
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
    const studentChartEngagementByModule = Chart.getChart('studentChartEngagementByModule');
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
            
        // **** VLE BY MODULE (MOODLE) ****/
        case 'moduleEngagement':
            if (studentChartEngagementByModule) {
                studentChartEngagementByModule.destroy();
            }
            studentVLEDurationByModule(res, studentName);
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
