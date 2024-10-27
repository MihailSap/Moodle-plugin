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
 * Front-end class.
 *
 * @package availability_enroldate
 * @copyright 2024 Deloviye ludi
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_enroldate;

use cm_info;
use section_info;
use stdClass;

class frontend extends \core_availability\frontend {
	/**
     * Gets additional parameters for the plugin's initInner function.
     *
     * Default returns no parameters.
     *
     * @param stdClass $course Course object
     * @param cm_info|null $cm Course-module currently being edited (null if none)
     * @param section_info|null $section Section currently being edited (null if none)
     * @return array Array of parameters for the JavaScript function
     */
    protected function get_javascript_init_params($course, \cm_info $cm = null, \section_info $section = null) {
		global $DB;
        $optionsdwm = self::convert_associative_array_for_js([
            0 => get_string('minutes', 'availability_enroldate'),
            1 => get_string('hours', 'availability_enroldate'),
            2 => get_string('days', 'availability_enroldate'),
            3 => get_string('weeks', 'availability_enroldate'),
            4 => get_string('months', 'availability_enroldate'),
        ], 'field', 'display');

        $optionsstart = [['field' => 1, 'display' => get_string('dateenrol', 'availability_enroldate')]];
        return [$optionsdwm, $optionsstart, is_null($section)];
    }
}
