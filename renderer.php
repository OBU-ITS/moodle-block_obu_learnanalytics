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

/**
 * Learning analytics renderer
 * This is the main driving class for the dashboards, it has functions to emit the HTML for both
 * dashboards which are called from block_obu_learnanalytics.php
 */
class block_obu_learnanalytics_renderer extends plugin_renderer_base
{
    /**
     * Returns formatted error message instead of the expected dashboard, intended for serious errors such as database unavailable
     *
     * @param  String $error            The key to the language string to display
     * @param  Object $exception        Optional exception object
     * @return String HTML to render
     */
    public function error_page($error, $exception = null)
    {
        $out = '';
        $out .= html_writer::start_tag("div");
        $out .= html_writer::start_tag("b");
        // TODO finish page and uses styles instead of big
        $errorMsg = get_string($error, 'block_obu_learnanalytics');
        $out .= html_writer::tag("big", $errorMsg, array('style' => 'color:red'));
        $out .= html_writer::end_tag("b");
        $out .= html_writer::end_tag("div");

        return $out;
    }

    public function modal_any_popup()
    {
        $out = '';

        $out .= html_writer::start_tag("div", array("id" => "obula_modal_popup", "class" => "modal fade", "role" => "dialog"));
        $out .= html_writer::start_tag("div", array("class" => "modal-dialog modal-lg"));
        $out .= html_writer::start_tag("div", array("class" => "modal-content"));
        $out .= html_writer::start_tag("div", array("class" => "obula-modal-header modal-header"));
        $out .= html_writer::tag("button", "&times;", array("type" => "button", "class" => "close",  "data-dismiss" => "modal"));
        $out .= html_writer::tag("h5", "Header", array("id" => "obula_modal_popup_title", "class" => "obula-modal-title modal-title"));
        $out .= html_writer::end_tag("div");
        $out .= html_writer::start_tag("div", array("id" => "obula_modal_body", "class" => "modal-body"));
        $out .= html_writer::tag("p", "Working.....");
        $out .= html_writer::end_tag("div");
        $out .= html_writer::start_tag("div", array("class" => "modal-footer"));
        $out .= html_writer::tag("span", "", array("id" => "obula_modal_footer_text"));
        // Now possible buttons, pages will hide/show as needed and only allow one submit button visible/enabled
        // close button for help and similar pages
        $buttonAtts = array("type" => "submit", "id" => "obula_modal_close", "class" => "btn btn-default",  "data-dismiss" => "modal", "autofocus");
        $out .= html_writer::tag("button", "Close", $buttonAtts);
        // OK button for search and similar
        $buttonAtts = array("type" => "submit", "id" => "obula_modal_ok", "class" => "btn btn-default",  "data-dismiss" => "modal", "default");
        $buttonAtts["style"] = "display:none";
        $out .= html_writer::tag("button", "OK", $buttonAtts);
        // Cancel to go with OK
        $buttonAtts = array("type" => "button", "id" => "obula_modal_cancel", "class" => "btn",  "data-dismiss" => "modal");
        $buttonAtts["style"] = "display:none";
        $out .= html_writer::tag("button", "Cancel", $buttonAtts);
        // Now close out the tags
        $out .= html_writer::end_tag("div");
        $out .= html_writer::end_tag("div");
        $out .= html_writer::end_tag("div");
        $out .= html_writer::end_tag("div");

        return $out;
    }

    /**
     * Initial summary Dashboard for a Tutor
     *
     * @return html
     */
    public function tutor_dashboard_summary()
    {
        $scriptUrl = new moodle_url('/blocks/obu_learnanalytics/scripts/common.js?version=0.10.8');
        $outScripts = html_writer::script(null, $scriptUrl);
        $scriptUrl = new moodle_url('/blocks/obu_learnanalytics/scripts/tutor_dashboard.js?version=0.10.8');
        $outScripts .= html_writer::script(null, $scriptUrl);
        $scriptUrl = new moodle_url('/blocks/obu_learnanalytics/scripts/check_connection.js?version=0.10.8');
        $outScripts .= html_writer::script(null, $scriptUrl);
        // End of scripts
        $out = $outScripts;
        $out .= self::modal_any_popup();           // For Help explanation

        //Want to use Google Material fonts
        //<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        // see this for icons https://material.io/resources/icons/?icon=flight_takeoff&style=baseline 
        $out .= html_writer::empty_tag("link", array("rel" => "stylesheet", "href" => "https://fonts.googleapis.com/icon?family=Material+Icons"));
        // for fun try         $out .= html_writer::tag("i", "face", array("class" => "material-icons"));

        global $USER;

        $userPrefs = get_user_preferences();
        if (array_key_exists("obula_last_tutor_grid_date", $userPrefs)) {
            $today = new DateTime();
            $today->setTime(0, 0, 0);
            $lastAccess = unserialize($userPrefs["obula_last_tutor_grid_date"]);
            $lastAccess->setTime(0, 0, 0);
            if ($lastAccess == $today) {
                $message = "Last accessed Today";
            } else {
                $message = "Last accessed " . $lastAccess->format('d-M-Y');
            }
        } else {
            $message = "You have not checked this out";
        }

        // Hidden fields to hold state of the nav panel
        $out .= html_writer::tag('input', '', array("type" => 'hidden', "id" => 'obula_navbar_ariahidden', "value" => '?'));
        $out .= html_writer::tag('input', '', array("type" => 'hidden', "id" => 'obula_page_taken', "value" => '?'));
        $out .= html_writer::tag('input', '', array("type" => 'hidden', "id" => 'obula_copy2clip', "value" => '?'));

        // Links for Help and Feedback
        $links = html_writer::tag("a", "Help", array("href" => "javascript:showHelp('tutor')", "class" => "link-right link-help"));
        $links .= html_writer::tag("a", "Feedback", array("href" => "javascript:gotoFeedback('tutor')", "class" => "link-right"));

        // Button for either panel
        $temp = get_string("tutor-show", 'block_obu_learnanalytics');
        $atts = array("type" => "button", "disabled" => "true", "value" => $temp, "class" => "ssc-button", "onclick" => "showTutorFull()", "id" => "obula-show-tf-sml");
        $tag_name = "input";
        $button_html = html_writer::empty_tag($tag_name, $atts);

        // Actually start with nothing visible and javascript can enable the ones for the correct size
        // Small right panel
        $atts = array("id" => "obula_ts_heading_sml", "style" => "display: none");
        $out .= html_writer::start_tag("panel", $atts);
        $out .= html_writer::start_tag("div");
        $temp2 = get_string("tutor-dash-title-sml", 'block_obu_learnanalytics');
        $out .= html_writer::tag("h5", $temp2 . "   " . $links);
        // Output both OK and failed tags for js to update
        $out .= html_writer::tag("span", $message);
        $out .= $button_html;
        // Now tags for possible error message
        // TODO mouseover or something for admins to get full error
        $out .= html_writer::end_tag("div");
        // error tags are in a div
        $out .= self::connection_error_placeHolder(true, $USER->username);
        
        // $out .= html_writer::start_tag("div", array("id" => "obula_ts_input_sml", "style" => "display: inline"));
        // Button is next to message for small dashboard
        // $out .= html_writer::end_tag("div");
        $out .= html_writer::end_tag("panel");

        // Full or 2/3 width panel
        $atts = array("id" => "obula_ts_heading_med", "style" => "display: none");
        $out .= html_writer::start_tag("panel", $atts);
        $out .= html_writer::tag("h5", "Learning Analytics - {$message}" . "   " . $links);
        $out .= html_writer::start_tag("div", array("id" => "obula_ts_input_med", "style" => "display: inline"));
        // TODO mouseover or something for admins to get full error
        $out .= self::connection_error_placeHolder(false, $USER->username);
        $out .= str_replace("obula-show-tf-sml", "obula-show-tf-med", $button_html);
        $out .= html_writer::end_tag("div");
        $out .= html_writer::end_tag("panel");

        // Now other placeholders
        $consolehtml = "";
        $out .= self::any_dashboard_host_placeholders($consolehtml);
        return $out;
    }

