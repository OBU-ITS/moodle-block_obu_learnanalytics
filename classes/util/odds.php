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
 * Learning Analytics data for Oxford Brookes University Students and Tutors
 *
 * @package     block_obu_learnanalytics
 * @copyright   2020 Ken Burch <ken.burch@brookes.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_obu_learnanalytics\util;

class odds
{    
    /**
     * get_la_role
     * This method is to protect page from being called from outside moodle
     * and breaking security by impersonating a post
     * as well as deciding if it's an SSC, Tutor or Student
     * and checking logged in
     * if only 1 role is going to be valid then pass it to save it checking irrelevant options
     * Defaulst to just DIE's if there is a problem
     *
     * @param  string  $roleNeeded
     * @param  boolean $justDIE    False to return a null for failure
     * @return string  SSC, TUTOR, STUDENT or null if $justDIE false
     */
    public function get_la_role($roleNeeded = null, $justDIE = true)
    {
        global $USER;
        $role = null;
        require_login(null, false, null, false, false);     // 4th parameter stops it coming back
        defined('MOODLE_INTERNAL') || die();
        if ($USER == null || $USER->id == 0) {
            if (!$justDIE) {
                return null;
            }
            die("Not Authenticated");
        }
        if (!isset($role) && ($roleNeeded == null || $roleNeeded == 'SSC')) {
            global $DB;
            $course = $DB->get_record('course', array('idnumber' => 'SUBS_LA_SSCS'));   //TODO move to config
            if ($course) {
                $context = \context_course::instance($course->id);
                if ($context != null) {
                    if (is_enrolled($context, $USER->id, '', true)) {
                        $role = 'SSC';
                    }
                }
            }
        }
        if (!isset($role) && ($roleNeeded == null || $roleNeeded == 'TUTOR')) {
            global $DB;
            $course = $DB->get_record('course', array('idnumber' => 'SUBS_LA_TUTORS'));   //TODO move to config
            if ($course) {
                $context = \context_course::instance($course->id);
                if ($context != null) {
                    if (is_enrolled($context, $USER->id, '', true)) {
                        $role = 'TUTOR';
                    }
                }
            }
        }
        //TODO option to return 'STUDENT'
        if (!isset($role) && is_siteadmin()) {
            $role = $roleNeeded ?? 'TUTOR';   // If role passed admin can be it, else give Tutor
        }
        if (!isset($role) && $justDIE) {
            die("Permission Denied");
        }
        return $role;
    }

    /**
     * store_Parameters, mimics behavior of javascript storeParameters function
     * given the same name in the hope we find it when changing the js
     * So that may need changing if you change this one
     * Doesn't actualy store it, jus returns the formatted value
     *
     * @param  mixed $programme
     * @param  mixed $studyStage
     * @param  mixed $studentNumber
     * @param  mixed $studentName
     * @return string               The Value to store in the tag
     *
     */
    public function store_parameters($programme, $studyStage, $studentNumber, $studentName)
    {
        // TODO think about moving it to utility class
        $params = array($programme, $studyStage, $studentNumber, $studentName);
        // Don't think I need htmlspecialchars equiv, but if so https://stackoverflow.com/questions/1787322/htmlspecialchars-equivalent-in-javascript
        return json_encode($params);
    }

    // As the phpinfo from the Moodle server didn't include the PECL maths library
    // I've borrowed this from php.net
    /**
     * This user-land implementation follows the implementation quite strictly;
     * it does not attempt to improve the code or algorithm in any way. It will
     * raise a warning if you have fewer than 2 values in your array, just like
     * the extension does (although as an E_USER_WARNING, not E_WARNING).
     *
     * @param array $a
     * @param bool $sample [optional] Defaults to false
     * @return float|bool The standard deviation or false on error.
     */
    public function stats_standard_deviation(array $a, $sample = false)
    {
        $n = count($a);
        if ($n === 0) {
            trigger_error("The array has zero elements", E_USER_WARNING);
            return false;
        }
        if ($sample && $n === 1) {
            trigger_error("The array has only 1 element", E_USER_WARNING);
            return false;
        }
        $mean = array_sum($a) / $n;
        $carry = 0.0;
        foreach ($a as $val) {
            $d = ((double) $val) - $mean;
            $carry += $d * $d;
        };
        if ($sample) {
            --$n;
        }
        return sqrt($carry / $n);
    }

// End of class
}
