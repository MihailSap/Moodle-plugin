/**
 * JavaScript для редактирования условий данных в форме.
 *
 * @module moodle-availability_enroldate-form
 */
M.availability_enroldate = M.availability_enroldate || {};

/**
 * @class M.availability_enroldate.form
 * @extends M.core_availability.plugin
 */
M.availability_enroldate.form = Y.Object(M.core_availability.plugin);

/**
 * Инициализирует этот плагин.
 * Поскольку поля даты являются сложными в зависимости от настроек календаря Moodle,
 * мы создаем HTML-код для этих полей на PHP и передаем его этому методу.
 *
 * @method initInner
 * @param {String} html HTML, используемый для полей дат
 * @param {Number} defaultTime Значение времени, соответствующее исходным полям
 */
M.availability_enroldate.form.initInner = function(html, defaultTime) {
    this.html = html;
    this.defaultTime = defaultTime;
};

M.availability_enroldate.form.getNode = function(json) {
    var html = '<span class="col-form-label p-r-1">' +
                    M.util.get_string('direction_before', 'availability_enroldate') + '</span> <span class="availability-group">' +
            '<label><span class="accesshide">' + M.util.get_string('direction_label', 'availability_enroldate') + ' </span>' +
            '<select name="direction" class="custom-select">' +
            '<option value="&gt;=">' + M.util.get_string('enroldate_after', 'availability_enroldate') + '</option>' +
            '<option value="&lt;">' + M.util.get_string('enroldate_before', 'availability_enroldate') + '</option>' +
            '</select></label></span> ' + this.html;
    var node = Y.Node.create('<span>' + html + '</span>');

    // Установим начальное значение, если оно задано
    if (json.t !== undefined) {
        node.setData('time', json.t);
        // Отключим все
        node.all('select:not([name=direction])').each(function(select) {
            select.set('disabled', true);
        });

        var url = M.cfg.wwwroot + '/availability/condition/enroldate/ajax.php?action=fromtime' + '&time=' + json.t;
        Y.io(url, {on: {
            success: function(id, response) {
                var fields = Y.JSON.parse(response.responseText);
                for (var field in fields) {
                    var select = node.one('select[name=x\\[' + field + '\\]]');
                    select.set('value', '' + fields[field]);
                    select.set('disabled', false);
                }
            },
            failure: function() {
                window.alert(M.util.get_string('ajaxerror', 'availability_enroldate'));
            }
        }});
    } else {
        // Установим время по умолчанию, соответствующее HTML-селекторам
        node.setData('time', this.defaultTime);
    }
    if (json.d !== undefined) {
        node.one('select[name=direction]').set('value', json.d);
    }

    // Добавим обработчики событий (только в первый раз)
    if (!M.availability_enroldate.form.addedEvents) {
        M.availability_enroldate.form.addedEvents = true;

        var root = Y.one('.availability-field');
        root.delegate('change', function() {
            // Что касается направления, обновим поля формы
            M.core_availability.form.update();
        }, '.availability_enroldate select[name=direction]');

        root.delegate('change', function() {
            // Обновим время с помощью AJAX-вызова с корневого узла
            M.availability_enroldate.form.updateTime(this.ancestor('span.availability_enroldate'));
        }, '.availability_enroldate select:not([name=direction])');
    }

    if (node.one('a[href=#]')) {
        M.form.dateselector.init_single_date_selector(node);

        // Этот обработчик определяет, когда при выборе даты меняется год
        var yearSelect = node.one('select[name=x\\[year\\]]');
        var oldSet = yearSelect.set;
        yearSelect.set = function(name, value) {
            oldSet.call(yearSelect, name, value);
            if (name === 'selectedIndex') {
                // Сделаем это после истечения времени ожидания или после того, как другие поля еще не были заданы
                setTimeout(function() {
                    M.availability_enroldate.form.updateTime(node);
                }, 0);
            }
        };
    }

    return node;
};

/**
 * Обновляет время с помощью AJAX. Всякий раз, когда значения полей меняются, мы пересчитываем
 * фактическое время с помощью AJAX-запроса в Moodle.
 * Это установит данные "времени" на узле, а затем обновит форму, как только
 * получит ответ AJAX.
 *
 * @method updateTime
 * @param {Y.Node} component
 */
M.availability_enroldate.form.updateTime = function(node) {
    // После изменения даты/времени нам нужно повторно вычислить
	//фактическое время с помощью AJAX, поскольку это зависит от
	//часового пояса пользователя и параметров календаря.
    var url = M.cfg.wwwroot + '/availability/condition/enroldate/ajax.php?action=totime' +
            '&year=' + node.one('select[name=x\\[year\\]]').get('value') +
            '&month=' + node.one('select[name=x\\[month\\]]').get('value') +
            '&day=' + node.one('select[name=x\\[day\\]]').get('value') +
            '&hour=' + node.one('select[name=x\\[hour\\]]').get('value') +
            '&minute=' + node.one('select[name=x\\[minute\\]]').get('value');
    Y.io(url, {on: {
        success: function(id, response) {
            node.setData('time', response.responseText);
            M.core_availability.form.update();
        },
        failure: function() {
            window.alert(M.util.get_string('ajaxerror', 'availability_enroldate'));
        }
    }});
};

M.availability_enroldate.form.fillValue = function(value, node) {
    value.d = node.one('select[name=direction]').get('value');
    value.t = parseInt(node.getData('time'), 10);
};