    /**
     * Dashboard for a student coordinator
     *
     * @return html
     */
    public function ssc_dashboard()
    {
        $scriptUrl = new moodle_url('/blocks/obu_learnanalytics/scripts/common.js?version=0.10.8');
        $outScripts = html_writer::script(null, $scriptUrl);
        $scriptUrl = new moodle_url('/blocks/obu_learnanalytics/scripts/ssc_dashboard.js?version=0.10.8');
        $outScripts .= html_writer::script(null, $scriptUrl);
        $scriptUrl = new moodle_url('/blocks/obu_learnanalytics/scripts/check_connection.js?version=0.10.8');
        $outScripts .= html_writer::script(null, $scriptUrl);
        // End of scripts
        $out = $outScripts;
        $out .= self::modal_any_popup();           // For Help explanation

        //Want to use Google Material fonts
        //<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        // see this for icons https://material.io/resources/icons/?icon=flight_takeoff&style=baseline
        $out .= html_writer::empty_tag("link", array("rel" => "stylesheet", "href" => "https://fonts.googleapis.com/icon?family=Material+Icons"));
        // for fun try         $out .= html_writer::tag("i", "face", array("class" => "material-icons"));

        global $USER;

        // Hidden fields to hold state of the nav panel
        $out .= html_writer::tag('input', '', array("type" => 'hidden', "id" => 'obula_navbar_ariahidden', "value" => '?'));
        $out .= html_writer::tag('input', '', array("type" => 'hidden', "id" => 'obula_page_taken', "value" => '?'));
        $out .= html_writer::tag('input', '', array("type" => 'hidden', "id" => 'obula_copy2clip', "value" => '?'));
        $out .= html_writer::tag('input', '', array("type" => 'hidden', "id" => 'obula_ssc_student', "value" => '?'));

        // Links for either format
        $links = html_writer::tag("a", "Help", array("href" => "javascript:showHelp('ssc')", "class" => "link-right link-help"));       // TODO CSS right
        $links .= html_writer::tag("a", "Feedback", array("href" => "javascript:gotoFeedback('ssc')", "class" => "link-right"));

        // Actually start with nothing visible and javascript can enable the ones for the correct size
        // First the Small panel
        $atts = array("id" => "obula_ssc_heading_sml", "style" => "display: none");
        $out .= html_writer::start_tag("panel", $atts);
        $out .= html_writer::start_tag("div");
        $temp2 = get_string("ssc-dash-title-sml", 'block_obu_learnanalytics');
        $out .= html_writer::tag("h5", $temp2 . "   " . $links);
        $out .= html_writer::tag("label", "Student Number", array("for" => "obula_ssc_sid_sml"));
        //TODO Protect against SQL Inject attacks, just check it's an int
        $out .= html_writer::empty_tag("input disabled", array("type" => "text", "id" => "obula_ssc_sid_sml", "class" => "ssc-sid-sml"));
        $temp = get_string("ssc-students-view", 'block_obu_learnanalytics');
        $atts = array("type" => "button", "value" => $temp, "class" => "ssc-button", "id" => "obula-show-sv-sml", "onclick" => "showBecomeView('S', 'ssc-sid-sml')");
        $out .= html_writer::empty_tag("input disabled", $atts);
        $temp = get_string("ssc-tutors-view", 'block_obu_learnanalytics');
        $atts = array("type" => "button", "value" => $temp, "class" => "ssc-button", "id" => "obula-show-tv-sml", "onclick" => "showBecomeView('T', 'ssc-sid-sml')");
        $out .= html_writer::empty_tag("input disabled", $atts);
        $out .= html_writer::end_tag("div");
        $out .= self::connection_error_placeHolder(true, $USER->username);
        $out .= html_writer::end_tag("panel");

        // Then Medium panel
        $atts = array("id" => "obula_ssc_heading_med", "style" => "display: none");
        $out .= html_writer::tag("h5", "Welcome to Learning Analytics " . $links, $atts);
        $out .= html_writer::start_tag("div", array("id" => "obula_ssc_input_med", "style" => "display: none"));
        $out .= html_writer::tag("label", "Student Number", array("for" => "obula_ssc_sid_med"));
        //TODO Protect against SQL Inject attacks
        $out .= html_writer::empty_tag("input disabled", array("type" => "text", "id" => "obula_ssc_sid_med", "class" => "ssc-sid-med"));
        $temp = get_string("ssc-students-view", 'block_obu_learnanalytics');
        $atts = array("type" => "button", "value" => $temp, "class" => "ssc-button", "id" => "obula-show-sv-med", "onclick" => "showBecomeView('S', 'ssc-sid-med')");
        $out .= html_writer::empty_tag("input disabled", $atts);
        $temp = get_string("ssc-tutors-view", 'block_obu_learnanalytics');
        $atts = array("type" => "button", "value" => $temp, "class" => "ssc-button", "id" => "obula-show-tv-med", "onclick" => "showBecomeView('T', 'ssc-sid-med')");
        $out .= html_writer::empty_tag("input disabled", $atts);
        $out .= self::connection_error_placeHolder(false, $USER->username);
        $out .= html_writer::end_tag("div");

        // Now placeholders
        $consolehtml = "";
        $out .= self::any_dashboard_host_placeholders($consolehtml);
        return $out;
    }

    /**
     * output_error and hide the details for hint to pick up
     *
     * @param  string $username       The Moodle username (student number or p number)
     * @return string
     */
    public function connection_error_placeHolder(bool $smallPanel, string $username = null)
    {
        $id_suffix = $smallPanel ? "_sml" : "_med";
        $isAdmin = is_siteadmin() || $username == "p0090268";
        $class = ($isAdmin) ? "error error-tip" : "error";
        $out = html_writer::start_tag("div", array("class" => $class, "id" => "obula_cc_errordiv" . $id_suffix, "style" => "display: none"));
        $out .= "???";
        $out .= html_writer::end_tag("div");
        return $out;
    }

