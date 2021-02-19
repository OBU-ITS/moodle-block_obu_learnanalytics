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

use DateInterval;
use DateTime;

class date_functions
{
    /**
     * Gets the academic year and week for today
     *
     * @return array            year as int, week_number as int, first_day_week as DateTime object
     */
    public function get_current_week()
    {
        date_default_timezone_set('UTC');
        //Unix $today = strtotime('midnight');     // Today's date without timestamp
        $today = new DateTime();
        //$today = new DateTime('2020-12-16');        // For debug only
        return self::get_week_for_date($today);
    }

    public function get_semesters($maxDate = null)
    {
        $semesters = array();           //TODO find where I can pick this up OR move to config
        $semesters[] = array('code' => '201809', 'from' => new DateTime('2018-09-17 00:00'), 'to' => new DateTime('2018-12-20 23:59'), 'label' => "2018/19 Sem 1");
        $semesters[] = array('code' => '201901', 'from' => new DateTime('2019-01-21 00:00'), 'to' => new DateTime('2019-05-18 23:59'), 'label' => "2018/19 Sem 2");
        $semesters[] = array('code' => '201909', 'from' => new DateTime('2019-09-16 00:00'), 'to' => new DateTime('2019-12-19 23:59'), 'label' => "2019/20 Sem 1");
        $semesters[] = array('code' => '202001', 'from' => new DateTime('2020-01-20 00:00'), 'to' => new DateTime('2020-05-16 23:59'), 'label' => "2019/20 Sem 2");
        $semesters[] = array('code' => '202009', 'from' => new DateTime('2020-09-14 00:00'), 'to' => new DateTime('2020-12-19 23:59'), 'label' => "2020/21 Sem 1");
        $semesters[] = array('code' => '202101', 'from' => new DateTime('2021-01-18 00:00'), 'to' => new DateTime('2021-05-15 23:59'), 'label' => "2020/21 Sem 2");
        $semesters[] = array('code' => '202109', 'from' => new DateTime('2021-09-13 00:00'), 'to' => new DateTime('2021-12-17 23:59'), 'label' => "2021/22 Sem 1");
        $semesters[] = array('code' => '202201', 'from' => new DateTime('2022-01-17 00:00'), 'to' => new DateTime('2022-05-14 23:59'), 'label' => "2021/22 Sem 2");
        //Seems crude but now let's delete any past $maxDate
        if ($maxDate != null) {
            $currentSemesters = array();
            foreach ($semesters as $semester) {
                if ($maxDate < $semester['from']) {
                    break;
                }
                $currentSemesters[] = $semester;
            }
            return $currentSemesters;
        }

        return $semesters;
    }
    
    /**
     * Gets the monday for the last week in the semester
     *
     * @param  string   $semesterCode   The code for example 202001
     * @return array    year as int, week_number as int, first_day_week as DateTime object
     */
    public function get_semester_wc($semesterCode)
    {
        $semesters = self::get_semesters();         // TODO store in cache if we can
        $codeCol = array_column($semesters, "code");
        $key = array_search($semesterCode, $codeCol);
        if ($key === null || $key === false) {
            throw new Exception("Error matching semester", 1);
        }
        $semester = $semesters[$key];
        $endDate = $semester["to"];
        return self::get_week_for_date($endDate, true);
    }

    public function get_current_semester_start()
    {
        $semesters = self::get_semesters();

        date_default_timezone_set('UTC');
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        foreach ($semesters as $semester) {
            if ($today >= $semester[1] && $today <= $semester[2]) {
                return self::get_week_for_date($semester[0]);
            }
        }
        return null;
    }
    
    /**
     * Not intended for use in project but as a tool to calculate academic week for a date
     * Can be used from debug console - self::get_week_for_ymd(2020,1,29);
     *
     * @param  int $year        Year for calculation
     * @param  int $month       Month for calculation
     * @param  int $day         Day for calculation
     * @return array            year as int, week_number as int, first_day_week as DateTime object
     */
    public function get_week_for_ymd(int $year, int $month, int $day)
    {
        date_default_timezone_set('UTC');
        $date = new DateTime();
        $date->setDate($year, $month, $day);
        return self::get_week_for_date($date);
    }
    
