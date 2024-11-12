<?php

namespace availability_enroldate;

class autoupdate
{
    public static function update_from_event(\core\event\base $event): void {
        $data = $event->get_data();
        $courseid = $data['courseid'];
        if ($course = get_course($courseid)) {
            $modid = $data['objectid'];
            if (condition::completion_value_used($course, $modid)) {
                \core_availability\info::update_dependency_id_across_course($course, 'course_modules', $modid, -1);
                rebuild_course_cache($course->id, true);
            }
        }
    }
}