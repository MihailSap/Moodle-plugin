<?php

/**
 * Languages configuration for the availability_enroldate plugin.
 *
 * @package   availability_enroldate
 * @copyright 2024 Deloviye ludi
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// You must use the right namespace (matching your plugin component name).
namespace availability_enroldate;

class condition extends \core_availability\condition {
    // Any data associated with the condition can be stored in member
    // variables. Here's an example variable:
    protected $allow;

    // Используется для определения условия доступности, начиная с заданной даты
    const AVAILABLE_AFTER_DATE = '>=';

    // Используется для определения условия доступности до заданной даты
    const AVAILABLE_BEFORE_DATE = '<';

    // Хранит тип условия доступности (один из двух, представленных)
    private $available_type;

    // Определяет общее время,
    // Нужно извлекать из JSON
    private $time;

    // Определяет время регистрации пользователя на курс,
    // Нужно извлекать из БД
    private $time_user_registration;

    //
    private static $forcecurrenttime = 0;

    public function __construct($structure) {
        // Retrieve any necessary data from the $structure here. The
        // structure is extracted from JSON data stored in the database
        // as part of the tree structure of conditions relating to an
        // activity or section.
        // For example, you could obtain the 'allow' value:
        $this->allow = $structure->allow;

        // It is also a good idea to check for invalid values here and
        // throw a coding_exception if the structure is wrong.
    }

    // СДЕЛАНО | НУЖНО ПРОТЕСТИРОВАТЬ
    // Сохраняет текущие условия в БД,
    // готовит к созданию JSON
    public function save() {
        return (object)array(
            'type' => 'date',
            'd' => $this->available_type,
            't' => $this->time
        );
    }


    // СДЕЛАНО | НУЖНО ПРОТЕСТИРОВАТЬ
    // Определяет доступность в зависимости от даты регистрации
    // и от заданного времени доступности
    public function is_available(
        $not,
        \core_availability\info $info,
        $grabthelot,
        $userid
    ) {
        switch ($this->available_type) {
            case self::AVAILABLE_AFTER_DATE:
                $allow = $this->time_user_registration >= $this->time;
                break;
            case self::AVAILABLE_BEFORE_DATE:
                $allow = $this->time_user_registration < $this->time;
                break;
            default:
                throw new \coding_exception('Unexpected available type');
        }
        if ($not) {
            $allow = !$allow;
        }
        return $allow;
    }

    public function get_description(
        $full,
        $not,
        \core_availability\info $info
    ) {
        // This function returns the information shown about the
        // condition on editing screens.
        // Usually it is similar to the information shown if the
        // user doesn't meet the condition.
        // Note: it does not depend on the current user.
        $allow = $not ? !$this->allow : $this->allow;
        return $allow ? 'Users are allowed' : 'Users not allowed';
    }

    protected function get_debug_string() {
        // This function is only normally used for unit testing and
        // stuff like that. Just make a short string representation
        // of the values of the condition, suitable for developers.
        return $this->allow ? 'YES' : 'NO';
    }

    private function get_user_enroldate(\stdClass $user) {
        // Логика получения даты зачисления проверяемого пользователя должна быть здесь.
        // Это может включать обращение к базе данных или к объектам Moodle API.
        return $user->enroltime ?? null;
    }


//    protected static function get_time() {
//        if (self::$forcecurrenttime) {
//            return self::$forcecurrenttime;
//        } else {
//            return time();
//        }
//    }
}
