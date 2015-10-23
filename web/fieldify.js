
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
    populate: function(field_values)
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

    fld_value_set: function(name, value)
    {
        console.log(name + "=" + value);
        this.ancestor.getElementsByClassName(name + '_id')[0].innerHTML = value;
    },
    
    fld_radio_set: function(name, value)
    {
        var arr = this.ancestor.getElementsByClassName(name + '_id');

        console.log(name + "=" + value);

        for (k in arr) {
            if (arr[k].value == value)
                arr[k].checked = true;
            else 
                arr[k].checked = false;
        }
    },
    tap: null
}
