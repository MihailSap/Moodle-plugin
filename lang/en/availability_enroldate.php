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
 * Languages configuration for the availability_enroldate plugin.
 *
 * @package   availability_enroldate
 * @copyright 2024 Deloviye ludi
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['description'] = 'Open the materials depending on the date of registration of the user for the course.';
$string['pluginname'] = 'Restriction by Enrolment date';
$string['title'] = 'Restriction by Enrolment date';
$string['privacy:metadata'] = 'The Restriction by date plugin does not store any personal data.';
$string['description_before'] = 'It has been ';
$string['description_after'] = 'since the date of enrollment for the course.';
$string['day'] = 'day(-s)';
$string['hour'] = 'hour(-s)';
$string['minute'] = 'minute(-s)';
$string['month'] = 'month(-s)';
$string['week'] = 'week(-s)';
$string['year'] = 'year(-s)';
$string['until'] = 'There is a limit associated with the date and time of registration for the course. The material will be available until {$a->rnumber} {$a->rtime} {$a->rela}';
$string['from'] = 'There is a limit associated with the date and time of registration for the course. The material will be available from {$a->rnumber} {$a->rtime} {$a->rela}';
