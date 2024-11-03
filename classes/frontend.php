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

defined('MOODLE_INTERNAL') || die();

class frontend extends \core_availability\frontend {
    /**
     * Возвращает список строковых идентификаторов (в языковом файле плагина),
	 * которые требуются в JavaScript для этого плагина.
	 * Значение по умолчанию ничего не возвращает.
     *
     * @return array Массив требуемых строковых идентификаторов
     */
    protected function get_javascript_strings() {
        return array('ajaxerror', 'direction_before', 'enroldate_after', 'enroldate_before', 'direction_label');
    }

    /**
     * При заданных значениях поля получает соответствующую временную метку timestamp.
     *
     * @param int $year
     * @param int $month
     * @param int $day
     * @param int $hour
     * @param int $minute
     * @return int Timestamp
     */
    public static function get_time_from_fields($year, $month, $day, $hour, $minute) {
        $calendartype = \core_calendar\type_factory::get_calendar_instance();
        $gregoriandate = $calendartype->convert_to_gregorian($year, $month, $day, $hour, $minute);
        return make_timestamp($gregoriandate['year'], $gregoriandate['month'],
                $gregoriandate['day'], $gregoriandate['hour'], $gregoriandate['minute'], 0);
    }

    /**
     * Учитывая временную метку, получает соответствующие значения полей.
     *
     * @param int $time Timestamp
     * @return array Объект с полями для года, месяца, дня, часа, минуты
     */
    public static function get_fields_from_time($time) {
        $calendartype = \core_calendar\type_factory::get_calendar_instance();
        $wrongfields = $calendartype->timestamp_to_date_array($time);
        return array(
			'day' => $wrongfields['mday'],
            'month' => $wrongfields['mon'],
			'year' => $wrongfields['year'],
            'hour' => $wrongfields['hours'],
			'minute' => $wrongfields['minutes']
		);
    }

    /**
     * Получает дополнительные параметры для функции initInner плагина.
     *
     * Значение по умолчанию не возвращает никаких параметров.
     *
     * @param \stdClass $course Объект курса
     * @param \cm_info $cm Курс-модуль, редактируемый в данный момент (null, если нет)
     * @param \section_info $section Редактируемый в данный момент раздел (null, если нет)
     * @return array Массив параметров для функции JavaScript
     */
    protected function get_javascript_init_params($course, \cm_info $cm = null, \section_info $section = null) {
        global $CFG, $OUTPUT;
        require_once($CFG->libdir . '/formslib.php');

        $calendartype = \core_calendar\type_factory::get_calendar_instance();

        // Получим текущую дату и установим время равным 00:00
        $wrongfields = $calendartype->timestamp_to_date_array(time());
        $current = array(
			'day' => $wrongfields['mday'],
            'month' => $wrongfields['mon'],
			'year' => $wrongfields['year'],
            'hour' => 0,
			'minute' => 0
		);

        // Получим массивы часов и минут
        $hours = array();
        for ($i = 0; $i <= 23; $i++) {
            $hours[$i] = sprintf("%02d", $i);
        }
        $minutes = array();
        for ($i = 0; $i < 60; $i += 5) {
            $minutes[$i] = sprintf("%02d", $i);
        }

		// Получим список дат
        $fields = $calendartype->get_date_order($calendartype->get_min_year(), $calendartype->get_max_year());

        // Добавляем поля минут и часов и разделитель ':'
        $fields['split'] = '/';
        if (right_to_left()) {
            $fields['minute'] = $minutes;
            $fields['colon'] = ':';
            $fields['hour'] = $hours;
        } else {
            $fields['hour'] = $hours;
            $fields['colon'] = ':';
            $fields['minute'] = $minutes;
        }

        // Выводим все поля данных
        $html = '<span class="availability-group">';
        foreach ($fields as $field => $options) {
            if ($options === '/') {
                $html = rtrim($html);
                $html .= '</span> <span class="availability-group">';
                continue;
            }
            if ($options === ':') {
                $html .= ': ';
                continue;
            }
            $html .= \html_writer::start_tag('label');
            $html .= \html_writer::span(get_string($field) . ' ', 'accesshide');
            $html .= \html_writer::start_tag('select', array('name' => 'x[' . $field . ']', 'class' => 'custom-select'));
            foreach ($options as $key => $value) {
                $params = array('value' => $key);
                if ($current[$field] == $key) {
                    $params['selected'] = 'selected';
                }
                $html .= \html_writer::tag('option', s($value), $params);
            }
            $html .= \html_writer::end_tag('select');
            $html .= \html_writer::end_tag('label');
            $html .= ' ';
        }
        $html = rtrim($html) . '</span>';

        // Получим время, соответствующее этой дате по умолчанию
        $time = self::get_time_from_fields($current['year'], $current['month'],
                $current['day'], $current['hour'], $current['minute']);

        return array($html, $time);
    }
}