    /**
     * any_dashboard_host_placeholders
     * Constructs placeholders for dashboards and error messages to be inserted into
     *
     * @return string An html table with cells for dashboard and/or error messages
     */
    public function any_dashboard_host_placeholders($errorCellContents = "Error Message")
    {
        // So let's try this as a table - 1st with summary of what they are seeing and 2nd with data
        // When these are shown the options will be hidden
        $out = html_writer::start_tag("table");
        $out .= html_writer::start_tag("tr", array("id" => "obula_error_row", "style" => "display: none"));
        $atts = array("style" => "error", "id" => "obula_error_cell", "colspan" => "4");
        $out .= html_writer::tag("td", $errorCellContents, $atts);
        $out .= html_writer::end_tag("tr");
        $out .= html_writer::start_tag("tr", array("id" => "obula_summary_row", "style" => "display: none"));
        $out .= html_writer::empty_tag("td", array("id" => "obula_summary_cell"));
        $out .= html_writer::end_tag("tr");
        $out .= html_writer::start_tag("tr", array("id" => "obula_dash_row", "style" => "display: none"));
        // For now let's put a DIV in there
        $out .= html_writer::tag("td", html_writer::empty_tag("div", array("id" => "obula_dash_div")));
        $out .= html_writer::end_tag("tr");
        $out .= html_writer::end_tag("table");
        return $out;
    }

    /**
     * Renders the Tutors dashboard with comparison grid and placeholders for charts
     * called from self::tutor_dashboard_summary and become_students_tutor.php
     *
     * @param  string  $defaultProgramme The default programme code for the tutor
     * @param  boolean $subDashboard     True if called from SSC fashboard
     * @return string                    HTML to render
     */
    public function tutor_dashboard(string $defaultProgramme, bool $subDashboard, string $studentNumber = null)
    {
        $out = '';
        $out .= html_writer::start_tag("div");
        $out .= self::tutor_grid($defaultProgramme, $subDashboard, $studentNumber);
        $out .= html_writer::end_tag("div");

        $out .= html_writer::empty_tag("br");
        //$out .= html_writer::empty_tag("br");

        $out .= self::student_charts(true, true, null);

        // For now just put it below the student charts
        $out .= self::student_marks_placeholder();

        // ditto scatter chart
        //TODO $out .= self::student_marks_v_engagement();

        return $out;
    }

