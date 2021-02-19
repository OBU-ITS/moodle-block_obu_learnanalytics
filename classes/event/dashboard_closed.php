<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
 
/**
 * The dashboard_closed event.
 *
 * @package     block_obu_learnanalytics
 * @copyright   2020 Ken Burch <ken.burch@brookes.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_obu_learnanalytics\event;
defined('MOODLE_INTERNAL') || die();
/**
 * The dashboard_closed event class.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - PUT INFO HERE
 * }
 *
 * @since     Moodle 3.8.2
 * @copyright 2021 Ken Burch
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class dashboard_closed extends \core\event\base
{
    protected function init() {
        $this->data['crud'] = 'r'; // c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_OTHER;
        //$this->data['other'] = json_encode(array("HTTP_USER_AGENT" => $_SERVER['HTTP_USER_AGENT']));
    }


 
    // public static function get_name() {
    //     return get_string('dashboard_closed', 'block_obu_learnanalytics');
    // }
 
    // public function get_description() {
    //     return "The user with id {$this->userid} looked at Tutor Grid for ???.";
    // }
 
    // public function get_url() {
    //     return new \moodle_url('....', array('parameter' => 'value', ...));
    // }
}
