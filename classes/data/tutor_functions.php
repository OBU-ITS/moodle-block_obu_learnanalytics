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
 * @package   block_obu_learnanalytics
 * @copyright 2020 Ken Burch <ken.burch@brookes.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_obu_learnanalytics\data;

/**
 * tutor_functions
 * Functions for processing arrays or data for the tutor pages
 */
class tutor_functions
{
    public static $util_dates = null;
    
    /**
     * __construct
     * Instantiate needed util classes
     *
     * @return void
     */
    public function __construct()
    {
        if (self::$util_dates == null) {
            self::$util_dates = new \block_obu_learnanalytics\util\date_functions();
        }
    }

    /*public function __destruct()
    {
    }
    */
    
    public function sortAscAscCohort($a, $b)
    {
        return self::sortCmp($a["cohort_comparison_perc"], $b["cohort_comparison_perc"], $a["student_comparison_prev0_perc"], $b["student_comparison_prev0_perc"]);
    }

    public function sortAscDescCohort($a, $b)
    {
        return self::sortCmp($a["cohort_comparison_perc"], $b["cohort_comparison_perc"], $b["student_comparison_prev0_perc"], $a["student_comparison_prev0_perc"]);
    }

    public function sortDescDescCohort($a, $b)
    {
        return self::sortCmp($b["cohort_comparison_perc"], $a["cohort_comparison_perc"], $b["student_comparison_prev0_perc"], $a["student_comparison_prev0_perc"]);
    }

    public function sortDescAscCohort($a, $b)
    {
        return self::sortCmp($b["cohort_comparison_perc"], $a["cohort_comparison_perc"], $a["student_comparison_prev0_perc"], $b["student_comparison_prev0_perc"]);
    }

    public function sortAscAscStudent($a, $b)
    {
        return self::sortCmp($a["student_comparison_prev0_perc"], $b["student_comparison_prev0_perc"], $a["cohort_comparison_perc"], $b["cohort_comparison_perc"]);
    }

    public function sortAscDescStudent($a, $b)
    {
        return self::sortCmp($a["student_comparison_prev0_perc"], $b["student_comparison_prev0_perc"], $b["cohort_comparison_perc"], $a["cohort_comparison_perc"]);
    }

    public function sortDescDescStudent($a, $b)
    {
        return self::sortCmp($b["student_comparison_prev0_perc"], $a["student_comparison_prev0_perc"], $b["cohort_comparison_perc"], $a["cohort_comparison_perc"]);
    }

    public function sortDescAscStudent($a, $b)
    {
        return self::sortCmp($b["student_comparison_prev0_perc"], $a["student_comparison_prev0_perc"], $a["cohort_comparison_perc"], $b["cohort_comparison_perc"]);
    }

    public function sortEngagement($a, $b)
    {
        // sortCmp was built for 2 pairs, but we can use it by passing the first pair as equal
        return self::sortCmp(1, 1, $a["student_weighted_engagement"], $b["student_weighted_engagement"]);
    }

    /**
     * Compares 2 pairs of values, if the first pair are equal then the second set are compared
     * Responsibility of the caller to pass parameters appropriately to control Asc/Desc
     */
    public function sortCmp($a, $b, $c, $d)
    {
        if ($a == $b) {
            if ($c == $d) {
                return 0;
            }
            return ($c < $d) ? -1 : 1;
        }
        return ($a < $b) ? -1 : 1;
    }
    
    /**
     * sort_student_comparitives
     *
     * @param  mixed $comparitives  Passed by Reference (&)
     * @param  mixed $ascend1
     * @param  mixed $ascend2
     * @param  mixed $bycohortfirst
     * @return void
     */
    public function sort_student_comparitives(&$comparitives, $ascend1, $ascend2, $bycohortfirst)
    {
        $routine = "self::sort";
        $routine .= ($ascend1) ? "Asc" : "Desc";
        $routine .= ($ascend2) ? "Asc" : "Desc";
        $routine .= ($bycohortfirst) ? "Cohort" : "Student";
        uasort($comparitives, $routine);
    }
} // End of class
