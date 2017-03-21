/**
 * Title
 * @version 1.0.2
 */
function title_update(title)
{
	document.title = title;
	$(".title").text(title);
}

/**
 * Buttons
 * @version 1.0.2
 */
function buttons_init()
{
	$(document).ready(function()
	{
		$("button").not(".btn-navbar").button();
	});
}

/**
 * Dialogs
 * @version 1.0.2
 */
function dialogs_init()
{
	$(document).on("keydown", ".ui-dialog", function(event)
	{
		if (event.keyCode != 13)
			return true;

		var buttons = $(this).find(".ui-dialog-buttonpane").find("button");
		if (buttons.length <= 1)
			return true;

		buttons.eq(0).click();
		return false;
	});
}

/**
 * Content
 * @uses Ajax
 * @version 1.0.7
 */
Loader.scripts(["jquery-url", "ajax"]);

var content_query = {'get': {}, 'post': {}};

function content_url(params)
{
	if (typeof params['module'] == "undefined")
		params['module'] = Ajax.param("module");

	var url = $.url.build({'params': params});

	return Ajax.nocache(url);
}

function content_page(page, ajax)
{
	if (typeof ajax == "undefined")
		ajax = true;

	if (ajax)
	{
		content_load({'page': page}, {}, true);
		return;
	}

	var url = $.url.parse(location.href);

	url['params']['page'] = page;

	Ajax.clear(url);

	location.href = $.url.build(url);
}

function content_action(action, get, module, newWindow)
{
	if (typeof get == "undefined")
		get = {};
	if (typeof module != "undefined" && module != null)
		get['module'] = module;

	if (typeof newWindow == "undefined")
		newWindow = false;

	get['action'] = action;

	var url = content_url(get);

	if (newWindow)
		window.open(url);
	else
		location.href = url;
}

function content_get(action, post, data_type)
{
	if (data_type === undefined)
		data_type = "text";

	var url = content_url({'action': action});

	return Ajax.post(url, post, data_type);
}

function content_load(get, post, merge)
{
	if (merge)
	{
		get = $.extend(content_query['get'], get);
		post = $.extend(content_query['post'], post);
	}

	content_query['get'] = get;
	content_query['post'] = post;

	$("#pages_content").html("Загрузка страницы...");

	var url = content_url(get);

	$.post(url, post,
		function(data)
		{
			$("#pages_content").html(data);
		},
		"html"
	);
}

/**
 * Forms
 * @version 1.0.2
 */
function forms_init()
{
	$(document).ready(function()
	{
		$("FORM.pages_form").ajaxForm(
		{
			target: "#pages_content",
			beforeSubmit: forms_disable,
			success: forms_enable
		});

		$("FORM.pages_form_files").ajaxForm(
		{
			beforeSubmit: forms_disable,
			success: forms_set_xml,
			iframe: true,
			dataType: "xml"
		});
	});
}

function forms_disable()
{
	$("FORM.form button").prop("disabled", true);
	$("#edit_box button").prop("disabled", true);
}

function forms_enable()
{
	$("FORM.form button").prop("disabled", false);
	$("#edit_box button").prop("disabled", false);
}

function forms_set_xml(data)
{
	var content = $(data).find("content");
	$("#pages_content").html(content.text());
}