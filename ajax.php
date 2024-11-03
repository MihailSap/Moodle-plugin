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
 * Handles AJAX processing (convert date to timestamp using current calendar).
 *
 * @package availability_enroldate
 * @copyright 2024 Deloviye ludi
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_login();
$action = required_param('action', PARAM_ALPHA);

switch ($action) {
    case 'totime':
        // Преобразует данные из полей времени в метку времени, используя календарь текущего пользователя и часовой пояс.
        echo \availability_enroldate\frontend::get_time_from_fields(
                required_param('year', PARAM_INT),
                required_param('month', PARAM_INT),
                required_param('day', PARAM_INT),
                required_param('hour', PARAM_INT),
                required_param('minute', PARAM_INT));
        exit;

    case 'fromtime' :
        // Преобразует временные метки в поля времени.
        echo json_encode(\availability_enroldate\frontend::get_fields_from_time(
                required_param('time', PARAM_INT)));
        exit;
}

// Неожиданные действия приводят к исключению coding_exception (эта ошибка не должна возникать, если нет ошибки в коде).
throw new coding_exception('Unexpected action parameter');
