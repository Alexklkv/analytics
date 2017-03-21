/**
 * Users
 * @uses jQuery dynatree
 * @uses Ajax
 * @uses SortedTable
 * @uses ContextMenu
 * @uses Tables
 * @uses Validator
 * @version 1.0.6
 */
Loader.styles(["jquery-dynatree"]);
Loader.scripts(["jquery-dynatree", "ajax", "sorted_table", "context_menu", "tables", "validator"], "users_init");

var users_table;
var context_menu;

function users_init()
{
	$("#button_add").bind("click", users_add);

	context_menu = new ContextMenu(
	{
		'menu': "context_menu",
		'bind_right': "#users_table tbody tr",
		'handlers':
		{
			'edit': users_edit,
			'password': users_password,
			'access': users_access,
			'delete': users_delete
		}
	});

	users_table = new SortedTable("users",
	{
		'columns':
		[
			{sClass: "center"},
			null,
			{sClass: "center"},
			{sClass: "center"},
			{sClass: "center lastchild", bSortable: false}
		],
		'options':
		{
			sAjaxSource: content_url({'action': "get_data"})
		}
	});

	$("#access_filter").bind("change", function()
	{
		users_table.table.fnFilter(this.value, 4);
	});

	$("#edit_dialog").dialog(
	{
		width: 350,
		autoOpen: false,
		autoResize: true,
		modal: true,
		resizable: false,
		buttons:
		{
			'Сохранить': function()
			{
				users_edit_save($(this));
			},
			'Отмена': function()
			{
				$(this).dialog("close");
			}
		}
	});

	$("#access_dialog").dialog(
	{
		width: 350,
		autoOpen: false,
		autoResize: true,
		modal: true,
		resizable: false,
		buttons:
		{
			'Применить': function()
			{
				users_access_apply($(this));
			},
			'Отмена': function()
			{
				$(this).dialog("close");
			}
		}
	});

	$("#password_dialog").dialog(
	{
		width: 350,
		autoOpen: false,
		autoResize: true,
		modal: true,
		resizable: false,
		buttons:
		{
			'Изменить': function()
			{
				users_password_change($(this));
			},
			'Отмена': function()
			{
				$(this).dialog("close");
			}
		}
	});

	$("#add_dialog").dialog(
	{
		width: 450,
		autoOpen: false,
		autoResize: true,
		modal: true,
		resizable: false,
		buttons:
		{
			'Добавить': function()
			{
				users_add_submit($(this));
			},
			'Отмена': function()
			{
				$(this).dialog("close");
			}
		}
	});

	var access_filter = $("#access_filter");

	var options = access_filter.children("optgroup").length;
	if (options == 0)
		access_filter.prop("disabled", true);

	$(document).on("click", "a.access_link", function()
	{
		var access_dialog = $(this).data("dialog");
		if (!access_dialog)
		{
			access_dialog = users_access_dialog(this);
			$(this).data("dialog", access_dialog);
		}

		access_dialog.dialog("open");
		return false;
	});

	$("#change_defaults").click(users_expand_add_access);
}

function users_access_dialog(link)
{
	var tds = $(link).closest("tr").children("td");
	var user_id = tds.eq(0).text();
	var user_email = tds.eq(1).text();

	var access_dialog = $("<div><div class='access_div'></div></div>").dialog(
	{
		title: "Права доступа пользователя " + user_email,
		autoOpen: false,
		resizable: false,
		buttons:
		{
			'Закрыть': function()
			{
				$(this).dialog("close");
			}
		}
	});

	users_access_load(access_dialog, user_id, false);

	return access_dialog;
}

function users_password()
{
	$("#password_old,#password_new,#password_repeat").val("");

	var params = users_get_menu_params();

	var password_dialog = $("#password_dialog");

	password_dialog.dialog("option", "title", "Изменение пароля пользователя " + params.email);
	password_dialog.dialog("open");

	Validator.reset();
}

function users_password_change(dialog)
{
	var params = users_get_menu_params();

	var password_old = $("#password_old");
	var password_new = $("#password_new");
	var password_repeat = $("#password_repeat");

	var xml = content_get("password_change", {'id': params.id, 'password_old': password_old.val(), 'password_new': password_new.val(), 'password_repeat': password_repeat.val()}, "xml");
	if (!Ajax.check_error(xml, {'auth': "password_errors"}))
		return;

	Tables.info("Пароль пользователя " + params.email + " изменён");
	dialog.dialog("close");
}

function users_access()
{
	var params = users_get_menu_params();

	var access_dialog = $("#access_dialog");
	users_access_load(access_dialog, params.id, true);

	access_dialog.dialog("option", "title", "Изменение прав пользователя " + params.email);
	access_dialog.dialog("open");

	Validator.reset();
}

