/**
 * SortedTable
 * @uses jQuery DataTables
 * @uses Arrays
 * @version 1.0.6
 */
Loader.styles(["sorted_table"]);
Loader.scripts(["jquery-datatables", "arrays"]);

var SortedTable = function(name, data)
{
	// Public
	SortedTable.prototype.add		= sorted_table_add;
	SortedTable.prototype.redraw		= sorted_table_redraw;

	// Private
	SortedTable.prototype.init		= sorted_table_init;
	SortedTable.prototype.apply_filters	= sorted_table_apply_filters;
	SortedTable.prototype.bind_click	= sorted_table_bind_click;
	SortedTable.prototype.row_hover		= sorted_table_row_hover;
	SortedTable.prototype.setup_defaults	= sorted_table_setup_defaults;

	this.init(name, data);
}

function sorted_table_init(name, data)
{
	this.timeout_id	= null;
	this.data	= data;

	this.setup_defaults();

	this.filters = [];
	this.filters.length = data['columns'].length;

	var container = $("#" + name + "_table");

	this.table	= container.dataTable(this.data['options']);
	this.inputs	= container.find("tfoot input[type='text']");
	this.selects	= container.find("tfoot select");
	this.tfoot_filters = container.find("tfoot input[type='text'], tfoot select");

	this.apply_filters();

	var init = function(instance)
	{
		instance.inputs.on("keyup change", function()
		{
			if (instance.timeout_id != null)
				clearTimeout(instance.timeout_id);

			instance.timeout_id = setTimeout(function() {instance.apply_filters();}, 500);
		});
		instance.selects.on("change", function()
		{
			if (instance.timeout_id != null)
				clearTimeout(instance.timeout_id);

			instance.timeout_id = setTimeout(function() {instance.apply_filters();}, 500);
		});
	}

	init(this);

	var filters = data['filters'];

	for (var filter in filters)
	{
		var bind = function(instance, key, index)
		{
			$("#" + name + "_" + key + "_filter").bind("change", function()
			{
				instance.table.fnFilter(this.value, index);
			});
		};

		bind(this, filter, filters[filter]);
	}
}

function sorted_table_redraw()
{
	this.table.fnDraw();
}

function sorted_table_setup_defaults()
{
	var data = {
		'offset': 0,
		'click_check': false,
		'options': {},
		'filters': {},
		'columns': []
	};

	this.data = $.extend(true, data, this.data);

	var options = {
		'bLengthChange': true,
		'bPaginate': true,
		'bProcessing': true,
		'bServerSide': true,
		'bInfo': true,
		'bFilter': true,
		'bAutoWidth': false,
		'bSortClasses': false,
		'sPaginationType': "full_numbers",
		'sDom': 'rlt<"source_info"pi>',
		'asStripeClasses':
		[
			"",
			"grayed"
		],
		'oLanguage':
		{
			'sProcessing':		"Подождите...",
			'sLengthMenu':		"Показать _MENU_ записей",
			'sZeroRecords':		"Записи отсутствуют.",
			'sInfo':		"Записи с _START_ до _END_ из _TOTAL_ записей",
			'sInfoEmpty':		"Записи с 0 до 0 из 0 записей",
			'sInfoFiltered':	"(отфильтровано из _MAX_ записей)",
			'sInfoPostFix':		"",
			'sSearch':		"Поиск:",
			'sUrl':			"",
			'oPaginate':
			{
				'sFirst':	"Первая",
				'sPrevious':	"Предыдущая",
				'sNext':	"Следующая",
				'sLast':	"Последняя"
			}
		},
		'fnRowCallback': function(instance)
		{
			return function(row)
			{
				if (instance.data['click_check'])
					instance.bind_click(row);

				instance.row_hover(row);
				return row;
			};
		} (this),
		'fnServerData': function (sSource, aoData, fnCallback)
		{
			$.ajax({'type': "POST", 'url': sSource, 'data': aoData, 'success': fnCallback, 'dataType': "json"});
		},
		'aoColumns': data['columns']
	};

	this.data['options'] = $.extend(true, options, this.data['options']);
}

function sorted_table_apply_filters()
{
	var apply = function(instance)
	{
		instance.tfoot_filters.each(function(i)
		{
			if (typeof instance.filters[i] == "undefined")
				instance.filters[i] = "";
			if (instance.filters[i] == this.value)
				return;

			instance.filters[i] = this.value;

			instance.table.fnFilter(this.value, i + instance.data['offset']);
		});
	}

	apply(this);

	this.timeout_id = null;
}

function sorted_table_bind_click(row)
{
	$(row).bind("click", function()
	{
		var checkbox = $(this).find("td:eq(0) input[type='checkbox']");
		checkbox.prop("checked", !checkbox.prop("checked"));
	});

	$(row).find("input, select, textarea").bind("click dblclick", function(event)
	{
		event.stopImmediatePropagation();
	});
}

function sorted_table_row_hover(row)
{
	$(row).hover(
		function()
		{
			$(this).addClass("col_selected");
		},
		function()
		{
			$(this).removeClass("col_selected");
		}
	);
}

function sorted_table_add(data, clear)
{
	if (clear)
		this.table.fnClearTable();

	for (var i = 0; i < data.length; i++)
	{
		var text = data.eq(i).text();

		var tds = $(text).find("td");
		var items = Arrays.make_html(tds);

		this.table.fnAddData(items, false);
	}

	this.table.fnDraw();
}