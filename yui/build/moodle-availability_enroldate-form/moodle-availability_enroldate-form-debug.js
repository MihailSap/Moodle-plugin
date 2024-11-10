YUI.add('moodle-availability_enroldate-form', function (Y, NAME) {

/**
 * JavaScript для редактирования условий relativedate в форме.
 *
 * @module moodle-availability_relativedate-form
 */
M.availability_relativedate = M.availability_relativedate || {};

// Класс M.availability_relativedate.form расширяет M.core_availability.plugin.
M.availability_relativedate.form = Y.Object(M.core_availability.plugin);

// Поля времени, доступные для выбора.
M.availability_relativedate.form.timeFields = null;

// Раздел или модуль.
M.availability_relativedate.form.isSection = null;


/**
 * Инициализирует этот плагин.
 *
 * @method initInner
 * @param {array} timeFields Набор временных полей
 * @param {boolean} isSection Это раздел или нет
 */
M.availability_relativedate.form.initInner = function(timeFields, isSection) {
    this.timeFields = timeFields;
    this.isSection = isSection;
};

M.availability_relativedate.form.getNode = function(json) {
    var html = '<span class="availability-relativedate">';
	html += '<span class="gone">' + M.util.get_string('gone', 'availability_enroldate') + '</span>'

    html += '<label><select name="relativenumber">';
    for (i = 1; i < 60; i++) {
        html += '<option value="' + i + '">' + i + '</option>';
    }
    html += '</select></label> ';

    html += '<label><select name="relativednw">';
    for (i = 0; i < this.timeFields.length; i++) {
        html += '<option value="' + this.timeFields[i].field + '">' + this.timeFields[i].display + '</option>';
    }
    html += '</select></label> ';

    html += '<span class="relativestart">' + M.util.get_string('dateenrol', 'availability_enroldate') + '</span>';
    var node = Y.Node.create('<span>' + html + '</span>');

    // Установите начальные значения, если они указаны.
	var value = 1;
    if (json.n !== undefined) {
        value = json.n;
    }
    node.one('select[name=relativenumber]').set('value', value);

    value = 2;
    if (json.d !== undefined) {
        value = json.d;
    }
    node.one('select[name=relativednw]').set('value', value);

    // Добавьте обработчики событий (только в первый раз).
    if (!M.availability_relativedate.form.addedEvents) {
        M.availability_relativedate.form.addedEvents = true;
        var root = Y.one('.availability-field');
        root.delegate('change', function() {
            M.core_availability.form.update();
        }, '.availability_relativedate select');
    }

    return node;
};

M.availability_relativedate.form.fillValue = function(value, node) {
    value.n = Number(node.one('select[name=relativenumber]').get('value'));
    value.d = Number(node.one('select[name=relativednw]').get('value'));
};

M.availability_relativedate.form.fillErrors = function(errors, node) {
    this.fillValue({}, node);
};

	}, '@VERSION@', {"requires": ["base", "node", "event", "moodle-core_availability-form"]});
