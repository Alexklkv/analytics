/**
 * Ajax
 * @uses Validator
 * @version 1.0.9
 */
Loader.scripts(["jquery-url", "validator"]);

window.Ajax = {
	'post': function(url, data, data_type)
	{
		if (data_type === undefined)
			data_type = "xml";

		url = this.nocache(url);

		var answer = $.ajax({'url': url, 'type': "POST", 'async': false, 'data': data, 'dataType': data_type});

		if (data_type != "xml")
			return answer.responseText;

		try
		{
			if (!answer.responseXML)
				throw "";

			var result = $(answer.responseXML).children("answer");
			if (!result.length)
				throw "";

			return result;
		}
		catch(e)
		{}

		try
		{
			var errors = "<errors><![CDATA[" + unescape(answer.responseText) + "]]></errors>";

			var xml;
			if (DOMParser !== undefined)
			{
				var doc = new DOMParser();
				xml = doc.parseFromString(errors, "text/xml");
			}
			else
			{
				xml = new ActiveXObject("Microsoft.XMLDOM");
				xml.async = false;
				xml.loadXML(errors);
			}

			return $(xml);
		}
		catch (e)
		{
			alert(answer.responseText);
		}
		return false;
	},
	'nocache': function(url)
	{
		var url_new = $.url.parse(url);
		var url_old = $.url.parse(location.href);

		if ("params" in url_old && "nocache" in url_old['params'])
		{
			if (!("params" in url_new))
				url_new['params'] = {};
			url_new['params']['nocache'] = 1;
		}

		this.clear(url_new);

		return $.url.build(url_new);
	},
	'build': function(url, params)
	{
		var parsed = $.url.parse(url);
		if (!("params" in parsed))
			parsed['params'] = {};

		for (var key in params)
			parsed['params'][key] = params[key];

		this.clear(parsed);

		return $.url.build(parsed);
	},
	'param': function(param)
	{
		var url = $.url.parse(location.href);

		if (!('params' in url) || !(param in url['params']))
			return "";

		return url['params'][param];
	},
	'clear': function(url)
	{
		delete url['source'];
		delete url['authority'];
		delete url['userInfo'];
		delete url['directory'];
		delete url['file'];
		delete url['relative'];
		delete url['query'];
	},
	'check_error': function(xml, map)
	{
		if (xml === false)
			return false;

		xml = $(xml);

		var errors = xml.find("errors").text();
		if (errors == "")
			return true;

		Validator.reset();

		if (map !== undefined)
			Validator.map(map);

		$("body").append(errors);
		return false;
	},
	'xml_load': function(element, url, data)
	{
		var xml = this.send_post(url, data);

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