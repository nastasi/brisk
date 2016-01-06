function ends_with(s, suffix)
{
    if (s.indexOf(suffix, s.length - suffix.length) !== -1) {
        return true;
    }
    return false;
}

function __ffa_nav(obj, ret, name)
{
    var arr;

    arr = obj.className.split(" ");
    if (arr.indexOf(name + "_id") != -1) {
        ret.push(obj);
        return;
    }

    // check if the current element is a leaf or a node
    // if it is then return
    for (var i = 0 ; i < arr.length ; i++) {
        if (ends_with(arr[i], "_id")) {
            return;
        }
    }

    for (var i = 0 ; i < obj.children.length ; i++) {
        __ffa_nav(obj.children[i], ret, name);
    }
    return;
}

function fieldify_get_dom_element(objarr, name)
{
    var obj, ret = [];

    for (var i = 0 ; i < objarr.length ; i++) {
        obj = objarr[i];
        for (var e = 0 ; e < obj.children.length ; e++) {
            __ffa_nav(obj.children[e], ret, name);
        }
    }

    if (ret.length > 0) {
        return ret;
    }
    return false;
}

// fieldsdescr = { name: { type: 'typename' }, ... }
function Fieldify(dom_elements, fieldsdescr)
{
    var item;

    this.dom_elements = dom_elements;
    this.field = new Array();
    for (k in fieldsdescr) {
        this.field[k] = fieldsdescr[k];
        if (this.field[k].type == 'fields') {
            if (item = fieldify_get_dom_element(this.dom_elements, k)) {
                this.field[k].obj = new Fieldify(item, this.field[k].fields);
            }
        }
    }
}

Fieldify.prototype = {
    dom_elements: null,
    field: null,

    visible: function(is_visible) {
        this.dom_elements[0].style.visibility = (is_visible ? "visible" : "hidden" );
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
        var item = fieldify_get_dom_element(this.dom_elements, name);
        if (item) {
            item[0].innerHTML = value;
        }
    },

    fld_value_get: function(name)
    {
        var item = fieldify_get_dom_element(this.dom_elements, name);
        if (item) {
            return (item[0].innerHTML);
        }
        return false;
    },

    fld_radio_set: function(name, value)
    {
        var arr = fieldify_get_dom_element(this.dom_elements, name);
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
        var arr = fieldify_get_dom_element(this.dom_elements, name);
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