function users_access_apply(dialog)
{
	var params = users_get_menu_params();
	var accesses = users_access_get(dialog).join(",");

	var xml = content_get("access_set", {'id': params.id, 'access': accesses}, "xml");
	if (!Ajax.check_error(xml, {'auth': "access_errors"}))
		return;

	Tables.info("Права пользователя " + params.email + " изменены");
	dialog.dialog("close");
}

function users_delete()
{
	var params = users_get_menu_params();

	if (!confirm("Вы действительно хотите удалить пользователя " + params.email + "?"))
		return;

	content_get("delete", {'id': params.id});

	params.row.parentNode.removeChild(params.row);
	Tables.info("Пользователь " + params.email + " удалён");
}

function users_edit()
{
	var params = users_get_menu_params();

	$("#edit_email").val(params.email);

	var edit_dialog = $("#edit_dialog");

	edit_dialog.dialog("option", "title", "Редактирование данных пользователя " + params.email);
	edit_dialog.dialog("open");

	Validator.reset();
}

function users_edit_save(dialog)
{
	var params = users_get_menu_params();

	var email = $("#edit_email");

	var xml = content_get("edit", {'id': params.id, 'email': email.val()}, "xml");
	if (!Ajax.check_error(xml, {'auth': "edit_errors"}))
		return;

	users_table.redraw();

	Tables.info("Данные пользователя " + params.email + " изменены");
	dialog.dialog("close");
}

function users_add()
{
	$("#email,#password,#password2,#access").val("");

	$("#add_access_expand").hide();

	var add_dialog = $("#add_dialog");
	users_access_load(add_dialog, 0, true);

	add_dialog.dialog("option", "title", "Добавление нового пользователя");
	add_dialog.dialog("option", "width", 450);
	add_dialog.dialog("open");

	Validator.reset();
}

function users_add_submit(dialog)
{
	var email = $("#email");
	var password = $("#password");
	var password2 = $("#password2");

	var accesses = users_access_get(dialog);

	var xml = content_get("add", {'email': email.val(), 'password': password.val(), 'password2': password2.val(), 'access': accesses}, "xml");
	if (!Ajax.check_error(xml, {'auth': "add_errors"}))
		return;

	users_table.redraw();

	Tables.info("Пользователь " + email.val() + " добавлен");
	dialog.dialog("close");
}

function users_access_get(dialog)
{
	var access_div = dialog.find(".access_div");

	var nodes = access_div.dynatree("getSelectedNodes");

	var accesses = new Array();
	for (var i = 0; i < nodes.length; i++)
		accesses[accesses.length] = nodes[i].data.key;

	return accesses;
}

function users_access_make_array(user_access, edit)
{
	var array = [];

	var accesses = user_access.children();
	for (var i = 0; i < accesses.length; i++)
	{
		var access = accesses.eq(i);

		var element = {};

		element['title'] = access.attr("title");
		element['key'] = access.attr("value");

		if (edit && access.attr("checked") == "1")
			element['select'] = true;

		var children = users_access_make_array(access, edit);
		if (children.length)
		{
			element['isFolder'] = true;
			element['children'] = children;
		}

		array[array.length] = element;
	}

	return array;
}

function users_access_load(dialog, user_id, edit)
{
	var xml = content_get("access_get", {'id': user_id, 'edit': edit}, "xml");

	var inEventHandler = false;

	var access_div = dialog.find(".access_div");
	access_div.dynatree(
	{
		minExpandLevel: 1,
		idPrefix: "",
		checkbox: edit,
		selectMode: 2,
		onSelect: function(select, dtnode)
		{
			// Ignore, if this is a recursive call
			if (inEventHandler)
				return;

			// Select all children of currently selected node
			try
			{
				inEventHandler = true;
				dtnode.visit(function(childNode)
				{
					childNode.select(select);
				});
			}
			finally
			{
				inEventHandler = false;
			}
		}


	});

	var children = users_access_make_array(xml, edit);

	var root = access_div.dynatree("getRoot");
	root.removeChildren();
	root.addChild(children);
}

function users_expand_add_access()
{
	var add_dialog = $("#add_dialog");
	var add_access_expand = $("#add_access_expand");

	add_access_expand.toggle();

	if (add_access_expand.is(":hidden"))
		add_dialog.dialog("option", "width", 450);
	else
		add_dialog.dialog("option", "width", 700);

	return false;
}

function users_get_menu_params()
{
	var owner = context_menu.owner;

	var tds = $("td", owner);
	var id = tds.eq(0).text();
	var email = tds.eq(1).text();

	return {'row': owner, 'id': id, 'email': email};
}

$(users_init);