    /**
     * Gets the academic year and week for a specified datetime
     *
     * @param  DateTime $dateIn A DateTime object
     * @param  bool $incompleteWeek, true if want the actual W/C rather than the previous complete week
     * @return array            year as int, week_number as int, first_day_week as DateTime object
     */
    public function get_week_for_date($dateIn, $incompleteWeek = false)
    {
        // Data is fixed so calculate it from current date
        // Dates in most languages are horrible - PHP included
        // So there is a Unix style long int which is seconds since 1/1/1970
        // And there's a date object that has UTC and hopefully deals with leap years
        // So let's try doing it all using the Date Object
        // But week day number only works with Unix format
        // Not sure which is faster but performance isn't an issue
        date_default_timezone_set('UTC');
        $dateIn->setTime(0, 0, 0);
        $weekDayNumber = date('N', $dateIn->getTimestamp()); // ISO addition 1 = Monday and 7 = Sunday
        // So now work out the previous W/C Monday for the date
        // TODO test/cope with say the 2nd August or the 8th etc
        $days = ($incompleteWeek) ? 0 : 7;
        $days = $days + $weekDayNumber - 1;
        if ($days == 0) {
            $prevMonday = $dateIn;
        } else {
            $interval = 'P' . $days . 'D';
            $prevMonday = $dateIn->sub(new DateInterval($interval));
        }
        // So now work out what week 1 was and the actual Monday so I can work from that
        // First work out if it's August last year or this, if we are August or great than it's this year
        $thisMonth = date("n", $dateIn->getTimestamp());
        $academicYear = date("Y", $dateIn->getTimestamp());
        if ($thisMonth < 8) {
            $academicYear--;
        }
        // So now create a 1st Aug date for the academic year
        //$aug1st = mktime(0, 0, 0, 8, 1, $academicYear);
        $aug1st = new DateTime("$academicYear-08-01");
        // Normally Brookes academic weeks start on a Monday
        // Apart from Week 1 that starts on the 1st August if it's a weekday
        // or the Monday if it's the weekend.
        $aug1stWeekDay = date("N", mktime(0, 0, 0, 8, 1, $academicYear)); // Use the ISO day where 1 = Monday, 7 = Sunday
        // Now work out the actual Monday for week 1 (even if week 1 starts mid week)
        if ($aug1stWeekDay == 0) {
            $aug1stMonday = $aug1st;
        } elseif ($aug1stWeekDay >= 6) { // The weekend so go forward
            $interval = 'P' . (8 - $aug1stWeekDay) . 'D';
            $aug1stMonday = $aug1st->add(new DateInterval($interval));
        } else {
            $interval = 'P' . (1 - $aug1stWeekDay) * -1 . 'D';
            $aug1stMonday = $aug1st->sub(new DateInterval($interval));
        }
        $dateInterval = $aug1stMonday->diff($prevMonday);
        $weekNumber = ($dateInterval->days / 7) + 1;

        $last_year = (int) $academicYear;
        $last_week = (int) $weekNumber;
        $last_fdw = $prevMonday;

        $result = array();
        $result["year"] = $last_year;
        $result["week_number"] = $last_week;
        $result["first_day_week"] = $last_fdw;

        return $result;
    }

    /**
     * Found on https://stackoverflow.com/questions/3243900/convert-cast-an-stdclass-object-to-another-class
     * But couldn't get it to work (it was called cast)
     */
    public function fudge_cast($destination, $sourceObject)
    {
        if (is_string($destination)) {
            $destination = new $destination();
        }
        $sourceReflection = new ReflectionObject($sourceObject);
        $destinationReflection = new ReflectionObject($destination);
        $sourceProperties = $sourceReflection->getProperties();
        foreach ($sourceProperties as $sourceProperty) {
            $sourceProperty->setAccessible(true);
            $name = $sourceProperty->getName();
            $value = $sourceProperty->getValue($sourceObject);
            if ($destinationReflection->hasProperty($name)) {
                $propDest = $destinationReflection->getProperty($name);
                $propDest->setAccessible(true);
                $propDest->setValue($destination, $value);
            } else {
                $destination->$name = $value;
            }
        }
        return $destination;
    }

