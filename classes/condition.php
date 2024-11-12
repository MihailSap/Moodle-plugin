<?php

/**
 * Languages configuration for the availability_enroldate plugin.
 *
 * @package   availability_enroldate
 * @copyright 2024 Deloviye ludi
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_enroldate;

use context_course;
use core_availability\info;
use stdClass;

class condition extends \core_availability\condition {
    /**
     * @var int $relativenumber Определяет количество относительно:
     * сколько единиц измерения использовать (например, 3 дня, 2 недели).
     */
    private $relativenumber;

    /**
     * @var int $relativedwm Определяет единицу измерения для времени:
     * 0 - минуты,
     * 1 - часы,
     * 2 - дни,
     * 3 - недели,
     * 4 - месяцы.
     */
    private $relativedwm;

    /**
     * @var int $relativestart Указывает, относительно какого события или времени определяется условие:
     * 1 - После даты начала курса,
     * 2 - До даты окончания курса,
     * 3 - После даты зачисления пользователя,
     * 4 - После окончания метода зачисления,
     * 5 - После даты окончания курса,
     * 6 - До даты начала курса,
     * 7 - После завершения активности.
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
     *                  'n' - Указывает кол-во единиц ()
     *                  'd' - Единица времени, в которой производится рассчёт (см. relativedwm)
     *                  's' - Тип начального события, по отношению к которому применяется условие (см. relativestart)
     *                  'm' - ID модуля курса, используемого в расчёте (см. relativecoursemodule)
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

    /**
     * Определяет, доступен ли элемент пользователю в текущий момент.
     * Вычисляет временное значение через calc().
     * Если значение равно 0, элемент считается недоступным.
     * Доступ определяется сравнением текущего
     * времени с вычисленным значением и, при необходимости, инверсией результата.
     *
     * @param bool $not Если true, инвертирует результат доступности
     * @param info $info Объект, содержащий информацию о курсе
     * @param bool $grabthelot Зарезервировано для будущего использования
     * @param int $userid ID пользователя, для которого проверяется доступность
     * @return bool true, если элемент доступен пользователю, иначе false
     */
    public function is_available($not, info $info, $grabthelot, $userid) {
        $calc = $this->calc($info->get_course(), $userid);

        // Нет доступа, если не удалось извлечь значение
        if ($calc === 0) {
            return false;
        }

        $allow = time() > $calc;
        if ($not) {
            $allow = !$allow;
        }
        return $allow;
    }

    /**
     * Возвращает строку, описывающую ограничения для указанного элемента,
     * независимо от их текущего применения.
     * Определяет, какие ограничения применяются к элементу на основе курса и прав пользователя.
     * Если конец курса не установлен, возвращает сообщение об отсутствии даты.
     * В зависимости от флага $not, определяет, использовать ли 'from' или 'until'.
     * Возвращает отформатированную дату ограничения с дополнительной отладочной информацией, если это разрешено.
     *
     * @param bool $full Если true, возвращает полную информацию об ограничениях
     * @param bool $not Если true, инвертирует условия описания
     * @param info $info Объект, содержащий информацию об элементе, который проверяется
     * @return string Строка с информацией для администрирования об ограничениях данного элемента
     */
    public function get_description($full, $not, info $info): string {
        global $USER;
        $course = $info->get_course();
        $capability = has_capability('moodle/course:manageactivities', context_course::instance($course->id));
        $relative = $this->relativestart;
        if ($relative === 2 || $relative === 5) {
            if ((!isset($course->enddate) || (int)$course->enddate === 0) && $capability) {
                return get_string('noenddate', 'availability_relativedate');
            }
        }
        if ($relative === 2 || $relative === 6) {
            $frut = $not ? 'from' : 'until';
        } else {
            $frut = $not ? 'until' : 'from';
        }
        $calc = $this->calc($course, $USER->id);
        if ($calc === 0) {
            return '(' . trim($this->get_debug_string()) . ')';
        }
        $a = new stdClass();
        $a->rnumber = userdate($calc, get_string('strftimedatetime', 'langconfig'));
        $a->rtime = ($capability && $full) ? '(' . trim($this->get_debug_string()) . ')' : '';
        $a->rela = '';
        return trim(get_string($frut, 'availability_relativedate', $a));
    }

    /**
     * Obtains a representation of the options of this condition as a string for debugging.
     *
     * @return string Text representation of parameters
     */

    /**
     * Возвращает строковое представление параметров этого условия для отладки.
     * Создаёт строку со значением относительного кол-ва времени,
     * единицы измерения и описание начального события.
     *
     * @return string Текстовое представление параметров для отладки.
     * @throws \coding_exception
     */
    protected function get_debug_string() {
        // TODO: Избавиться от concat.
        $modname = '';
        if ($this->relativestart === 7) {
            $modname = ' ';
            if (get_coursemodule_from_id('', $this->relativecoursemodule)) {
                $modname .= \core_availability\condition::description_cm_name($this->relativecoursemodule);
            } else {
                $modname .= \html_writer::span(get_string('missing', 'availability_relativedate'), 'alert alert-danger');
            }
        }
        return ' ' . $this->relativenumber . ' ' . self::options_dwm($this->relativenumber)[$this->relativedwm] . ' ' .
            self::options_start($this->relativestart) . $modname;
    }

    /**
     * Возвращает строковое представление начальных событий в зависимости от заданного индекса.
     *
     * Метод предоставляет текстовые метки для различных событий, относительно которых рассчитывается
     * доступность элемента.
     * Идентификатор события передается в метод в виде целого числа, и метод
     * возвращает соответствующую строку с использованием функции `get_string`, чтобы загрузить локализованный
     * текст из языковых файлов плагина.
     *
     * @param int $i Индекс начального события. Определяет тип события.
     * @return string Локализованная строка, соответствующая типу начального события.
     */
    public static function options_start(int $i) {
        switch ($i) {
            case 1:
                return get_string('datestart', 'availability_relativedate');
            case 2:
                return get_string('dateend', 'availability_relativedate');
            case 3:
                return get_string('dateenrol', 'availability_relativedate');
            case 4:
                return get_string('dateendenrol', 'availability_relativedate');
            case 5:
                return get_string('dateendafter', 'availability_relativedate');
            case 6:
                return get_string('datestartbefore', 'availability_relativedate');
            case 7:
                return get_string('datecompletion', 'availability_relativedate');
        }
        return '';
    }

    /**
     * Obtains a the options for hours days weeks months.
     *
     * @param int $number
     * @return array
     */

    /**
     * Возвращает массив строк, представляющих единицы времени.
     * Если переданное количество (`$number`) больше одной, к названию единицы добавляется 's'.
     *
     * @param int $number Определяет, следует ли использовать единственное или множественное число в описании
     *                    единицы времени. По умолчанию '1', что приводит к единственному числу.
     * @return array Ассоциативный массив, где ключи представляют индексы единиц времени,
     *               а значения — их локализованные текстовые представления.
     */
    public static function options_dwm($number = 1) {
        $s = $number === 1 ? '' : 's';
        return [
            0 => get_string('minute' . $s, 'availability_relativedate'),
            1 => get_string('hour' . $s, 'availability_relativedate'),
            2 => get_string('day' . $s, 'availability_relativedate'),
            3 => get_string('week' . $s, 'availability_relativedate'),
            4 => get_string('month' . $s, 'availability_relativedate'),
        ];
    }

    /**
     * Возвращает строковое представление единицы времени на основе заданного индекса.
     *
     * Метод принимает целочисленный индекс и возвращает строку, представляющую соответствующую
     * единицу времени — минуту, час, день, неделю или месяц.
     * Если индекс не совпадает с ожидаемыми значениями, возвращает пустую строку.
     *
     * @param int $i Индекс единицы времени.
     * @return string Строка, обозначающая единицу времени, или пустая строка, если индекс не распознан.
     */
    public static function option_dwm(int $i): string {
        switch ($i) {
            case 0:
                return 'minute';
            case 1:
                return 'hour';
            case 2:
                return 'day';
            case 3:
                return 'week';
            case 4:
                return 'month';
        }
        return '';
    }

    /**
     * Вычисляет относительное время на основе заданных параметров курса и пользователя.
     *
     * Метод определяет дату и время, используемые для проверки доступности элементов курса,
     * на основе заданных относительных параметров. Рассчитывает дату в зависимости от различных
     * событий курса, таких как начало или конец курса, дата зачисления или завершение активности.
     *
     * @param stdClass $course Объект курса, содержащий информацию о текущем курсе.
     * @param int $userid Идентификатор пользователя, для которого производится вычисление.
     * @return int Относительная дата в формате Unix-времени. Возвращает 0, если вычисление невозможно.
     *
     * Логика обработки:
     * - Начиная с даты начала курса.
     * - До даты окончания курса.
     * - После даты окончания курса.
     * - После последней даты начала зачисления.
     * - После последней даты окончания зачисления.
     * - После завершения модуля.
     *
     * Обработка каждой ситуации зависит от значения переменной `$this->relativestart`,
     * для чего выбирается соответствующая SQL-запрос и дальнейшая манипуляция с датами.
     * @throws \dml_exception
     */
    private function calc($course, $userid): int {
        $a = $this->relativenumber;
        $b = $this->option_dwm($this->relativedwm);
        $x = "$a $b";
        switch ($this->relativestart) {
            case 6:
                // Before course start date.
                return $this->fixdate("-$x", $course->startdate);
            case 2:
                // Before course end date.
                return $this->fixdate("-$x", $course->enddate);
            case 5:
                // After course end date.
                return $this->fixdate("+$x", $course->enddate);
            case 3:
                // After latest enrolment start date.
                $sql = 'SELECT ue.timestart
                        FROM {user_enrolments} ue
                        JOIN {enrol} e on ue.enrolid = e.id
                        WHERE e.courseid = :courseid AND ue.userid = :userid AND ue.timestart > 0
                        ORDER by ue.timestart DESC';
                $lowest = $this->getlowest($sql, ['courseid' => $course->id, 'userid' => $userid]);
                if ($lowest === 0) {
                    // A teacher or admin without restriction - or a student with no limit set?
                    $sql = 'SELECT ue.timecreated
                            FROM {user_enrolments} ue
                            JOIN {enrol} e on (e.id = ue.enrolid AND e.courseid = :courseid)
                            WHERE ue.userid = :userid
                            ORDER by ue.timecreated DESC';
                    $lowest = $this->getlowest($sql, ['courseid' => $course->id, 'userid' => $userid]);
                }
                return $this->fixdate("+$x", $lowest);
            case 4:
                // After latest enrolment end date.
                $sql = 'SELECT e.enrolenddate
                        FROM {user_enrolments} ue
                        JOIN {enrol} e on ue.enrolid = e.id
                        WHERE e.courseid = :courseid AND ue.userid = :userid
                        ORDER by e.enrolenddate DESC';
                $lowest = $this->getlowest($sql, ['courseid' => $course->id, 'userid' => $userid]);
                return $this->fixdate("+$x", $lowest);
            case 7:
                // Since completion of a module.

                if ($this->relativecoursemodule < 1) {
                    return 0;
                }

                $cm = new stdClass();
                $cm->id = $this->relativecoursemodule;
                $cm->course = $course->id;
                try {
                    $completion = new \completion_info($course);
                    $data = $completion->get_data($cm, false, $userid);
                    return $this->fixdate("+$x", $data->timemodified);
                } catch (\Exception $e) {
                    return 0;
                }
        }
        // After course start date.
        return $this->fixdate("+$x", $course->startdate);
    }

    /**
     * Извлекает запись с наименьшим значением из базы данных на основе SQL-запроса.
     *
     * Выполняет SQL-запрос с заданными параметрами и извлекает одну запись из
     * результата, игнорируя возможные дубликаты. Полученная запись преобразуется в
     * массив, из которого извлекается значение первого элемента, представляющее минимальное значение.
     *
     * @param string $sql SQL-запрос для выполнения, который должен выбирать конкретное значение.
     * @param array $parameters Массив параметров для подстановки в SQL-запрос.
     * @return int Наименьшее найденное значение в записи. Возвращает 0, если запись не найдена.
     * @throws \dml_exception
     */
    private function getlowest($sql, $parameters): int {
        global $DB;
        if ($lowestrec = $DB->get_record_sql($sql, $parameters, IGNORE_MULTIPLE)) {
            $recs = get_object_vars($lowestrec);
            foreach ($recs as $value) {
                return $value;
            }
        }
        return 0;
    }


    /**
     * Корректирует дату, прибавляя или вычитая заданное количество времени, и возвращает новое время.
     *
     * Метод принимает строку, представляющую временной интервал, который будет добавлен к заданной
     * дате. Если временной интервал больше одного дня (`$this->relativedwm > 1`), исходное
     * время суток (часы, минуты, секунды) от даты `$newdate` сохраняется в результирующей дате.
     *
     * @param string $calc Строка, описывающая временной интервал и оператор ('+' или '-'), например,
     *                     '+2 days' или '-3 weeks'.
     * @param int $newdate Исходная дата, с которой производится вычитание или сложение, в формате Unix-времени.
     * @return int Новое значение даты в формате Unix-времени. Возвращает 0, если исходная дата (`$newdate`)
     *             не является положительной.
     */
    private function fixdate($calc, $newdate): int {
        if ($newdate > 0) {
            $olddate = strtotime($calc, $newdate);
            if ($this->relativedwm > 1) {
                $arr1 = getdate($olddate);
                $arr2 = getdate($newdate);
                return mktime($arr2['hours'], $arr2['minutes'], $arr2['seconds'], $arr1['mon'], $arr1['mday'], $arr1['year']);
            }
            return $olddate;
        }
        return 0;
    }

    /**
     * Проверяет, использует ли условие доступности значение завершения для заданного курса и модуля.
     *
     * Метод служит для определения, является ли конкретный модуль курса частью условия доступности,
     * зависящего от завершения другого модуля. Он выполняет проверку на наличие условия доступности
     * с использованием идентификатора модуля курса в дереве доступности всех модулей курса.
     *
     * @param int|stdClass $course Идентификатор курса или объект курса, для которого производится проверка.
     * @param int $cmid Идентификатор модуля курса, для проверки его использования в условии.
     * @return bool `true`, если модуль используется в условии доступности, основанном на завершении,
     *              иначе `false`.
     *
     * Логика работы:
     * - Извлекает информацию обо всех модулях в рамках курса.
     * - Проверяет каждое условие доступности относительно того, связано ли оно с завершением модуля.
     * - Выполняет SQL-запрос на уровни секций, чтобы удостоверить отсутствие иных ограничений.
     */
    public static function completion_value_used($course, $cmid): bool {
        global $DB;
        $courseobj = (is_object($course)) ? $course : get_course($course);
        $modinfo = get_fast_modinfo($courseobj);
        foreach ($modinfo->cms as $othercm) {
            if (is_null($othercm->availability)) {
                continue;
            }
            $ci = new \core_availability\info_module($othercm);
            $tree = $ci->get_availability_tree();
            foreach ($tree->get_all_children('availability_relativedate\condition') as $cond) {
                if ($cond->relativestart === 7 && $cond->relativecoursemodule === $cmid) {
                    return true;
                }
            }
        }
        // Availability of sections (get_section_info_all) is always null.
        $sqllike = $DB->sql_like('availability', ':availability');
        $params = ['course' => $courseobj->id, 'availability' => '%"s":7,"m":' . $cmid . '%'];
        return count($DB->get_records_sql("SELECT id FROM {course_sections} WHERE course = :course AND $sqllike", $params)) > 0;
    }

    /**
     * Обновляет идентификатор зависимости для модуля курса или секции.
     *
     * Метод используется для замены старого идентификатора модуля курса на новый в случае изменения
     * структуры данных (например, при клонировании или восстановлении курса).
     * Он проверяет, что условие относится к завершению модуля, и обновляет идентификатор модуля в
     * таблицах `course_modules` или `course_sections`.
     *
     * @param string $table Название таблицы, в которой проводится обновление (должна быть 'course_modules' или 'course_sections').
     * @param int $oldid Старый идентификатор модуля курса, который необходимо заменить.
     * @param int $newid Новый идентификатор модуля курса, которым заменяется старый идентификатор.
     * @return bool `true`, если идентификатор был успешно обновлен, иначе `false`.
     */
    public function update_dependency_id($table, $oldid, $newid) {
        if ($this->relativestart === 7) {
            if (in_array($table, ['course_modules', 'course_sections'])) {
                if ($this->relativecoursemodule === $oldid) {
                    $this->relativecoursemodule = $newid;
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Обновляет зависимости модуля после восстановления из резервной копии.
     *
     * Метод предназначен для настройки идентификаторов модулей курса, когда элементы курса восстанавливаются
     * из резервной копии. Он ищет новые идентификаторы для модулей и обновляет их в случае, если они
     * изменились. Метод также записывает предупреждения в логе, если модуль не был восстановлен.
     *
     * @param string $restoreid Идентификатор процесса восстановления, используемый для поиска новых идентификаторов.
     * @param int $courseid Идентификатор курса, в который производится восстановление.
     * @param \base_logger $logger Объект логгера, используемый для записи предупреждений и других сообщений.
     * @param string $name Имя элемента, используемое в сообщениях предупреждений для идентификации.
     * @return bool `true`, если произошли изменения и информация была успешно обновлена, иначе `false`.
     *
     * Логика работает следующим образом:
     * - Получает записи идентификаторов, чтобы определить новые идентификаторы модулей курса.
     * - Если модуль не был восстановлен, записывает предупреждение в лог и сбрасывает идентификатор.
     * - В случае успешного обновления меняет идентификатор модуля на новый.
     */
    public function update_after_restore($restoreid, $courseid, \base_logger $logger, $name): bool {
        $rec = \restore_dbops::get_backup_ids_record($restoreid, 'course_module', $this->relativecoursemodule);
        if (!($rec && $rec->newitemid)) {
            // If we are on the same course (e.g. duplicate) then we can just use the existing one.
            if (!get_coursemodule_from_id('', $this->relativecoursemodule, $courseid)) {
                $this->relativecoursemodule = 0;
                $logger->process(
                    "Restored item ($name has availability condition on module that was not restored",
                    \backup::LOG_WARNING
                );
                return false;
            }
        } else {
            $this->relativecoursemodule = $rec->newitemid;
        }
        return true;
    }
}
