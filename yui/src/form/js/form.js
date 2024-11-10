/**
 * JavaScript для редактирования условий relativedate в форме.
 *
 * @module moodle-availability_relativedate-form
 */
M.availability_relativedate = M.availability_relativedate || {};

/**
 * @class M.availability_relativedate.form
 * @extends M.core_availability.plugin
 */
M.availability_relativedate.form = Y.Object(M.core_availability.plugin);

// Поля времени, доступные для выбора.
M.availability_relativedate.form.timeFields = null;

// Текст перед выпадающим списком
M.availability_relativedate.form.description_before = null;

// Текст после выпадающего списка
M.availability_relativedate.form.description_after = null;

// Раздел или модуль.
M.availability_relativedate.form.isSection = null;


/**
 * Инициализирует этот плагин.
 *
 * @method initInner
 * @param {array} timeFields Набор временных полей
 * @param {string} description_before Строка перед списком
 * @param {string} description_after Строка после списка
 * @param {boolean} isSection Это раздел или нет
 */
M.availability_relativedate.form.initInner = function(timeFields, description_before, description_after, isSection) {
    this.timeFields = timeFields;
	this.description_before = description_before;
    this.description_after = description_after;
    this.isSection = isSection;
};

M.availability_relativedate.form.getNode = function(json) {
    var html = '<span class="availability-relativedate">';

	html += '<span class="relativebefore">' + this.description_before + '</span>';

    html += '<label><select name="relativenumber">';
    for (var i = 1; i < 60; i++) {
        html += '<option value="' + i + '">' + i + '</option>';
    }

    html += '</select></label> ';
    html += '<label><select name="relativednw">';
    for (var i = 0; i < this.timeFields.length; i++) {
        html += '<option value="' + this.timeFields[i].field + '">' + this.timeFields[i].display + '</option>';
    }
    html += '</select></label> ';
    html += '<span class="relativestart">' + this.description_after + '</span>';
    var node = Y.Node.create('<span>' + html + '</span>');

    // Установим начальные значения, если они указаны.
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
