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
require_once(__DIR__ . '/vendor/autoload.php');
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
     * New dashboard for all staff, SSCs, Tutors, Module leads and ??
     * NOT students
     */
    public function staff_dashboard_summary($studentNumber)
    {

        
        $scriptUrl = new moodle_url('/blocks/obu_learnanalytics/scripts/common.js?version=1.12.6');
        $outScripts = html_writer::script(null, $scriptUrl);
        $scriptUrl = new moodle_url('/blocks/obu_learnanalytics/scripts/staff_dashboard.js?version=1.12.6');
        $outScripts .= html_writer::script(null, $scriptUrl);
        $scriptUrl = new moodle_url('/blocks/obu_learnanalytics/scripts/check_connection.js?version=1.12.6');
        $outScripts .= html_writer::script(null, $scriptUrl);


        // Include Chart.js script
        $chartJsUrl = new moodle_url('https://cdn.jsdelivr.net/npm/chart.js');
        $outScripts .= html_writer::script(null, $chartJsUrl);

        // Include Chart.js datalabels plugin
        $datalabelsUrl = new moodle_url('https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels');
        $outScripts .= html_writer::script(null, $datalabelsUrl);
        // Include our ChartJS chart functions
        $chartjsObjects = new moodle_url('/blocks/obu_learnanalytics/scripts/chartjs_objects.js?version=1.12.5');
        $outScripts .= html_writer::script(null, $chartjsObjects);

        

        // End of scripts
        $out = $outScripts;
        $out .= self::modal_any_popup();           // For Help explanation

        $out .= html_writer::empty_tag("link", array("rel" => "stylesheet", "href" => "https://fonts.googleapis.com/icon?family=Material+Icons"));
        // for fun try         $out .= html_writer::tag("i", "face", array("class" => "material-icons"));

        global $USER;

        $userPrefs = get_user_preferences();        // Moodle function get's values from database
        $checkforAA = false;
        if (array_key_exists("obula_last_tutor_grid_date", $userPrefs)) {
            $today = new DateTime();
            $lastAccess = unserialize($userPrefs["obula_last_tutor_grid_date"]);
            // Before we unset time check how long it really was
            // As EDW is only update once a day it's not worth checking every time
            // And moodle doesn't want the traffic everytime
            // store it in preferences once checked
            $interval = $lastAccess->diff($today);
            if ($interval->days > 0 || $interval->h > 12) {
                $checkforAA = true;
            }
            $today->setTime(0, 0, 0);
            $lastAccess->setTime(0, 0, 0);
            if ($lastAccess == $today) {
                $message = "Last accessed Today";
            } else {
                $message = "Last accessed " . $lastAccess->format('d-M-Y');
            }
        } else {
            $maessage = "You have not checked this out";
            $checkforAA = true;
        }
        $advisees = "0";
        if (array_key_exists("obula_last_advisee_count", $userPrefs) == false || $checkforAA == true) {
            try {
                // A semester of 000000 will get the current default one
                $params = "tutor/adviseescount/000000/$USER->username/";
                $curl_common = new \block_obu_learnanalytics\guzzle\common();
                $results = $curl_common->send_request($params);
                $advisees = $results[0]["advisees"];
            } catch (Exception $e) {
                // Don't show it
            }
            set_user_preference('obula_last_advisee_count', $advisees);
        } else {
            $advisees = $userPrefs["obula_last_advisee_count"];
        }

        // Hidden fields to hold state of the nav panel
        $out .= html_writer::tag('input', '', array("type" => 'hidden', "id" => 'obula_navbar_rightDrawerDA', "value" => '?'));
        $out .= html_writer::tag('input', '', array("type" => 'hidden', "id" => 'obula_page_taken', "value" => '?'));
        $out .= html_writer::tag('input', '', array("type" => 'hidden', "id" => 'obula_copy2clip', "value" => '?'));
        $out .= html_writer::tag('input', '', array("type" => 'hidden', "id" => 'obula_host', "value" => '?'));
        $out .= html_writer::tag('input', '', array("type" => 'hidden', "id" => 'obula_lablockid', "value" => '?'));
        $out .= html_writer::tag('input', '', array("type" => 'hidden', "id" => 'obula_nextblockid', "value" => '?'));
        $out .= html_writer::tag('input', '', array("type" => 'hidden', "id" => 'obula_parentblockid', "value" => '?'));

        // Links for Help and Feedback
        $links = html_writer::tag("a", "Help", array("href" => "javascript:showHelp('staff')", "class" => "link-right link-help"));
        //$links .= html_writer::tag("a", "Feedback", array("href" => "javascript:gotoFeedback('tutor')", "class" => "link-right"));

        // Buttons (prepared up front because they were emitted twice for small and medium size)
        $tag_name = "input";
        // Programme
        $temp = get_string("tutor-show-pgm", 'block_obu_learnanalytics');
        $temp_hint = get_string("tutor-show-pgm-hint", 'block_obu_learnanalytics');
        $atts = array("type" => "button", "value" => $temp, "title" => $temp_hint, "class" => "summ-show-button", "onclick" => "showTutorFull()", "id" => "obula_show_pgm");
        $show_button_html = html_writer::empty_tag($tag_name, $atts);
        // Students programme
        $temp = get_string("tutor-show-stud", 'block_obu_learnanalytics');
        $temp_hint = get_string("tutor-show-stud-hint", 'block_obu_learnanalytics');
        $atts = array("type" => "button", "value" => $temp, "title" => $temp_hint, "class" => "summ-show-button", "onclick" => "showBecomeView('T', 'obula_show_stud_no')", "id" => "obula_show_stud_pgm");
        $become_button_html = html_writer::empty_tag($tag_name, $atts);
        // Advisees
        if ($advisees != "0") {
            $temp = get_string("show-advisees", 'block_obu_learnanalytics');
            $temp_hint = get_string("show-advisees-hint", 'block_obu_learnanalytics');
            $atts = array("type" => "button", "value" => $temp, "title" => $temp_hint, "class" => "summ-show-button", "id" => "obula_show_advisees", "onclick" => "showAdvisees('Show', '$studentNumber')");
            $advisee_button_html = html_writer::empty_tag($tag_name, $atts);
        }
        // Values for boxes
        $last_pgm_code = "";
        $last_pgm = "";
        if (array_key_exists("obula_last_tutor_grid_pgm", $userPrefs)) {
            $last_pgm_code = $userPrefs["obula_last_tutor_grid_pgm"];
            $last_pgm = $last_pgm_code;
        }
        if (array_key_exists("obula_last_tutor_grid_pgm_desc", $userPrefs)) {
            $last_pgm = $userPrefs["obula_last_tutor_grid_pgm_desc"];
        }

        // Put a div around everything we want to move
        // Actually start with nothing visible/enabled and javascript can enable the ones for the correct size
        $atts = array("id" => "obula_staff_heading", "style" => "display: none");
        $out .= html_writer::start_tag("panel", $atts);
        $out .= html_writer::start_tag("div");
        $temp2 = get_string("staff-dash-title", 'block_obu_learnanalytics');
        $out .= html_writer::tag("h5", $temp2 . "   " . $links);
        // Output both OK and failed tags for js to update
        $out .= html_writer::tag("span", $message);

        // Now the actual buttons etc, use a table with 2 columns for line up
        $out .= "<table><tr>";
        $out .= "<td>" . $show_button_html . "</td>";
        $out .= "<td>";  //For Programme code etc
        $out .= html_writer::tag("input disabled", null, array("type" => "text", "value" => $last_pgm_code, "id" => "obula_show_pgm_code", "class" => "summ-show-id", "style" => "max-width: 75%;box-sizing:border-box;display:block"));
        $out .= "</td>";
        $out .= "</tr>";
        // Now show student and programme
        $out .= "<tr>";
        $out .= "<td>" . $become_button_html . "</td>";
        $out .= "<td>";
        // TODO PROTECT AGAINST SQL INJECTION
        // onclick" => "showBecomeView('T', 'obula_show_stud_no')"
        $out .= html_writer::empty_tag("input", array("type" => "text", "id" => "obula_show_stud_no", "class" => "summ-show-id", "style" => "max-width: 75%;box-sizing:border-box;display:block"));
        $out .= "</td>";
        $out .= "</tr>";

        // Now show advisees button
        $out .= "<tr>";
        $out .= "<td colspan='2'>" . $advisee_button_html . "</td>";
        $out .= "</tr>";
        $out .= "</table>";

        // Now tags for possible error message
        // TODO mouseover or something for admins to get full error
        $out .= html_writer::end_tag("div");
        // error tags are in a div
        $out .= self::connection_error_placeHolder(true, $USER->username);
        $out .= html_writer::end_tag("panel");

        // Below here it did all again but with different ids etc
        //
        // But all gone for now

        // Now other placeholders
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
        //$id_suffix = $smallPanel ? "_sml" : "_med";
        $isAdmin = is_siteadmin() || $username == "p0090268";
        $class = ($isAdmin) ? "error error-tip" : "error";
        $out = html_writer::start_tag("div", array("class" => $class, "id" => "obula_cc_errordiv", "style" => "display: none"));
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
     * Initial summary Dashboard for a Advisor, showing their advisees
     *
     * @return html
     */
    public function advisor_dashboard($advisorStaffNumber)
    {
        $out = '';
        $out .= html_writer::start_tag("div");
        $out .= self::advisees_grid($advisorStaffNumber);
        $out .= html_writer::end_tag("div");

        $out .= html_writer::empty_tag("br");
        //$out .= html_writer::empty_tag("br");

        //$out .= self::student_charts(true, true, null);

        // For now just put it below the student charts
        //$out .= self::student_marks_placeholder();

        // ditto scatter chart
        //TODO $out .= self::student_marks_v_engagement();

        return $out;
    }

    /**
     * Render the grid for list of advisees
     *
     * @see ?? for the expected structure
     * @return string
     */
    public function advisees_grid($staffNumber)
    {
        $util_dates = new \block_obu_learnanalytics\util\date_functions();
        $curl_common = new \block_obu_learnanalytics\guzzle\common();
        $outScripts = "";
		$scriptUrl = new moodle_url('/blocks/obu_learnanalytics/scripts/common.js?version=1.12.6');
		$outScripts .= html_writer::script(null, $scriptUrl);

        // Now the main one that we always want to load
        $scriptUrl = new moodle_url('/blocks/obu_learnanalytics/scripts/advisees_grid.js?version=1.12.6');
        $outScripts .= html_writer::script(null, $scriptUrl);
        // End of scripts


        $outParams = html_writer::start_tag('div');
        $outParams = html_writer::start_tag('div', array("id" => "obula_control_params_parent", "class" => "parameters", style => "display: flex; align-items: center;"));

        $outParams .= html_writer::start_tag('table id=obula-advisee-params-grid');
        // Placeholder for date_controls.php
        $outParams .= html_writer::empty_tag("td", array("id" => "obula_semester_control_cell", "class" => "parameters"));
        $outParams .= html_writer::empty_tag("td", array("id" => "obula_week_control_cell", "class" => "parameters"));

        $outParams .= html_writer::end_tag('table');


        // ******* Placeholder for a later release ******* //

        // Build the URL for the image using Moodle's base URL.
        $imgurl = $CFG->wwwroot . '/blocks/obu_learnanalytics/pix/attendance_matrix.png';
        $img = html_writer::empty_tag('img', array(
            'src'    => $imgurl,
            'alt'    => 'Attendance Matrix Icon',
            'title'  => 'View Attendance Matrix',  // Tooltip text.
            'width'  => 50,
            'height' => 50,
        ));

        // Wrap the image element in a div container
        $outParams .= html_writer::tag('div', $img, array(
            'id'    => 'obula_launch_attendance_matrix',
            'class' => 'parameters',
            'onclick' => "renderAttendanceMatrix('{$semester}','{$student}');"

        ));

        $outParams .= html_writer::end_tag('div');

        $outParams .= html_writer::end_tag('div');

        // Now we need table for Advisees Grid on the left
        $outPlaceHolders = '';
        $outPlaceHolders .= html_writer::start_tag('div');
        $outPlaceHolders .= html_writer::start_tag('table', array('id' => 'obula_advisee_parent_grid'));

        $outPlaceHolders .= html_writer::start_tag("tr");
        $outPlaceHolders .= html_writer::start_tag("td", array("id" => "obula_advisee_grid_div", "style" => "vertical-align: top"));
        $outPlaceHolders .= html_writer::end_tag("td");

        // $outPlaceHolders .= html_writer::start_tag('table', array("id" => "obula_advisee_grid_table"));
        // $outPlaceHolders .= html_writer::start_tag("tr");
        // // Can't get align top to work at the moment so set the max height - but that didn't work either
        // $outPlaceHolders .= html_writer::start_tag("td", array('style' => 'vertical-align: top; max-height: 18px'));
        // $chartAtts = array("href" => "javascript:hideCharts(false)", "id" => "obula_chart_hide", "class" => "chart-links");
        // $chartAtts['style'] = "display:none";
        // $outPlaceHolders .= html_writer::tag('a', 'Hide Chart', $chartAtts);
        // $outPlaceHolders .= "&nbsp";
        // $outPlaceHolders .= html_writer::end_tag("td");

        // $outPlaceHolders .= html_writer::end_tag("tr");

        // $outPlaceHolders .= html_writer::start_tag("tr");
        // $outPlaceHolders .= html_writer::start_tag("tr");
        // $outPlaceHolders .= html_writer::end_tag("td");
        // $outPlaceHolders .= html_writer::end_tag("tr");
        // $outPlaceHolders .= html_writer::end_tag('table');
        $outPlaceHolders .= html_writer::start_tag('table', array("id" => "obula_advisee_grid2_table"));
        $outPlaceHolders .= html_writer::start_tag("tr");
        $outPlaceHolders .= html_writer::start_tag("td", array("id" => "obula_advisee_grid2_div", "style" => "vertical-align: top"));
        $outPlaceHolders .= html_writer::end_tag("tr");
        $outPlaceHolders .= html_writer::end_tag('div');

        $out = $outScripts . $outParams . $outPlaceHolders;
        return $out;
    }

    /**
     * Renders the Tutors dashboard with comparison grid and placeholders for charts
     * called from self::staff_dashboard_summary and become_students_tutor.php
     *
     * @param  string  $defaultProgramme The default programme code for the tutor
     * @param  boolean $subDashboard     True if called from SSC fashboard
     * @return string                    HTML to render
     */
    public function tutor_dashboard(string $defaultProgramme, bool $subDashboard, string $studentNumber = null, string $type)
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
        $curl_common = new \block_obu_learnanalytics\guzzle\common();
        $outScripts = "";
        if (!$subDashboard) {
            // Only loaded if it's not a subDashboard as parent should have loaded these
            $scriptUrl = new moodle_url('/blocks/obu_learnanalytics/scripts/common.js?version=1.12.6');
            $outScripts .= html_writer::script(null, $scriptUrl);
        }

        // Now the main one that we always want to load
        $scriptUrl = new moodle_url('/blocks/obu_learnanalytics/scripts/tutor_grid.js?version=1.12.6');
        $outScripts .= html_writer::script(null, $scriptUrl);



        // So now output some selection and sorting criteria
        // in a table
        $outParams = html_writer::start_tag('div');
        $outParams .= html_writer::start_tag('table id=obula-tutor-params-grid');

        $context = $this->page->context;

        $outParams .= html_writer::start_tag("tr", array("class" => "parameters", "style" => "min-width:100px"));
        // So get the data we need
        $outParams .= html_writer::start_tag('td', array("class" => "parameters"));
        // Get the current week to show  (This is what is on the server)
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

        // Now study stages (replaced by Level)
        // Now Module Level, no point in calling for them
        $options = array(
            "*" => "All",
            "0" => "Level 0",
            "1" => "Level 1",
            "2" => "Level 2",
            "3" => "Level 3",
            "4" => "Level 4",
            "5" => "Level 5",
            "6" => "Level 6",
            "7" => "Level 7",
            "8" => "Level 8"
        );
        // Just render them all, post load sorts it out
        $outParams .= html_writer::start_tag('td', array("class" => "parameters"));
        $outParams .= html_writer::tag('label', 'Level', array('for' => 'selModLevel', 'style' => 'min-width:100px'));
        $selectAtts = array("name" => "ModLevels", "id" => "selModLevel", "onchange" => "modLevelChanged()", "style" => "min-width:160px;max-width:160px");

        $outParams .= html_writer::start_tag('select', $selectAtts);
        $count = 0;
        foreach ($options as $key => $data) {
            $atts = array("value" => "$key");
            if (++$count == 1) {
                $atts['selected'] = 'selected';
            }
            $outParams .= html_writer::tag("option", $data, $atts);
        }

        $outParams .= html_writer::end_tag("select");
        
        $outParams .= html_writer::end_tag("td");

        // Placeholder for date_controls.php
        $outParams .= html_writer::empty_tag("td", array("id" => "obula_semester_control_cell", "class" => "parameters"));

        $outParams .= html_writer::end_tag("tr");

        // Now some more
        $outParams .= html_writer::start_tag("tr", array("class" => "parameters"));

        // Campus
        $outParams .= html_writer::start_tag('td', array("class" => "parameters"));
        $outParams .= html_writer::tag('label', 'Campus', array('for' => 'selCampusCode', 'style' => 'min-width:100px'));
        $selectAtts = array("name" => "Campus", "id" => "selCampusCode", "onchange" => "campusCodeChanged()");
        $outParams .= html_writer::start_tag('select', $selectAtts);
        $params = 'tutor/allCampus/';
        $activeCampus = $curl_common->send_request($params);
        foreach ($activeCampus as $key => $data) {
            $atts = array("value" => "$key", "title" => $data["campus_code"]);
            if ($key == '*') {
                $atts['selected'] = 'selected';
            }
            $outParams .= html_writer::tag("option", $data["campus"], $atts);
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
        $outParams .= html_writer::empty_tag("td", array("id" => "obula_week_control_cell", "class" => "parameters"));
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
        // // Rather than try and load the chart on load, just give a show link
        // $chartAtts = array("href" => "javascript:showChart()", "id" => "obula_chart_show", "class" => "chart-links");
        // $chartAtts['style'] = "display:none";
        // $outPlaceHolders .= html_writer::tag('a', 'Chart engagement', $chartAtts);
        // To make things simpler, add an an empty img
        // TODO revamp - remove extra cell that housed chart
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
        $scriptUrl = new moodle_url('/blocks/obu_learnanalytics/scripts/student_charts.js?version=1.12.6');
        $outScripts .= html_writer::script(null, $scriptUrl);
        // End of scripts

        $out = $outScripts;

        // If we are a student then there is some more to output before the charts
        if (!$fromtutordb) {
            $curl_common = new \block_obu_learnanalytics\guzzle\common();
            $advisorDetails = $curl_common->get_academic_advisor($USER->username, '202409');      //TODO needs to pass semester properly
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
        $out .= self::student_chart_placeholders_v2(!$fromtutordb, $hide);

        return $out;
    }



    public function student_chart_placeholders_v2(bool $outputCohortPlaceHolder, bool $hide)
    {

        $out = "";
        $atts = array('id' => 'obula_studentGraphs_div');
        if ($hide) {
            $atts['style'] = 'display: none';
        }
        $out .= html_writer::start_tag('div', $atts);

            // ──────────────────────────────────────────────────
            // (1) Engagement + Radios in one horizontal row
            // ──────────────────────────────────────────────────
            $out .= html_writer::start_tag('div', [
                'id' => 'vleEngagement',
                'style' => 'display: flex; justify-content: center; align-items: center; margin-top: 20px;'
            ]);

                // A) Engagement Chart Container (left side)
                $out .= html_writer::start_tag('div', [
                    'id' => 'studentChartVLEEngagementContainer',
                    'style' => 'min-width:60%; margin-top: 20px; display: flex; justify-content: center; align-items: center;'
                ]);
                    $out .= html_writer::tag('canvas', '', [
                        'id' => 'studentChartVLEEngagement',
                        'style' => 'display:block;' 
                    ]);
                $out .= html_writer::end_tag('div');
                // B) Radios (right side)
                $out .= html_writer::start_tag('div', [
                    'id' => 'radiosForVLEEngagement',
                    // Use flex-direction: column to stack the radio buttons vertically
                    'style' => 'margin-left: 20px; display: flex; flex-direction: column;'
                ]);
                    // Duration
                    $out .= html_writer::start_tag('label');
                    $out .= html_writer::empty_tag('input', [
                        'type' => 'radio',
                        'name' => 'vleEngagementRadio',
                        'value' => 'vleduration',
                        'checked' => 'checked',
                        'onclick' => 'radioSwitch(this.value)'
                    ]);
                    $out .= 'Duration';
                    $out .= html_writer::end_tag('label');

                    // Visits
                    $out .= html_writer::start_tag('label');
                    $out .= html_writer::empty_tag('input', [
                        'type' => 'radio',
                        'name' => 'vleEngagementRadio',
                        'value' => 'vlesessions',
                        'onclick' => 'radioSwitch(this.value)'
                    ]);
                    $out .= 'Visits';
                    $out .= html_writer::end_tag('label');
                    // Page Views
                    $out .= html_writer::start_tag('label');
                    $out .= html_writer::empty_tag('input', [
                        'type' => 'radio',
                        'name' => 'vleEngagementRadio',
                        'value' => 'vleviews',
                        'onclick' => 'radioSwitch(this.value)'
                    ]);
                    $out .= 'Page Views';
                    $out .= html_writer::end_tag('label');
                     // Then add a button underneath
                $out .= html_writer::start_tag('div', [
                    'style' => 'margin-top: 10px;' // some spacing from the radios
                ]);

                // This creates a <button> with label "By Module" and some inline CSS
                $out .= html_writer::tag('button', 'By Module', [
                    'id' => 'byModuleButton',
                    'type' => 'button',
                    'onclick' => 'showModuleEng_v2()',
                    'style' => 'padding: 10px 16px; background-color: #d10373; color: #fff; border: none; border-radius: 4px; cursor: pointer;'
                ]);

                $out .= html_writer::end_tag('div');
                $out .= html_writer::end_tag('div'); // end radiosForVLEEngagement
               
            $out .= html_writer::end_tag('div'); // end vleEngagementAndRadiosRow
        
            // ──────────────────────────────────────────────────
            // (1.5) Engagement by Module
            // ──────────────────────────────────────────────────
            $out .= html_writer::start_tag('div', [
                'id' => 'vleEngagementByModule',
                'style' => 'display: none; justify-content: center; align-items: center; margin-top: 20px;'
            ]);

                // A) Engagement Chart Container (left side)
                $out .= html_writer::start_tag('div', [
                    'id' => 'studentChartEngagementByModuleContainer',
                    'style' => 'min-width:60%; margin-top: 20px; display: flex; justify-content: center;'
                ]);
                    $out .= html_writer::tag('canvas', '', [
                        'id' => 'studentChartEngagementByModule',
                        'style' => 'display:block;' 
                    ]);
                $out .= html_writer::end_tag('div');
            $out .= html_writer::end_tag('div'); // end EngagementbyModule row
        

            // ──────────────────────────────────────────
            // (2) Attendance chart
            // ──────────────────────────────────────────

            $out .= html_writer::start_tag('div', [
                'id' => 'attendancerow',
                'style' => 'display: flex; justify-content: center; align-items: center; margin-top: 20px;'
            ]);
                $out .= html_writer::start_tag('div', [
                    'id' => 'studentChartAttendanceContainer',
                    'style' => 'min-width:60%; margin-top: 20px; display: flex; justify-content: center;'
                ]);
                    $out .= html_writer::tag('canvas', '', [
                        'id' => 'studentChartAttendance',
                        'width' => '200',
                        'height' => '100',
                        'style' => 'display:block;'
                    ]);
                    
                $out .= html_writer::end_tag('div');
                $out .= html_writer::start_tag('div', [
                    'id' => 'radiosForEngagement',
                    // Use flex-direction: column to stack the radio buttons vertically
                    'style' => 'margin-left: 20px; display: flex; flex-direction: column;'
                ]);
                    // Line Chart
                    $out .= html_writer::start_tag('label');
                    $out .= html_writer::empty_tag('input', [
                        'type' => 'radio',
                        'name' => 'attendanceRadio',
                        'value' => 'attperc_linechart',
                        'checked' => 'checked',
                        'onclick' => 'radioSwitch(this.value)'
                    ]);
                    $out .= 'Line Chart';
                    $out .= html_writer::end_tag('label');

                     // Bar Chart
                     $out .= html_writer::start_tag('label');
                     $out .= html_writer::empty_tag('input', [
                         'type' => 'radio',
                         'name' => 'attendanceRadio',
                         'value' => 'attperc_barchart',
                        'onclick' => 'radioSwitch(this.value)'
                     ]);
                     $out .= 'Bar Chart';
                     $out .= html_writer::end_tag('label');
                $out .= html_writer::end_tag('div');    // End radios
            $out .= html_writer::end_tag('div'); // end attendancerow

            // ──────────────────────────────────────────
            // (3) eLibrary (EzProxy) chart
            // ──────────────────────────────────────────
            $out .= html_writer::start_tag('div', [
                'id' => 'eLibEngagement',
                'style' => 'display: flex; justify-content: center; align-items: center; margin-top: 20px;margin-bottom:20px;'
            ]);

                // A) Engagement Chart Container (left side)
                $out .= html_writer::start_tag('div', [
                    'id' => 'studentChartELibEngagementContainer',
                    'style' => 'min-width:60%; margin-top: 20px; display: flex; justify-content: center;'
                ]);
                    $out .= html_writer::tag('canvas', '', [
                        'id' => 'studentChartELibEngagement',
                        'style' => 'display:block;' 
                    ]);
                $out .= html_writer::end_tag('div');
                // B) Radios (right side)
                $out .= html_writer::start_tag('div', [
                    'id' => 'radiosELibEngagement',
                    // Use flex-direction: column to stack the radio buttons vertically
                    'style' => 'margin-left: 20px; display: flex; flex-direction: column;'
                ]);
                    // Duration
                    $out .= html_writer::start_tag('label');
                    $out .= html_writer::empty_tag('input', [
                        'type' => 'radio',
                        'name' => 'eLibEngagementRadio',
                        'value' => 'ezduration',
                        'checked' => 'checked',
                        'onclick' => 'radioSwitch(this.value)'
                    ]);
                    $out .= 'Duration';
                    $out .= html_writer::end_tag('label');

                    // Visits
                    $out .= html_writer::start_tag('label');
                    $out .= html_writer::empty_tag('input', [
                        'type' => 'radio',
                        'name' => 'eLibEngagementRadio',
                        'value' => 'ezsessions',
                        'onclick' => 'radioSwitch(this.value)'
                    ]);
                    $out .= 'Visits';
                    $out .= html_writer::end_tag('label');
                    // Page Views
                    $out .= html_writer::start_tag('label');
                    $out .= html_writer::empty_tag('input', [
                        'type' => 'radio',
                        'name' => 'eLibEngagementRadio',
                        'value' => 'ezsize',
                        'onclick' => 'radioSwitch(this.value)'
                    ]);
                    $out .= 'Downloaded (MB)';
                    $out .= html_writer::end_tag('label');
                $out .= html_writer::end_tag('div'); // end radiosForEngagement
            $out .= html_writer::end_tag('div'); // end engagementAndRadiosRow
        


        $out .= html_writer::end_tag('div'); // End of obula_studentGraphs_div
        // Return the output
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
        $types = array("vle", "att", "ez"); //, "loans", "att");
        $buttonsData = array();
        $buttonsData[] = array("vleduration", "Duration", "vlesessions", "Visits", "vleviews", "Page Views");
        $buttonsData[] = array("attpercline", "Line Chart", "attpercbar", "Bar Chart");
        $buttonsData[] = array("ezduration", "Duration", "ezsessions", "Visits", "ezsize", "Downloaded (MB)");
        //$buttonsData[] = array("loansline", "Line Chart", "loansbar", "Bar Chart", "loanscomb", "Combined");
        //$buttonsData[] = array("attduration", "Duration", "attsessions", "Lectures");
        $noCharts = 3; // Was a parameter once

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
            $curl_common = new \block_obu_learnanalytics\guzzle\common();

            global $USER;
            global $SESSION;
            $outScripts = "";
            if (!$subDashboard) {
                $scriptUrl = new moodle_url('/blocks/obu_learnanalytics/scripts/common.js?version=1.12.6');
                $outScripts .= html_writer::script(null, $scriptUrl);
            }
            $scriptUrl = new moodle_url('/blocks/obu_learnanalytics/scripts/student_dashboard.js?version=1.12.6');
            $outScripts .= html_writer::script(null, $scriptUrl);
            // End of scripts

            $out = $outScripts;
            $out .= self::modal_any_popup();           // For Help explanation

            //Want to use Google Material fonts
            //<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
            // see this for icons https://material.io/resources/icons/?icon=flight_takeoff&style=baseline 
            $out .= html_writer::empty_tag("link", array("rel" => "stylesheet", "href" => "https://fonts.googleapis.com/icon?family=Material+Icons"));
            // for fun try         $out .= html_writer::tag("i", "face", array("class" => "material-icons"));

            $advisorDetails = $curl_common->get_academic_advisor($USER->username,'202409');      //TODO needs to pass semester not hardcoded
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
                // Next will not work until we change the WS to take the semester
                // But I don't think we want to show this anymore
                $params = "student/cohorteng/$programme/*/*/$weeks/$simpleCurrent/";
                $curl_common = new \block_obu_learnanalytics\guzzle\common();
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
                    $imageName = "ArrowDown";
                    break;
                case 'Green':
                    $imageName = "ArrowUp";
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
