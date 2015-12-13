function __fieldify_find_ancestors(objarr, name)
{
    var obj;

    for (var i = 0 ; i < objarr.length ; i++) {
        obj = objarr[i];
        var item = obj.getElementsByClassName(name + '_id');
        if (item.length > 0) {
            return (item);
        }
    }
    return false;
}

// fieldsdescr = { name: { type: 'typename' }, ... }
function Fieldify(ancestors, fieldsdescr)
{
    var item;

    this.ancestors = ancestors;
    this.field = new Array();
    for (k in fieldsdescr) {
        this.field[k] = fieldsdescr[k];
        if (this.field[k].type == 'fields') {
            if (item = __fieldify_find_ancestors(this.ancestors, k)) {
                this.field[k].obj = new Fieldify(item, this.field[k].fields);
            }
        }
    }
}

Fieldify.prototype = {
    ancestors: null,
    field: null,

    visible: function(is_visible) {
        this.ancestors[0].style.visibility = (is_visible ? "visible" : "hidden" );
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
            else if (this.field[k].type == 'fields') {
                this.field[k].obj.json2dom(field_values[k]);
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
            else if (this.field[k].type == 'fields') {
                ret[k] = this.field[k].obj.dom2json();
            }
        }
        return ret;
    },

    fld_value_set: function(name, value)
    {
        var item = __fieldify_find_ancestors(this.ancestors, name);
        if (item) {
            item[0].innerHTML = value;
        }
    },

    fld_value_get: function(name)
    {
        var item = __fieldify_find_ancestors(this.ancestors, name);
        if (item) {
            return (item[0].innerHTML);
        }
        return false;
    },

    fld_radio_set: function(name, value)
    {
        var arr = __fieldify_find_ancestors(this.ancestors, name);
        if (arr) {
            for (k in arr) {
                if (arr[k].value == value)
                    arr[k].checked = true;
                else
                    arr[k].checked = false;
            }
        }
    },

    fld_radio_get: function(name)
    {
        var ret = null;
        var arr = __fieldify_find_ancestors(this.ancestors, name);
        if (arr) {
            for (k in arr) {
                if (arr[k].checked == true) {
                    ret = arr[k].value;
                    break;
                }
            }
        }
        return ret;
    },

    tap: null
}
