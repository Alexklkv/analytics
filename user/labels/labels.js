/**
 * Labels
 * @uses jQuery dynatree
 * @uses Ajax
 * @uses SortedTable
 * @uses ContextMenu
 * @uses Tables
 * @uses Validator
 *
 * @version 1.0.0
 */

Loader.styles(["jquery-dynatree"]);
Loader.scripts(["jquery-dynatree", "ajax", "sorted_table", "context_menu", "tables", "validator", "string"], "labels_init");

var labels_table;
var context_menu;

function labels_init()
{
	$("#button_add").bind("click", labels_add);

	context_menu = new ContextMenu(
	{
		'menu': "context_menu",
		'bind_right': "#labels_table tbody tr",
		'handlers':
		{
			'edit': labels_edit,
			'delete': labels_delete
		}
	});

	labels_table = new SortedTable("labels",
	{
		'columns':
		[
			{sClass: "center", bSearchable: true, sType: "string"},
			{
				bSortable: false,
				bSearchable: true,
				fnRender: function(oObj)
				{
					return oObj.aData[1].nl2br();
				}
			}
		],
		'options':
		{
			aaSorting: [[0, "desc"]],
			sAjaxSource: content_url({'action': "get", 'service': Ajax.param("action")})
		}
	});

	$("#edit_dialog").dialog(
	{
		width: 650,
		autoOpen: false,
		autoResize: true,
		modal: true,
		resizable: false,
		buttons:
		{
			'hidden':
			{
				class: "hidden-button"
			},
			'Сохранить': function()
			{
				labels_edit_save($(this));
			},
			'Отмена': function()
			{
				$(this).dialog("close");
			}
		},
		open: function()
		{
			$("#edit_date").datepicker("enable");
		},
		close: function()
		{
			$("#edit_date").datepicker("disable");
		}
	});

	$("#add_dialog").dialog(
	{
		width: 650,
		autoOpen: false,
		autoResize: true,
		modal: true,
		resizable: false,
		buttons:
		{
			'hidden':
			{
				class: "hidden-button"
			},
			'Сохранить': function()
			{
				labels_add_submit($(this));
			},
			'Отмена': function()
			{
				$(this).dialog("close");
			}
		},
		open: function()
		{
			$("#add_date").datepicker("enable");
		},
		close: function()
		{
			$("#add_date").datepicker("disable");
		}
	});

	$.datepicker.setDefaults($.datepicker.regional['ru']);

	$("#date_filter").datepicker(
	{
		changeMonth: true,
		changeYear: true,
		yearRange: "-1:+0",
		gotoCurrent: true,
		showButtonPanel: true,
		dateFormat: "yy-mm-dd",
		beforeShow: function(input)
		{
			clear_button(input);
		}
	});

	$("#add_date").datepicker(
	{
		changeMonth: true,
		changeYear: true,
		yearRange: "-1:+0",
		gotoCurrent: true,
		showButtonPanel: true,
		dateFormat: "yy-mm-dd",
		onClose: function(input)
		{
			$("#add_value").focus();
		}
	}).datepicker("disable");

	$("#edit_date").datepicker(
	{
		changeMonth: true,
		changeYear: true,
		yearRange: "-1:+0",
		gotoCurrent: true,
		showButtonPanel: true,
		dateFormat: "yy-mm-dd",
		onClose: function(input)
		{
			$("#edit_value").focus();
		}
	}).datepicker("disable");
}

function clear_button(input)
{
	setTimeout(function()
	{
		var buttonPane = $(input).datepicker("widget").find(".ui-datepicker-buttonpane");

		var btn = buttonPane.children().eq(0);
		btn.html("Очистить").unbind("click");
		btn.bind("click", function()
		{
			$.datepicker._clearDate(input);
		});
	}, 1);
}

function labels_delete()
{
	var params = labels_get_menu_params();

	if (!confirm("Вы действительно хотите удалить тег " + params.date + "?"))
		return;

	content_get("delete", {'service': Ajax.param("action"), 'date': params.date});

	params.row.parentNode.removeChild(params.row);
	Tables.info("Тег " + params.date + " удалён");
}

function labels_edit()
{
	var params = labels_get_menu_params();

	$("#edit_date").val(params.date);
	$("#edit_value").val(params.value);

	var edit_dialog = $("#edit_dialog");

	edit_dialog.dialog("option", "title", "Редактирование тега " + params.date);
	edit_dialog.dialog("open");

	Validator.reset();
}

function labels_edit_save(dialog)
{
	var params = labels_get_menu_params();

	var date = $("#edit_date");
	var value = $("#edit_value");

	var xml = content_get("edit", {'service': Ajax.param("action"), 'old_date': params.date, 'date': date.val(), 'value': value.val()}, "xml");
	if (!Ajax.check_error(xml, {'labels': "edit_date"}))
		return;

	labels_table.redraw();

	Tables.info("Данные тега " + params.date + " изменены");
	dialog.dialog("close");
}

function labels_add()
{
	$("#add_date,#add_value").val("");

	var add_dialog = $("#add_dialog");

	add_dialog.dialog("option", "title", "Добавление нового тега");
	add_dialog.dialog("option", "width", 450);
	add_dialog.dialog("open");

	Validator.reset();
}

function labels_add_submit(dialog)
{
	var date = $("#add_date");
	var value = $("#add_value");

	var xml = content_get("add", {'service': Ajax.param("action"), 'date': date.val(), 'value': value.val()}, "xml");
	if (!Ajax.check_error(xml, {'labels': "add_date"}))
		return;

	labels_table.redraw();

	Tables.info("Тег " + date.val() + " добавлен");
	dialog.dialog("close");
}

function labels_get_menu_params()
{
	var owner = context_menu.owner;

	var tds = $("td", owner);
	var date = tds.eq(0).text();
	var value = tds.eq(1).text();

	return {'row': owner, 'date': date, 'value': value};
}

$(labels_init);