    /**
     * Render the grid for students engagement
     *
     * @see ?? for the expected structure
     * @return string
     */
    public function tutor_grid($defaultProgramme, $subDashboard, $studentNumber)
    {
        $util_dates = new \block_obu_learnanalytics\util\date_functions();
        $curl_common = new \block_obu_learnanalytics\curl\common();
        $outScripts = "";
        if (!$subDashboard) {
            // Only loaded if it's not a subDashboard as parent should have loaded these
            $scriptUrl = new moodle_url('common.js?version=0.10.8');
            $outScripts .= html_writer::script(null, $scriptUrl);
        }
        // Now the main one that we always want to load
        $scriptUrl = new moodle_url('/blocks/obu_learnanalytics/scripts/tutor_grid.js?version=0.10.8');
        $outScripts .= html_writer::script(null, $scriptUrl);
        // End of scripts

        // So now output some selection and sorting criteria
        // in a table
        $outParams = html_writer::start_tag('div');
        $outParams .= html_writer::start_tag('table id=obula-tutor-params-grid');

        $context = $this->page->context;

        $outParams .= html_writer::start_tag("tr", array("class" => "parameters", "style" => "min-width:100px"));
        // So get the data we need
        $outParams .= html_writer::start_tag('td', array("class" => "parameters"));
        // Get the current week to show
        $current = $util_dates->get_current_week();
        $params = 'tutor/activepgms/' . $current["first_day_week"]->format('Y-m-d') . '/';
        $activeProgrammes = $curl_common->send_request($params);

        $outParams .= html_writer::tag("label", "Programme", array("for" => "selProgramme", "style" => "min-width:100px"));
        $selectAtts = array("name" => "Programmes", "id" => "selProgramme", "onchange" => "programmeChanged()", "style" => "min-width:200px");
        $outParams .= html_writer::start_tag('select', $selectAtts);
        foreach ($activeProgrammes as $key => $data) {
            $atts = array("value" => "$key");
            if ($key == $defaultProgramme) {
                $atts['selected'] = 'selected';
            }
            $outParams .= html_writer::tag("option", $data["programme_name"], $atts);
        }
        $outParams .= html_writer::end_tag('select');
        // Now a search button for Programme
        // TODO remove old lines and class and pix
        //old $buttonAtts = array("class" => "button-search", "onclick" => "clickSearchProgramme()");
        //old $outParams .= html_writer::tag("button", null, $buttonAtts);
        $buttonAtts = array("class" => "material-icons search", "onclick" => "clickSearchProgramme()", "title" => "Search");
        $outParams .= html_writer::tag("a", "search", $buttonAtts);
        $outParams .= html_writer::end_tag('td');

        // Now study stages
        $params = "tutor/allsstages/1/";
        $curl_common = new \block_obu_learnanalytics\curl\common();
        $allSStages = $curl_common->send_request($params);
        // But if there is only 1 then take it (* only returned if there is more than 1)
        $study_stage = (count($allSStages) == 1) ? array_keys($allSStages)[0] : "*";

        $outParams .= html_writer::start_tag('td', array("class" => "parameters"));
        $outParams .= html_writer::tag('label', 'Study Stage', array('for' => 'selStudyStage', 'style' => 'min-width:100px'));
        $selectAtts = array("name" => "StudyStages", "id" => "selStudyStage", "onchange" => "studyStageChanged()", "style" => "min-width:160px;max-width:160px");

        $outParams .= html_writer::start_tag('select', $selectAtts);

        foreach ($allSStages as $key => $data) {
            $name = $data['study_stage_desc'];
            if ($key == $study_stage) {
                $atts = array("value" => $key, "selected" => "selected");
            } else {
                $atts = array("value" => $key);
            }
            $outParams .= html_writer::tag("option", $name, $atts);
        }

        $outParams .= html_writer::end_tag("select");

        $outParams .= html_writer::end_tag("td");

        // Placeholder for date_controls.php
        $outParams .= html_writer::empty_tag("td", array("id" => "obula_week_control_cell", "class" => "parameters"));

        $outParams .= html_writer::end_tag("tr");

        // Now some more
        $outParams .= html_writer::start_tag("tr", array("class" => "parameters"));

        // Banding
        $outParams .= html_writer::start_tag('td', array("class" => "parameters"));
        // Hidden field to hold current banding, TODO if we are keeping this as an option then pick up from saved preference
        $outParams .= html_writer::tag('input', '', array("type" => 'hidden', "id" => 'obula_banding_calc', "value" => 'MED-20-4'));

        $options = array(
            //"AVG-10-1" => "Mean Average +/- 10% - 1 Week",
            "AVG-20-1" => "Mean Average +/- 20% - 1 Week",
            //"AVG-30-1" => "Mean Average +/- 30% - 1 Week",
            //"AVG-10-4" => "Mean Average +/- 10% - 4 Weeks",
            //"AVG-20-4" => "Mean Average +/- 20% - 4 Weeks",
            //"AVG-30-4" => "Mean Average +/- 30% - 4 Weeks",
            //"MED-10-1" => "Median Average +/- 10% - 1 Week",
            "MED-20-1" => "Median Average +/- 20% - 1 Week",
            "MED-20-2" => "Median Average +/- 20% - 2 Weeks",
            "MED-20-3" => "Median Average +/- 20% - 3 Weeks",
            "MED-20-4" => "Median Average +/- 20% - 4 Weeks",
            "MED-30-1" => "Median Average +/- 30% - 1 Week",
            "MED-30-2" => "Median Average +/- 30% - 2 Weeks",
            "MED-30-3" => "Median Average +/- 30% - 3 Weeks",
            "MED-30-4" => "Median Average +/- 30% - 4 Weeks",
            //"MED-30-4" => "Median Average +/- 30% - 4 Weeks",
        );
        $outParams .= html_writer::tag('label', 'Banding', array('for' => 'selBanding', 'style' => 'min-width:100px'));
        $selectAtts = array("name" => "Banding", "id" => "selBanding", "onchange" => "bandingChanged()");
        $outParams .= html_writer::start_tag('select', $selectAtts);
        foreach ($options as $key => $data) {
            $atts = array("value" => "$key");
            if ($key == 'MED-20-4') {
                $atts['selected'] = 'selected';
            }
            $outParams .= html_writer::tag("option", $data, $atts);
        }
        $outParams .= html_writer::end_tag('select');
        $outParams .= html_writer::end_tag('td');
        // End of Banding

        // Now Study Mode (hard coded for now, WS started but it's hard coded as well)
        $options = array(
            "*" => "All",
            "F" => "Full Time (F)",
            "P" => "Part Time (P)",
            "O" => "Other (O)"
        );
        $outParams .= html_writer::start_tag('td', array("class" => "parameters"));
        $outParams .= html_writer::tag('label', 'Study Mode', array('for' => 'selStudyType', 'style' => 'min-width:100px'));
        $selectAtts = array("name" => "StudyType", "id" => "selStudyType", "onchange" => "studyTypeChanged()", "style" => "min-width:160px;max-width:160px");
        $outParams .= html_writer::start_tag('select', $selectAtts);
        foreach ($options as $key => $data) {
            $atts = array("value" => "$key");
            if ($key == '*') {
                $atts['selected'] = 'selected';
            }
            $outParams .= html_writer::tag("option", $data, $atts);
        }
        $outParams .= html_writer::end_tag('select');
        $outParams .= html_writer::end_tag('td');
        // End of Study Mode

        // Placeholder for date_controls.php
        $outParams .= html_writer::empty_tag("td", array("id" => "obula_semester_control_cell", "class" => "parameters"));
        $outParams .= html_writer::end_tag("tr");
        // End of selection row

        $outParams .= html_writer::end_tag('table');
        $outParams .= html_writer::end_tag('div');

        // Now we need another table for Tutor Grid on the left and a chart on the right
        $outPlaceHolders = '';
        $outPlaceHolders .= html_writer::start_tag('div');
        // Now a hidden field to hold which week, year, W/C date we are looking at
        //$jsonWeek = htmlspecialchars(json_encode($current)); // Serialize as json and deal with special charcaters so I can get it from javascript
        //$outPlaceHolders .= html_writer::tag('input', '', array('type' => 'hidden', "id" => "obula_currentweek", "value" => $jsonWeek));
        $outPlaceHolders .= html_writer::start_tag('table', array('id' => 'obula_tutor_parent_grid'));

        $outPlaceHolders .= html_writer::start_tag("tr");
        $outPlaceHolders .= html_writer::start_tag("td", array("id" => "obula_tutor_grid_div", "style" => "vertical-align: top"));
        $outPlaceHolders .= html_writer::end_tag("td");

        $outPlaceHolders .= html_writer::start_tag("td", array("id" => "obula_tutor_chart_div", "style" => "vertical-align: top"));
        // Right hand cell needs a link to show the grid, so put a table inside the cell
        $outPlaceHolders .= html_writer::start_tag('table', array("id" => "obula_tutor_grid_table"));
        $outPlaceHolders .= html_writer::start_tag("tr");
        // Can't get align top to work at the moment so set the max height - but that didn't work either
        $outPlaceHolders .= html_writer::start_tag("td", array('style' => 'vertical-align: top; max-height: 18px'));
        $chartAtts = array("href" => "javascript:hideCharts(false)", "id" => "obula_chart_hide", "class" => "chart-links");
        $chartAtts['style'] = "display:none";
        $outPlaceHolders .= html_writer::tag('a', 'Hide Chart', $chartAtts);
        $outPlaceHolders .= "&nbsp";
        $chartAtts = array("href" => "javascript:expandChart()", "id" => "obula_chart_expand", "class" => "chart-links");
        $chartAtts['style'] = "display:none";
        $outPlaceHolders .= html_writer::tag('a', 'Expand chart', $chartAtts);
        $outPlaceHolders .= html_writer::end_tag("td");

        $outPlaceHolders .= html_writer::end_tag("tr");

        $outPlaceHolders .= html_writer::start_tag("tr");
        $outPlaceHolders .= html_writer::start_tag("td", array("id" => "obula_tutor_grid_chart"));
        // Rather than try and load the chart on load, just give a show link
        $chartAtts = array("href" => "javascript:showChart()", "id" => "obula_chart_show", "class" => "chart-links");
        $chartAtts['style'] = "display:none";
        $outPlaceHolders .= html_writer::tag('a', 'Chart engagement', $chartAtts);
        // To make things simpler, add an an empty img
        $outPlaceHolders .= html_writer::tag("img", null, array('src' => '', 'id' => 'obula_tutorsGraph_img', 'style' => 'display:none'));
        $outPlaceHolders .= html_writer::end_tag("td");
        $outPlaceHolders .= html_writer::end_tag("tr");
        $outPlaceHolders .= html_writer::end_tag('table');
        $outPlaceHolders .= html_writer::end_tag("td");

        $outPlaceHolders .= html_writer::end_tag("tr");
        $outPlaceHolders .= html_writer::end_tag('table');
        $outPlaceHolders .= html_writer::end_tag('div');

        $out = $outScripts . $outParams . $outPlaceHolders;
        return $out;
    }

    public function student_marks_placeholder()
    {
        $out = "";
        $atts = array('id' => 'obula_studentmarks_div', 'style' => 'display: none');
        $out .= html_writer::start_tag('div', $atts);
        $out .= html_writer::end_tag('div');
        return $out;
    }

    public function student_marks_v_engagement()
    {
        // Do this as a table so we can put the buttons alongside
        $out = "";
        $atts = array('id' => 'obula_marksveng_tbl', 'style' => 'display: none');
        $out .= html_writer::start_tag('table', $atts);
        $out .= html_writer::start_tag("tr");
        $out .= html_writer::start_tag("td");
        // To make things simpler, add an an empty img
        $out .= html_writer::tag("img", null, array('src' => '', 'id' => "obula_marksveng_img"));
        $out .= html_writer::end_tag("td");
        // Now some options
        $buttonData = array("1wk", "1 Week", "4wks", "4 Weeks", "sem", "Semester");
        //$buttonData = array("1wk", "1 Week", "4wks", "4 Weeks", "_disabled", "Semester");
        $out .= self::outputRadioButtonsInCell($buttonData, "obula_marksveng_rbs", "mveradbuttons", "showMarksvEng");
        $out .= html_writer::end_tag("tr");
        $out .= html_writer::end_tag('table');
        return $out;
    }

