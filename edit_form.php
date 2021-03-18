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
 * Form for editing OBU Learning Analytics block instances.
 *
 * @package     block_obu_learnanalytics
 * @copyright   2021 Ken Burch <ken.burch@brookes.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_obu_learnanalytics_edit_form extends block_edit_form
{
    protected function specific_definition($mform)
    {
        global $DB;

        // Fields for editing personal Learning Analytics settings.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block_obu_learnanalytics'));

        $mform->addElement('selectyesno', 'config_demomode', get_string('demomode', 'block_obu_learnanalytics'));
        $mform->setDefault('config_demomode', 0);

        $mform->addElement('selectyesno', 'config_ignoressc', get_string('ignoressc', 'block_obu_learnanalytics'));
        $mform->setDefault('config_ignoressc', 0);
    }
}
