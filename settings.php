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
 * Settings for the RSS client block.
 *
 * @package   block_obu_learnanalytics
 * @copyright 2020 Ken Burch
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

//  NOTE Since Moodle 2.4 you have to add a public function has_config() { return true; } to the main php file
//  If you change it then going to the admin index page forces the changes eg http://localhost/moodle/admin/index.php
//  But if you add or change language strings then you need to Purge the Language Strings Cache
//  If we want more than WS settings here - there is a admin_setting_configtextarea to play with

if ($ADMIN->fulltree) {   // also tried $hassiteconfig ||
    $settings->add(new admin_setting_heading(
        'block_obu_learnanalytics/settingsheader',
        get_string('ws_settings_header', 'block_obu_learnanalytics'),
        get_string('ws_settings_header_info', 'block_obu_learnanalytics')
    ));
    $settings->add(new admin_setting_configtext(
        'block_obu_learnanalytics/ws_root_url',
        get_string('ws_root_url', 'block_obu_learnanalytics'),
        get_string('ws_root_url_info', 'block_obu_learnanalytics'),
        '',
        PARAM_RAW_TRIMMED,
        127
    ));
    $settings->add(new admin_setting_configpasswordunmask(
        'block_obu_learnanalytics/ws_bearer_token',
        get_string('ws_bearer_token', 'block_obu_learnanalytics'),
        get_string('ws_bearer_token_info', 'block_obu_learnanalytics'),
        ''
    ));
    $settings->add(new admin_setting_configtext(
        'block_obu_learnanalytics/ws_curl_timeout_cc',
        get_string('ws_curl_timeout_cc', 'block_obu_learnanalytics'),
        get_string('ws_curl_timeout_cc_info', 'block_obu_learnanalytics'),
        '5',
        PARAM_INT
    ));
    $settings->add(new admin_setting_configtext(
        'block_obu_learnanalytics/ws_accept_timeout',
        get_string('ws_curl_timeout', 'block_obu_learnanalytics'),
        get_string('ws_curl_timeout_info', 'block_obu_learnanalytics'),
        '30',
        PARAM_INT
    ));
    $settings->add(new admin_setting_configcheckbox(
        'block_obu_learnanalytics/ws_accept_selfcert',
        get_string('ws_accept_selfcert', 'block_obu_learnanalytics'),
        get_string('ws_accept_selfcert_info', 'block_obu_learnanalytics'),
        '0',
        '1',
        '0'
    ));
    $settings->add(new admin_setting_configcheckbox(
        'block_obu_learnanalytics/ws_trace_calls',
        get_string('ws_trace_calls', 'block_obu_learnanalytics'),
        get_string('ws_trace_calls_info', 'block_obu_learnanalytics'),
        '0',
        '1',
        '0'
    ));
}
