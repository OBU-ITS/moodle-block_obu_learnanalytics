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
 * The tutor_programme_changed event.
 *
 * @package     block_obu_learnanalytics
 * @copyright   2020 Ken Burch <ken.burch@brookes.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_obu_learnanalytics\event;
defined('MOODLE_INTERNAL') || die();
/**
 * The tutor_programme_changed event class.
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
class tutor_programme_changed extends \core\event\base
{
    protected function init()
    {
        $this->data['crud'] = 'r'; // c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }
 
}
