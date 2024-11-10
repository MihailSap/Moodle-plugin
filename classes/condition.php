<?php

/**
 * Languages configuration for the availability_enroldate plugin.
 *
 * @package   availability_enroldate
 * @copyright 2024 Deloviye ludi
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_enroldate;

class condition extends \core_availability\condition {
    /**
     * Определяет условия доступности, начиная с заданной даты.
     * @var string
     */
    const AVAILABLE_AFTER_DATE = '>=';

    /**
     * Определяет условия доступности до заданной даты.
     * @var string
     */
    const AVAILABLE_BEFORE_DATE = '<';

    /**
     * Хранит тип условия доступности (один из двух, представленных выше)
     * @var string
     */
    private $AVAILABLE_TYPE;

    /**
     * Определяет общее время
     * Нужно извлекать из JSON
     * @var int
     */
    private $time;

    /**
     * Определяет время регистрации пользователя на курс
     * Нужно извлекать из БД
     * @var int
     */
    private $time_user_registration;

    /** @var int Forced current time (for unit tests) or 0 for normal. */
    private static $forcecurrenttime = 0;

    /**
     * Конструктор
     * @param \stdClass $structure Data structure from JSON decode
     * @throws \coding_exception If invalid data structure.
     */
    public function __construct($structure) {
        global $CFG, $USER, $COURSE, $DB;

        // Проверяем наличие и корректность направления условия 'd'
        if (isset($structure->d) && in_array($structure->d,
                array(self::AVAILABLE_AFTER_DATE, self::AVAILABLE_BEFORE_DATE))) {
            // Устанавливаем направление условия
            $this->AVAILABLE_TYPE = $structure->d;
        } else {
            throw new \coding_exception('Missing or invalid ->d for date condition');
        }

        // Получаем контекст текущего курса
        $coursecontext = \context_course::instance($COURSE->id);

        // Проверяем, зачислен ли пользователь в курс
        if (is_enrolled($coursecontext)) {
            // Формируем SQL-запрос для получения времени зачисления
            $sql = "SELECT max(ue.timecreated) as enroldate
                      FROM {user_enrolments} ue
                      JOIN {enrol} e ON (e.id = ue.enrolid AND e.courseid = :courseid)
                      JOIN {user} u ON u.id = ue.userid
                     WHERE ue.userid = :userid AND u.deleted = 0";

            // Параметры для SQL-запроса: ID пользователя и ID курса
            $params = array('userid' => $USER->id, 'courseid' => $coursecontext->instanceid);

            // Получаем дату зачисления
            $enroldate = $DB->get_field_sql($sql, $params, IGNORE_MISSING);
            $this->time_user_registration = $enroldate;
        }

        // Проверяем наличие и корректность времени условия 't'
        if (isset($structure->t) && is_int($structure->t)) {
            $this->time = $structure->t;
        } else {
            throw new \coding_exception('Missing or invalid ->t for date condition');
        }
    }

    /**
     * Сохраняет текущие условия в БД
     * Готовит к созданию JSON
     *
     * @return \stdClass Structure object
     */
    public function save() {
        return (object)array(
            'type' => 'date',
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

    /**
     * Метод, необходимый для отладки и модульного тестирования
     * Строковое представление значений condition
     *
     * @return string Text representation of parameters
     */
    protected function get_debug_string() {
        return $this->AVAILABLE_TYPE . ' ' . gmdate('Y-m-d H:i:s', $this->time);
    }

    /**
     * Returns a JSON object which corresponds to a condition of this type.
     *
     * Intended for unit testing, as normally the JSON values are constructed
     * by JavaScript code.
     *
     * @param string $direction DIRECTION_xx constant
     * @param int $time Time in epoch seconds
     * @return stdClass Object representing condition
     */
    public static function get_json($direction, $time) {
        return (object)array(
            'type' => 'date',
            't' => (int)$time
        );
    }

    /**
     * Получает строку, описывающую данное ограничение (влияет ли оно или нет).
     * Используется для получения информации, которая отображается студентам,
     * если активность для них недоступна, и для сотрудников, чтобы видеть
     * условия доступа.
     *
     * @param bool $full True, если это вид "полной информации".
     * @param bool $not True, если мы инвертируем условие.
     * @param \core_availability\info $info Элемент, который мы проверяем.
     * @return string Строка информации (для администраторов) обо всех
     * ограничениях на этот элемент.
     */
    public function get_description($full, $not, \core_availability\info $info) {
        return $this->get_either_description($not, false);
    }

    /**
     * Показывает описание с использованием различных языковых строк для
     * автономной версии или полной.
     *
     * @param bool $not True, если действует условие НЕ (инверсия).
     * @param bool $standalone True, если использовать автономные языковые строки.
     */
    protected function get_either_description($not, $standalone) {
        $direction = $this->get_logical_direction($not);
        $midnight = self::is_midnight($this->time);
        $midnighttag = $midnight ? '_date' : '';
        $satag = $standalone ? 'short_' : 'full_';
        switch ($direction) {
            case self::AVAILABLE_AFTER_DATE:
                return get_string($satag . 'from' . $midnighttag, 'availability_enroldate',
                    self::show_time($this->time, $midnight, false));
            case self::AVAILABLE_BEFORE_DATE:
                return get_string($satag . 'until' . $midnighttag, 'availability_enroldate',
                    self::show_time($this->time, $midnight, true));
        }
    }

    /**
     * Получает фактическое направление проверки на основе значения $not.
     *
     * @param bool $not Правда, если условие инвертировано.
     * @return string Константа направления.
     * @throws \coding_exception
     */
    protected function get_logical_direction($not) {
        switch ($this->AVAILABLE_TYPE) {
            case self::AVAILABLE_AFTER_DATE:
                return $not ? self::AVAILABLE_BEFORE_DATE : self::AVAILABLE_AFTER_DATE;
            case self::AVAILABLE_BEFORE_DATE:
                return $not ? self::AVAILABLE_AFTER_DATE : self::AVAILABLE_BEFORE_DATE;
            default:
                throw new \coding_exception('Unexpected direction');
        }
    }

    /**
     * Показывает время либо как дату, либо как полный формат даты и времени,
     * в зависимости от временной зоны пользователя.
     *
     * @param int $time Время.
     * @param bool $dateonly Если правда, используется только дата.
     * @param bool $until Если правда, и если используется только дата,
     * показывает предыдущую дату.
     * @return string Дата.
     */
    protected function show_time($time, $dateonly, $until = false) {
        return userdate($time,
            get_string($dateonly ? 'strftimedate' : 'strftimedatetime', 'langconfig'));
    }

    /**
     * Проверяет, если заданное время относится точно к полуночи
     * (в текущей временной зоне пользователя).
     *
     * @param int $time Время.
     * @return bool True, если время относится к полуночи, false в противном случае.
     */
    protected static function is_midnight($time) {
        return usergetmidnight($time) == $time;
    }
}