    /**
     * student_charts
     * Render a single students engagement as chart(s)
     *
     * @param  Boolean $fromtutordb     Indicates if it is being called for the Tutor Dashboard
     * @param  Boolean $hide            Indicates if it should not show for now
     * @param  String $sid              The Student Number
     * @param  String $sname            The Students Name
     * @param  String $programme        The Programme Code
     * @return String                   HTML for Moodle to render
     */
    public function student_charts(bool $fromtutordb, $hide, $sid, $sname = null, $programme = null)
    {
        $util_dates = new \block_obu_learnanalytics\util\date_functions();
        $util_odds = new \block_obu_learnanalytics\util\odds();

        global $USER;
        global $SESSION;
        $parameters = "?from=tutor";
        if ($sid != null) {
            $parameters = "?studentNumber={$USER->username}&sname={$sname}&programme={$programme}";
        }
        $outScripts = "";
        // TODO check if student_charts still needed now common.js created
        $scriptUrl = new moodle_url('/blocks/obu_learnanalytics/scripts/student_charts.js?version=0.10.8');
        $outScripts .= html_writer::script(null, $scriptUrl);
        // End of scripts

        $out = $outScripts;

        // If we are a student then there is some more to output before the charts
        if (!$fromtutordb) {
            $curl_common = new \block_obu_learnanalytics\curl\common();
            $advisorDetails = $curl_common->get_academic_advisor($USER->username);
            if ($advisorDetails == null) {
                $out .= html_writer::tag('h3', 'You do not have an Academic Adviser assigned');
            } else {
                $out .= html_writer::start_tag('h3');
                $out .= "My Academic Adviser is {$advisorDetails['Name']}";      //TODO get Name
                // Let's output a message them button
                $out .= html_writer::start_tag("div", array("class" => "btn-group header-button-group"));
                // First the link
                $url = new moodle_url("/message/index.php?id={$advisorDetails['userid']}");
                $atts = array("id" => "message-user-button", "role" => "button", "data-conversationid" => "0", "data-userid" => $advisorDetails['userid'], "class" => "btn", "href" => $url);
                $out .= html_writer::start_tag("a", $atts);
                // Now the icon
                $out .= html_writer::start_tag("span");
                $atts = array("class" => "icon fa fa-comment fa-fw iconsmall", "aria-label" => "Message");
                $out .= html_writer::tag("i", "", $atts);
                $out .= html_writer::tag("span", "Message", array("class" => "header-button-title"));
                $out .= html_writer::end_tag('span');
                $out .= html_writer::end_tag('a');
                // End of message them
                // Now add to contacts before div closed
                // First the link
                $sessKey = sesskey();
                $url = new moodle_url("/message/index.php?user1={$USER->id}&user2={$advisorDetails['userid']}&addcontact={$advisorDetails['userid']}&sesskey={$sessKey}");
                $atts = array("id" => "toggle-contact-button", "data-userid" => $advisorDetails['userid'], "data-is-contact" => "0", "class" => "ajax-contact-button btn", "href" => $url);
                $out .= html_writer::start_tag("a", $atts);
                // Now the icon
                $out .= html_writer::start_tag("span");
                $atts = array("class" => "icon fa fa-address-card fa-fw iconsmall", "aria-label" => "Add to contacts", "title" => "Add to contacts");
                $atts["data-toggle"] = "tooltip";
                $out .= html_writer::tag("i", "", $atts);
                $out .= html_writer::tag("span", "Add to contacts", array("class" => "header-button-title"));
                $out .= html_writer::end_tag('span');
                $out .= html_writer::end_tag('a');
                // add to contacts done
                $out .= html_writer::end_tag('div');
                $out .= html_writer::end_tag('h3');
            }
        }

        $jsonParams = $util_odds->store_parameters($programme, "*", $sid, $sname);
        $out .= html_writer::tag('input', '', array('type' => 'hidden', 'id' => 'obula_parameters', 'value' => "$jsonParams"));
        $out .= self::student_chart_placeholders(!$fromtutordb, $hide);

        return $out;
    }

    /**
     * student_chart_placeholders
     *
     * @param  boolean $outputCohortPlaceHolder
     * @param  boolean $hide
     * @return string  HTML for section
     */
    public function student_chart_placeholders(bool $outputCohortPlaceHolder, bool $hide)
    {
        $out = "";
        $atts = array('id' => 'obula_studentGraphs_div');
        if ($hide) {
            $atts['style'] = 'display: none';
        }
        $out .= html_writer::start_tag('div', $atts);

        // Even one chart goes in a table so we can put the radio buttons alongside
        // But currently no of charts is fixed at 2
        $out .= html_writer::start_tag("table");

        // If this is the student page then put out a place holder for the cohort comparison
        if ($outputCohortPlaceHolder) {
            // Now placeholder for cohort_engagement.php
            $out .= html_writer::start_tag("tr", array("id" => "obula_cohort_comparison_row", "style" => "display: none"));
            $out .= html_writer::start_tag("td", array("id" => "obula_cohort_comparison"));
            // To make things simpler, add an an empty img
            $out .= html_writer::tag("img", null, array('src' => '', 'id' => "obula_cohort_comparison_img"));
            $out .= html_writer::end_tag("td");
            // Now some options
            //$buttonData = array("1wk", "1 Week", "4wks", "4 Weeks", "sem", "Semester");
            $buttonData = array("1wk", "1 Week", "4wks", "4 Weeks", "_disabled", "Semester");
            $out .= self::outputRadioButtonsInCell($buttonData, "obula_cohort_comparison_rbs", "cohcompradbuttons", "showCohortComparison");

            $out .= html_writer::end_tag("tr");
        }

        // Setup to output the graphs
        $types = array("vle", "ez", "loans", "att");
        $buttonsData = array();
        $buttonsData[] = array("vleduration", "Duration", "vlesessions", "Visits", "vleviews", "Page Views");
        $buttonsData[] = array("ezduration", "Duration", "ezsessions", "Visits", "ezsize", "Downloaded (MB)");
        //$buttonsData[] = array("loansline", "Line Chart", "loansbar", "Bar Chart", "loanscomb", "Combined");
        //$buttonsData[] = array("attduration", "Duration", "attsessions", "Lectures");
        $noCharts = 2; // Was a parameter once

        for ($i = 1; $i <= $noCharts; $i++) {
            $buttonData = $buttonsData[$i - 1];
            $out .= html_writer::start_tag("tr");
            // First the chart
            $out .= html_writer::start_tag("td");
            // Empty img as the ready event will fire it off
            $out .= html_writer::tag("img", null, array('src' => '', 'id' => "obula_studentGraph_img_$i"));
            $out .= html_writer::end_tag("td");
            if ($buttonData[0] != "") {
                $name = $types[$i - 1] . "charttype";
                // Now the Radio Buttons
                $out .= self::outputRadioButtonsInCell($buttonData, "obula_studradbuttons{$i}", $name, "changeChartTypeRB", $i, false);
                // And something to show VLE Moodle breakdown by Module
                if ($name == "vlecharttype") {
                    $atts = array("type" => "button", "value" => "By Module", "id" => "obula_mod_eng", "onclick" => "showModuleEng()");
                    $out .= html_writer::empty_tag("input", $atts);
                }
                $out .= html_writer::end_tag("td");
            }

            // Now finish the row
            $out .= html_writer::end_tag("tr");
            // If it's the VLE then insert a row to show Module breakdown
            $atts = array('id' => 'obula_studentModule_row');
            if ($hide) {
                $atts['style'] = 'display: none';
            }
            $out .= html_writer::start_tag("tr", $atts);
            $out .= html_writer::start_tag("td");
            // Insert empty img as the ready event will fire off a request
            $out .= html_writer::tag("img", null, array('src' => '', 'id' => "obula_studentModule_img", "alt" => "Barchart of Module Engagement"));
            $out .= html_writer::end_tag("td");
            $out .= html_writer::end_tag("tr");
        }
        $out .= html_writer::end_tag("table");
        $out .= html_writer::end_tag('div');

        return $out;
    }