    /**
     * json_2_current_week
     * Sort out the date as going to JSON and back has lost the type
     * and I want it as a DateTime for calculations
     *
     * @param  mixed $jsonWeek
     * @return array with year, week_number and first_day_week
     */
    public function json_2_current_week($jsonWeek)
    {
        // Trying to not use specialchars for date as there shouldn't be any need
        // but for now check and decode if it is
        if (substr($jsonWeek, 0, 7) == "{&quot;") {
            $decoded = json_decode(htmlspecialchars_decode($jsonWeek));
        } else {
            $decoded = json_decode($jsonWeek);
        }

        $result = array();
        $result["year"] = $decoded->year;
        $result["week_number"] = $decoded->week_number;
        $strDate = $decoded->first_day_week->date; // This is not a date type, it's got mangled somewhere
        $phpDate = date_create_from_format('Y-m-d G:i:s.u', $strDate); //TODO, $decoded->first_day_week->timezone);
        //$newDate = new DateTime();
        //$newDate = fudgeCast($newDate, $decoded->first_day_week);
        //$newDate->setTime(0, 0, 0, 0);
        $result["first_day_week"] = $phpDate;

        return $result;
    }
    
    /**
     * Get an array of Dates and Weekdays (Monday, Tuesday...)
     * From a date for a number of days
     *
     * @param  array $current       Current week array
     * @param  int $fwdRange        Number of days forward needed
     * @param  int $backRange       Number of days back needed
     * @return array                Simple array of dates and weekdays
     */
    public function get_days_array(array $current, int $fwdRange, int $backRange = 0)
    {
        $daysArray = array();
        $date = clone($current["first_day_week"]);
        for ($loop = 0; $loop < $fwdRange; $loop++) {
            $data = array();
            $data["date"] = clone($date);
            $data["weekday"] = $date->format("l");
            array_push($daysArray, $data);          // Add to end
            $date->modify("+1 day");
        }
        if ($backRange > 0) {
            $date = clone($current["first_day_week"]);
            for ($loop = 0; $loop < $backRange; $loop++) {
                // Adjust first as we don't want current twice
                $date->modify("-1 day");
                $data = array();
                $data["date"] = clone($date);
                $data["weekday"] = $date->format("l");
                array_unshift($daysArray, $data);       // Add to beginning
            }
        }
        return $daysArray;
    }

    /**
     * Calculates the last x weeks with week commencing dates
     * going back from the current week and coping with crossing the year beginning to week 52/53
     * but returns an array in ascending order
     *
     * @param  array $current       A week object to go back from
     * @param  integer $range       Number of weeks to get
     * @param  integer $skip        Number of weeks to skip, so a range of 13, skip 1 will get 13 weeks but not including this week
     * @return array                Any array of week arrays in ascending order (year, week_number, first_day_week)
     */
    public function get_weeks_array(array $current, int $range, int $skip)
    {
        $weeksArray = array();
        //$workWeek = $current;  It's OK it was passed by value

        // At the moment we haven't worried about 52/53 week years
        // TODO fix this
        // So loop for the number of needed weeks + how many we will then skip
        for ($loop = 0; $loop < $range + $skip; $loop++) {
            $fdw = $current["first_day_week"];
            $year = $current["year"];
            $week = $current["week_number"];
            $data = array();
            $data["first_day_week"] = $fdw->format("d-M-Y"); // TODO make it work as a date object
            $data["year"] = $year;
            $data["week_number"] = $week;
            // Use unshift to put them in so they are ascending for the return
            array_unshift($weeksArray, $data);
            // Now adjust for next loop
            $current = self::get_prev_week($current);
        }

        // Now remove the last x entries if skipping,
        // (didn't subtract earlier from week because of year end)
        while ($skip > 0) {
            unset($weeksArray[count($weeksArray) - 1]);
            $skip--;
        }
        return $weeksArray;
    }

