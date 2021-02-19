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
 * array_functions
 * Generic functions for processing arrays not specific to any page
 */
class array_functions
{

    /*public function __construct()
    {
    }
    */

    /*public function __destruct()
    {
    }
    */
    
    /**
     * Calculates the mean average for a data element in an array
     * with option to ignore zero rows
     *
     * @param  array   $array
     * @param  string  $columnName   The name of the field in the array to peform calculation on
     * @param  boolean $includeZeros
     * @return float
     */
    public function calculate_mean(array $dataArray, string $columnName, bool $includeZeros = true)
    {
        // Invalid syntax $total = array_sum($array["$columnName"]);
        $total = 0;
        $count = 0;
        \array_walk($dataArray, function ($data) use (&$total, &$count, $includeZeros, $columnName) {
            ;
            if ($includeZeros || $data["{$columnName}"] > 0) {
                $total += $data["{$columnName}"];
                $count++;
            }
        });
        return $total / $count;
    }
    
    /**
     * Calculates the median average for a data element in an array
     * with option to ignore zero rows
     *
     * @param  array   $array
     * @param  string  $columnName   The name of the field in the array to peform calculation on
     * @param  boolean $includeZeros
     * @return float
     */
    public function calculate_median(array $array, string $columnName, bool $includeZeros = true)
    {
        if (!$includeZeros) {
            $array = self::remove_zeros($array);
        }
        $rowCount = count($array);
        // now calculate median, if the number of rows is odd then it's the middle value
        // calculation is often quoted as (n+1) / 2, but arrays start from zero so don't add 1
        // otherwise the average of the middle 2
        $medianAvg = 0;
        if ($rowCount > 0) {
            $keys = array_keys($array);
            if ($rowCount % 2 == 0) {
                $med2 = $rowCount / 2;
                $med1 = $med2 - 1;
                $medianAvg = $array[$keys[$med1]]["$columnName"] + $array[$keys[$med1]]["$columnName"];
                $medianAvg = $medianAvg / 2;
            } else {
                $med1 = floor($rowCount / 2);
                $medianAvg = $array[$keys[$med1]]["$columnName"];
            }
        }
        return $medianAvg;
    }
    
    /**
     * calculate_quartile_positions
     * So far I've seen 4 different wasy of calculating this on the web, so put in a routine
     * so only 1 place to fix.  Going for simple to implement solution
     * and I only want the position that anything left of is considered first quartile
     *
     * @param  array   $array
     * @param  string  $columnName   The name of the field in the array to use
     * @param  boolean $includeZeros
     * @return array   Associative array with the Q1 and Q3 position to use in < and > tests for 1st/top quartile entries
     */
    public function calculate_quartile_positions(array $array, string $columnName, bool $includeZeros = true)
    {
        if (!$includeZeros) {
            $array = self::remove_zeros($array);
        }
        $rowCount = count($array);
        // now calculate q1, if the number of rows is odd then it's the middle value
        // otherwise the average of the middle 2
        // calculation is often quoted as (n+1) / 4, but arrays start from zero so will need to add, then divide, then subtract
        $q1pos = 0;
        $q3pos = 999999;
        if ($rowCount > 0) {
            $keys = array_keys($array);
            // So calculate the Q1 Position, just by rounding up I don't need to worry about divides with no remainders
            $q1pos = ceil(($rowCount + 1) / 4);       // Actual pos, not array pos yet
            // Don't repeat the calculation as I want the same no of entries in top and bottom quartiles
            $q3pos = $rowCount - $q1pos + 1;        // Actual pos, not array pos yet
            // now adjust use max to make sure we don't go negative
            $q1pos = max(0, $q1pos - 1);
            $q3pos = max(0, $q3pos - 1);
        }
        return array("q1_pos" => $q1pos, "q3_pos" => $q3pos);
    }

    /**
     * Remove rows from an array when a specified field is zero
     *
     * @param  array  $array
     * @param  string $columnName   The name of the field in the array to use
     * @return array  Resultant array with no zero rows
     */
    public function remove_zeros($array, string $columnName)
    {
        if (count($array) > 0) {          // Safety code
            return \array_filter($array, function ($data) {
                return $data["$columnName"] > 0;
            });
        } else {
            return $array;
        }
    }
} // End of class
