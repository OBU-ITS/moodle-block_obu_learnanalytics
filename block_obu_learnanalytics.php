<?php
/**
 * Creates the HTML for Learning Analytics
 * NOTE - Unlike report plugins, blocks does not use Index.php, instead needs this main block
 */
class block_obu_learnanalytics extends block_base
{

    /** @var string The name of the block */
    public $blockname = null;

    public function init()
    {
        $this->blockname = get_class($this);
        $this->title = get_string('obu_learnanalytics', $this->blockname);
    }

    /**
     * Allow the block to have a configuration page
     *
     * @return boolean
     */
    public function has_config()
    {
        return true;
    }
    
    /**
     * instance_allow_multiple
     * Stop them doing adding it multiple
     * @return void
     */
    public function instance_allow_multiple()
    {
        return false;   // Stop them adding it multiple times
    }
    
    /**
     * hide_header
     * Tell Moodle we are taking control of the header
     * @return true  Always
     */
    public function hide_header()
    {
        return true;
    }

    // public function get_content_for_output($output)
    // {
    //     return $output;
    // }

    public function get_content()
    {
        global $PAGE;
        $PAGE->requires->jquery();
        // Add some local config changes to user global as that seems to persist fine
        global $USER;
        if ($this->config != null) {
            $USER->ignoressc = $this->config->ignoressc;
            $USER->demomode = $this->config->demomode;
        }

        if ($this->content !== null) {
            return $this->content;
        }

        $renderer = $this->page->get_renderer($this->blockname);

        // Do some basic checks about the user
        $util_odds = new \block_obu_learnanalytics\util\odds();
        $laRole = $util_odds->get_la_role(null, false);    // Protects against attacks, wrong roles and everything

        $this->content = new stdClass;

        if (!isset($laRole)) {
            $this->content->text = $renderer->error_page('capability_error');
            return $this->content;
        }

        //TODO
        /*if ($db_student::$db_common->db_status != "OK") {
            $this->content->text = $renderer->error_page('edw_connect_error');
            return $this->content;
        }*/

        $this->content->footer = "<div id='obula_footer' style='display:none'>Data Currency</div>";
        switch ($laRole) {
            case ("SSC"):
                $this->content->text = $renderer->ssc_dashboard();
                break;
            case ("TUTOR"):
                // // TODO find way to pick up programme for Tutor
                // $pgm = "MBA";
                // if (strtoupper($USER->username) == "P0074883") {
                //     $pgm = "MSC-ASE";
                // }
                $this->content->text = $renderer->tutor_dashboard_summary();
                break;
            case ("STUDENT"):
                // This code is duplicated (nearly) in become_student.php
                $params = "student/programmes/$USER->username/";
                $curl_common = new \block_obu_learnanalytics\curl\common();
                $pgms = $curl_common->send_request($params);
                $pgm = $pgms[0]["programme_code"]; // TODO cope with zero and > 1
                $sname = $USER->firstname . ' ' . $USER->lastname;
                //$this->content->text = implode(':', $pgms[0]) . " ($pgm)";
                //return;
                try {
                    $this->content->text = $renderer->students_dashboard(false, $USER->username, $USER->firstname, $sname, $pgm);
                } catch (\Exception $ex) {
                    $this->content->text = $renderer->error_page('Error Creating Student Dashboard', $ex);
                    return;      // $this->content;
                }
                break;
            default:
                $this->content->text = $this->content->footer = "";
                break;
        }

        return;         //Not Needed? $this->content;
    }

    public function get_required_javascript()
    {
        //TODO - try using this
    }

    public function applicable_formats()
    {
        // Tried to use this to stop inappropriate users even seeing the block plugin to configure
        // But we don't have a context
        //global $USER;
        //$context = $this->page->context;
        // $hasAccess = has_capability('block/obu_learnanalytics:tutor_dashboard', $context)
        // //            || has_capability('block/obu_learnanalytics:student_dashboard', $context)
        //             || has_capability('block/obu_learnanalytics:ssc_dashboard', $context);
        // return array('site-index' => $hasAccess);
        return array('my' => true);
    }

} // End of class
