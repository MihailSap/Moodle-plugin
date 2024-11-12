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
     * @var int $relativenumber Определяет количество относительно:
     * сколько единиц измерения использовать (например, 3 дня, 2 недели).
     */
    private $relativenumber;

    /**
     * @var int $relativedwm Определяет единицу измерения для времени:
     * 0 => минуты,
     * 1 => часы,
     * 2 => дни,
     * 3 => недели,
     * 4 => месяцы.
     */
    private $relativedwm;

    /**
     * @var int $relativestart Указывает, относительно какого события или времени определяется условие:
     * 1 => После даты начала курса,
     * 2 => До даты окончания курса,
     * 3 => После даты зачисления пользователя,
     * 4 => После окончания метода зачисления,
     * 5 => После даты окончания курса,
     * 6 => До даты начала курса,
     * 7 => После завершения активности.
     */
    private $relativestart;

    /**
     * @var int $relativecoursemodule ID модуля курса, используется для типа условия 6.
     * Указывает, какую активность следует принимать во внимание при расчетах.
     */
    private $relativecoursemodule;

    /**
     * Конструктор для класса condition, инициализирующий объект условия
     * доступности на основе относительных временных параметров.
     * @param stdClass $structure результат декодирования JSON, содержащий конфигурацию условия.
     *                  'n' => Указывает кол-во единиц
     *                  'd' => Единица времени, в которой производится рассчёт (см. relativedwm)
     *                  's' => Тип начального события, по отношению к которому применяется условие (см. relativestart)
     *                  'm' => ID модуля курса, используемого в расчёте (при необходимости)
     */
    public function __construct($structure) {
        $this->relativenumber = property_exists($structure, 'n') ? intval($structure->n) : 1;
        $this->relativedwm = property_exists($structure, 'd') ? intval($structure->d) : 2;
        $this->relativestart = property_exists($structure, 's') ? intval($structure->s) : 1;
        $this->relativecoursemodule = property_exists($structure, 'm') ? intval($structure->m) : 0;
    }

    /**
     * Сохраняет текущие настройки условия в виде объекта для дальнейшего использования в БД.
     * Готовит к созданию JSON
     *
     * @return \stdClass объект, содержащий текущую конфигурацию условия для записей состояния
     */
    public function save() {
        return (object)[
            'type' => 'relativedate',
            'n' => intval($this->relativenumber),
            'd' => intval($this->relativedwm),
            's' => intval($this->relativestart),
            'm' => intval($this->relativecoursemodule),
        ];
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
