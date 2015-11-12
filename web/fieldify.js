
// fieldsdescr = { name: { type: 'typename' }, ... }
function Fieldify(ancestor, fieldsdescr)
{
    this.ancestor = ancestor;

    this.field = new Array();
    for (k in fieldsdescr) {
        this.field[k] = fieldsdescr[k];
    }
}

Fieldify.prototype = {
    ancestor: null,
    field: null,

    visible: function(is_visible) {
        this.ancestor.style.visibility = (is_visible ? "visible" : "hidden" );
    },

    // { 'name': 'value' }
    json2dom: function(field_values)
    {
        for (k in this.field) {
            if (this.field[k].type == 'value') {
                this.fld_value_set(k, field_values[k]);
            }
            else if (this.field[k].type == 'radio') {
                this.fld_radio_set(k, field_values[k]);
            }
        }
    },

    dom2json: function()
    {
        var ret = {};
        for (k in this.field) {
            if (this.field[k].perms == 'ro')
                continue;
            if (this.field[k].type == 'value') {
                ret[k] = this.fld_value_get(k);
            }
            else if (this.field[k].type == 'radio') {
                ret[k] = this.fld_radio_get(k);
            }
        }
        return ret;
    },

    fld_value_set: function(name, value)
    {
        this.ancestor.getElementsByClassName(name + '_id')[0].innerHTML = value;
    },

    fld_value_get: function(name)
    {
        return this.ancestor.getElementsByClassName(name + '_id')[0].innerHTML;
    },

    fld_radio_set: function(name, value)
    {
        var arr = this.ancestor.getElementsByClassName(name + '_id');

        for (k in arr) {
            if (arr[k].value == value)
                arr[k].checked = true;
            else
                arr[k].checked = false;
        }
    },

    fld_radio_get: function(name)
    {
        var arr = this.ancestor.getElementsByClassName(name + '_id');
        ret = null;

        for (k in arr) {
            if (arr[k].checked == true) {
                ret = arr[k].value;
                break;
            }
        }
        return ret;
    },

    tap: null
}
