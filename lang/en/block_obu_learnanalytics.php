<?php

// This file is for use with Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
// This file is covered by the same agreement

/**
 * Displays Learning Analytics data for Oxford Brookes University Students and Tutors
 *
 * @package     block_obu_learnanalytics
 * @copyright   2020 Ken Burch <ken.burch@brookes.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
$string['pluginname'] = 'Brookes Learning Analytics';           // Used for administering capabilities, DON't delete
$string['obu_learnanalytics'] = 'Brookes Learning Analytics';   // This is the one used (not sure what for now)  //TODO
$string['obu_learnanalytics:view'] = 'Brookes View Learning Analytics';

// Capability strings
$string['obu_learnanalytics:student_dashboard'] = 'Student Dashboard';
$string['obu_learnanalytics:tutor_dashboard'] = 'Tutor Dashboard';
$string['obu_learnanalytics:ssc_dashboard'] = 'Student Support Coordinator Dashboard';

// Strings for settings page
$string['ws_settings_header'] = 'Web Service Settings';
$string['ws_settings_header_info'] = 'Settings to access Learning Analytics Web Services';
$string['ws_root_url'] = 'Root URL';
$string['ws_root_url_info'] = 'The root URL for Learning Analytics Web Services';
$string['ws_bearer_token'] = 'Bearer Token';
$string['ws_bearer_token_info'] = 'The Bearer Token for Learning Analytics Web Service Authorisation';
$string['ws_accept_selfcert'] = 'Accept Self Certification';
$string['ws_accept_selfcert_info'] = 'Only for test instances, it will allow the web service to use a self issued certificate';
$string['ws_trace_calls'] = 'Trace Web Service calls';
$string['ws_trace_calls_info'] = 'Only for test instances, file is obu_learnanalytics_wstraces.txt in Moodle temp folder';

// Error strings
$string['edw_connect_error'] = 'Error connecting to Enterprise Data Warehouse, please try later';
$string['capability_error'] = 'Incorrect Role(s) to Access Learning Analytics';

// General Terminology
$string['chart-duration'] = 'Duration';
$string['marks-published'] = 'Final';
$string['marks-notpublished'] = 'Provisional';
$string['student-help-title'] = 'Student Help';

$string['tutor-show'] = 'Show';
$string['tutor-help-title'] = 'Tutor Help';
$string['tutor-dash-title-sml'] = 'Learning Analytics';
$string['tutor-dash-title-med'] = 'Welcome to Learning Analytics';

$string['ssc-help-title'] = 'Student Support Coordinator Help';
$string['ssc-students-view'] = 'Students View';
$string['ssc-tutors-view'] = 'Tutors View';
$string['ssc-dash-title-sml'] = 'Learning Analytics';
$string['ssc-dash-title-med'] = 'Learning Analytics (SSC View)';

// Events
$string['tutor_dashboard_opened'] = 'Tutor Dashboard Opened';
$string['tutor_programme_changed'] = 'Tutor Programme Changed';
$string['dashboard_closed'] = 'Dashboard Closed';
