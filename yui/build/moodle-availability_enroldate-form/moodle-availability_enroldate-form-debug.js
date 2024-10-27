YUI.add('moodle-availability_enroldate-form', function (Y, NAME) {

/**
 * JavaScript for form editing date conditions.
 *
 * @module moodle-availability_enroldate-form
 */
M.availability_enroldate = M.availability_enroldate || {};

/**
 * @class M.availability_enroldate.form
 * @extends M.core_availability.plugin
 */
M.availability_enroldate.form = Y.Object(M.core_availability.plugin);

// Time fields available for selection.
M.availability_enroldate.form.timeFields = null;

// Start field available for selection.
M.availability_enroldate.form.startFields = null;

// A section or a module.
M.availability_enroldate.form.isSection = null;

// Optional warnings that can be displayed.
M.availability_enroldate.form.warningStrings = null;

/**
 * Initialises this plugin.
 *
 * @method initInner
 * @param {array} timeFields Collection of time fields
 * @param {array} startFields Collection of start fields
 * @param {boolean} isSection Is this a section
 */
M.availability_enroldate.form.initInner = function(timeFields, startFields, isSection) {
    this.timeFields = timeFields;
    this.startFields = startFields;
    this.isSection = isSection;
};

M.availability_enroldate.form.getNode = function(json) {
	var html = '<span class="availability-relativedate">';
    var fieldInfo;

    html += '<label><select name="relativenumber">';
    for (var i = 1; i < 60; i++) {
        html += '<option value="' + i + '">' + i + '</option>';
    }
	html += '</select></label> ';

    html += '<label><select name="relativednw">';
    for (i = 0; i < this.timeFields.length; i++) {
        fieldInfo = this.timeFields[i];
        html += '<option value="' + fieldInfo.field + '">' + fieldInfo.display + '</option>';
    }
    html += '</select></label> ';

    html += '<label><select name="relativestart">';
    for (i = 0; i < this.startFields.length; i++) {
        fieldInfo = this.startFields[i];
        html += '<option value="' + fieldInfo.field + '">' + fieldInfo.display + '</option>';
    }
    html += '</select></label>';

	var node = Y.Node.create('<span>' + html + '</span>');

	i = 1;
    if (json.n !== undefined) {
        i = json.n;
    }
    node.one('select[name=relativenumber]').set('value', i);

    i = 2;
    if (json.d !== undefined) {
        i = json.d;
    }
    node.one('select[name=relativednw]').set('value', i);

    i = 1;
    if (json.s !== undefined) {
        i = json.s;
    }
    node.one('select[name=relativestart]').set('value', i);

    if (!M.availability_enroldate.form.addedEvents) {
        M.availability_enroldate.form.addedEvents = true;
        var root = Y.one('.availability-field');
        root.delegate('change', function() {
			M.core_availability.form.update();
        }, '.availability_relativedate select');
    }

    return node;
};

M.availability_enroldate.form.fillValue = function(value, node) {
    value.n = Number(node.one('select[name=relativenumber]').get('value'));
    value.d = Number(node.one('select[name=relativednw]').get('value'));
    value.s = Number(node.one('select[name=relativestart]').get('value'));
};

M.availability_enroldate.form.fillErrors = function(errors, node) {
	var value = {};
	this.fillValue(value, node);
};

}, '@VERSION@', {"requires": ["base", "node", "event", "moodle-core_availability-form"]});
