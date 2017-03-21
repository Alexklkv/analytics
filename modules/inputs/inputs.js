/**
 * Inputs
 * @version 1.0.3
 */
window.Inputs = {
	'select_get_values': function(element)
	{
		var result = new Array();

		var options = element.find("option:[value!='']");

		for (var i = 0; i < options.length; i ++)
			result[i] = options.eq(i).val();

		return result;
	},
	'checkbox_get_values': function(checkboxes)
	{
		var result = new Array();
		for (var i = 0; i < checkboxes.length; i ++)
		{
			var checkbox = checkboxes.eq(i);
			if (!checkbox.prop("checked"))
				continue;

			result[result.length] = checkbox.prop("value");
		}

		return result;
	},
	'checkbox_toogle': function(event)
	{
		event.stopImmediatePropagation();

		var element = $(event.target);
		var checkboxes = element.closest("table").find("input:enabled[type=checkbox]").not(element).prop("checked", element.prop("checked"));
	},
	'fill_values': function(names, xml, prefix)
	{
		for (var i = 0; i < names.length; i++)
		{
			var name = names[i];
			if (typeof prefix != "undefined")
				name = prefix + name;

			var value = xml.attr(name);
			if (typeof value == "undefined")
				continue;

			var element = $("#" + name);
			var type = element[0].type;

			switch (type)
			{
				case "text":
					element.val(value);
					break;
				case "checkbox":
					element.prop("checked", (value == "1"));
					break;
			}
		}
	},
	'fill_options': function(element, xml)
	{
		var options = xml.find("element");
		for (var i = 0; i < options.length; i++)
		{
			var option = options.eq(i);

			var name = option.attr("name");
			var value = option.attr("value");

			element.append("<option value='" + value + "'>" + name + "</option>");
		}

		if (options.length != 0)
			element.removeAttr("disabled");
	}
};