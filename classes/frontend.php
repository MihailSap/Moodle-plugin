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
     * Получает дополнительные параметры для внутренней функции init плагина.
     *
     * Значение по умолчанию не возвращает никаких параметров.
     *
     * @param stdClass $course Объект курса
     * @param cm_info|null $cm Модуль курса, редактируемый в данный момент (null, если нет)
     * @param section_info|null $section Редактируемый в данный момент раздел (null, если нет)
     * @return array Массив параметров для функции JavaScript
     */
    protected function get_javascript_init_params($course, ?cm_info $cm = null, ?section_info $section = null) {
        $optionsdwm = self::convert_associative_array_for_js([
            0 => get_string('minute', 'availability_enroldate'),
            1 => get_string('hour', 'availability_enroldate'),
            2 => get_string('day', 'availability_enroldate'),
            3 => get_string('week', 'availability_enroldate'),
            4 => get_string('month', 'availability_enroldate'),
        ], 'field', 'display');
        return [$optionsdwm, is_null($section)];
    }
}