    /**
     * Takes a week object (Year, Week and W/C Date and calculates the following week)
     * Done in PHP as javascript is bad at this and we need consistent calculations and object types
     * Will cope with 52/53 week problem TODO
     *
     * @param  array $current       A week object to go back from
     */
    public function get_next_week($current)
    {
        date_default_timezone_set('UTC');
        $fdw = clone $current["first_day_week"];
        //$fdw->setTime(0, 0, 0, 0);
        //$fdw->setDate($current["first_day_week"]->Date);
        $fdw->modify('1 week');
        $year = $current["year"];
        $week = $current["week_number"];
        $week++;
        return self::check_and_return_week($fdw, $year, $week);
    }

    /**
     * Takes a week object (Year, Week and W/C Date and calculates the previous week)
     * Done in PHP as javascript is bad at this and we need consistent calculations and object types
     * Will cope with 52/53 week problem TODO
     */
    public function get_prev_week($current)
    {
        date_default_timezone_set('UTC');
        $fdw = clone $current["first_day_week"];
        //$fdw->setTime(0, 0, 0, 0);
        //$fdw->setDate($current["first_day_week"]->DateTime);
        $fdw->modify('-1 week');
        $year = $current["year"];
        $week = $current["week_number"];
        $week--;
        return self::check_and_return_week($fdw, $year, $week);
    }

    public function check_and_return_week($fdw, $year, $week)
    {
        if ($week < 1) {
            $year--;
            $week = 53; // TODO or 52
            // ?? check if 53 ok and recalc fdw
        }
        if ($week > 53) {
            $year++;
            $week = 1;
            // ?? recalc fdw
        }
        if ($week == 53) {
            // Check ??
        }
        $result = array();
        $result["year"] = $year;
        $result["week_number"] = $week;
        $result["first_day_week"] = $fdw;

        return $result;
    }

    public function calculate_where_weeks($weeksArray)
    {
        // Now calculate a From clause using the Array
        $weeks = count($weeksArray);
        $whereRange = "(";
        if ($weeks === 1) {
            $whereRange .= "year = " . $weeksArray[0]["year"];
            $whereRange .= " AND week_number = " . $weeksArray[0]["week_number"];
        } elseif ($weeksArray[0]["year"] == $weeksArray[count($weeksArray) - 1]["year"]) {
            // Simple case it doesn't span the year
            $whereRange .= "year = " . $weeksArray[0]["year"];
            $whereRange .= " AND week_number >= " . $weeksArray[0]["week_number"];
            $whereRange .= " AND week_number <= " . $weeksArray[count($weeksArray) - 1]["week_number"];
        } else {
            // Spans the year
            $whereRange .= "(year = " . $weeksArray[0]["year"];
            $whereRange .= " AND week_number >= " . $weeksArray[0]["week_number"] . ")";
            $whereRange .= " OR (year = " . $weeksArray[count($weeksArray) - 1]["year"];
            $whereRange .= " AND week_number <= " . $weeksArray[count($weeksArray) - 1]["week_number"] . ")";
        }
        $whereRange .= ")";
        return $whereRange;
    }

    public function interprete_duration_code($durationCode)
    {
        switch ($durationCode) {
            case '4wks':
                $weeks = 4;
                break;
    
                case 'sem':
                    $semesters = self::get_semesters();
                    $weeks = 10;     //TODO
                    break;
               
            default:
            $weeks = 1;
        break;
        }
        return $weeks;
    }
    
    /**
     * createSimpleCurrentParam
     * Create urlencoded string from Year/Month/Date array
     * for use as a parameter to a web request
     *
     * @param  array $current
     * @return urlencoded string Y-m-d|yyyy|ww
     */
    public function createSimpleCurrentParam(array $current) {
        $simpleCurrent = $current["first_day_week"]->format("Y-m-d");
        $simpleCurrent .= "|" . $current["year"];
        $simpleCurrent .= "|" . $current["week_number"];
        return urlencode($simpleCurrent);
    }
} // End of class