    /**
     * A new experimental look for the students dashboard
     *
     * @param  bool $subDashboard   true if loaded from another dashboard (ssc_dashboard)
     * @param  mixed $sid           Student Number
     * @param  mixed $fname         Students First Name
     * @param  mixed $sname         Students full name
     * @param  mixed $programme     Programme Code
     * @return string containing HTML to be sent to browser (using echo)
     */
    public function students_dashboard($subDashboard, $sid, $fname, $sname, $programme)
    {
        try {
            // NOTE - try doesn't catch html_writer problems, which is why I tried it, but might as well leave it
            $util_dates = new \block_obu_learnanalytics\util\date_functions();
            $util_odds = new \block_obu_learnanalytics\util\odds();
            $curl_common = new \block_obu_learnanalytics\curl\common();

            global $USER;
            global $SESSION;
            $outScripts = "";
            if (!$subDashboard) {
                $scriptUrl = new moodle_url('/blocks/obu_learnanalytics/scripts/common.js?version=0.10.8');
                $outScripts .= html_writer::script(null, $scriptUrl);
            }
            $scriptUrl = new moodle_url('/blocks/obu_learnanalytics/scripts/student_dashboard.js?version=0.10.8');
            $outScripts .= html_writer::script(null, $scriptUrl);
            // End of scripts

            $out = $outScripts;
            $out .= self::modal_any_popup();           // For Help explanation

            //Want to use Google Material fonts
            //<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
            // see this for icons https://material.io/resources/icons/?icon=flight_takeoff&style=baseline 
            $out .= html_writer::empty_tag("link", array("rel" => "stylesheet", "href" => "https://fonts.googleapis.com/icon?family=Material+Icons"));
            // for fun try         $out .= html_writer::tag("i", "face", array("class" => "material-icons"));

            $advisorDetails = $curl_common->get_academic_advisor($USER->username);
            if ($advisorDetails == null) {
                $out .= html_writer::tag('h5', "Hi {$fname}, you do not have an Academic Adviser assigned");
            } else {
                $out .= html_writer::start_tag('h5');
                $out .= "Hi {$fname}, your Academic Adviser is {$advisorDetails['Name']}"  . implode(':', $advisorDetails);  //TODO get Name
                // Let's output a message them button
                $out .= html_writer::start_tag("div", array("class" => "btn-group header-button-group"));
                // First the link
                $url = new moodle_url("/message/index.php?id={$advisorDetails['userid']}");
                $atts = array("id" => "message-user-button", "role" => "button", "data-conversationid" => "0", "data-userid" => $advisorDetails['userid'], "class" => "btn", "href" => $url);
                $out .= html_writer::start_tag("a", $atts);
                // Now the icon
                $out .= html_writer::start_tag("span");
                $atts = array("class" => "icon fa fa-comment fa-fw iconsmall", "aria-label" => "Message");
                $out .= html_writer::tag("i", "", $atts);
                $out .= html_writer::tag("span", "Message", array("class" => "header-button-title"));
                $out .= html_writer::end_tag('span');
                $out .= html_writer::end_tag('a');
                // End of message them
                // Now add to contacts before div closed
                // First the link
                $sessKey = sesskey();
                $url = new moodle_url("/message/index.php?user1={$USER->id}&user2={$advisorDetails['userid']}&addcontact={$advisorDetails['userid']}&sesskey={$sessKey}");
                $atts = array("id" => "toggle-contact-button", "data-userid" => $advisorDetails['userid'], "data-is-contact" => "0", "class" => "ajax-contact-button btn", "href" => $url);
                $out .= html_writer::start_tag("a", $atts);
                // Now the icon
                $out .= html_writer::start_tag("span");
                $atts = array("class" => "icon fa fa-address-card fa-fw iconsmall", "aria-label" => "Add to contacts", "title" => "Add to contacts");
                $atts["data-toggle"] = "tooltip";
                $out .= html_writer::tag("i", "", $atts);
                $out .= html_writer::tag("span", "Add to contacts", array("class" => "header-button-title"));
                $out .= html_writer::end_tag('span');
                $out .= html_writer::end_tag('a');
                // add to contacts done
                $out .= html_writer::end_tag('div');
                $out .= html_writer::end_tag('h3');
            }

            // So done the welcome and advisor is, now we want to tell them how they are doing
            // Want an Ideas icon far right and the W/C date, so use tables
            //  - Outer one will have 2 columns (_data)
            //  - First row cell 1 will have a table for status comments (_status), cell 2 the ideas icon
            //  -   status table will have 2 columns, emoticon and text
            //  -   blank row after status
            //  -   placeholder for ideas in 2nd column
            //  - Blank row on outer table
            //  - Next row will have placeholder for cohort comparison col span = 2
            //  - Blank row, placeholder etc
            $outTables  = html_writer::start_tag("table", array("id" => "obula_student_data", "style" => "width:100%"));
            $outTables .= html_writer::start_tag("tr");

            $outTables .= html_writer::start_tag("td");
            $outTables .= html_writer::start_tag("table", array("id" => "obula_student_status"));

            $outTables .= html_writer::start_tag("tr");
            // Now how are they doing compared to others, if we like this then we can pre-calculate in EDW
            $params = "student/details/$USER->username/";
            $studentDetails = $curl_common->send_request($params);
            if ($studentDetails != null && $studentDetails["study_stage"] != '') {
                // Get two weeks in one go for comparisons
                $current = $util_dates->get_current_week();
                $params = "student/cohorteng/$programme/*/*/$weeks/$simpleCurrent/";
                $curl_common = new \block_obu_learnanalytics\curl\common();
                $studentsData = $curl_common->send_request($params);
                // For now remove zeros, but once active flag complete this may come out and/or go into get_active_cohort_colleagues
                // $studentsData = $db_cohort->remove_zeros($studentsData);
                $studentCount = count($studentsData);
                if ($studentCount >= 5) {
                    // Now sort on this week
                    uasort($studentsData, 'self::sort_engagement');
                    // Now find position in sorted array, $pos is zero based
                    $pos = array_search((int)$sid, array_keys($studentsData));
                    // Now sort on last week
                    uasort($studentsData, 'self::sort_engagement_lw');
                    // Now find position in sorted array
                    $lw_pos = array_search((int)$sid, array_keys($studentsData));

                    switch (true) {
                        case ($pos == 0):
                            $message = "You are less engaged than all of your cohort";
                            $emoticon = "sad.png";
                            break;
                        case ($pos == $studentCount - 1):
                            $message = "You more engaged than all of your cohort";
                            $emoticon = "angel.png";
                            break;
                        case ($pos + 1 > floor($studentCount / 2)): //So if that's more than median say more (be generous if there are an even number)
                            // Pos is from zero so don't need to subtract 1
                            $posPerc = sprintf("%.2f%%", $pos / $studentCount * 100);
                            $message = "You were more engaged than {$posPerc} of your cohort";
                            if ($pos > $lw_pos) {
                                $emoticon = "angel.png";
                            } else {
                                $emoticon = "smile.png";
                            }
                            break;

                        default:
                            $i = $studentCount - $pos - 1;
                            $posPerc = sprintf("%.2f%%", $i / $studentCount * 100);
                            $message = "You were less engaged than {$posPerc} of your cohort";
                            $emoticon = "sad.png";
                            break;
                    }
                    // No add a change comment
                    switch (true) {
                        case ($pos == $lw_pos):
                            $message .= ", no change from previous week ";
                            break;
                        case ($pos > $lw_pos):
                            $i = $pos - $lw_pos;
                            $message .= ", up {$i} places on previous week ";
                            break;
                        case ($pos < $lw_pos):
                            $i = $lw_pos - $pos;
                            $message .= ", down {$i} places on previous week ";
                            break;
                    }   // End of switch

                    // Now output 2 columns (cells)
                    $outTables .= html_writer::start_tag("td");      // Cell 1
                    $img = new moodle_url("/blocks/obu_learnanalytics/pix/{$emoticon}");
                    $outTables .= html_writer::img($img, "", array("style" => "max-height:28px; padding:4px"));
                    $outTables .= html_writer::end_tag("td");
                    $outTables .= html_writer::start_tag("td", array("style" => "width:100%"));   // Cell 2
                    $outTables .= html_writer::span($message);
                    $outTables .= html_writer::tag("a", "show", array("href" => "javascript:showCohortComparison()"));
                    $outTables .= html_writer::end_tag("td");

                    $outTables .= html_writer::end_tag("tr");         // Just the end of the 1st status message
                }
            }

            $outTables .= html_writer::start_tag("tr");
            $outTables .= html_writer::start_tag("td");
            $emoticon = "sad.png";
            $img = new moodle_url("/blocks/obu_learnanalytics/pix/{$emoticon}");
            $outTables .= html_writer::img($img, "", array("style" => "max-height:28px; padding:4px"));
            $outTables .= html_writer::end_tag("td");
            $outTables .= html_writer::start_tag("td");
            $outTables .= html_writer::span("Your own Engagement declined ");
            $outTables .= html_writer::tag("a", "show", array("href" => "javascript:showStudentGraphs()"));
            $outTables .= html_writer::end_tag("td");
            $outTables .= html_writer::end_tag("tr");

            /*
            // Now another dummy one for testing
            $outTables .= html_writer::start_tag("tr");
            $outTables .= html_writer::start_tag("td");
            $emoticon = "angel.png";
            $img = new moodle_url("/blocks/obu_learnanalytics/pix/{$emoticon}");
            $outTables .= html_writer::img($img, "", array("style" => "max-height:28px; padding:4px"));
            $outTables .= html_writer::end_tag("td");
            $outTables .= html_writer::start_tag("td");
            //$outTables .= html_writer::span("You are a rare/eratic/consistent engager (will delete as appropriate) ");
            $outTables .= html_writer::span("Your consistency is 3 out of 5 ");
            $outTables .= html_writer::tag("a", " show V1 ", array("href" => "javascript:showStudentConsistency('v1')"));
            $outTables .= html_writer::tag("a", " show V2 ", array("href" => "javascript:showStudentConsistency('v2')"));
            $outTables .= html_writer::tag("a", " show V3 ", array("href" => "javascript:showStudentConsistency('v3')"));
            $outTables .= html_writer::tag("a", " show Radar ", array("href" => "javascript:showStudentRadar()"));
            $outTables .= html_writer::end_tag("td");
            $outTables .= html_writer::end_tag("tr");
            */

            // Now an empty one for space
            $outTables .= html_writer::start_tag("tr", array("id" => "obula_cohort_afterstatus_row", "style" => "display: none"));
            $outTables .= html_writer::empty_tag("td", array("style" => "height: 12px"));
            $outTables .= html_writer::empty_tag("td");
            $outTables .= html_writer::end_tag("tr");

            // Now a row for the ideas placeholder
            $outTables .= html_writer::start_tag("tr", array("id" => "obula_student_ideas_row", "style" => "display: none"));      // dummy status row
            $outTables .= html_writer::empty_tag("td");      // Cell 1 empty
            // Placeholder for student_ideas.php
            $outTables .= html_writer::empty_tag("td", array("id" => "obula_student_ideas_div"));
            $outTables .= html_writer::end_tag("tr");

            // Now end the status table
            $outTables .= html_writer::end_tag("table");

            // so now the cell with the ideas icon
            $outTables .= html_writer::start_tag("td");
            // But I want space for W/C so put table here too
            $outTables .= html_writer::start_tag("table");
            $outTables .= html_writer::start_tag("tr");
            $outTables .= html_writer::start_tag("td", array("style" => "vertical-align: top; text-align: right")); // Yes text-align for an image
            // TODO I think there is an approved way of getting an url to an image that will then use cache etc
            $ideaimg = new moodle_url('/blocks/obu_learnanalytics/pix/icons8-idea-64.png');
            $atts = array("src" => $ideaimg, "style" => "max-height:48px", "title" => "Ideas to increase your engagement");
            $atts["onclick"] = "ideasClicked({$sid})";
            $outTables .= html_writer::empty_tag("img", $atts);
            $outTables .= html_writer::end_tag("td");
            $outTables .= html_writer::end_tag("tr");

            // Now row for explain link with a spacer
            $outTables .= html_writer::start_tag("tr");
            $outTables .= html_writer::empty_tag("td", array("style" => "height: 12px"));
            $outTables .= html_writer::end_tag("tr");

            $outTables .= html_writer::start_tag("tr", array("id" => "obula_explain_row"));
            // Can be shown with data toggle, but we need more control
            //$outTables .= html_writer::tag("td", html_writer::tag("button", "explain", array("class" => ""
            //                , "data-toggle" => "modal", "data-target" => "#obula_modal_popup")));
            $outTables .= html_writer::tag("td", html_writer::tag("a", "explain", array("href" => "javascript:showHelp('student')")));

            $outTables .= html_writer::end_tag("tr");
            $outTables .= html_writer::end_tag("table");
            $outTables .= html_writer::end_tag("td");

            $outTables .= html_writer::end_tag("tr");             // End of status

            // Now let's output some rows as placeholders for later
            // First an empty row as a spacer
            $outTables .= html_writer::start_tag("tr", array("id" => "obula_before_placeholders_row", "style" => "display: none"));
            $outTables .= html_writer::empty_tag("td", array("style" => "height: 12px", "colspan" => "2"));
            $outTables .= html_writer::end_tag("tr");

            // Now a row for date control
            $outTables .= html_writer::start_tag("tr", array("id" => "obula_before_week_row", "style" => "display: none"));

            // Now let's put the weekcontrol here  // TODO empty spacer TD or centre or something
            // Placeholder for week_control.php
            //$outTables .= html_writer::empty_tag("td");
            $outTables .= html_writer::empty_tag("td", array("id" => "obula_week_control_cell", "class" => "parameters"));
            $outTables .= html_writer::empty_tag("td");
            $outTables .= html_writer::end_tag("tr");

            // Originally did these in the same table but that causes sizing issues when they reduce the width
            // so close and reopen a new table
            $outTables .= html_writer::end_tag("table");
            $outTables .= html_writer::start_tag("table", array("id" => "obula_student_charts"));        //NO, "style" => "width:100%"));

            // Placeholder for cohort_engagement.php
            $outTables .= html_writer::start_tag("tr", array("id" => "obula_cohort_comparison_row", "style" => "display: none"));
            $outTables .= html_writer::start_tag("td", array("id" => "obula_cohort_comparison"));
            // To make things simpler, add an an empty img
            $outTables .= html_writer::tag("img", null, array('src' => '', 'id' => "obula_cohort_comparison_img"));
            $outTables .= html_writer::end_tag("td");
            // Now some options
            //$buttonData = array("1wk", "1 Week", "4wks", "4 Weeks", "sem", "Semester");
            $buttonData = array("1wk", "1 Week", "4wks", "4 Weeks", "_disabled", "Semester");
            $outTables .= self::outputRadioButtonsInCell($buttonData, "obula_cohort_comparison_rbs", "cohcompradbuttons", "showCohortComparison");
            $outTables .= html_writer::end_tag("tr");

            // Placeholder for student_consistency.php
            $outTables .= html_writer::start_tag("tr", array("id" => "obula_student_consistency_row", "style" => "display: none"));
            $outTables .= html_writer::start_tag("td", array("id" => "obula_student_consistency", "colspan" => "2"));
            // To make things simpler, add an an empty img
            $outTables .= html_writer::tag("img", null, array('src' => '', 'id' => "obula_student_consistency_img"));
            $outTables .= html_writer::end_tag("td");
            $outTables .= html_writer::end_tag("tr");

            // Now placeholder for cohort_elibrary_history.php
            $outTables .= html_writer::start_tag("tr", array("id" => "obula_elibrary_history_row", "style" => "display: none"));
            $outTables .= html_writer::start_tag("td", array("id" => "obula_elibrary_history"));
            // To make things simpler, add an an empty img
            $outTables .= html_writer::tag("img", null, array('src' => '', 'id' => "obula_elibrary_history_img"));
            $outTables .= html_writer::end_tag("td");
            // Now some options
            //$buttonData = array("1wk", "1 Week", "4wks", "4 Weeks", "sem", "Semester");
            $buttonData = array("1wk", "1 Week", "4wks", "4 Weeks", "_disabled", "Semester");
            $outTables .= self::outputRadioButtonsInCell($buttonData, "obula_cohort_elibhis_rbs", "cohelibhisbuttons", "changeElibHistRB");
            $outTables .= html_writer::end_tag("tr");

            // Placeholder for students_graph.php
            $outTables .= html_writer::start_tag("tr");
            $outTables .= html_writer::start_tag("td", array("colspan" => "2"));
            $outTables .= self::student_chart_placeholders(false, true);
            $outTables .= html_writer::end_tag("td");
            $outTables .= html_writer::end_tag("tr");

            $outTables .= html_writer::end_tag("table");

            // Store some hidden input fields for javascript functions to use
            $jsonParams = $util_odds->store_parameters($programme, "*", $sid, $sname);
            $outTables .= html_writer::start_tag("div");
            $outTables .= html_writer::tag('input', '', array('type' => 'hidden', 'id' => 'obula_parameters', 'value' => "$jsonParams"));
            $outTables .= html_writer::end_tag("div");
            return $out . $outTables;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Call back function for uasort on StudentData Array, sorting on Duration
     *
     * @param  Array $a     Row as an associative Array
     * @param  Array $b     Row as an associative Array
     * @return Integer  -1, 0 or 1 for $a lower than $b, same or higher
     */
    public function sort_engagement($a, $b)
    {
        if ($a["weighted_duration"] == $b["weighted_duration"]) {
            return 0;
        }
        return ($a["weighted_duration"] < $b["weighted_duration"]) ? -1 : 1;
    }

    /**
     * Call back function for uasort on StudentData Array, sorting on Last Weeks Duration
     *
     * @param  Array $a     Row as an associative Array
     * @param  Array $b     Row as an associative Array
     * @return Integer  -1, 0 or 1 for $a lower than $b, same or higher
     */
    public function sort_engagement_lw($a, $b)
    {
        if ($a["weighted_duration_wk-1"] == $b["weighted_duration_wk-1"]) {
            return 0;
        }
        return ($a["weighted_duration_wk-1"] < $b["weighted_duration_wk-1"]) ? -1 : 1;
    }

    public function get_image_url($type, $colour)
    {
        $imageName = "";

        if ($type == "sStage") {
            $imageName = $colour . "Circle";
        } else {
            switch ($colour) {
                case 'Red':
                    $imageName = "RedArrowDown";
                    break;
                case 'Green':
                    $imageName = "GreenArrowUp";
                    break;
                default:
                    $imageName = "BlueEquals";
                    break;
            }
        }

        // TODO I think there is an approved way of getting an url to an image that will then use cache etc
        $ret = new moodle_url('/blocks/obu_learnanalytics/pix/' . $imageName . '.png');
        //image_url($imageName, "obu_learnanalytics");
        // or resolve_image_location
        return $ret;
    }

    /**
     * Outputs a cell (TD) with a variable number of Vertical Radio buttons
     *
     * @param  array $buttonData            Simple Array of value, label, value, label etc
     * @param  mixed $cellID                The ID for the cell
     * @param  mixed $name                  The name of the Radio Button Group
     * @param  mixed $event                 The click event
     * @param  mixed $eventP2               An optional param 2 for clcik event
     * @return void
     */
    public function outputRadioButtonsInCell(array $buttonData, string $cellID, string $name, string $event, int $eventP2 = -1, bool $closeTD = true)
    {
        // TODO change the label's to be string keys for language pickup
        $atts = array("valign" => "top", "id" => $cellID);          // they should be in display none row, "style" => "display: none");
        $out = html_writer::start_tag("td", $atts);
        for ($j = 0; $j < count($buttonData) / 2; $j++) {
            $value = $buttonData[$j * 2];
            if ($value != "") {
                $out .= html_writer::empty_tag("br");               // Space them down
                $atts = array("type" => "radio", "name" => $name);
                $atts["value"] = $value;
                if ($eventP2 >= 0) {
                    $atts["onchange"] = $event . "('{$value}', $eventP2)";
                } else {
                    $atts["onchange"] = $event . "('{$value}')";
                }
                if ($j == 0) {
                    $atts["checked"] = "Checked";
                } else {
                    unset($atts["checked"]);
                }
                $buttonLabel = $buttonData[$j * 2 + 1];
                $tag = ($value == "_disabled") ? "input disabled" : "input";
                $out .= html_writer::tag($tag, $buttonLabel, $atts);
            }
        }
        if ($closeTD) {
            $out .= html_writer::end_tag("td");
        }
        return $out;
    }
}   // End of class
