/**
 * Analytics
 * @version 1.0.3
 */

$.ajaxPrefilter(function(options, originalOptions) {
	if (options.dataType !== "script" && originalOptions.dataType !== "script")
		return;

	options.cache = true;
});

Loader.styles(["jquery-dynatree", "jquery-chosen"]);
Loader.scripts(["datepicker", "string", "notify", "jquery-mobile-taps", "jquery-dynatree", "jquery-chosen", "context_menu"]);

var an;

function analytics_init()
{
	AmCharts.shortMonthNames = ["Янв", "Фев", "Март", "Апр", "Май", "Июнь", "Июль", "Авг", "Сент", "Окт", "Ноя", "Дек"];
	AmCharts.shortDayNames = ["Вс", "Пн", "Вт", "Ср", "Чт", "Пт", "Сб"];
	AmCharts.useUTC = true;

	var options = {
		'colors': ["#72ADD2", "#8EC59E", "#FFCC33", "#5C9746", "#965136", "#367B96", "#D6707B", "#3366FF", "#814596", "#6FD6CC", "#FE8C12", "#4F6EA9", "#32C370", "#C52A79", "#B0AFA4", "#E45430"],
		'columns': 1,
		'min_width': 500,
		'min_height': 400,
		'is_api_reports' : (typeof api_reports === "undefined") ? false : api_reports,
		'is_subservient_section' : false,
		'subservient_handler' : null
	};

	if (typeof global_options !== "undefined")
		$.extend(options, global_options);

	$("a,button").each(function () {
		if (typeof $(this).attr("tabindex") !== "undefined")
			return;

		$(this).attr("tabindex", -1);
	});
	$("li.report_active").parent().find("li").clone().appendTo("#reports_active");

	an = new Analytics(service, options, (typeof main_report === "undefined"));
}

function Analytics(service, options, no_report)
{
	this.isMobile = (navigator.userAgent.match(/Android/i) !== null || navigator.userAgent.match(/BlackBerry/i) !== null || navigator.userAgent.match(/iPhone|iPad|iPod/i) !== null || navigator.userAgent.match(/Opera Mini/i) !== null || navigator.userAgent.match(/IEMobile/i) !== null);
	this.colors = options.colors;

	this.columns = options.columns;
	this.minWidth = options.min_width;
	this.minHeight = options.min_height;
	this.simpleMode = no_report;
	this.isApiReports = options.is_api_reports;
	this.isSubservientSection = options.is_subservient_section;
	this.subservientHandler = options.subservient_handler;

	this.service = service;

	this.ajaxCalls = [];
	this.timeouts = {'resize': 0};
	this.reports = [];
	this.checkedApiReports = {};
	this.charts = [];
	this.table = false;
	this.refreshBlock = false;

	this.connectableTypes = {
		'single': ["single", "filled"],
		'filled': ["single", "filled"],
		'round': [],
		'candles': [],
		'nodate': [],
		'stacked': ["single"]
	};

	this.contextMenu;
	this.averageEnabled = options.show_average;
	this.percentsEnabled = options.show_percent;
	this.differenceEnabled = options.show_difference;
	this.lineGraphs = options.line_graphs;
	this.fontWidth = {' ': 4};

	this.isLegendReduced = false;
	this.addIndicatorMenu = false;
	this.indicatorStatus = false;
	this.indicatorsEnabled = true;

	this.liveStreamIntervalId = false;

	Analytics.prototype.addDialog			= analytics_add_dialog;
	Analytics.prototype.getData			= analytics_get_data;
	Analytics.prototype.setData			= analytics_set_data;
	Analytics.prototype.refreshData			= analytics_refresh_data;
	Analytics.prototype.saveParams 			= analytics_save_params;

	Analytics.prototype.Notify			= new Notify();

	Analytics.prototype.showContextMenu		= analytics_context_menu;
	Analytics.prototype.reduceLegend		= analytics_reduce_legend;
	Analytics.prototype.updateSelectors		= analytics_update_selectors;
	Analytics.prototype.hideChart			= analytics_hide_chart;
	Analytics.prototype.showAverage			= analytics_show_average;
	Analytics.prototype.showPercents		= analytics_show_percents;
	Analytics.prototype.showDifference		= analytics_show_difference;
	Analytics.prototype.showAllCharts		= analytics_show_all_charts;
	Analytics.prototype.switchGraphType		= analytics_switch_graph_type;
	Analytics.prototype.handleSumline		= analytics_handle_sumline;
	Analytics.prototype.updateColumns		= analytics_update_columns;

	Analytics.prototype.exportDialog		= analytics_export_dialog;
	Analytics.prototype.exportReport		= analytics_export_report;
	Analytics.prototype.exportBuildForm		= analytics_export_build_form;

	Analytics.prototype.addIndicatorDialog		= analytics_add_indicator_dialog;
	Analytics.prototype.removeIndicator		= analytics_remove_indicator;
	Analytics.prototype.addSimpleIndicator		= analytics_add_simple_indicator;
	Analytics.prototype.removeSimpleIndicator	= analytics_remove_simple_indicator;

	Analytics.prototype.init		= function()
	{
		$(window).bind("resize", function() {
			if (this.timeouts.resize)
				clearTimeout(this.timeouts.resize);
			this.timeouts.resize = setTimeout(this.resize.bind(this), 150);
		}.bind(this));
		$("#reports").after($("<input id='report-search' tabindex='1' placeholder='Поиск' type='text' value='' />"));
		$("#reports").before($("<a id='home-button' href='?module=analytics&action=" + this.service + "' title='" + services_list[this.service] + "'><i class='icon-home'></i></a>"));
		$("#report-search").autocomplete({
			'select': function (event, ui) { window.location.href = window.location.protocol + "//" + window.location.host + "/?module=analytics&action=" + this.service + "&report=" + ui.item.id; }.bind(this),
			'source': function (request, response) { response(this.searchReport(request.term)); }.bind(this)
		});

		$(window).bind("keypress", function (e) {
			if (this.isMobile === true)
				return;
			if (e.charCode === 0)
				return;
			if (e.altKey === true || e.ctrlKey === true)
				return;
			if (e.target.id === $("#report-search").attr("id"))
				return;
			if ($(e.target).hasClass("ignore-keypress"))
				return;

			var regexp = new RegExp(/[a-zа-яeё`]/i);
			var input = String.fromCharCode(e.charCode);

			if (regexp.test(input) === false)
				return;

			$("#report-search").focus();
		}.bind(this));

		if (this.isSubservientSection)
		{
			this[this.subservientHandler]();
			return;
		}

		if (this.isApiReports)
		{
			this.simpleMode = false;
			main_report = "apipath_common";
			this.setApiReportsEvents();
		}

		if (this.simpleMode)
		{
			this.setSimpleEvents();
			this.parseSimpleData(service_indicators);
			this.resize();
			return;
		}

		this.setEvents();
		this.updateSelectorsHelper(0, true);
		this.contextMenu = new ContextMenu(
		{
			'menu': "context_menu",
			'bind_right': ".graph",
			'bind_touch': ".graph",
			'width': 180,
			'handlers':
			{
				'add': this.addDialog.bind(this),
				'hide': this.hideChart.bind(this),
				'reduce': this.reduceLegend.bind(this),
				'sumline': this.handleSumline.bind(this),
				'average': this.showAverage.bind(this),
				'percents': this.showPercents.bind(this),
				'difference': this.showDifference.bind(this),
				'show': this.showAllCharts.bind(this),
				'column': this.updateColumns.bind(this),
				'graph_type': this.switchGraphType.bind(this),
				'export': function () { $("#export_dialog").dialog("open"); }
			},
			'show': this.showContextMenu.bind(this)
		});

		if (!this.isApiReports)
			this.getData(main_report, function (data) { if (data.indicators) this.createIndicators(data.indicators); }.bind(this));

		AmCharts.ChartCursor.prototype.handleMouseDown = handle_right_mouse;
		AmCharts.AmLegend.prototype.createEntry = legend_with_image;

		if (window.navigator.platform === "MacIntel" || window.navigator.platform === "Macintosh" || window.navigator.platform === "MacPPC" || window.navigator.platform === "Mac68K")
			return;

		AmCharts.AmBalloon.prototype.draw = balloons_show; // No remove/create div (only once), using tranform-translate not LEFT-TOP
		AmCharts.AmBalloon.prototype.destroy = balloons_hide;
	};

	Analytics.prototype.addChart			= analytics_add_chart;
	Analytics.prototype.editChart			= analytics_edit_chart;

	Analytics.prototype.addIndicator		= analytics_add_indicator;
	Analytics.prototype.calcIndicators		= analytics_calc_indicators;
	Analytics.prototype.createIndicators		= analytics_create_indicators;
	Analytics.prototype.setIndicators		= analytics_set_indicators;

	Analytics.prototype.searchReport		= analytics_search_report;
	Analytics.prototype.gotoReport			= analytics_goto_report;

	Analytics.prototype.getSimpleData		= analytics_get_simple_data;
	Analytics.prototype.parseSimpleData		= analytics_parse_simple_data;
	Analytics.prototype.getUTCDate			= analytics_get_utc_date;

	Analytics.prototype.setEvents			= analytics_set_events;
	Analytics.prototype.setSimpleEvents		= analytics_set_simple_events;
	Analytics.prototype.setApiReportsEvents		= analytics_set_api_reports_events;
	Analytics.prototype.resize			= analytics_resize;
	Analytics.prototype.updateSelectorsHelper	= analytics_update_selectors_helper;

	Analytics.prototype.clearReport			= analytics_clear_report;
	Analytics.prototype.errors			= analytics_errors;
	Analytics.prototype.format			= analytics_format;
	Analytics.prototype.getColor			= analytics_get_color;

	Analytics.prototype.refreshApiReportsTable	= analytics_refresh_api_reports_table;
	Analytics.prototype.refreshApiReportsGraph	= analytics_refresh_api_reports_graph;

	Analytics.prototype.setEditLiveStreamEvents	= analytics_set_edit_live_stream_events;
	Analytics.prototype.submitLiveStreamData	= analytics_submit_live_stream_data;
	Analytics.prototype.liveStreamSelectorChange	= analytics_live_stream_selector_change;
	Analytics.prototype.liveStreamSetPeriodStep	= analytics_live_stream_set_period_step;
	Analytics.prototype.updateLiveStream		= analytics_update_live_stream;

	this.init();
}

function Chart(Analytics, params)
{
	this.Analytics = Analytics;

	this.amChart;

	this.report = params.report;
	this.type = params.type;
	this.data = params.data;
	this.params = params.graph;
	this.index = params.index;

	this.legendMenu = false;
	this.withSumLine = false;
	this.sumGraph;

	Chart.prototype.init	= function ()
	{
		if (!(this.type + "Chart" in this))
		{
			this.Analytics.errors(1, "add", {'message': "", 'log': "No method: " + this.type + "Chart in class Chart"});
			return false;
		}

		this[this.type + "Chart"]();
	};

	Chart.prototype.defaults		= chart_defaults;
	Chart.prototype.candlesChart		= chart_candles;
	Chart.prototype.roundChart		= chart_round;
	Chart.prototype.nodateChart		= chart_nodate;
	Chart.prototype.singleChart		= chart_single;
	Chart.prototype.filledChart		= chart_filled;
	Chart.prototype.stackedChart		= chart_stacked;
	Chart.prototype.weeklyChart		= chart_single;
	Chart.prototype.weekly_ybChart		= chart_single;
	Chart.prototype.monthlyChart		= chart_single;
	Chart.prototype.specialChart		= chart_nodate;

	Chart.prototype.amChartSerial		= chart_amchart_serial;
	Chart.prototype.serialGraphs		= chart_serial_graphs;

	Chart.prototype.singleGraph		= chart_single_graph;
	Chart.prototype.nodateGraph		= chart_nodate_graph;
	Chart.prototype.filledGraphs		= chart_filled_graphs;
	Chart.prototype.filledGraph		= chart_filled_graph;
	Chart.prototype.stackedGraphs		= chart_stacked_graphs;
	Chart.prototype.stackedGraph		= chart_stacked_graph;

	Chart.prototype.valueAxisScroll		= chart_value_axis_scroll;
	Chart.prototype.drawSumline		= chart_draw_sumline;
	Chart.prototype.getLegendImage		= chart_get_legend_image;
	Chart.prototype.balloonFunction		= chart_balloon_function;
	Chart.prototype.balloonStacked		= chart_balloon_stacked;
	Chart.prototype.createItemSelector	= chart_create_item_selector;

	Chart.prototype.connect			= chart_connect;
	Chart.prototype.mergeData		= chart_merge_data;
	Chart.prototype.calcSumlineData		= chart_calc_sumline_data;

	Chart.prototype.showAverage		= chart_show_average;
	Chart.prototype.showPercents		= chart_show_percents;
	Chart.prototype.switchGraphType		= chart_switch_graph_type;
	Chart.prototype.handleSumline		= chart_handle_sumline;
	Chart.prototype.reduceLegend		= chart_reduce_legend;
	Chart.prototype.updateLegend		= chart_update_legend;
	Chart.prototype.updateTitle		= chart_update_title;
	Chart.prototype.addGraphMenu		= chart_add_graph_menu;

	Chart.prototype.enable			= chart_enable;
	Chart.prototype.disable			= chart_disable;
	Chart.prototype.getSizes		= chart_get_sizes;

	Chart.prototype.getColor		= this.Analytics.getColor;
	Chart.prototype.format			= this.Analytics.format;
	Chart.prototype.removeGrouped		= chart_remove_grouped;

	this.init();
}

function Table(Analytics, report)
{
	this.Analytics = Analytics;

	this.report = report;

	this.container;
	this.table;

	this.columns;
	this.groups;

	Table.prototype.init = function ()
	{
		this.Analytics.indicatorsEnabled = false;
		this.container = $("<div class='an-table'></div>");
		$("#chart").removeAttr("style");
		$("#chart").before(this.container);
		$("#info").hide();

		this.createTable();
		this.updateValues();
		this.repaint();

		$("#an-indicator").hide();
	};

	Table.prototype.createTable	= table_create_table;
	Table.prototype.updateValues	= table_update_values;
	Table.prototype.writeValue	= table_write_value;
	Table.prototype.createValueCell	= table_create_value_cell;
	Table.prototype.calcValue	= table_calc_value;
	Table.prototype.addMenu		= table_add_menu;
	Table.prototype.repaint		= table_repaint;
	Table.prototype.updateMinMax	= table_update_min_max;
	Table.prototype.colEditForm	= table_col_edit_form;
	Table.prototype.sendColData	= table_send_col_data;
	Table.prototype.createEditForm	= table_create_edit_form;
	Table.prototype.showColImage	= table_show_col_image;
	Table.prototype.hideColImage	= table_hide_col_image;


	this.hoverValue = function (mobile, event)
	{
		if (mobile === true)
		{
			if (event.type !== "touchend")
				return;

			if (this.hovered === true)
				event.type = "mouseleave";
			else
				event.type = "mouseenter";
		}

		if (event.type === "touchend")
			return;

		if (event.type === "mouseenter")
		{
			for (var key in this.values)
			{
				var cell = this.values[key];

				cell.valueContainer.text(cell.hoverValue);
			}

			this.hovered = true;
			return;
		}

		for (var key in this.values)
		{
			var cell = this.values[key];

			cell.valueContainer.text(cell.visibleValue);
		}

		this.hovered = false;
	};
	this.groupButtonHandler = function (group, event)
	{
		event.preventDefault();
		$(event.target).blur();

		var length = $(".an-table-groups-button.active").length;
		if (group.active === true)
			return;

		$(".an-table-legend,.an-table-value").hide();
		$(".an-table-groups-button").removeClass("active");

		for (var key in this.groups)
			this.groups[key].active = false;

		group.active = true;
		group.button.addClass("active");

		for (var key in group.rows)
		{
			var row = group.rows[key];
			if (row.sum === 0)
			{
				row.tr.hide();
				continue;
			}

			row.tr.show();

			for (var vkey in row.values)
				row.values[vkey].container.show();
		}
		for (var key in group.delimiters)
		{
			var delimiter = group.delimiters[key];
			delimiter.attr("colspan", group.columns.length + 1);
		}

		for (var key in group.columns)
			group.columns[key].show();

		this.repaint();

		if ($("#tableFilter").length > 0)
			this.updateMinMax();
	};

	this.init();
}

function ItemSelector(chart, selected)
{
	this.dialog;
	this.content;
	this.searchInput;

	this.Chart = chart;

	this.hidden = false;
	this.defaults = {};

	this.settings = chart.params.legend_menu;
	this.sums = chart.params.sums;
	this.counts = chart.params.counts;
	this.show_sums = chart.params.show_sums;
	this.legend = chart.params.legend;
	this.selected = selected;

	this.options = {'mainChart': chart.amChart, 'valueAxes': {'normal': false, 'merged': false}};

	ItemSelector.prototype.create = function ()
	{
		var that = this;
		var index = this.Chart.index;
		var key = this.Chart.params.report_key;
		var chart = this.Chart.amChart;

		this.options.valueAxes.normal = chart.valueAxes[0];

		this.dialog = $("<div id='an-item-selector-" + key + index + "' class='an-item-selector'></div>");
		this.header = $("<div class='an-item-selector-head'><span>" + this.Chart.chartName + "</span><button>&#8211</button></div>");
		this.content = $("<div class='an-item-selector-content'></div>");
		this.searchInput = $("<input class='an-item-selector-search ignore-keypress' type='search' placeholder='Поиск' value='' />");

		this.dialog.append(this.header);

		if (this.settings.search === true)
		{
			var searchFunction = function (pattern)
			{
				var title = this.data.title;
				var translit_regexp = new RegExp(transliterate(pattern).replace(/\ |\\/g, "").split("").join("[a-zа-яeё -]*"), "i");
				var regexp = new RegExp(pattern.replace(/\ |\\/g, "").split("").join("[a-zа-яeё -\/]*"), "i");

				if (title.search(regexp) === -1 && title.search(translit_regexp) === -1)
					return false;
				return true;
			};

			this.searchInput.bind("input", function () {
				this.content.dynatree("getRoot").search(this.searchInput.val(), searchFunction);
			}.bind(this));
			this.dialog.append(this.searchInput);

			this.dialog.css("padding-top", 59);
			this.header.css("margin-top", -59);
		}

		this.header.children("button").bind("click", function ()
		{
			this.hidden = (this.hidden === false ? true : false);

			if (this.hidden === false)
			{
				this.dialog.resizable("enable");
				this.header.animate({'margin-top': this.defaults.headerMarginTop}, 200);
				this.dialog.animate({'height': this.defaults.height, 'border-bottom-style': "solid", 'padding-bottom': this.defaults.paddingBottom, 'padding-top': this.defaults.paddingTop}, 200);

				return;
			}

			this.defaults = {
				'height': this.dialog.height(),
				'paddingBottom': this.dialog.css("padding-bottom"),
				'paddingTop': this.dialog.css("padding-top"),
				'headerMarginTop': this.header.css("margin-top")
			};

			this.dialog.resizable("disable").removeClass("ui-state-disabled");
			this.header.animate({'margin-top': "-29px"}, 200);
			this.dialog.animate({'height': 0, 'border-bottom-style': "none", 'padding-bottom': 0, 'padding-top': 29}, 200);
		}.bind(this));

		this.dialog.append(this.content);
		this.write();
	};
	ItemSelector.prototype.clickEvent	= itemselector_click_event;
	ItemSelector.prototype.write		= itemselector_write;
}

/**
 * Analytics functions
 */
function analytics_refresh_data(index)
{
	if (!(index in this.ajaxCalls))
		return;

	var params = this.ajaxCalls[index];
	$.get(
		"/",
		params,
		function (result) {
			this.setData(result);
			this.refreshData(++index);
			this.refreshBlock = false;
		}.bind(this),
		"json"
	).fail(
		function (result) {
			this.errors(1, "data", {'message': "", 'log': result.responseText});
			this.refreshData(++index);
			this.refreshBlock = false;
		}.bind(this)
	);
}

function analytics_add_dialog()
{
	var graph_id = this.contextMenu.owner.id.substr(5);
	var menu = $("#add_graphs");

	menu.dialog("option", "title", "Добавление нового графика");
	menu.dialog("option", "buttons", {
		'Добавить': this.getData.bind(this, null, menu.dialog.bind(menu, "close")),
		'Отмена': menu.dialog.bind(menu, "close")
	});

	this.addIndicatorMenu = false;
	this.updateSelectorsHelper(3, false, graph_id);

	menu.dialog("open");
}

function analytics_reduce_legend()
{
	this.isLegendReduced = (this.isLegendReduced === true ? false : true);

	for (var key in this.charts)
		this.charts[key].reduceLegend();

	this.resize();
}

function analytics_context_menu()
{
	var menu_id = this.contextMenu.menu.selector;
	var graph = $(this.contextMenu.owner).attr("type");

	$(menu_id + "_reduce").children().text("Сократить легенду");
	$(menu_id + "_average").children().text("Показать среднее");
	$(menu_id + "_percents").children().text("Показать проценты");
	$(menu_id + "_difference").children().text("Показать сравнение");
	$(menu_id + "_column").children().text("В две колонки");
	$(menu_id + "_sumline").children().text("Показать график сумм");
	$(menu_id + "_graph_type").children().text("Прямые графики").attr("title", "Показывать прямые графики");;

	this.contextMenu.keytoggle("column", true);
	this.contextMenu.keytoggle("hide", true);
	this.contextMenu.keytoggle("average", true);
	this.contextMenu.keytoggle("percents", true);

	if ($(".graph:visible").length <= 1)
	{
		this.contextMenu.keytoggle("hide", false);
		this.contextMenu.keytoggle("column", false);
	}

	if (graph === "round" || graph === "candles")
	{
		this.contextMenu.keytoggle("export", false);
		this.contextMenu.keytoggle("average", false);
		this.contextMenu.keytoggle("percents", false);
		this.contextMenu.keytoggle("sumline", false);
	}
	if (graph === "filled")
		this.contextMenu.keytoggle("sumline", false);

	if (this.averageEnabled)
		$(menu_id + "_average").children().text("Убрать среднее");

	if (this.percentsEnabled)
		$(menu_id + "_percents").children().text("Убрать проценты");

	if (this.differenceEnabled)
		$(menu_id + "_difference").children().text("Убрать сравнение");

	if (this.columns !== 1)
		$(menu_id + "_column").children().text("В одну колонку");

	if (this.isLegendReduced)
		$(menu_id + "_reduce").children().text("Вернуть легенду");

	if (this.contextMenu.owner.id.substr(5) in this.charts && this.charts[this.contextMenu.owner.id.substr(5)].withSumLine === true)
		$(menu_id + "_sumline").children().text("Скрыть график сумм");

	if (this.lineGraphs)
		$(menu_id + "_graph_type").children().text("Кривые графики").attr("title", "Показывать кривые графики");
}

function analytics_get_data(first, callback)
{
	var params = {
		'module': "analytics",
		'service': this.service,
		'action': "load_one",
		'report': first,
		'no_indicators': (this.isMobile ? 1 : 0)
	};

	if (first === null)
	{
		params.service = $("#service_selector").val();
		params.report = $("#report_selector").val();
		params.graph = $("#graph_selector").val();
		params.connect = $("#connect_selector").val();
		params.no_indicators = 1;
	}

	if (this.isApiReports && first !== null)
	{
		var connect = [];
		for (var key in this.checkedApiReports)
			connect.push(key);

		params.api_path_id = connect.join(",");
	}

	if (this.activeAjax === undefined)
		this.activeAjax = 0;
	this.activeAjax += 1;

	$.get(
		"/",
		params,
		function (currentAjax, first, result) {
			if (currentAjax !== this.activeAjax)
				return;

			if (first !== null)
			{
				this.resizing = true;
				$("#report-title h2").html(result.report.title);
				$("#report-title h4").html(result.report.description);
				this.resizing = false;
			}

			this.ajaxCalls.push(params);
			this.setData(result);

			if (typeof callback === "function")
				callback(result);
		}.bind(this, this.activeAjax, first),
		"json"
	).fail(function(result) { this.errors(1, "data", {'message': "", 'log': result.responseText}); }.bind(this));
}

function analytics_set_data(result)
{
	if (!result)
		return;

	var index = this.reports.push(result);

	this.addChart(index - 1);

	if (!this.isMobile && result.indicators)
		this.createIndicators(result.indicators);
}

function analytics_add_chart(index)
{
	var report = this.reports[index];

	this.index = index;

	if (report.type !== "table" && report.type !== "monthly" && report.type !== "special")
		this.indicatorsEnabled = true;

	if (report.data.length == 0)
		return this.errors(0, "empty", {'message': "", 'log': "No data for report: " + report.report.path});

	if (report.type === "table")
		return this.table = new Table(this, report);

	if (report.graphs.length == 0)
		return this.errors(0, "empty", {'message': "", 'log': "No data for report: " + report.report.path});

	if (report.connect.target != "" && report.connect.target !== false)
		return this.editChart(report.connect.target, {'report': report.report, 'type': report.type, 'data': report.data[report.connect.graph], 'graph': report.graphs[report.connect.graph], 'index': report.connect.graph});

	for (var key in report.data)
	{
		if (report.type === "candles" || report.type === "round")
		{
			this.charts.push(new Chart(this, {'report': report.report, 'type': report.type, 'data': report.data, 'graph': report.graphs, 'index': 0}));
			break;
		}

		if (!(key in report.graphs))
			continue;

		var chart = new Chart(this, {'report': report.report, 'type': report.type, 'data': report.data[key], 'graph': report.graphs[key], 'index': key});
		if (chart !== false)
			this.charts.push(chart);

		delete chart;
	}

	if (this.charts.length === 0)
		return;

	$("#chart").show();
	$("#info").hide();

	this.resize();

	if (window.location.hash === "")
		return;

	window.location.hash = [window.location.hash, window.location.hash = ""][0];
}

function analytics_edit_chart(index, new_chart)
{
	if (!(index in this.charts))
	{
		this.errors(0, "edit", {'message': "", 'log': "No specific chart found: " + index});
		return;
	}

	var chart = this.charts[index];

	if (this.connectableTypes[chart.type].indexOf(new_chart.type) === -1)
	{
		this.errors(0, "edit", {'message': "", 'log': "Can't connect '" + new_chart.type + "' to '" + chart_type.type + "'"});
		return;
	}

	chart.connect(new_chart);
	this.resize();
}

function analytics_show_average()
{
	for (var key in this.charts)
		this.charts[key].showAverage();

	this.averageEnabled = (this.averageEnabled === true ? false : true);
	this.saveParams("show_average", this.averageEnabled);
}

function analytics_hide_chart()
{
	if ($(".graph:visible").length == 1)
	{
		this.errors(2, null, {'message': "Это последний график на странице, его скрыть нельзя", 'log': ""});
		return;
	}

	var that = this;

	$(this.contextMenu.owner).hide(10, function () { that.resize(); });
	$("#context_menu_show").show();
}

function analytics_show_all_charts()
{
	var that = this;

	$("#context_menu_show").hide();
	$(".graph:hidden").show(10, function () { that.resize(); });
}

function analytics_switch_graph_type()
{
	for (var key in this.charts)
		this.charts[key].switchGraphType();

	this.resize();
	this.lineGraphs = (this.lineGraphs === true ? false : true);
	this.saveParams("line_graphs", this.lineGraphs);
}

function analytics_handle_sumline()
{
	var chart_id = this.contextMenu.owner.id.substr(5);
	if (!(chart_id in this.charts))
		return;

	this.charts[chart_id].handleSumline();
}

function analytics_show_percents()
{
	for (var key in this.charts)
		this.charts[key].showPercents();

	this.percentsEnabled = (this.percentsEnabled === true ? false : true);
	this.saveParams("show_percent", this.percentsEnabled);
}

function analytics_show_difference()
{
	this.differenceEnabled = (this.differenceEnabled === true ? false : true);
	for (var index in this.charts)
	{
		var chart = this.charts[index];
		if (chart.type == "round" || chart.type == "candles")
			continue;

		chart.amChart.showLegendImages = this.differenceEnabled;
	}

	this.resize();
	this.saveParams("show_difference", this.differenceEnabled);
}

function analytics_update_columns()
{
	this.columns = (this.columns == 1 ? 2 : 1);
	this.resize();
}

function analytics_export_dialog()
{
	$("input[name=filename]").val(main_report);
	$("#export_type").bind("change", function ()
	{
		if ($(this).val() == 0)
			return $("#export_reports").attr("disabled", true).trigger("chosen:updated");
		$("#export_reports").attr("disabled", false).trigger("chosen:updated");
	});
	$("#export_reports").empty();

	for (var key in reports_list[this.service])
	{
		var report = reports_list[this.service][key];
		var selected = " ";

		if (report.path == main_report)
			selected += "selected='selected'";

		$("#export_reports").append($("<option value='" + report.path + "'" + selected + " >" + report.title + "</option>"));
	}

	$("#export_reports").chosen({'no_results_text': "Ничего не найдено", 'disable_search_threshold': 3, 'width': "220px", 'allow_single_deselect': false});
	$(".chosen-choices").bind("click", function ()
	{
		$("#export_type").val(1).trigger("change");
		$("#export_reports").trigger("chosen:open");
	});
}

function analytics_export_report(dialog)
{
	if ($("#export_processing").length > 0)
		return;

	var reports = $("#export_reports").val();
	var type = $("#export_type").val();
	var filename = $("input[name=filename]").val();
	if (!filename)
		filename = main_report;
	if ((reports == null || reports.length == 0) && type == 1)
		return this.errors(1, null, {'message': "Не выбрано ни одного отчета для экспорта", 'log' : ""});
	if (type != 0 && type != 1)
		return this.errors(1, null, {'message': "Неверный тип экспорта", 'log': ""});
	if (this.reports[0].type == "candles")
		return this.errors(1, null, {'message': "Нельзя экспортировать данный тип графиков", 'log': ""});

	if (type == 1)
		return this.exportBuildForm("?module=analytics&action=export", {'service': this.service, 'reports': reports, 'filename': filename, 'type': 1});

	var active_legend = {};
	for (var key in this.charts)
	{
		var chart = this.charts[key];
		var index = chart.index;

		for (var gkey in chart.amChart.graphs)
		{
			var graph = chart.amChart.graphs[gkey];

			if (graph.valueField.substr(0, 5) !== "value")
				continue;
			if (graph.hidden === true)
				continue;
			if (graph.reportName != (this.service + "_" + main_report))
				continue;

			if (!(index in active_legend))
				active_legend[index] = {};

			var title = graph.title;
			if (title === graph.fullTitle)
				title = title.substring(title.indexOf(")") + 2);

			active_legend[index][graph.legendId] = title;
		}
	}

	this.exportBuildForm("?module=analytics&action=export&report=" + main_report, {'service': this.service, 'active_legend': active_legend, 'filename': filename, 'type': 0});
}

function analytics_export_build_form(url, data)
{
	var form = $("<form style='display: none;' method='post' action='" + url + "'></form>");
	for (var key in data)
	{
		var value = data[key];

		if (key != "reports" && key != "active_legend")
		{
			form.append($("<input type='text' name='" + key + "' value='" + value + "' />"));
			continue;
		}

		if (key != "active_legend")
		{
			for (var rkey in value)
				form.append($("<input type='text' name='reports[" + rkey + "]' value='" + value[rkey] + "' />"));
		}

		for (var rkey in value)
		{
			for (var lkey in value[rkey])
				form.append($("<input type='text' name='active_legend[" + rkey + "][" + lkey + "]' value='" + value[rkey][lkey] + "' />"));
		}
	}

	$("body").append(form);
	this.errors(2, null, {'message': "Данные собираются, не покидайте и не обновляйте страницу", 'log': ""});
	form.submit().remove();
	$("#export_dialog").dialog("close");
}

function analytics_calc_indicators(time)
{
	if (this.indicatorStatus === false)
		return;

	for (var key in this.indicatorsData)
	{
		var indicator = this.indicatorsData[key];

		for (var ikey in indicator.charts)
		{
			var target = $("tr[data-report=" + key + "][data-chart=" + ikey + "]").find("td[data-type=value]");
			var data = indicator.charts[ikey].data;

			if (!data[time])
			{
				target.text("Нет данных");
				continue;
			}

			target.text(this.format(parseFloat(data[time])) + indicator.charts[ikey].value_append);
		}
	}
}

function analytics_create_indicators(result)
{
	var that = this;
	var data = result.reports;

	if (this.indicatorsEnabled === false)
		return $("#an-indicator").hide();
	if ($("#an-indicator").length > 0)
		return this.setIndicators(data);

	var container = $("<div id='an-indicator' class='an-indicator'></div>");
	var slider = $("<div class='an-slider'><p>Основные показатели</p></div>");
	var content = $("<div class='an-indicator-content'></div>");
	var indicators_container = $("<table id='indicators_container'><tbody></tbody></table>");
	var indicators_add = $("<button type='button' tabindex='-1' class='an-indicator-add'>Добавить индикатор</button>");

	indicators_add.button();
	indicators_add.bind("click", function () { that.addIndicatorDialog(); });
	slider.bind("click", function () {
		if (that.indicatorStatus === true)
			$(this).parent().animate({'right': ($(this).parent().outerWidth() - $(this).outerWidth()) * -1 }, 200);
		else
			$(this).parent().animate({'right': 0}, 200);
		that.indicatorStatus = (that.indicatorStatus === true ? false : true);
	});


	this.setIndicators(data, indicators_container);

	content.append(indicators_container).append(indicators_add);
	container.append(slider).append(content);
	container.css("top", ($("#reports_active").position().top + $("#reports_active").outerHeight())).css("display", "none");
	$("body").append(container);

	container.css("right", (container.outerWidth() - slider.outerWidth()) * -1).show(0);
}

function analytics_set_indicators(result, container, one)
{
	if (!container)
		container = $("#indicators_container");
	if (container.length == 0)
		return;
	if ($("#an-indicator:visible").length === 0)
		$("#an-indicator").show();

	var that = this;
	for (var key in result)
	{
		var indicator = result[key];

		for (var ikey in indicator.charts)
		{
			if (container.find("tr[data-report=" + key + "][data-chart=" + ikey + "]").length > 0)
				continue;

			var values = indicator.charts[ikey];
			var row = $("<tr data-report='" + key + "' data-chart='" + ikey + "'></tr>");
			var name_column = $("<td data-type='name'></td>");
			var value_column = $("<td align='right' data-type='value'></td>");
			var remove_button = $("<span title='Удалить' class='an-indicator-remove ui-icon ui-icon-closethick'></span>");
			var remove_column = $("<td align='right' data-type='remove'></td>").append(remove_button);;

			remove_button.bind("click", function () { that.removeIndicator($(this)); });

			if (values.title != values.sub_title)
				name_column.text(indicator.title + " (" + values.title + " / " + values.sub_title + ")");
			else
				name_column.text(indicator.title + " (" + values.title + ")");
			value_column.text("0");

			row.append(name_column).append(value_column).append(remove_column);
			container.append(row);
		}
	}

	if (one)
		this.indicatorsData[one] = result[one];
	else
		this.indicatorsData = result;
}

function analytics_add_indicator()
{
	var report = $("#report_selector").val();
	var value = [$("#graph_selector").val(), $("#legend_selector").val()];

	if (report === main_report)
		return;

	var that = this;
	var callback = function (e) {
		$.get(
			"?module=analytics&action=load_indicators",
			{
				'service': that.service,
				'report': report,
				'skip_report': main_report,
				'date_begin': $("#date_begin").val(),
				'date_end': $("#date_end").val(),
				'type': "report"
			},
			function (result) {
				that.setIndicators(result.reports, false, report);
			},
			"json"
		);
	};

	this.saveParams("indicator_handler", true, {'report': this.service + "_" + report, 'value': value}, callback);
}

function analytics_add_indicator_dialog()
{
	var that = this;

	if ($("#legend_selector").length == 0)
	{
		var legend_selector = $("<select id='legend_selector'></select>");
		var legend_container = $("<tr></tr>").append(
			$("<td style='vertical-align: top; padding-right: 10px;'></td>")
				.append(
				$("<div style='padding-bottom: 3px;'></div>")
				.append(
				$("<label></label>")
					.attr("for", "legend")
					.text("Легенда")
				)
				.append($("<br />"))
				.append(legend_selector)
			)
		);
		$("#add_graphs").find("table tbody").append(legend_container);
	}

	$("#add_graphs").dialog("option", "title", "Добавление нового индикатора");
	$("#add_graphs").dialog("option", "buttons", {
		'Добавить': function()
		{
			that.addIndicator();
			$(this).dialog("close");
		},
		'Отмена': function()
		{
			$(this).dialog("close");
		}
	});


	this.addIndicatorMenu = true;
	this.updateSelectorsHelper(3, false, false, true);

	$("#add_graphs").dialog("open");
}

function analytics_remove_indicator(elem)
{
	var report = elem.parent().parent().attr("data-report");
	var value = elem.parent().parent().attr("data-chart").split("-");

	this.saveParams("indicator_handler", false, {'report': this.service + "_" + report, 'value': value});

	delete this.indicatorsData[report][value];
	elem.parent().parent().remove();
}

function analytics_save_params(type, push, options, callback)
{
	var that = this;
	var url = "?module=analytics&action=save_params";
	var params = {
		'type': type,
		'push': (push ? 1 : 0)
	};

	if (options)
		$.extend(params, options);

	$.post(
		url,
		params,
		callback,
		"json"
	).fail(function(result) {
		that.errors(0, type, {'message': "", 'log': result.responseText});
	});
}

function analytics_update_selectors(target)
{
	var type = target.split("_", 2);
	var type = type[0];

	switch (type)
	{
		case "service":
			return this.updateSelectorsHelper(1, false, false, an.addIndicatorMenu);
		case "report":
			return this.updateSelectorsHelper(2, false, false, an.addIndicatorMenu);
		case "graph":
			return this.updateSelectorsHelper(3, false, false, an.addIndicatorMenu);
	}
}

function analytics_update_selectors_helper(level, init, connect, hide_connects)
{
	var active_service = $("#service_selector").val();
	var active_report = $("#report_selector :selected").attr("point");
	var legend_selector = ($("#legend_selector").length > 0 ? $("#legend_selector").parent().parent().parent() : false);
	$("#connect_selector").prop("disabled", true);

	$("#service_selector").parent().parent().parent().show(0);
	$("#connect_selector").parent().parent().parent().show(0);

	if (legend_selector !== false)
		legend_selector.hide(0);
	if (hide_connects)
	{
		if (legend_selector !== false)
			legend_selector.show(0);
		$("#service_selector").parent().parent().parent().hide(0);
		$("#connect_selector").parent().parent().parent().hide(0);
	}

	if (!connect)
		connect = $("#connect_selector").val();

	switch (level)
	{
		case 0:
			$("#service_selector").empty();
			$("#report_selector").empty();
			$("#graph_selector").empty();
			$("#connect_selector").empty();

			for (var service in services_list)
			{
				var selected = "";

				if (this.service == service)
					selected = " selected='true'";
				$("#service_selector").append("<option value='" + service + "'" + selected + ">" + services_list[service] + "</option>");
			}
			break;
		case 1:
			$("#report_selector").empty();
			$("#graph_selector").empty();
			$("#connect_selector").empty();

			if (!([active_service] in reports_list))
			{
				this.errors(0, "", {'message': "Нет отчетов для выбранного сервиса", 'log': "No reports for service: " + active_service});
				return;
			}

			var point = reports_list[active_service];

			for (var report in point)
				$("#report_selector").append("<option point='" + report + "' value='" + point[report]['path'] + "'>" + point[report]['title'] + "</option>");
			break;
		case 2:
			$("#graph_selector").empty();
			$("#connect_selector").empty();

			if (!(['graphs'] in reports_list[active_service][active_report]))
			{
				this.errors(0, "", {'message': "Нет графиков для выбранного отчета", 'log': "No graphs for report: " + active_report + ", service: " + active_service});
				return;
			}

			if (reports_list[active_service][active_report]['type'] == "candles")
			{
				$("#graph_selector").prepend("<option value='0' selected='selected'>Сумма</option>");
				break;
			}

			var point = reports_list[active_service][active_report]['graphs'];

			for (var graph in point)
				$("#graph_selector").append("<option value='" + graph + "'>" + point[graph]['title'] + "</option>");

			if (init)
				return;
			break;
		case 3:
			if (!hide_connects)
				break;

			$("#legend_selector").empty();

			var active_graph = $("#graph_selector").val();
			var point = reports_list[active_service][active_report]['graphs'][active_graph]['legend'];

			for (var legend in point)
				$("#legend_selector").append("<option value='" + legend + "'>" + point[legend] + "</option>");
			return;
		case 4:
			$("#connect_selector").empty();

			if (this.charts.length == 0)
			{
				this.errors(0, "", {'message': "Отсутствуют графики на странице", 'log': "No active chart on page"});
				return;
			}

			var point = reports_list[active_service][active_report];
			for (var chart in this.charts)
			{
				if (this.connectableTypes[this.charts[chart].type].indexOf(point['type']) === -1)
					continue;
				if ($(this.charts[chart].amChart.div).is(":hidden"))
					continue;

				var selected = "";
				if (connect && chart == connect)
					selected = " selected='true'";

				$("#connect_selector").append("<option value='" + chart + "' " + selected + ">" + this.charts[chart].chartName + "</option>");
			}

			if ($("#connect_selector").children().length == 0)
			{
				$("#connect_selector").prepend("<option value=''>Нельзя соединить</option>");
				return;
			}
			$("#connect_selector").prop("disabled", false);
			$("#connect_selector").append("<option value=''>Не соединять</option>");
			return;
	}

	this.updateSelectorsHelper(++level, init, connect, hide_connects);
}

function analytics_errors(level, type, error)
{
	if ($(".graph").length == 0 && type !== "col_edit")
	{
		$("#chart").hide();
		$("#info").show();
	}

	switch (type)
	{
		case "data":
			var message = "Ошибка при загрузке данных";
			break;
		case "edit":
			var message = "Невозможно соединить графики";
			break;
		case "add":
			var message = "Не удалось добавить график";
			break;
		case "empty":
			var message = "Данных за этот период нет";
			break;
		case "legend_hide":
			var message = "Не удалось сохранить легенду";
			break;
		case "show_average":
			var message = "Не удалось сохранить отображение среднего значения";
			break;
		case "show_percent":
			var message = "Не удалось сохранить отображение процентов";
			break;
		case "indicator_handler":
			var message = "Не удалось сохранить изменения индикторов";
			break;
		case "col_edit":
			var message = "Не удалось загрузить форму редактирования";
			break;
		case "col_save":
			var message = "Не удалось сохранить данные формы";
			break;
		case "col_image":
			var message = "Ошибка при загрузке изображения";
			break;
		default:
			var message = (error.message != "" ? error.message : "Неизвестная ошибка");
	}

	switch (level)
	{
		case 0:
			return this.Notify.warning(message, error.log);
		case 1:
			return this.Notify.error(message, error.log);
		case 2:
			return this.Notify.simple(message);
	}
}

function analytics_add_simple_indicator()
{
	var report = $("#report_selector").val();
	var value = [$("#graph_selector").val(), $("#legend_selector").val(), $(".an-simple-indicator:not(.indicator-clone)").length];

	var that = this;
	var callback = function () {
		$.get(
			"?module=analytics&action=load_indicators",
			{ 'service': that.service, 'report': report, 'date_begin': $("#date_begin").val(), 'date_end': $("#date_end").val(), 'type': 'service' },
			function (result) { that.parseSimpleData(result); },
			"json"
		);
	};

	this.saveParams("simple_indicator_handler", true, {'report': this.service + "_" + report, 'value': value}, callback);
}

function analytics_remove_simple_indicator(elem)
{
	var report = elem.parent().attr("data-report");
	var value = elem.parent().attr("data-chart").split("-");
	value.push(elem.parent().index());

	this.saveParams("simple_indicator_handler", false, {'report': this.service + "_" + report, 'value': value});

	elem.parent().remove();
	$(".indicator-clone").remove();
}

function analytics_get_simple_data()
{
	var that = this;

	$.get(
		"?module=analytics&action=load_indicators",
		{'service': this.service, 'date_begin': $("#date_begin").val(), 'date_end': $("#date_end").val()},
		function (data) { that.parseSimpleData(data); },
		"json"
	);
}

function analytics_parse_simple_data(data)
{
	var that = this;
	var reports = data.reports;
	var dates = data.dates;

	for (var path in reports)
	{
		var title = reports[path].title;

		for (var key in reports[path].charts)
		{
			var url = "?module=analytics&action=" + this.service + "&report=" + path;
			var report = reports[path].charts[key];
			if ($(".an-simple-indicator[data-report=" + path + "][data-chart=" + key +"]").length > 0)
				continue;

			var indicator = $("<a class='an-simple-indicator' href='" + url + "' data-report='" + path + "' data-chart='" + key + "' data-sort='" + report.order + "'></a>");
			var container = $("<div class='an-simple-indicator-info'></div>");
			var title_container = $("<div class='indicator-title'><div class='inner'></div></div>");

			$(title_container).find(".inner").text(title + " " + report.sub_title + " (" + report.title + ")");

			var default_value = (report.values[0].diff === undefined ? report.values[0].value : report.values[0].diff);
			var values = report.values.slice(1);
			var first = true;

			if (report.trend !== "" && first !== false)
				indicator.append("<div class='an-simple-indicator-trend'><img src='data:image/png;base64," + report.trend + "'></img></div>");

			var top_pos = 140;

			for (var vkey in values)
			{
				var value = (values[vkey].diff === undefined ? values[vkey].value : values[vkey].diff);
				var main_value = default_value;
				var value_container = $("<div class='indicator-value-wrap'" + (first === false ? " style='position: absolute; top: " + top_pos + "px'" : "") + "></div>");

				if (first === false)
					value_container.addClass("before-val");

				var indicator_value = $("<span class='indicator-value'></span>");
				if (first === true)
					indicator_value.append("<span class='primary'>" + this.format(report.values[0].value) + report.value_append + "</span>");

				var indicator_date = $("<span class='indicator-small'></span>");
				var date_string = this.getUTCDate(dates.periods[parseInt(vkey) + 1].umin, dates.periods[parseInt(vkey) + 1].umax, "%d1-%d2 %Ms1 %Y1");

				indicator_date.text(date_string);

				var indicator_difference = $("<span class='indicator-difference'></span>");
				if (report.negative === true)
					main_value = [value, value = main_value][0];

				if (value < main_value && value != 0)
					indicator_difference.prepend("<span class='diff'>&uArr; " + this.format(Math.abs((main_value - value) / value * 100)) + "%</span>");

				if (value > main_value && value != 0)
					indicator_difference.addClass("down").prepend("<span class='diff'>&dArr; " + this.format(Math.abs((value - main_value) / value * 100)) + "%</span>");

				indicator_difference.append(indicator_date);
				indicator_difference.append($("<span class='indicator-small'>" + this.format(value) + report.value_append + "</span>"));
				value_container.append(indicator_value, indicator_difference);
				container.append(value_container);

				if (first === false)
					top_pos += 40;

				first = false;
			}

			var remove_button = $("<span title='Удалить'></span>");
			remove_button.addClass("an-indicator-remove ui-icon ui-icon-closethick").css({"margin-left": -16, 'margin-top': 2, 'float': "left", 'display': "none"});
			remove_button.bind("click", function (e) { e.preventDefault(); that.removeSimpleIndicator($(this)); });

			indicator.attr("title", title + " " + report.title + " (" + report.sub_title + ", " + this.getUTCDate(dates.periods[0].umin, dates.periods[0].umax) + ")");
			if (report.values[0].diff !== undefined)
				indicator.attr("title", indicator.attr("title") + ", прогнозируемое значение: " + this.format(report.values[0].diff) + report.value_append);

			indicator.bind("mouseenter", function () {
				$(this).find("span.an-indicator-remove").show();
				$(this).addClass("hovered");
			});
			indicator.bind("mouseleave", function (event) {
				$(this).find("span.an-indicator-remove").hide();
				$(this).removeClass("hovered");
			});

			indicator.append(container);
			indicator.append(title_container);

			var date_title_string = this.getUTCDate(dates.periods[0].umin, dates.periods[0].umax, "%d1-%d2 %Ms1 %Y1");

			indicator.prepend("<span class='title-date'>" + date_title_string + "</span>");
			indicator.append(remove_button);
			$("#service-indicators").append(indicator);
		}
	}

	this.resize();
}

function analytics_get_utc_date(timestamp1, timestamp2, format)
{
	var monthNames = ["Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"];
	var monthNames2 = ["Января", "Февраля", "Марта", "Апреля", "Мая", "Июня", "Июля", "Августа", "Сентября", "Октября", "Ноября", "Декабря"];

	if (!timestamp2)
	{
		var date = new Date(timestamp1);
		var day = date.getUTCDate();

		if (day == 1)
			return monthNames[date.getUTCMonth()] + " " + date.getUTCFullYear();
		return ("0" + day).slice(-2) + "." + ("0" + (date.getUTCMonth() + 1)).slice(-2) + "." + date.getUTCFullYear();
	}

	var date1 = new Date(timestamp1);
	var date2 = new Date(timestamp2);

	var day1 = date1.getUTCDate();
	var day2 = date2.getUTCDate();

	if (date1.getUTCMonth() === date2.getUTCMonth() && date1.getUTCFullYear() === date2.getUTCFullYear() && date2.getUTCDate() === (new Date(date2.getUTCFullYear(), date2.getUTCMonth() + 1, 0)).getDate())
		return monthNames[date1.getUTCMonth()] + " " + date1.getUTCFullYear();

	if (!format)
		return ("0" + day1).slice(-2) + "." + ("0" + (date1.getUTCMonth() + 1)).slice(-2) + "." + date1.getUTCFullYear() + " - " + ("0" + day2).slice(-2) + "." + ("0" + (date2.getUTCMonth() + 1)).slice(-2) + "." + date2.getUTCFullYear();

	return format.replace(/%\w*/g, function (letter)
	{
		letter = letter.slice(1);
		switch (letter)
		{
			case "d1":
				return ("0" + day1).slice(-2);
			case "d2":
				return ("0" + day2).slice(-2);
			case "m1":
				return ("0" + date1.getUTCMonth() + 1).slice(-2);
			case "m2":
				return ("0" + date2.getUTCMonth() + 1).slice(-2);
			case "M1":
				return monthNames[date1.getUTCMonth()];
			case "M2":
				return monthNames[date2.getUTCMonth()];
			case "Ms1":
				return monthNames2[date1.getUTCMonth()];
			case "Ms2":
				return monthNames2[date2.getUTCMonth()];
			case "y1":
			case "Y1":
				return date1.getUTCFullYear();
			case "y2":
			case "Y2":
				return date2.getUTCFullYear();
			default:
				return "%" + letter;
		}
	});
}

function analytics_search_report(words)
{
	if (typeof this.preSearchReports === "undefined")
	{
		if (!this.service in reports_list)
			return;

		this.preSearchReports = {};
		for (var key in reports_list[this.service])
		{
			var report = reports_list[this.service][key];

			if (!(report.title in this.preSearchReports))
				this.preSearchReports[report.title] = [];
			this.preSearchReports[report.title].push({'path': report.path, 'title': report.title});

			if (report.type == "candles")
				continue;

			for (var gkey in report.graphs)
			{
				if (!(report.graphs[gkey].title in this.preSearchReports))
					this.preSearchReports[report.graphs[gkey].title] = [];
				this.preSearchReports[report.graphs[gkey].title].push({'path': report.path, 'title': report.title});

				for (var lkey in report.graphs[gkey].legend)
				{
					if (!(report.graphs[gkey].legend[lkey] in this.preSearchReports))
						this.preSearchReports[report.graphs[gkey].legend[lkey]] = [];
					this.preSearchReports[report.graphs[gkey].legend[lkey]].push({'path': report.path, 'title': report.title});
				}
			}
		}
	}

	var translit_regexp = new RegExp(transliterate(words).replace(/\ |\\/g, "").split("").join("\\s?"), "i");
	var regexp = new RegExp(words.replace(/\ |\\/g, "").split("").join("\\s?"), "i");

	var unique = {};
	var result = [];
	for (var key in this.preSearchReports)
	{
		if (key.search(regexp) === -1 && key.search(translit_regexp) === -1)
			continue;

		for (var rkey in this.preSearchReports[key])
		{
			if (unique[this.preSearchReports[key][rkey].path] !== undefined)
				continue;

			unique[this.preSearchReports[key][rkey].path] = true;
			result.push({'label': this.preSearchReports[key][rkey].title, 'value': this.preSearchReports[key][rkey].title, 'id': this.preSearchReports[key][rkey].path});
		}
	}

	return result;
}

function analytics_set_simple_events()
{
	var that = this;

	$("#reports ul.dropdown-menu").clone().removeClass("dropdown-menu an-menu-dropdown").addClass("nav nav-pills").appendTo("#service_div");
	$("#refresh_button").bind("click", function() {
		if (that.refreshBlock === true)
			return;

		$("#service-indicators").empty();

		that.getSimpleData();
	});
	$("#add_indicator").bind("click", function() {
		$("#add_graphs").dialog("open");
	});
	$("#service-indicators").sortable({
		'tolerance': "pointer",
		'start': function (event, ui) {
			var element = $(ui.item);

			ui.placeholder.width(ui.helper.outerWidth());
			$(".an-simple-indicator.indicator-clone").remove();
			$(element).removeClass("hovered");
		},
		'stop': function (event, ui) {
			var element = $(ui.item);
			var report = element.attr("data-report");
			var value = element.attr("data-chart").split("-");

			value.push(element.index());
			value.push(element.attr("data-sort"));

			that.saveParams("simple_indicator_handler", true, {'report': that.service + "_" + report, 'value': value}, false);

			$(".an-simple-indicator:not(.indicator-clone)").each(function () { $(this).attr("data-sort", $(this).index()); });
			element.trigger("mouseenter");
		},
		'items': "> .an-simple-indicator:not(.indicator-clone)"
	});

	$("#add_graphs").find("select").bind("change", function(e) { that.updateSelectors(e.target.id); });
	$("#add_graphs").dialog(
	{
		'width': 250,
		'autoOpen': false,
		'autoResize': true,
		'modal': true,
		'resizable': false,
		'title': "Добавление нового показателя",
		'buttons':
		{
			'Добавить': function()
			{
				that.addSimpleIndicator();
				$(this).dialog("close");
			},
			'Отмена': function()
			{
				$(this).dialog("close");
			}
		}
	});

	this.updateLiveStream(that);
	$(".stream-items").on("scroll", function () {
		if ($(this).scrollTop() + $(this).height() + 50 > $(this).parent().height())
		{
			clearInterval(that.liveStreamIntervalId);
			that.liveStreamIntervalId = false;
			that.updateLiveStream(that, $(this).find(".well").length + 20);
		}
	});

	set_datepicker(
		function(selectedDate) {
			$("#date_end").datepicker("option", "minDate", selectedDate);
		},

		function(selectedDate) {
			$("#date_begin").datepicker("option", "maxDate", selectedDate);
		}
	);

	this.addIndicatorMenu = true;
	this.updateSelectorsHelper(0, false, false, true);
}

function analytics_set_api_reports_events()
{
	var that = this;

	set_datepicker(
		function(selectedDate) {
			$("#date_end").datepicker("option", "minDate", selectedDate);
			Ajax.post("?module=analytics&action=save_date", {'date_begin': selectedDate, 'date_end': $("#date_end").val()});
		},

		function(selectedDate) {
			$("#date_begin").datepicker("option", "maxDate", selectedDate);
			Ajax.post("?module=analytics&action=save_date", {'date_begin': $("#date_begin").val(), 'date_end': selectedDate});
		}
	);

	$("#api-reports-filter").submit(function () {
		$("#api-reports-filter input[name='current_page']").val(1);
		return that.refreshApiReportsTable();
	});

	$("#pos-quantity-select select").change(function () {
		$("#api-reports-filter input[name='current_page']").val(1);
		that.refreshApiReportsTable();
	});

	$(".api-sort").click(function () {
		var	new_order = "",
			icon = {};

		$("#api-reports-filter input[name='current_page']").val(1);
		$("#api-reports-filter input[name='order']").val($(this).data("order"));
		$(".api-sort .sort-icon").remove();

		if ($(this).data("order").split("-")[1] === "asc")
		{
			new_order = $(this).data("order").split("-")[0] + "-desc";
			icon = $("<i class=\"sort-icon icon-arrow-up\"></i>");
		}
		else
		{
			new_order = $(this).data("order").split("-")[0] + "-asc";
			icon = $("<i class=\"sort-icon icon-arrow-down\"></i>");
		}

		$(this).data("order", new_order).prepend(icon);

		that.refreshApiReportsTable();
	});

	$(".path-filter").bind("click", function () {
		$("#api-reports-filter input[name='current_page']").val(1);
		$("#api-reports-filter input[name='filter']").val($(this).text());
		that.refreshApiReportsTable();
		return false;
	});

	$("#api-reports-filter input[name='filter']").typeahead({
		'source' : function (query, process) {
			var url = "?module=analytics&action=api_reports_filter";

			$.ajax({
				'url' : url,
				'data' : {
					'filter' : query,
					'service' : that.service
				},
				'type' : "post",
				'dataType' : "json",
				'success' : function (data) {
					if (data.paths !== undefined && data.paths.length > 0)
						return process(data.paths);
				},
				'error' : function () {

				}
			});
		}
	});

	$("#api-all-table input[type='checkbox']").change(function () {
		var el = this;
		that.refreshApiReportsGraph(el);
	});

	var limit = $("#pos-quantity-select select").val();

	if (pos_count > limit)
		get_pagination(limit, pos_count, "right", 1, api_pagination_handler).insertAfter("#pos-quantity-select");

	if (window.location.hash == "")
		return;

	$("#api-reports-filter input[name='filter']").val(window.location.hash.substr(1));
	that.refreshApiReportsTable();
}

function analytics_set_events()
{
	var that = this;

	$.datepicker.setDefaults($.datepicker.regional['ru']);
	$("#refresh_button").bind("click", function() {
		if (that.refreshBlock === true)
			return;

		that.refreshBlock = true;
		this.clearReport();
		this.refreshData(0);
	}.bind(this));
	$("#add_graphs").find("select").bind("change", function(e) { that.updateSelectors(e.target.id); });

	set_datepicker(
		function(selectedDate) {
			$("#date_end").datepicker("option", "minDate", selectedDate);
			Ajax.post("?module=analytics&action=save_date", {'date_begin': selectedDate, 'date_end': $("#date_end").val()});
		},

		function(selectedDate) {
			$("#date_begin").datepicker("option", "maxDate", selectedDate);
			Ajax.post("?module=analytics&action=save_date", {'date_begin': $("#date_begin").val(), 'date_end': selectedDate});
		}
	);

	$("#add_graphs").dialog(
	{
		'width': 250,
		'autoOpen': false,
		'autoResize': true,
		'modal': true,
		'resizable': false,
		'title': "Добавление нового графика"
	});

	$("#export_dialog").dialog(
	{
		'width': 250,
		'autoOpen': false,
		'autoResize': true,
		'modal': true,
		'resizeable': false,
		'title': "Экспорт данных",
		'create': function () { that.exportDialog(); },
		'buttons':
		{
			'Экспорт': function ()
			{
				that.exportReport($(this));
			},
			'Отмена': function ()
			{
				$(this).dialog("close");
			}
		}
	});

	$("#reports_active a, #reports .dropdown-menu a").bind("click", function (e) {
		if (window.history === undefined || e.ctrlKey === true || e.button !== 0 || this.isApiReports)
			return;

		e.preventDefault();
		this.gotoReport(e.target);
	}.bind(this));

	if (window.history !== undefined)
		history.replaceState({'report': main_report}, null, window.location.href);

	window.addEventListener("popstate", function(e) {
		if (e.state !== null && e.state.report !== undefined)
			this.gotoReport();
	}.bind(this));
}

function analytics_resize(old_graph)
{
	if (this.resizing === true && typeof old_graph === "undefined")
		return false;

	this.resizing = true;

	if (this.simpleMode)
	{
		var stream_items_height = $("#live-stream-indicators").height() - $("#live-stream-indicators h2").height() - 40;
		$(".stream-items").height(stream_items_height >= 85 ? stream_items_height : 85);

		this.resizing = false;
		return false;
	}

	var body_width = $("body").outerWidth();
	var body_height = $("body").outerHeight();

	if (this.isMobile && typeof old_graph === "undefined")
	{
		if (typeof this.savedBodyHeight !== "undefined" && Math.abs(body_height - this.savedBodyHeight) < 70 && this.savedBodyHeight !== body_height)
		{
			this.resizing = false;
			return false;
		}

		this.savedBodyHeight = body_height;
	}

	var graphs_length = $(".graph").filter(function() { return $(this).css("display") !== "none";}).length;
	if (graphs_length === 0)
	{
		this.resizing = false;
		return false;
	}

	$("#chart").show();
	$("#info").hide();

	var that = this;
	var offset = graphs_length % this.columns;
	var allow_offset = true;
	var chart = {'width': Math.max(body_width - $("#chart").offset().left - 10, this.minWidth), 'height': body_height - $("#chart").offset().top - 1};

	$("#chart").css("width", chart.width);
	if (chart.height > this.minHeight)
		$("#chart").height(chart.height).css("overflow-y", "auto");
	else
		$("#chart").css({'overflow-y': "hidden", 'height': "auto"});

	var graph = {'width': chart.width - detect_scrollbar_width($("#chart")), 'height': Math.max(chart.height, this.minHeight)};

	if (graph.width > this.minWidth * this.columns)
		graph.width = Math.round(graph.width / this.columns) - (this.columns > 1 ? this.columns * 2 : 0);
	else
	{
		graph.width = Math.max(graph.width, this.minWidth);
		allow_offset = false;
	}

	if (typeof old_graph !== "undefined" && graph.width === old_graph.width && graph.height === old_graph.height)
		return false;

	for (var key in this.charts)
	{
		var sizes = this.charts[key].getSizes(graph.width);
		var height = Math.max(chart.height, (this.minHeight + sizes.height));

		$(this.charts[key].amChart.div).css({'width': graph.width, 'height': height, 'float': "left"});
	}

	$("#report-title").css({'marginLeft': 0});

	var buttons_width = $(".btn-toolbar").length > 0 ? $(".btn-toolbar").offset().left + $(".btn-toolbar").outerWidth() : 0;
	var title_left = $("#report-title h2").offset().left;
	var title_width = $("#report-title h2").outerWidth() / 2;

	if (title_left < buttons_width)
		$("#report-title").css({'marginLeft': (title_width > (buttons_width - title_left) * 2 ? (buttons_width - title_left) * 2 : buttons_width)});
	if (offset > 0 && allow_offset)
		$(".graph").slice(offset * (-1)).css({'width': (graph.width) * (this.columns / offset) });

	if (typeof old_graph !== "undefined")
		return graph;

	var counter = 0;
	this.resizing = true;

	while (this.resizing)
	{
		if (graph === false || counter === 3)
		{
			this.resizing = false;
			break;
		}

		graph = this.resize(graph);
		counter += 1;
	}

	if ($(".an-item-selector").length > 0)
		$(".an-item-selector").each(function() { $(this).css({'top': 0, 'right': 18, 'left': "auto"}); });

	clearTimeout(this.timeouts.invalidateSize);
	this.timeouts.invalidateSize = setTimeout(function () { for (var key in that.charts) { that.charts[key].valueAxisScroll(true); that.charts[key].amChart.invalidateSize(); } }, 100);
}

function analytics_format(value, decimals)
{
	if (typeof decimals === "undefined")
		decimals = 2;
	if (value < 10 && value > -10)
		decimals = 2;

	value = value.toFixed(decimals).replace(/(\d)(?=(\d\d\d)+([^\d]|$))/g, "$1,");
	return value.replace(/\.00$/g, "");
}

function analytics_get_color(id)
{
	if (!this.colors)
		this.colors = this.Analytics.colors;
	if (id in this.colors)
		return this.colors[id];
	if (id < 0)
		id = 0xFFFFFFFF + id + 1;

	var blue = 50 + 195 * Math.abs(Math.cos(id * 29999999)) << 32;
	var red = 60 + 195 * Math.abs(Math.sin(id * 999999)) << 16;
	var green = 60 + 185 * Math.abs(Math.cos(id * 199999)) << 8;

	return "#" + (red | green | blue).toString(16);
}

function analytics_goto_report(element)
{
	$("#tableFilter").remove();

	if (element !== undefined)
		var report = $(element).data("report");
	else
		var report = history.state.report;

	this.clearReport();
	this.ajaxCalls = [];
	$("#report-title h2").empty();
	$("#report-title h4").empty();

	$("li.report_active").removeClass("active report_active");

	if (main_report.substring(0, main_report.indexOf("_")) !== report.substring(0, report.indexOf("_")))
		$("#reports_active").html($("#reports a[data-report='" + report + "']").parent().parent().find("li").clone(true));

	$("#reports_active a[data-report='" + report + "']").parent().addClass("active report_active");

	main_report = report;

	this.getData(report, function (data) {
		$("title").html($("title").text().substring(0, $("title").text().indexOf(":") + 1) + " " + data.report.title);
	}.bind(this));

	if (element !== undefined)
		history.pushState({report: report}, null, $(element).attr("href"));
}

function analytics_clear_report()
{
	this.reports = [];
	this.charts = [];
	this.resizing = false;
	this.isLegendReduced = false;

	if ($("#an_table").length !== 0)
		$("#an_table").parent()[0].removeChild($("#an_table")[0]);

	if ($("#tableFilter").length > 0)
		$("#tableFilter").remove();

	$("#chart").empty();
	$("#report-graphs").empty();
	$("#an-indicator").hide();
}

function analytics_refresh_api_reports_table()
{
	var that = this;

	var values = $("#api-reports-filter input[name='filter'], #api-reports-filter input[name='order'], #api-reports-filter input[name='current_page'], #pos-quantity-select select");
	var url = "?module=analytics&action=api_reports&service=" + this.service;
	$.ajax({
		'url' : url,
		'data' : values,
		'type' : "post",
		'dataType' : "json",
		'success' : function (data) {
			if (data.data === undefined || data.data.length === 0)
				return that.errors(0, "", {'message': "По вашему запросу ничего не найдено", 'log': ""});

			if ($(".pagination").length > 0)
				$(".pagination").remove();

			var tbody = $("<tbody />");

			for (var key in data.data)
			{
				var row = $("<tr />");

				row.append("<td><input type=\"checkbox\" autocomplete=\"off\" value=\"" + data.data[key].id +"\" name=\"path_values[]\" /></td>")
					.append("<td><a href=\"#\" class=\"path-filter\">" + data.data[key].path + "</a></td>")
					.append("<td>" + data.data[key].visitors_avg.split("").reverse().join("").replace(/(\d{3})(?=\d)/g, "$1 ").split("").reverse().join("") + "</td>")
					.append("<td>" + data.data[key].hits_avg.split("").reverse().join("").replace(/(\d{3})(?=\d)/g, "$1 ").split("").reverse().join("") + "</td>")
					.append("<td>" + data.data[key].visitors_sum.split("").reverse().join("").replace(/(\d{3})(?=\d)/g, "$1 ").split("").reverse().join("") + "</td>")
					.append("<td>" + data.data[key].hits_sum.split("").reverse().join("").replace(/(\d{3})(?=\d)/g, "$1 ").split("").reverse().join("") + "</td>");

				if (that.checkedApiReports[data.data[key].id] !== undefined)
					row.find("input").attr("checked", "checked");

				tbody.append(row);
			}

			$("#api-all-table tbody").replaceWith(tbody);

			$(".path-filter").unbind("click");
			$(".path-filter").bind("click", function () {
				$("#api-reports-filter input[name='current_page']").val(1);
				$("#api-reports-filter input[name='filter']").val($(this).text());
				that.refreshApiReportsTable(that.service);
				return false;
			});

			$("#api-all-table input[type='checkbox']").change(function () {
				var el = this;
				that.refreshApiReportsGraph(el);
			});

			pos_count = data.pos_count;
			var limit = $("#pos-quantity-select select").val();

			if (parseInt(pos_count) > limit)
				get_pagination(limit, pos_count, "right", data.current_page, api_pagination_handler).insertAfter("#pos-quantity-select");

			window.location.hash = "#" + $("#api-reports-filter input[name='filter']").val();
		},
		'error' : function () {
			return that.errors(1, "", {'message': "Произошла ошибка на сервере!", 'log': ""});
		}
	});
	return false;
}

function analytics_refresh_api_reports_graph(el)
{
	this.clearReport();
	if (el.checked === false)
		delete this.checkedApiReports[$(el).val()];
	else
		this.checkedApiReports[$(el).val()] = 1;

	if (Object.keys(this.checkedApiReports).length > 0)
		return this.getData(main_report, function (data) { if (data.indicators) this.createIndicators(data.indicators); }.bind(this));

	$("#chart").width(0);
	$("#chart").height(0);
}

function analytics_set_edit_live_stream_events()
{
	var that = this;

	var dialog = $("#upd_live_stream_dialog").dialog({
		'autoOpen' : false,
		'title' : "Добавить/Редактировать показатель",
		'width' : 500,
		'modal' : true,
		'create' : function () {
			var	active_service = $("#upd_live_stream_dialog form").find("input[name='service_name']").val(),
				point = reports_list[active_service],
				active_report = 0,
				active_chart = 0;

			for (var report in point)
				$("#report_selector").append("<option value=\"" + point[report]['path'] + "\">" + point[report]['title'] + "</option>");

			if (!(['graphs'] in reports_list[active_service][active_report]))
			{
				this.errors(0, "", {'message': "Нет графиков для выбранного отчета", 'log': "No graphs for report: " + active_report + ", service: " + active_service});
				return;
			}

			if (reports_list[active_service][active_report]['type'] === "candles")
				$("#chart_selector").prepend("<option value=\"0\" selected=\"selected\">Сумма</option>");
			else
			{
				point = reports_list[active_service][active_report]['graphs'];

				for (var graph in point)
					$("#chart_selector").append("<option value=\"" + graph + "\">" + point[graph]['title'] + "</option>");
			}

			active_chart = $("#chart_selector").val();

			point = reports_list[active_service][active_report]['graphs'][active_chart]['legend'];

			for (var type in point)
				$("#type_selector").append("<option value=\"" + type + "\">" + point[type] + "</option>");

			$("#report_selector").change(that.liveStreamSelectorChange);
			$("#chart_selector").change(that.liveStreamSelectorChange);
			$("#compare_type_selector").change(that.liveStreamSetPeriodStep);
		},
		'show' : {
			'effect' : "drop",
			'duration' : 500
		},
		'hide' : {
			'effect' : "explode",
			'duration' : 500
		},
		'close' : function (event, ui) {
			$("#upd_live_stream_dialog input[name='id']").val(0);
			$("#report_selector option").removeAttr("selected");
			$("#report_selector option:first-child").attr("selected", "selected");
			$("#report_selector").change();
			$("#direction_selector option").removeAttr("selected");
			$("#direction_selector option:first-child").attr("selected", "selected");
			$("#direction_selector").change();
			$("#compare_range_slider").slider("value", 1);
			$("#compare_range_selector").val(1);
			$("#period_slider").slider("value", 1);
			var period = 1200;
			$("#period_selector").val(get_human_languge_period_val(period));
			$("#upd_live_stream_dialog input[name='period']").val(period);
			$("#name_selector").val("");
			$("#live-stream-data-table tr").removeClass("edited-row");
		},
		'buttons': {
			'Сохранить' : that.submitLiveStreamData.bind(that),
			'Отмена' : function() {
				dialog.dialog("close");
			}
		}
	});

	$("#open_live_stream_dialog").click(function (e) {
		e.preventDefault();
		$("#upd_live_stream_dialog").dialog("open");
	});

	$("#compare_range_slider").slider({
		'range' : "min",
		'value' : 1,
		'min' : 1,
		'max' : 100,
		'slide' : function (event, ui) {
			$("#compare_range_selector").val(ui.value);
		}
	});
	$("#compare_range_selector").val($("#compare_range_slider").slider("value"));
	$("#compare_range_slider").on("wheel", function (event) {
		var wheel_value = event.originalEvent.deltaY;
		event.preventDefault();
		var value = $("#compare_range_slider").slider("value");

		if (wheel_value < 0)
		{
			if (value < 100)
				++value;

			$("#compare_range_slider").slider("value", value);
			$("#compare_range_selector").val(value);
		}
		else
		{
			if (value > 1)
				--value;

			$("#compare_range_slider").slider("value", value);
			$("#compare_range_selector").val(value);
		}
	});

	$("#period_slider").slider({
		'range' : "min",
		'value' : 1,
		'min' : 1,
		'max' : 1008,
		'step' : 1,
		'slide' : function (event, ui) {
			var period = ui.value * 1200;
			$("#period_selector").val(get_human_languge_period_val(period));
			$("#upd_live_stream_dialog input[name='period']").val(period);
		}
	});
	var period = $("#period_slider").slider("value") * 1200;
	$("#period_selector").val(get_human_languge_period_val(period));
	$("#upd_live_stream_dialog input[name='period']").val(period);
	$("#period_slider").on("wheel", function (event) {
		var wheel_value = event.originalEvent.deltaY;
		event.preventDefault();
		var value = $("#period_slider").slider("value");

		if (wheel_value < 0)
		{
			if (value < 1008)
				++value;

			$("#period_slider").slider("value", value);
			var period = value * 1200;
			$("#period_selector").val(get_human_languge_period_val(period));
			$("#upd_live_stream_dialog input[name='period']").val(period);
		}
		else
		{
			if (value > 1)
				--value;

			$("#period_slider").slider("value", value);
			var period = value * 1200;
			$("#period_selector").val(get_human_languge_period_val(period));
			$("#upd_live_stream_dialog input[name='period']").val(period);
		}
	});

	$("#events-wrapper").on("click", "#live-stream-data-table .edit", function (e) {
		e.preventDefault();
		var	id = $(this).data("id"),
			service_name = $(this).data("service"),
			row = $(this).parent().parent();

		$.ajax({
			'url' : "/?module=analytics&action=live_stream_data_by_id",
			'type' : "post",
			'dataType' : "json",
			'data' : {
				'service_name' : service_name,
				'id' : id
			},
			success : function (data) {
				if (data.chart === undefined)
					return that.errors(1, "", {'message': "Произошла ошибка на сервере!", 'log': ""});

				$("#upd_live_stream_dialog input[name='id']").val(data.id);
				$("#report_selector").val(data.report);
				$("#report_selector").change();
				$("#chart_selector").val(data.chart);
				$("#chart_selector").change();
				$("#type_selector").val(data.type);
				$("#type_selector").change();
				$("#compare_type_selector").val(data.compare_type);
				$("#compare_type_selector").change();
				$("#upd_live_stream_dialog input[name='period']").val(data.period);
				$("#period_slider").slider("value", data.period / 1200);
				$("#period_selector").val(get_human_languge_period_val(data.period));
				$("#direction_selector").val(data.direction);
				$("#compare_range_slider").slider("value", data.compare_range);
				$("#compare_range_selector").val(data.compare_range);
				$("#name_selector").val(data.name);

				$("#upd_live_stream_dialog").dialog("open");
				row.addClass("edited-row");
			},
			error : function () {
				return that.errors(1, "", {'message': "Произошла ошибка на сервере!", 'log': ""});
			}
		});
	});

	$("#events-wrapper").on("click", "#live-stream-data-table .remove", function (e) {
		e.preventDefault();
		var id = $(this).data("id");
		var service_name = $(this).data("service");
		var row = $(this).parent().parent();

		$("#remove_live_stream_dialog").dialog({
			'resizable' : false,
			'height' : 185,
			'modal' : true,
			'buttons' : {
				'Да' : function () {
					var self = this;
					$.ajax({
						'url' : "/?module=analytics&action=remove_live_stream",
						'data' : {
							'id' : id,
							'service_name' : service_name
						},
						'type' : 'post',
						'dataType' : 'json',
						'success' : function (data) {
							if (data.success === undefined)
								return that.errors(1, "", {'message': "Произошла ошибка на сервере!", 'log': ""});

							row.remove();

							if ($("#live-stream-data-table tbody tr").length === 0)
								$("#live-stream-data-table").replaceWith("<div class=\"alert\"><a class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Внимание!</strong> Ни к одному отчету не привязано отслеживание изменений. Чтобы добавить отслеживание нажмите кнопку &quot;Добавить показатель&quot; выше этого сообщения.</div>");

							$(self).dialog("close");
						},
						'error' : function () {
							return that.errors(1, "", {'message': "Произошла ошибка на сервере!", 'log': ""});
						}
					});
				},
				'Нет' : function () {
					$(this).dialog("close");
				}
			}

		});
	});
}

function analytics_submit_live_stream_data()
{
	var	that = this,
		data = $("#upd_live_stream_dialog form").find("input, select"),
		name = $("#upd_live_stream_dialog input[name='name']").val(),
		service_name = $("#upd_live_stream_dialog input[name='service_name']").val();

	$.ajax({
		'url' : "/?module=analytics&action=save_live_stream",
		'type' : "post",
		'dataType' : "json",
		'data' : data,
		success : function (data) {
			if (data.error !== undefined && data.error === "1")
				return that.errors(1, "", {'message': "Ошибка: заполните правильно поля формы.", 'log': ""});

			if (data.error !== undefined && data.error === "2")
				return that.errors(1, "", {'message': "Ошибка: доступ запрещён.", 'log': ""});

			if (data.id === undefined)
				return that.errors(1, "", {'message': "Произошла ошибка на сервере!", 'log': ""});

			if ($("#live-stream-data-table").length === 0)
				$(".alert").replaceWith(create_table(["ID", "Название", "Редактировать", "Удалить"], null, "live-stream-data-table", "table table-bordered table-hover"));

			var row = $("<tr><td>" + data.id + "</td><td>" + name + "</td><td><a href=\"#\" class=\"edit\" data-id=\"" + data.id + "\" data-service=\"" + service_name + "\"><i class=\"icon-edit\"></i> Редактировать</a></td><td><a href=\"#\" class=\"remove\" data-id=\"" + data.id + "\" data-service=\"" + service_name + "\"><i class=\"icon-remove\"></i> Удалить</a></td></tr>");
			if ($(".edited-row").length > 0)
				$(".edited-row").replaceWith(row);
			else
				$("#live-stream-data-table tbody").append(row);

			$("#upd_live_stream_dialog").dialog("close");
		},
		error : function () {
			return that.errors(1, "", {'message': "Произошла ошибка на сервере!", 'log': ""});
		}
	});
}

function analytics_live_stream_selector_change()
{
	var	active_service = $("#upd_live_stream_dialog form").find("input[name='service_name']").val(),
		point = reports_list[active_service],
		report_path = $("#report_selector").val(),
		active_report = 0,
		active_chart = 0;

	$("#type_selector").empty();

	for (var report in point)
	{
		if (point[report]['path'] === report_path)
		{
			active_report = report;
			break;
		}
	}

	if ($(this).attr("id") === "report_selector")
	{
		$("#chart_selector").empty();

		if (reports_list[active_service][active_report]['type'] === "candles")
			$("#chart_selector").prepend("<option value=\"0\" selected=\"selected\">Сумма</option>");
		else
		{
			point = reports_list[active_service][active_report]['graphs'];

			for (var graph in point)
				$("#chart_selector").append("<option value=\"" + graph + "\">" + point[graph]['title'] + "</option>");
		}
	}

	active_chart = $("#chart_selector").val();

	point = reports_list[active_service][active_report]['graphs'][active_chart]['legend'];

	for (var type in point)
		$("#type_selector").append("<option value=\"" + type + "\">" + point[type] + "</option>");
}

function analytics_live_stream_set_period_step()
{
	if ($(this).val() === "1")
	{
		$("#period_slider").slider("option", "step", 1);
		$("#period_slider").slider("option", "min", 1);
		$("#period_slider").slider("value", 1);
	}
	else
	{
		$("#period_slider").slider("option", "step", 72);
		$("#period_slider").slider("option", "min", 72);
		$("#period_slider").slider("value", 72);
	}

	var period = $("#period_slider").slider("value") * 1200;
	$("#period_selector").val(get_human_languge_period_val(period));
	$("#upd_live_stream_dialog input[name='period']").val(period);
}

function analytics_update_live_stream(scope, end)
{
	var end_param = end ? end : 20;

	$.ajax({
		'url' : "/?module=analytics&action=get_live_stream&service=" + service + "&end=" + end_param,
		'type' : "get",
		'dataType' : "json",
		success : function (data) {
			var	diff,
				direction_class,
				date,
				date_diff;

			if (data && data.length > 0)
			{
				$("#live-stream-indicators .stream-items").empty();
				for (var key in data)
				{
					diff = (data[key].value_2 - data[key].value_1) / data[key].value_1 * 100;
					direction_class = (diff > 0) ? "up" : "down";
					date = new Date(data[key].time_2.replace(' ', 'T'));
					date_diff = get_human_languge_period_val(Math.round((Date.now() - date.getTime()) / 1000), 3);
					$("#live-stream-indicators .stream-items").append("<div class=\"well well-small\"><a href=\"" + window.location.protocol + "//" + window.location.host + "/?module=analytics&action=" + service + "&report=" + data[key].report + "\"><strong>" + data[key].name + "</strong></a>: <span class=\"diff " + direction_class + "\">" + Math.round(Math.abs(diff)) + "%</span> <span class=\"muted\">" + date_diff + " назад</span></div>");
				}
			}

			if (scope.liveStreamIntervalId === false)
				scope.liveStreamIntervalId = window.setInterval(scope.updateLiveStream, 60000, scope, end_param);
		},
		error : function () {
			return scope.errors(1, "", {'message': "Произошла ошибка на сервере!", 'log': ""});
		}
	});
}

/**
 * Chart functions
 */
function chart_defaults()
{
	var that = this;
	// Baloons
	var balloon = this.amChart.balloon;
	balloon.adjustBorderColor = false;
	balloon.animationDuration = 0;
	balloon.maxWidth = 600;
	balloon.fadeOutDuration = 0;
	balloon.fillAlpha = 0.9;
	balloon.borderAlpha = 0.6;
	balloon.borderColor = "#FFFFFF";
	balloon.borderThickness = 1;
	balloon.shadowAlpha = 0;
	balloon.color = "#FFFFFF";
	balloon.verticalPadding = 1;
	balloon.horizontalPadding = 6;
	balloon.pointerWidth = 0;

	// Category axis
	var categoryAxis = this.amChart.categoryAxis;
	categoryAxis.gridAlpha = 0;
	categoryAxis.gridColor = "#000000";
	categoryAxis.axisColor = "#DADADA";

	if (this.type !== "nodate" && this.type !== "special")
		categoryAxis.parseDates = true;

	var axis = new AmCharts.ValueAxis();
	axis.inside = true;
	axis.gridAlpha = 0;
	axis.axisAlpha = 0;
	axis.axisThickness = 0;
	axis.gridThickness = 0;
	axis.labelsEnabled = false;
	axis.itsLabelAxis = true;

	if (this.type === "stacked")
	{
		axis.minimum = -1;
		axis.maximum = 0;
	}
	this.amChart.addValueAxis(axis);

	var graph = new AmCharts.AmGraph();
	graph.valueField = "label";
	graph.type = "line";
	graph.title = "";
	graph.lineAlpha = 0;
	graph.lineThickness = 0;
	graph.lineColor = "#00AAFF";
	graph.balloonText = "[[description]]";
	graph.bullet = "round";
	graph.connect = false;
	graph.valueAxis = axis;
	graph.visibleInLegend = false;
	this.amChart.addGraph(graph);

	// Cursor
	var cursor = new AmCharts.ChartCursor();
	cursor.zoomable = true;
	cursor.graphBulletSize = 1;
	cursor.animationDuration = 0;
	cursor.cursorPosition = "middle";
	cursor.cursorColor = "#f87673";
	cursor.bulletsEnabled = false;

	if (this.type === "monthly")
		cursor.categoryBalloonDateFormat = "MMM, YYYY";
	else
		cursor.categoryBalloonDateFormat = "EEE, DD.MM.YYYY";

	cursor.addListener("changed", function(e) { if (typeof e.index == "undefined") return; that.Analytics.calcIndicators((e.target.data[e.index].dataContext.date)); });

	if (this.type === "stacked")
		cursor.oneBalloonOnly = true;
	this.amChart.addChartCursor(cursor);

	// Legend
	var legend = new AmCharts.AmLegend();
	legend.valueWidth = 70;
	legend.color = "#000000";
	legend.markerSize = 12;
	legend.spacing = 5;
	legend.verticalGap = 6;
	legend.addListener("hideItem", function(e) { that.updateLegend(e.dataItem, true); });
	legend.addListener("showItem", function(e) { that.updateLegend(e.dataItem, false); });

	this.chartName = this.report.title + " (" + this.params.title + ")";
	this.amChart.addLegend(legend);
	this.amChart.Chart = this;
	this.amChart.showLegendImages = this.Analytics.differenceEnabled;
	this.amChart.addListener("drawn", function(e) { that.valueAxisScroll(); });
}

function chart_draw_sumline()
{
	var that = this;
	var params = this.params;
	var sum_graph = new AmCharts.AmGraph();

	sum_graph.title = "Сумма";
	sum_graph.fullTitle = "(" + this.report.title + ") " + sum_graph.title;
	sum_graph.legendPeriodValueText = "[[value.sum]]";

	sum_graph.legendImage = false;

	if (params['legend_hide'].indexOf("sumline") !== -1)
		sum_graph.hidden = true;

	sum_graph.type = (this.Analytics.lineGraphs === true ? "line" : "smoothedLine");
	sum_graph.legendId = "sumline";
	sum_graph.valueField = "sumline-" + params['report_key'] + this.index;
	sum_graph.lineColor = "#D3D3D3";
	sum_graph.bullet = "round";
	sum_graph.bulletSize = 6;
	sum_graph.lineThickness = 2;
	sum_graph.graphId = this.index;
	sum_graph.reportName = this.params.report_name;

	sum_graph.balloonFunction = function (dataItem) { return that.balloonFunction(dataItem, ""); };
	sum_graph.connect = false;
	this.sumGraph = sum_graph;
	this.withSumLine = true;
	this.amChart.addGraph(sum_graph);
}

function chart_balloon_function(dataItem, valueAppend)
{
	var index = dataItem.index - 1;
	var valueField = dataItem.graph.valueField;
	var now = dataItem.values.value;
	var growth = "";
	var closest = false;
	var close = "</span>";

	balloonText = "<span class='an-balloon'>" + dataItem.graph.title + ": " + this.format(now) + valueAppend;
	if (this.Analytics.percentsEnabled)
		balloonText += ", " + this.format(dataItem.values.percents) + "%";

	var graphs = dataItem.serialDataItem.axes[dataItem.graph.valueAxis.id].graphs;
	for (var key in graphs)
	{
		if (graphs[key].graph.hidden === true)
			continue;
		if (graphs[key].graph.valueField === "label")
			continue;

		var value = graphs[key].values.value;

		if (typeof value === "undefined")
			continue;
		if (value <= now)
			continue;
		if (closest === false)
			closest = value;

		closest = Math.min(closest, value);
	}

	if (closest !== false)
		growth = "; " + this.format((now / closest - 1) * 100) + "%";
	if (!this.Analytics.differenceEnabled || dataItem.graph.type == "column")
		return balloonText + growth + close;

	var equal = " <i class=\"an-icon\"></i> +0%";
	var up = " <i class=\"an-icon\"></i> +";
	var down = " <i class=\"an-icon down\"></i> -";

	if (index < 0 || !dataItem.graph.data[index])
		return balloonText + equal + growth + close;

	var old = dataItem.graph.data[index].dataContext[valueField];
	if (dataItem.graph.negativeValue === true)
		now = [old, old = now][0];

	if (old == 0 && old != now)
		return balloonText + up + "100%" + growth + close;

	if (old < now)
		return balloonText + up + this.format(Math.abs((now - old) / old * 100)) + "%" + growth + close;
	if (old > now)
		return balloonText + down + this.format(Math.abs((old - now) / old * 100)) + "%" + growth + close;
	return balloonText + equal + growth + close;
}

function chart_balloon_stacked(dataItem, valueAppend)
{
	var index = dataItem.index - 1;
	var valueField = dataItem.graph.valueField;
	var now = dataItem.values.value;
	var sum = 0;
	var close = "</span>";
	var value_key = "value-" + this.params.report_key + this.index;

	for (var i = 0; i <= dataItem.graph.index; i++)
	{
		var key = value_key + i;
		if (dataItem.dataContext[key] === undefined)
			continue;

		sum += parseFloat(dataItem.dataContext[key]);
	}

	var balloonText = "<span class='an-balloon'><p style='text-align: center;'>" + dataItem.graph.title + ":<br /></p>";
	balloonText += "<p>Сумма: " + this.format(sum) + valueAppend + ", Текущее: " + this.format(now) + valueAppend + "<br /></p>";

	this.temporaryValue = dataItem;
	if (this.Analytics.percentsEnabled)
		balloonText += "<p>Общая доля: " + this.format(dataItem.values.percents) + "%<br /></p>";

	if (!this.Analytics.differenceEnabled)
		return balloonText + close;

	balloonText += "<p>";
	close = "</p>" + close;

	var equal = "Без изменений<i class=\"an-icon\"></i> +0%";
	var up = "Рост<i class=\"an-icon\"></i> +";
	var down = "Падение<i class=\"an-icon down\"></i> -";

	if (index < 0 || !dataItem.graph.data[index])
		return balloonText + equal + close;

	var old = dataItem.graph.data[index].dataContext[valueField];
	if (dataItem.graph.negativeValue === true)
		now = [old, old = now][0];

	if (old == 0 && old != now)
		return balloonText + up + "100%" + close;

	if (old < now)
		return balloonText + up + this.format(Math.abs((now - old) / old * 100)) + "%" + close;
	if (old > now)
		return balloonText + down + this.format(Math.abs((old - now) / old * 100)) + "%" + close;
	return balloonText + equal + close;
}

function chart_get_legend_image(sum_old, sum_now, count_old, count_now, negative)
{
	var image = {'url': "/user/analytics/arrow-up.png", 'width': 16, 'height': 16, 'text': "0%"};

	if (count_now != count_old)
	{
		sum_old = sum_old / count_old;
		sum_now = sum_now / count_now;
	}

	if (negative === true)
		sum_now = [sum_old, sum_old = sum_now][0];

	if (sum_old < sum_now && sum_old != 0)
	{
		image.url = "/user/analytics/arrow-up.png";
		image.text = "+" + this.format(Math.abs((sum_now - sum_old) / sum_old * 100)) + "%";
	}
	if (sum_old > sum_now && sum_old != 0)
	{
		image.url = "/user/analytics/arrow-down.png";
		image.text = "-" + this.format(Math.abs((sum_old - sum_now) / sum_old * 100)) + "%";
	}

	return image;
}

function chart_create_item_selector(active_graphs)
{
	if (this.params.legend_menu === false)
		return;

	this.legendMenu = new ItemSelector(this, active_graphs);
}

function chart_connect(new_chart)
{
	this.disable();
	this.mergeData(new_chart.data);

	var groups = {};
	var axis_count = this.amChart.valueAxes.length - 1;

	var params = new_chart.graph;
	var legend = params['legend'];
	var splits = params['split_axis'];
	var group = 0;

	for (var key in legend)
	{
		for (; group < splits.length; group++)
		{
			if (splits[group].indexOf(key) === -1)
				continue;
			break;
		}

		if (!(group in groups))
		{
			var axis = new AmCharts.ValueAxis();
			if (splits.length > 1)
				axis.title = legend[key];
			else
				axis.title = new_chart.report.title + " (" + params['title'] + ")";

			axis.unit = params['value_append'][key];
			axis.axisColor = this.getColor(axis_count);
			axis.offset = 100 * axis_count;
			axis.gridAlpha = 0;
			axis.axisThickness = 2;
			this.amChart.addValueAxis(axis);

			groups[group] = axis;
			axis_count++;
		}

		this[new_chart.type + "Graph"](legend[key], parseInt(key), axis, new_chart.index, params, new_chart.report);
	}

	this.updateTitle();
	this.enable();
}

function chart_merge_data(data2)
{
	var data1 = this.amChart.dataProvider;

	var result = [];
	var offset = 0;

	for (var key in data2)
	{
		var position = parseInt(key - offset);

		if (!(position in data1))
		{
			result.push.apply(result, data2.slice(key));
			break;
		}

		time1 = data1[position].date;
		time2 = data2[key].date;
		if (time1 !== time2)
		{
			if (time1 > time2)
			{
				offset++;
				result.push(data2[key]);
				continue;
			}

			for (position in data1)
			{
				time1 = data1[position].date;
				if (time1 === time2)
					break;

				result.push(data1[position]);
				offset--;
			}
		}

		var merged = $.extend({}, data1[position], data2[key]);
		result.push(merged);
	}

	var position = parseInt(key - offset) + 1;
	if (position in data1)
		result.push.apply(result, data1.slice(position));

	this.amChart.dataProvider = result;
}

function chart_disable()
{
	this.amChart.chartCreated = false;
}

function chart_enable(no_validate)
{
	this.amChart.chartCreated = true;
	if (!no_validate)
		this.amChart.validateData();
}

function chart_remove_grouped(event)
{
	if (event.dataItem.description != "grouped")
		return;

	this.amChart.groupPercent = 0;
	this.amChart.labelRadius = 0.1;
	this.amChart.startAngle = 140;
	this.amChart.radius = "25%";

	this.amChart.validateData();
}

function chart_calc_sumline_data()
{
	for (var key in this.data)
	{
		this.data[key][this.sumGraph.valueField] = 0;

		for (var gkey in this.amChart.graphs)
		{
			var graph = this.amChart.graphs[gkey];

			if (graph.valueField.substr(0, 5) !== "value")
				continue;
			if (!this.data[key][graph.valueField])
				continue;
			if (graph.hidden !== false)
				continue;

			this.data[key][this.sumGraph.valueField] += parseFloat(this.data[key][graph.valueField]);
		}
	}
}

function chart_update_legend(graph, push)
{
	var options = {
		'value': [graph.graphId, graph.legendId],
		'report': graph.reportName
	};

	delete graph.valueAxis.minimum;
	delete graph.valueAxis.maximum;

	if (this.Analytics.averageEnabled && graph.legendId !== "sumline")
	{
		if (push)
			graph.valueAxis.removeGuide(graph.averageGuide);
		else
			graph.valueAxis.addGuide(graph.averageGuide);
	}

	graph.hidden = (push ? true : false);
	if (this.withSumLine)
		this.calcSumlineData();

	this.Analytics.saveParams("legend_hide", push, options);
}

function chart_update_title()
{
	for (var key in this.amChart.graphs)
	{
		var point = this.amChart.graphs[key];
		if (point.valueField == "label")
			continue;

		point.title = point.fullTitle;
	}
}

function chart_show_average()
{
	if (this.type == "round" || this.type == "candles")
	{
		this.Analytics.errors(2, null, {'message': "Невозможно отобразить среднее на круговом и свечном графиках", 'log': ""});
		return;
	}

	for (var key in this.amChart.graphs)
	{
		var graph = this.amChart.graphs[key];
		if (graph.valueField === "label")
			continue;
		if (!graph.averageGuide)
			continue;
		if (graph.hidden)
			continue;

		var axis = graph.valueAxis;
		if (this.Analytics.averageEnabled)
			axis.removeGuide(graph.averageGuide);
		else
			axis.addGuide(graph.averageGuide);
	}

	this.amChart.validateNow();

	if (this.legendMenu)
		this.legendMenu.write();
}

function chart_show_percents()
{
	if (this.type == "round" || this.type == "candles")
	{
		this.Analytics.errors(2, null, {'message': "Невозможно отобразить проценты на круговом и свечном графиках", 'log': ""});
		return;
	}

	for (var key in this.amChart.graphs)
	{
		var graph = this.amChart.graphs[key];

		if (graph.valueField === "label")
			continue;

		if (this.Analytics.percentsEnabled)
			graph.balloonText = graph.balloonText.replace(/,\s\[\[percents]]%/g, "");
		else
			graph.balloonText += ", [[percents]]%";
	}
}

function chart_switch_graph_type()
{
	if (this.type == "round" || this.type == "candles")
		return;

	for (var key in this.amChart.graphs)
		this.amChart.graphs[key].type = (this.Analytics.lineGraphs === false ? "line" : "smoothedLine");
}

function chart_handle_sumline()
{
	if (this.type == "round" || this.type == "candles")
		return;

	this.disable();

	if (this.withSumLine === true)
	{
		this.withSumLine = false;
		this.amChart.removeGraph(this.sumGraph);
		this.enable();
		return;
	}

	this.drawSumline();
	this.calcSumlineData();
	this.enable();
}

function chart_reduce_legend()
{
	var reduce_to = 2;
	var counter = 0;

	if (this.Analytics.isLegendReduced)
	{
		$(this.amChart.legend.container.container).height(this.amChart.legend.container.div.style.height);
		$(this.amChart.legend.container.div).css("max-height", 110).css("overflow-y", "auto");
	}
	else
		$(this.amChart.legend.container.div).css("max-height", "").css("overflow-y", "hidden");

	for (var key in this.amChart.graphs)
	{
		var graph = this.amChart.graphs[key];
		if (graph.visibleInLegend === false)
			continue;

		counter += 1;
		if (reduce_to >= counter)
			continue;

		graph.hidden = this.Analytics.isLegendReduced;
		if (!this.Analytics.averageEnabled)
			continue;

		if (this.Analytics.isLegendReduced)
			graph.valueAxis.removeGuide(graph.averageGuide);
		else
			graph.valueAxis.addGuide(graph.averageGuide);
	}

	if (!this.withSumLine)
		return;

	this.calcSumlineData();
	this.amChart.validateData();
}

function chart_get_sizes(new_width)
{
	if (!this.amChart.legend)
		return {'height': 0, 'width': 0, 'lineHeight': 0};

	var chart = this.amChart;
	var legend_width = new_width - (chart.legend.marginLeft === 0 ? (100 * chart.valueAxes.length - 100) : chart.legend.marginLeft) - chart.legend.marginRight;

	if (chart.legend.entries === undefined)
		chart.legend.entries = [];

	var params = chart.legend.entries.reduce(
		function (params, element)
		{
			var size = element.getBBox();

			if (params.addGap !== 0)
			{
				params.legendHeight += params.addGap;
				params.lineHeight = 0;
				params.addGap = 0;
			}

			params.lineHeight = Math.max(params.lineHeight, size.height);
			params.legendWidth += chart.legend.spacing + Math.ceil(size.width) + 1;

			if (params.legendWidth < legend_width)
				return params;

			params.legendWidth = Math.ceil(size.width) + 1;
			params.addGap = chart.legend.verticalGap + params.lineHeight;

			return params;
		},
		{
			'legendWidth': 0 - chart.legend.spacing,
			'legendHeight': 0,
			'lineHeight': 0,
			'addGap': chart.legend.verticalGap
		}
	);

	params.legendHeight += params.addGap + params.lineHeight + chart.legend.verticalGap + chart.legend.marginTop + chart.legend.marginBottom;

	return {'height': params.legendHeight, 'width': legend_width, 'lineHeight': params.lineHeight};
}

function chart_value_axis_scroll(resize)
{
	var that = this;

	if (this.type == "round")
		return;

	if ($("#axis-zoom-info").length == 0)
		$("body").append($("<div id='axis-zoom-info'></div>"));
	if (this.Analytics.isLegendReduced)
		$(this.amChart.legend.container.container).height(this.amChart.legend.container.div.style.height);

	var parent_div = $(this.amChart.div).first();

	for (var key in this.amChart.valueAxes)
	{
		var axis = this.amChart.valueAxes[key];
		if (axis.itsLabelAxis || axis.stackType == "100%")
			continue;

		var scroll = $(".an-scroll[data-chart=" + this.index + "][data-axis=" + key + "]");
		if (axis.foundGraphs === false)
			return scroll.hide(0);
		scroll.show(0);

		if (scroll.length == 0)
		{
			scroll = $("<div class='an-scroll'><div class='an-dragicon' data-type='max' style='top: 0px'></div><div class='an-dragicon' data-type='min' style='bottom: 0px;'></div></div>");
			scroll.attr("data-chart", this.index);
			scroll.attr("data-axis", key);
			parent_div.append(scroll);
		}
		if (typeof scroll.attr("data-min") == "undefined" || (!axis.minimum && !axis.maximum))
		{
			scroll.attr("data-min", axis.min);
			scroll.attr("data-max", axis.max);
			scroll.children().first().css("top", axis.viH / 4 + axis.viY).attr("data-default", axis.viH / 4 + axis.viY);
			scroll.children().last().css("top", axis.viH / 1.75 + axis.viY).attr("data-default", axis.viH / 1.75 + axis.viY);
		}

		if (resize)
		{
			scroll.children().first().css("top", axis.viH / 4 + axis.viY).attr("data-default", axis.viH / 4 + axis.viY);
			scroll.children().last().css("top", axis.viH / 1.75 + axis.viY).attr("data-default", axis.viH / 1.75 + axis.viY);
			delete axis.minimum;
			delete axis.maximum;
		}

		scroll.height(axis.viH);
		scroll.css("left", axis.viX - 10 - axis.offset);
		scroll.css("top", axis.viY);

		if (scroll.data("init") === true)
			continue;

		scroll.data("init", true);
		scroll.children().draggable({
			'axis': "y",
			'containment': "parent",
			'stop': function (event, action) {
				var target = $(event.target);
				var type = target.attr("data-type");
				var axisId = target.parent().attr("data-axis");
				var minReal = parseFloat(target.parent().attr("data-min"));
				var maxReal = parseFloat(target.parent().attr("data-max"));
				var height = target.parent().height();
				var offset;

				if (type == "max")
					offset = height + (parseFloat(target.attr("data-default")) - action.position.top) * 3.0;
				if (type == "min")
					offset = (parseFloat(target.attr("data-default")) - action.position.top + target.height()) * 3.0;

				var new_value = ((maxReal - minReal) / height * offset) + minReal;
				var axis = that.amChart.valueAxes[axisId];

				$("#axis-zoom-info").hide();

				if (type == "min")
				{
					if (new_value > axis.max)
						return;

					axis.minimum = new_value;
				}
				else
				{
					if (new_value < axis.min)
						return;

					axis.maximum = new_value;
				}

				that.amChart.invalidateSize();
				that.amChart.balloon.created = false;
			},
			'drag': function (event, action) {
				var target = $(event.target);
				var type = target.attr("data-type");
				var axisId = target.parent().attr("data-axis");
				var minReal = parseFloat(target.parent().attr("data-min"));
				var maxReal = parseFloat(target.parent().attr("data-max"));
				var height = target.parent().height();
				var offset;

				if (type == "max")
					offset = height + (parseFloat(target.attr("data-default")) - action.position.top) * 3.0;
				if (type == "min")
					offset = (parseFloat(target.attr("data-default")) - action.position.top + target.height()) * 3.0;

				var new_value = ((maxReal - minReal) / height * offset) + minReal;
				var axis = that.amChart.valueAxes[axisId];
				var info = $("#axis-zoom-info");

				info.show();
				info.css("top", action.offset.top);
				info.css("left", action.offset.left + 28);

				if (type == "min")
				{
					if (new_value > axis.max)
						return info.text(axis.max);

					info.text(Math.round(new_value, 2));
				}
				else
				{
					if (new_value < axis.min)
						return info.text(axis.min);

					info.text(Math.round(new_value, 2));
				}
			}
		});
	}
}

function chart_add_graph_menu(index)
{
	var url = window.location.href;
	var length = $("#report-graphs").children().length;

	if (url.indexOf("#") !== -1)
		url = url.slice(0, url.indexOf("#"));

	$("#report-graphs").hide();
	if (length > 0)
		$("#report-graphs").append("&nbsp;|&nbsp;");

	$("#report-graphs").append($("<a href='" + url + "#" + index +"'>" + this.params.title + "</a>"));

	length += 1;
	if (length > 1)
		$("#report-graphs").show();
}

/**
 * Chart add functions
 */
function chart_candles()
{
	var params = this.params;
	var graph_index = $(".graph").length;

	$("#chart").append("<div id='graph" + graph_index + "' class='graph' type='candles'></div>");

	this.amChartSerial();
	var chart = this.amChart;

	if (params['count'] == 0)
	{
		params['sum'] = 0;
		params['count'] = 1;
	}

	var axis = new AmCharts.ValueAxis();
	axis.title = params['legend'];
	axis.unit = params['value_append'];
	axis.axisColor = "#8EC59E";
	axis.gridAlpha = 0;
	axis.axisThickness = 2;
	chart.addValueAxis(axis);

	var guide = new AmCharts.Guide();
	guide.value = params['sum'] / params['count'];
	guide.lineColor = "#8EC59E";
	guide.dashLength = 4;
	guide.label = this.format(guide.value);
	guide.inside = true;
	guide.lineAlpha = 1;
	axis.addGuide(guide);

	var graph = new AmCharts.AmGraph();
	graph.title = params['legend'];

	if (params['show_sums'])
		graph.legendPeriodValueText = "[[value.sum]]";

	graph.type = "candlestick";
	graph.lineColor = "#8EC59E";
	graph.fillColors = "#8EC59E";
	graph.negativeLineColor = "#DB4C3C";
	graph.negativeFillColors = "#DB4C3C";
	graph.fillAlphas = 1;
	graph.openField = "value0";
	graph.closeField = "value1";
	graph.lowField = "value2";
	graph.highField = "value3";
	graph.valueField = "value1";
	graph.valueAxis = axis;
	graph.balloonText = params['legend'] + ": [[value]]" + params['value_append'] + "\nМакс: [[high]]" + params['value_append'] + "\nМин: [[low]]" + params['value_append'];
	graph.connect = false;
	chart.addGraph(graph);

	chart.balloon.verticalPadding = 0;
	chart.balloon.horizontalPadding = 6;

	this.defaults();
	this.amChart.Chart = this;
	this.amChart.write("graph" + graph_index);
}

function chart_round()
{
	var graph_index = $(".graph").length;
	$("#chart").append("<div id='graph" + graph_index + "' class='graph' type='round'></div>");

	this.amChart = new AmCharts.AmPieChart();
	var chart = this.amChart;

	chart.dataProvider = this.data;
	chart.type = "round";
	chart.addTitle(graph['title']);
	chart.color = "#000000";
	chart.marginLeft = 0;
	chart.index = this.index;
	chart.labelRadius = 4;
	chart.minRadius = 150;
	chart.radius = "30%";
	chart.titleField = "legend";
	chart.valueField = "value";

	chart.balloonText = "[[title]]: [[value]]" + this.params['value_append'];
	chart.groupPercent = 1.5;
	chart.groupedDescription = "grouped";
	chart.groupedColor = "#D3D3D3";
	chart.groupedTitle = "Остальные";

	var that = this;
	chart.addListener("clickSlice", function (e) { that.removeGrouped(e); });

	if (this.params['show_legend'])
	{
		var legend = new AmCharts.AmLegend();
		legend.valueWidth = 60;
		legend.color = "#000000";
		legend.align = "center";
		legend.maxColumns = 4;
		chart.addLegend(legend);
	}

	this.amChart.Chart = this;
	this.amChart.write("graph" + graph_index);
	this.addGraphMenu("graph" + graph_index);
}

function chart_amchart_serial()
{
	this.amChart = new AmCharts.AmSerialChart();
	var chart = this.amChart;

	chart.pathToImages = "/modules/amcharts/images/";
	chart.dataProvider = this.data;
	chart.type = "serial";
	chart.categoryField = "date";
	chart.color = "#000000";
	chart.marginLeft = 0;
	chart.index = this.index;
}

function chart_single()
{
	var graph_index = $(".graph").length;
	$("#chart").append("<div id='graph" + graph_index + "' class='graph' type='single'></div>");

	this.amChartSerial();
	this.serialGraphs();

	if (this.params['show_sumline'])
		this.calcSumlineData();

	this.defaults();
	this.amChart.write("graph" + graph_index);
	this.addGraphMenu("graph" + graph_index);

	if (this.legendMenu)
		this.legendMenu.create();
}

function chart_serial_graphs()
{
	var params = this.params;
	var groups = {};
	var axis_count = 0;
	var splits = params['split_axis'];

	var legend = params.legend;
	var ordered_legend = Object.keys(params.legend);

	var graph_counter = 0;
	var max_graphs = -1;
	var active_graphs = [];

	if (params.legend_menu)
	{
		max_graphs = (params.legend_menu.items ? params.legend_menu.items : 5);

		ordered_legend = [];
		for (var key in params.legend_menu.menu)
		{
			var id = params.legend_menu.menu[key].id;
			if (id)
			{
				if (typeof params.sums[id] !== "undefined")
					ordered_legend.push(id);
				continue;
			}

			if (params.legend_menu.menu[key].sum_by === "children_hidden")
				continue;
			if (params.legend_menu.menu[key].sum_by === "children")
				params.legend_menu.menu[key].sum_by = params.legend_menu.menu[key].children;
			if (params.legend_menu.menu[key].sum_by === "parent")
				params.legend_menu.menu[key].sum_by = [params.legend_menu.menu[key].id];

			for (var skey in params.legend_menu.menu[key].sum_by)
			{
				var id = params.legend_menu.menu[key].sum_by[skey];
				if (typeof params.sums[id] !== "undefined")
					ordered_legend.push(id);
			}
		}

		params.sort_graphs = "desc";
	}

	if (params.sort_graphs)
	{
		ordered_legend.sort(function(a, b)
		{
			var previous = params.sums[a];
			var current = params.sums[b];

			if (params.show_sums[a])
				previous = (!params.counts[a] ? 0 : params.sums[a] / params.counts[a]);
			if (params.show_sums[b])
				current = (!params.counts[b] ? 0 : params.sums[b] / params.counts[b]);

			return (previous - current) * (params.sort_graphs === "desc" ? -1 : 1);
		});
	}

	var total_sum = 0;
	var max_values = [0, 0];
	var max_keys = [false, false];

	for (var order in ordered_legend)
	{
		var key = ordered_legend[order];

		if (!(key in params['sums']) || params['sums'][key] == 0)
			continue;
		var value = params['sums'][key];

		total_sum += value;

		if (max_values[0] < value)
		{
			max_values[1] = max_values[0];
			max_keys[1] = max_keys[0];

			max_values[0] = value;
			max_keys[0] = key;
		}
		else if (max_values[1] < value)
		{
			max_values[1] = value;
			max_keys[1] = key;
		}
	}

	total_sum = total_sum * 0.2;

	for (var order in ordered_legend)
	{
		var key = ordered_legend[order];

		if (graph_counter >= max_graphs && max_graphs > 0)
			break;
		active_graphs.push(key);

		if (!(key in params['sums']) || params['sums'][key] == 0)
			continue;

		var group = 0;

		for (var splits_length = splits.length; group < splits_length; group++)
		{
			if (splits[group].indexOf(key) === -1)
				continue;
			break;
		}

		if (!(group in groups))
		{
			var axis = new AmCharts.ValueAxis();
			if (splits.length > 1)
				axis.title = legend[key];
			else
				axis.title = params['title'];

			axis.unit = params['value_append'][key];
			axis.axisColor = this.getColor(axis_count);
			axis.offset = 100 * axis_count;
			axis.gridAlpha = 0;
			axis.axisThickness = 2;
			this.amChart.addValueAxis(axis);

			groups[group] = axis;
			axis_count++;
		}

		if (params['show_sumline'] && this.withSumLine !== true)
			this.drawSumline();

		var hidden = false;
		if (ordered_legend.length >= 3 && params['sums'][key] < total_sum && max_keys[0] != key && max_keys[1] != key)
			hidden = true;

		if (this.type === "single") // nodate and single are equal except graphs
			this.singleGraph(legend[key], parseInt(key), groups[group], this.index, hidden);
		else
			this.nodateGraph(legend[key], parseInt(key), groups[group], this.index);

		graph_counter += 1;
	}

	this.createItemSelector(active_graphs);
}

function chart_single_graph(title, type, axis, chart_id, hidden)
{
	var that = this;
	var index = this.amChart.graphs.length;
	var params = this.params;
	var report = this.report;

	if (!hidden)
		hidden = false;

	var count = (params['counts'][type] == 0 ? 1 : params['counts'][type]);
	var count_old = (params['old_period']['counts'][type] == 0 ? 1 : params['old_period']['counts'][type]);
	var sum = (params['counts'][type] == 0 ? 0 : params['sums'][type]);
	var sum_old = (params['old_period']['counts'][type] == 0 ? 0 : params['old_period']['sums'][type]);

	var right_guide = false;
	var padding = index;
	while (padding * 26 > this.Analytics.minWidth)
	{
		padding -= Math.ceil(this.Analytics.minWidth / 26);
		right_guide = !right_guide;
	}

	padding += axis.offset * 0.1;
	padding = new Array(padding * 4).join(String.fromCharCode(0xA0));

	var graph = new AmCharts.AmGraph();
	graph.title = title;
	graph.type = (this.Analytics.lineGraphs === true ? "line" : "smoothedLine");
	graph.hidden = hidden;
	graph.valueAxis = axis;
	graph.negativeValue = params['negative'][type];
	graph.legendImage = this.getLegendImage(sum_old, sum, count_old, count, params['negative'][type]);

	var guide = new AmCharts.Guide();
	guide.value = sum / count;
	guide.lineColor = this.getColor(index);
	guide.dashLength = 4;
	guide.inside = true;
	guide.lineAlpha = 1;
	guide.label = this.format(guide.value) + params['value_append'][type];
	graph.averageGuide = guide;

	if (right_guide === true)
	{
		guide.position = "right";
		guide.label += padding;
	}
	else
		guide.label = padding + guide.label;

	if (params['show_sums'][type])
		graph.legendPeriodValueText = "[[value.sum]]";
	if (params['legend_hide'].indexOf(type + "") !== -1)
		graph.hidden = true;
	if (this.Analytics.averageEnabled && !graph.hidden)
		axis.addGuide(guide);

	graph.legendId = type;
	graph.graphId = chart_id;
	graph.reportName = params.report_name;
	graph.valueField = "value-" + params['report_key'] + graph.graphId + type;
	graph.lineColor = this.getColor(index);
	graph.bullet = "round";
	graph.bulletSize = 6;
	graph.lineThickness = 2;
	graph.balloonFunction = function (dataItem) { return that.balloonFunction(dataItem, params['value_append'][type]); };
	graph.fullTitle = "(" + report.title + ") " + graph.title;
	graph.connect = false;

	this.amChart.addGraph(graph);
}

function chart_stacked()
{
	var graph_index = $(".graph").length;
	$("#chart").append("<div id='graph" + graph_index + "' class='graph' type='stacked'></div>");

	this.amChartSerial();
	this.stackedGraphs();
	this.defaults();

	this.amChart.write("graph" + graph_index);
	this.addGraphMenu("graph" + graph_index);
}

function chart_stacked_graphs()
{
	var params = this.params;
	var legend = params.legend;

	var axis = new AmCharts.ValueAxis();
	axis.title = "Процент";
	axis.stackType = "100%";
	axis.unit = "%";
	axis.axisColor = this.getColor(0);
	axis.gridAlpha = 0;
	axis.axisThickness = 2;
	axis.minVerticalGap = 8;
	axis.labelFrequency = 2.5;
	this.amChart.addValueAxis(axis);

	for (var key in legend)
	{
		if (!(key in params['sums']) || params['sums'][key] == 0)
			continue;

		this.stackedGraph(legend[key], parseInt(key), axis, this.index);
	}
}

function chart_stacked_graph(title, type, axis, chart_id, params, report)
{
	var that = this;
	var index = this.amChart.graphs.length;

	if (!params)
		params = this.params;
	if (!report)
		report = this.report;

	var count = (params['counts'][type] == 0 ? 1 : params['counts'][type]);
	var count_old = (params['old_period']['counts'][type] == 0 ? 1 : params['old_period']['counts'][type]);
	var sum = (params['counts'][type] == 0 ? 0 : params['sums'][type]);
	var sum_old = (params['old_period']['counts'][type] == 0 ? 0 : params['old_period']['sums'][type]);

	var graph = new AmCharts.AmGraph();
	graph.title = title;
	graph.negativeValue = params['negative'][type];
	graph.legendImage = this.getLegendImage(sum_old, sum, count_old, count, params['negative'][type]);
	graph.averageGuide = false;

	if (params['show_sums'][type])
		graph.legendPeriodValueText = "[[value.sum]]";
	if (params['legend_hide'].indexOf(type + "") !== -1)
		graph.hidden = true;

	graph.type = "column";
	graph.valueAxis = axis;
	graph.legendId = type;
	graph.graphId = chart_id;
	graph.reportName = params.report_name;
	graph.valueField = "value-" + params['report_key'] + graph.graphId + type;
	graph.lineColor = this.getColor(index);
	graph.fillColor = this.getColor(index);
	graph.fillAlphas = 1;
	graph.lineThickness = 1;
	graph.visibleInLegend = false;
	graph.balloonFunction = function (dataItem) { return that.balloonStacked(dataItem, params['value_append'][type]); };

	graph.fullTitle = "(" + report.title + ") " + graph.title;
	this.amChart.addGraph(graph);
}

function chart_nodate()
{
	var graph_index = $(".graph").length;
	$("#chart").append("<div id='graph" + graph_index + "' class='graph' type='nodate'></div>");

	this.amChartSerial();
	this.amChart.categoryField = "x";
	this.serialGraphs();

	if (this.params['show_sumline'])
		this.calcSumlineData(chart, chart.dataProvider, chart.graphs);

	this.defaults();
	this.amChart.write("graph" + graph_index);
	this.addGraphMenu("graph" + graph_index);

	if (this.legendMenu)
		this.legendMenu.create();
}

function chart_nodate_graph(title, type, axis, chart_id, params, report)
{
	var that = this;
	var index = this.amChart.graphs.length;

	if (!params)
		params = this.params;
	if (!report)
		report = this.report;

	var count = (params['counts'][type] == 0 ? 1 : params['counts'][type]);
	var sum = (params['counts'][type] == 0 ? 0 : params['sums'][type]);

	var right_guide = false;
	var padding = index;
	while (padding * 26 > this.Analytics.minWidth)
	{
		padding -= Math.ceil(this.Analytics.minWidth / 26);
		right_guide = !right_guide;
	}

	padding += axis.offset * 0.1;
	padding = new Array(padding * 4).join(String.fromCharCode(0xA0));

	var graph = new AmCharts.AmGraph();
	graph.title = title;
	graph.type = (this.Analytics.lineGraphs === true ? "line" : "smoothedLine");
	graph.valueAxis = axis;
	graph.negativeValue = params['negative'][type];
	graph.legendImage = false;

	var guide = new AmCharts.Guide();
	guide.value = sum / count;
	guide.lineColor = this.getColor(index);
	guide.dashLength = 4;
	guide.label = padding + this.format(guide.value) + params['value_append'][type];
	guide.inside = true;
	guide.lineAlpha = 1;
	graph.averageGuide = guide;

	if (right_guide === true)
	{
		guide.position = "right";
		guide.label += padding;
	}
	else
		guide.label = padding + guide.label;

	if (params['show_sums'][type])
		graph.legendPeriodValueText = "[[value.sum]]";
	if (params['legend_hide'].indexOf(type + "") !== -1)
		graph.hidden = true;
	if (this.Analytics.averageEnabled && !graph.hidden)
		axis.addGuide(guide);

	graph.legendId = type;
	graph.graphId = chart_id;
	graph.reportName = params.report_name;
	graph.valueField = "value-" + params['report_key'] + graph.graphId + type;
	graph.lineColor = this.Analytics.getColor(index);
	graph.bullet = "round";
	graph.bulletSize = 6;
	graph.lineThickness = 2;

	graph.balloonFunction = function (dataItem) { return that.balloonFunction(dataItem, params['value_append'][type]); };

	graph.fullTitle = "(" + report.title + ") " + graph.title;
	graph.connect = false;
	this.amChart.addGraph(graph);
}

function chart_filled()
{
	var graph_index = $(".graph").length;
	$("#chart").append("<div id='graph" + graph_index + "' class='graph' type='filled'></div>");

	this.amChartSerial();
	this.filledGraphs();
	this.defaults();

	this.amChart.write("graph" + graph_index);
	this.addGraphMenu("graph" + graph_index);

	if (this.legendMenu)
		this.legendMenu.create();
}

function chart_filled_graphs()
{
	var axis = new AmCharts.ValueAxis();
	axis.title = this.params.title;
	axis.unit = "%";
	axis.stackType = "100%";
	axis.axisColor = this.getColor(0);
	axis.gridAlpha = 0;
	axis.axisThickness = 2;
	this.amChart.addValueAxis(axis);

	var legend = this.params.legend;
	for (var key in legend)
	{
		if (!(key in this.params['sums']) || this.params['sums'][key] == 0)
			continue;

		this.filledGraph(legend[key], parseInt(key), axis, this.index);
	}
}

function chart_filled_graph(title, type, axis, chart_id, params, report)
{
	var that = this;
	var index = this.amChart.graphs.length;

	if (!params)
		params = this.params;
	if (!report)
		report = this.report;

	var count = (params['counts'][type] == 0 ? 1 : params['counts'][type]);
	var count_old = (params['old_period']['counts'][type] == 0 ? 1 : params['old_period']['counts'][type]);
	var sum = (params['counts'][type] == 0 ? 0 : params['sums'][type]);
	var sum_old = (params['old_period']['counts'][type] == 0 ? 0 : params['old_period']['sums'][type]);

	var right_guide = false;
	var padding = index;
	while (padding * 26 > this.Analytics.minWidth)
	{
		padding -= Math.ceil(this.Analytics.minWidth / 26);
		right_guide = !right_guide;
	}

	padding += axis.offset * 0.1;
	padding = new Array(padding * 4).join(String.fromCharCode(0xA0));

	var graph = new AmCharts.AmGraph();
	graph.title = title;
	graph.negativeValue = params['negative'][type];
	graph.legendImage = this.getLegendImage(sum_old, sum, count_old, count, params['negative'][type]);

	var guide = new AmCharts.Guide();
	guide.value = sum / count;
	guide.lineColor = this.getColor(index);
	guide.dashLength = 4;
	guide.label = padding + this.format(guide.value) + params['value_append'][type];
	guide.inside = true;
	guide.lineAlpha = 1;
	graph.averageGuide = guide;

	if (right_guide === true)
	{
		guide.position = "right";
		guide.label += padding;
	}
	else
		guide.label = padding + guide.label;

	if (params['show_sums'][type])
		graph.legendPeriodValueText = "[[value.sum]]";
	if (params['legend_hide'].indexOf(type + "") !== -1)
		graph.hidden = true;
	if (this.Analytics.averageEnabled && !graph.hidden)
		axis.addGuide(guide);

	graph.type = (this.Analytics.lineGraphs === true ? "line" : "smoothedLine");
	graph.valueAxis = axis;
	graph.legendId = type;
	graph.graphId = chart_id;
	graph.reportName = params.report_name;
	graph.valueField = "value-" + params['report_key'] + graph.graphId + type;
	graph.lineColor = this.getColor(index);
	graph.fillColor = this.getColor(index);
	graph.fillAlphas = 1;
	graph.lineThickness = 1;
	graph.balloonFunction = function (dataItem) { return that.balloonFunction(dataItem, params['value_append'][type]); };

	graph.fullTitle = "(" + report.title + ") " + graph.title;
	this.amChart.addGraph(graph);
}

/**
 * Table function
 */
function table_create_table()
{
	var	that		= this,
		table		= $("<table id='an_table'></table>"),
		thead		= $("<thead></thead>"),
		tbody		= $("<tbody></tbody>"),
		header		= $("<tr></tr>"),
		exists		= {},
		column		= {},
		data		= {},
		groups		= {},
		row		= {},
		filter		= $("<select />").attr("id", "tableFilter"),
		filter_data	= [],
		flt_option	= "",
		flt_legend	= "",
		flt_show	= [];

	for (var key in this.report.data)
	{
		data = this.report.data[key];
		for (var dkey in data)
			exists[dkey] = true;
	}

	for (var key in this.report.rows)
	{
		row = this.report.rows[key];
		if (row.data !== false && this.report.data[row.data] === undefined)
			continue;

		data = this.report.data[row.data];
		var tr = $("<tr />", {'class': "an-table-row"});
		var td = $("<td />", {
			'class': "an-table-title",
			'text': row.title
		});

		tr.append(td);

		if (row.data === false)
			td.addClass("an-table-delimiter");

		for (var gkey in this.report.legend_groups)
		{
			var group = this.report.legend_groups[gkey];
			if (groups[gkey] === undefined)
			{
				groups[gkey] = {
					'active': false,
					'rows': {},
					'columns': [],
					'delimiters': [],
					'created': false
				};
			}

			var point = groups[gkey];

			if (row.data === false)
			{
				point.delimiters.push(td);
				continue;
			}

			for (var lkey in group.groups)
			{
				var legend_id = group.groups[lkey];
				if (this.report.legend[legend_id] === undefined)
					continue;
				if (exists[legend_id] === undefined)
					continue;

				var value = data[legend_id];
				if (value === undefined)
					value = 0;

				var cell = this.createValueCell(value, legend_id);
				tr.append(cell.container);

				if (point.rows[key] === undefined)
				{
					point.rows[key] = {
						'max': 0,
						'min': 0xFFFFFFFF,
						'values': [],
						'sum': 0,
						'options': row,
						'tr': tr,
						'td': td
					};
				}

				if (row.value !== "date")
				{
					if (row.value === "percent" || row.value === "part")
						value = this.calcValue({'value': value, 'column': legend_id}, point.rows[key]).valueRounded;

					if (value != 0)
						point.rows[key].min = Math.min(value, point.rows[key].min);
					point.rows[key].max = Math.max(value, point.rows[key].max);
				}

				point.rows[key].sum += parseFloat(value);
				point.rows[key].values.push(cell);

				if (point.created !== false)
					continue;

				column = $("<th />", {
					'class': "an-table-legend",
					'text': this.report.show_legend ? this.report.legend[legend_id] : legend_id,
					'style': "display: none;",
					'data': {'legend': legend_id, 'group': gkey}
				});

				if (!this.report.show_legend)
					column.attr("title", this.report.legend[legend_id]);

				if (this.report.show_legend === false)
					column.addClass("bold");

				point.columns.push(column);
				column.prepend($("<span />", {'style': "background-color: " + this.Analytics.getColor(legend_id) + ";"}));

				if (this.report.editable !== false)
					column.append("<span class=\"edit-col\" data-id=\"" + legend_id + "\" data-report=\"" + this.report.report.path + "\" data-service=\"" + this.Analytics.service + "\" title=\"Редактировать\"><i class=\"icon-edit\"></i></span>");

				if (this.report.show_image !== false)
				{
					column.addClass("show-image");
					column.attr("data-id", legend_id);
					column.attr("data-report", this.report.report.path);
					column.attr("data-service", this.Analytics.service);
				}

				header.append(column);
			}

			if (point.columns.length === 0)
				continue;
			if (point.created === false)
				point.button = this.addMenu(gkey, group.title);

			point.created = true;
		}

		tbody.append(tr);
	}

	header.prepend("<th></th>");
	thead.append(header);

	table.append(thead, tbody);
	this.container.append(table);

	if (this.report.filter !== false)
	{
		for (var index in this.report.filter)
		{
			if (filter_data[this.report.filter[index]] === undefined)
				filter_data[this.report.filter[index]] = [];

			filter_data[this.report.filter[index]].push(index);
		}

		for (flt_legend in filter_data)
		{
			flt_option = filter_data[flt_legend].join(",");
			filter.append("<option value=\"" + flt_option + "\">" + flt_legend + "</option>");
		}

		if (filter.find("option").length > 1)
		{
			filter.prepend("<option value=\"showall\" selected=\"selected\">Показывать всё</option>");
			filter.insertBefore(table);
			filter.change(function () {
				table.find("th,td").removeClass("flt-hide");

				if ($(this).val() === "showall")
					return that.updateMinMax();

				flt_show = $(this).val().split(",");
				thead.find("th").each(function () {
					if ($(this).data("legend") === undefined)
						return;

					if (flt_show.indexOf($(this).data("legend").toString()) === -1)
					{
						$(this).addClass("flt-hide");
						tbody.find("tr").find("td:eq(" + $(this).index() + ")").addClass("flt-hide");
					}
				});

				that.updateMinMax();
			});
		}
	}

	if (this.report.editable !== false)
		$(".edit-col").click(this.colEditForm.bind(null, that));

	if (this.report.show_image !== false)
		$(".show-image").hover(this.showColImage.bind(null, that), this.hideColImage.bind(null, that));

	this.groups = groups;
}

function table_update_values()
{
	var keys = Object.keys(this.groups);
	var visible_key = false;

	for (var i = 0, keys_length = keys.length; i < keys_length; i++)
	{
		var key = keys[i];
		if (this.groups[key].created === false)
			continue;

		var visible_key = key;
		break;
	}

	if (visible_key === false)
	{
		this.container.empty();
		return this.errors(0, "empty", {'message': "", 'log': "No data for report: " + this.report.report.path});
	}

	for (var key in this.groups)
	{
		var group = this.groups[key];
		var length = group.columns.length;

		if (length === 0)
			continue;

		var left_index = 0;
		var right_index = length - 1;
		var left_legend = group.columns[left_index].data("legend");
		var right_legend = group.columns[right_index].data("legend");

		group.button.bind("click", this.groupButtonHandler.bind(this, group));

		for (var rkey in group.rows)
		{
			var row = group.rows[rkey];
			var average = row.sum / group.columns.length;
			var deviation = (average - row.min) / average * 100;
			var title = "Максимальное отклонение " + this.Analytics.format(deviation) + "%";

			if (row.options.negative === true)
				row.min = [row.max, row.max = row.min][0];

			if (visible_key === key)
			{
				row.tr.show();
				if (row.sum === 0)
					row.tr.hide();
			}

			for (var vkey in row.values)
				this.writeValue(row.values[vkey], row, {'title': title, 'leftLegend': left_legend, 'rightLegend': right_legend, 'key': key, 'visibleKey': visible_key, 'length': length});
		}

		for (var ckey in group.columns)
		{
			var column = group.columns[ckey];

			if (key === visible_key)
				column.show();
		}

		if (key !== visible_key)
			continue;

		for (var dkey in group.delimiters)
		{
			var delimiter = group.delimiters[dkey];
			delimiter.prop("colspan", group.columns.length + 1);
		}

		group.button.addClass("active");
	}
}

function table_calc_value(cell, row)
{
	var result = {
		'value': "",
		'hover': "",
		'valueFloat': parseFloat(cell.value),
		'hoverFloat': parseFloat(cell.value),
		'valueRounded': parseFloat(cell.value),
		'hoverRounded': parseFloat(cell.value)
	};
	var type = "value";

	if (row.options['value'] == "default")
		row.options['hover'] = "hover_percent";
	else
		row.options['hover'] = "default";

	type_switch:
	while (true)
	{
		var append = row.options[type + "_append"];
		var value = parseFloat(cell.value);

		switch (row.options[type])
		{
			case "date":
				if (value === 0)
					break type_switch;

				var date = new Date(value * 1000);
				result.value = result.hover = ("0" + date.getUTCDate()).slice(-2) + "." + ("0" + (date.getUTCMonth() + 1)).slice(-2) + "." + date.getUTCFullYear();
				break type_switch;
			case "percent":
			case "part":
				if (this.report.data[row.options.sub_data][cell.column] === undefined)
					break;

				var point = parseFloat(this.report.data[row.options.sub_data][cell.column]);
				if (point === 0)
					break;

				value = value / point;

				if (row.options[type] !== "percent")
					break;

				value *= 100;
				append = "%";
				break;
			case "hover_percent":
			case "hover_part":
				if (row.sum === 0)
					break;

				value = value / row.sum;

				if (row.options[type] !== "hover_percent")
					break;

				value *= 100;
				append = "%";
				break;
		}

		result[type + "Float"] = value;
		result[type + "Rounded"] = parseFloat(this.Analytics.format(value));
		result[type] = this.Analytics.format(value) + append;

		if (type === "hover")
			break;

		type = "hover";
	}

	return result;
}

function table_write_value(cell, row, options)
{
	var value = cell.value;
	var legend = cell.column;

	if (options.key === options.visibleKey && row.sum !== 0)
		cell.container.show();

	if (row.options.value === "date")
	{
		cell.valueContainer.text(this.calcValue(cell, row).value);
		return;
	}

	var calculated = this.calcValue(cell, row);

	if (row.options.value === "percent" || row.options.value === "part")
		value = calculated.valueRounded;

	var diff = this.Analytics.format(value / row.min * 100 - 100);
	if (diff > 0)
		diff = "+" + diff;

	if (row.min === 0)
		diff = "+0";
	if (row.options.tooltip !== false)
		cell.container.attr("title", options.title);

	cell.container.data("value", parseFloat(calculated.value.replace(",", "")));

	cell.visibleValue = calculated.value;
	cell.hoverValue = calculated.hover;
	cell.valueContainer.text(cell.visibleValue);
	cell.container.bind("mouseenter mouseleave touchend", this.hoverValue.bind(row, this.Analytics.isMobile));

	if (value === 0)
	{
		cell.container.addClass("null-cell");
		return;
	}

	if (value == row.min && row.min !== row.max)
	{
		if (options.length > 2)
		{
			cell.container.addClass("up-cell");
			cell.container.prepend("<span class='down'>&#9662;</span>");
		}
		return;
	}

	if (value == row.max)
	{
		cell.container.addClass("down-cell");
		cell.container.prepend("<span class='up'>&#9652;</span>");
	}

	cell.diffContainer.text(diff + "%");
	if (row.options.show_diff === true)
		cell.diffContainer.show();
}

function table_create_value_cell(value, column)
{
	var cell = {
		'container': $("<td />").addClass("an-table-value"),
		'valueContainer': $("<p />").addClass("an-table-value-text"),
		'diffContainer': $("<p />").addClass("an-table-value-diff"),
		'visibleValue': null,
		'hoverValue': null,
		'column': parseInt(column),
		'value': parseFloat(value)
	};

	cell.container.append(cell.valueContainer, cell.diffContainer);

	return cell;
}

function table_repaint()
{
	var rows = $(".an-table-row:visible");
	var i = 0;

	$(".an-table-title").removeClass("even");
	rows.each(function () {
		i += 1;

		if (i !== 2)
			return;

		$(this).children(".an-table-title").addClass("even");
		i = 0;
	});

	rows.removeClass("last");
	rows.last().addClass("last");
}

function table_add_menu(key, title)
{
	var button = $("<a />", {'class': "an-table-groups-button", 'text': title, 'href': "#an_table", 'data': {'group': key}});
	var length = $("#report-graphs").children().length;

	$("#report-graphs").hide();
	if (length > 0)
		$("#report-graphs").append("&nbsp;|&nbsp;");

	$("#report-graphs").append(button);

	if (length >= 1)
		$("#report-graphs").show();

	return button;
}

function table_update_min_max()
{
	var	min_cell	= {},
		max_cell	= {},
		min		= 0,
		max		= 0,
		value		= 0,
		i		= 0;

	$(".an-table-value span.down, .an-table-value span.up").remove();
	$(".an-table-value").removeClass("up-cell").removeClass("down-cell");
	$(".an-table-row").each(function () {
		if ($(this).find(".an-table-title").hasClass("an-table-delimiter"))
			return;

		min_cell = {};
		max_cell = {};
		min = 0;
		max = 0;
		i = 0;

		$(this).find(".an-table-value:visible").each(function () {
			value = parseFloat($(this).data("value"));

			if (isNaN(value) || value === 0)
				return;

			if (i === 0)
			{
				min = value;
			}

			if (value >= max)
			{
				max = value;
				if ((max_cell.length > 0) && (max_cell.data("value") === $(this).data("value")))
					max_cell = max_cell.add(this);
				else
					max_cell = $(this);
			}

			if (value <= min)
			{
				min = value;
				if ((min_cell.length > 0) && (min_cell.data("value") === $(this).data("value")))
					min_cell = min_cell.add(this);
				else
					min_cell = $(this);
			}

			i++;
		});

		if (min_cell.length > 0 && max_cell.length > 0 && min_cell.index() === max_cell.index())
		{
			max_cell.prepend("<span class=\"down\">&#9652;</span>").addClass("down-cell");
			return;
		}

		if (min_cell.length > 0)
			min_cell.prepend("<span class=\"up\">&#9662;</span>").addClass("up-cell");

		if (max_cell.length > 0)
			max_cell.prepend("<span class=\"down\">&#9652;</span>").addClass("down-cell");
	});
}

function table_col_edit_form(scope, e)
{
	var data = {
		'type' : $(e.target).parent().data("id"),
		'report' : $(e.target).parent().data("report"),
		'service' : $(e.target).parent().data("service")
	};

	$.ajax({
		'url' : "/?module=analytics&action=col_edit",
		'data' : data,
		'type' : "post",
		'dataType' : "json",
		'success' : function (data) {
			scope.createEditForm(data);
		},
		'error' : function (data) {
			scope.Analytics.errors(1, "col_edit", {'message': "", 'log': data.responseText});
		}
	});
}

function table_create_edit_form(data)
{
	var	edit_dialog_html = $("<div id=\"edit_dialog\" />"),
		dialog = {},
		callback = this.sendColData.bind(this);

	if (!data || data.editForm === undefined)
		return this.Analytics.errors(1, "col_edit", {'message': "", 'log': "Не получена форма редактирования с сервера"});

	edit_dialog_html.append(data.editForm);
	$("body").append(edit_dialog_html);

	if ($("#image_input").length > 0)
	{
		$("#edit_dialog form").submit(this.sendColData.bind(this));
		$("#image_input").uploadInput({
			'action' : "/?module=files&action=upload"
		});
		callback = function () {
			$("#edit_dialog form").submit();
		};
	}

	dialog = $("#edit_dialog").dialog({
		'autoOpen' : false,
		'title' : "Редактировать параметры",
		'width' : 500,
		'modal' : true,
		'show' : {
			'effect' : "drop",
			'duration' : 500
		},
		'hide' : {
			'effect' : "fade",
			'duration' : 500
		},
		'close' : function (event, ui) {
			$("#edit_dialog").remove();
		},
		buttons: {
			'Сохранить' : callback,
			'Отмена' : function() {
				dialog.dialog("close");
			}
		}
	});

	dialog.dialog("open");
}

function table_send_col_data()
{
	var	data = $("#edit_dialog").find("input, select"),
		that = this;

	$.ajax({
		'url' : "/?module=analytics&action=col_save",
		'data' : data,
		'type' : "post",
		'dataType' : "json",
		'success' : function (data) {
			$("#edit_dialog").dialog("close");
		},
		'error' : function (data) {
			that.Analytics.errors(1, "col_save", {'message': "", 'log': data.responseText});
		}
	});
}

function table_show_col_image(scope, e)
{
	var	el = $(e.target).closest(".show-image"),
		data = {
			'type' : $(el).data("id"),
			'report' : $(el).data("report"),
			'service' : $(el).data("service")
		},
		img_class = "col-img",
		img = $("<img class=\"" + img_class + "\" />");

	$(".show-image").popover("destroy");

	$.ajax({
		'url' : "/?module=analytics&action=col_image",
		'data' : data,
		'type' : "post",
		'dataType' : "json",
		'success' : function (data) {
			if (!data || data.imgUrl === undefined)
				return scope.Analytics.errors(1, "col_image", {'message': "", 'log': "Ошибка на сервере при загрузке изображения"});

			if (data.imgUrl === "")
				return;

			img.attr("src", data.imgUrl);

			$(el).popover({
				'html' : true,
				'content' : img,
				'placement' : "bottom",
				'container' : "body",
				'template' : "<div class=\"popover popover-medium\"><div class=\"arrow\"></div><div class=\"popover-inner\"><h3 class=\"popover-title\"></h3><div class=\"popover-content\"><p></p></div></div></div>"
			});
			$(el).popover('show');
		},
		'error' : function (data) {
			scope.Analytics.errors(1, "col_image", {'message': "", 'log': data.responseText});
		}
	});
}

function table_hide_col_image(scope, e)
{
	var el = $(e.target).closest(".show-image");

	$(el).popover("destroy");
}

/**
 * ItemSelector functions
 */
function itemselector_click_event(checked, elem)
{
	var type = elem.data.key;
	var merged = (!elem.data.isFolder && (elem.data.parents === false ? true : elem.data.parents.indexOf(parseInt(type)) === -1));
	var chart = this.options.mainChart;

	if (merged === false)
	{
		if (!checked)
		{
			for (var key in chart.graphs)
			{
				if (chart.graphs[key].legendId != type)
					continue;
				var graph = chart.graphs[key];

				if (this.Chart.Analytics.averageEnabled === true)
					graph.valueAxis.removeGuide(graph.averageGuide);

				this.Chart.disable();
				chart.removeGraph(graph);

				if (this.Chart.withSumLine)
					this.Chart.calcSumlineData();

				this.Chart.enable();
				break;
			}

			return;
		}

		var axis = this.options.valueAxes.normal;
		var legend = this.Chart.params.legend;

		this.Chart.disable();

		this.Chart.singleGraph(legend[type], parseInt(type), axis, this.Chart.index);
		if (this.Chart.withSumLine)
			this.Chart.calcSumlineData();

		this.Chart.enable();
		return;
	}

	if (!checked)
	{
		for (var graph in chart.graphs)
		{
			if (chart.graphs[graph].legendId != type)
				continue;

			this.Chart.disable();
			chart.removeGraph(chart.graphs[graph]);

			if (this.Chart.withSumLine)
				this.Chart.calcSumlineData();

			this.enable();
			break;
		}

		return;
	}

	if (this.options.valueAxes.merged === false)
	{
		var axis_count = 0;
		for (var key in chart.valueAxes)
		{
			if (chart.valueAxes[key].itsLabelAxis)
				continue;

			axis_count += 1;
		}

		var axis = new AmCharts.ValueAxis();
		axis.axisColor = this.Chart.getColor(axis_count);
		axis.offset = 100 * axis_count;
		axis.gridAlpha = 0;
		axis.axisThickness = 2;
		chart.addValueAxis(axis);

		this.options.valueAxes.merged = axis;
	}

	var axis = this.options.valueAxes.merged;
	var legend = this.Chart.params.legend;

	this.Chart.disable();

	this.Chart.singleGraph(legend[type], parseInt(type), axis, this.Chart.index);
	if (this.Chart.withSumLine)
		this.Chart.calcSumlineData();

	this.Chart.enable();
}

function itemselector_write()
{
	var that = this;
	var data = [];

	for (var key in this.settings.menu)
	{
		var value = this.settings.menu[key];
		var element = {
			'title': "",
			'sums': 0,
			'parents': (typeof value.sum_by === "undefined" ? false : value.sum_by),
			'key': value.id,
			'select': (this.selected.indexOf(value.id) !== -1),
			'isFolder': true,
			'hideCheckbox': value.id === false,
			'children': []
		};

		if (value.id === false)
		{
			for (var skey in value.sum_by)
			{
				var type = value.sum_by[skey];
				if (!this.sums[type])
					continue;

				var sum = this.sums[type];
				if (this.show_sums[type] === false)
					sum = (this.counts[type] == 0 ? 0 : sum / this.counts[type]);

				element.sums += sum;
			}

			element.title = value.name;
		}
		else
		{
			if (!this.legend[value.id])
				continue;
			if (!this.sums[value.id])
				continue;

			var sum = this.sums[value.id];
			if (this.show_sums[value.id] === false)
				sum = (this.counts[value.id] == 0 ? 0 : sum / this.counts[value.id]);

			element.sums += sum;
			element.title = this.legend[value.id];
		}

		if (!value.children || value.children.length == 0)
		{
			if (element.sums !== 0)
				data.push(element);
			continue;
		}

		for (var ckey in value.children)
		{
			var children_value = value.children[ckey];

			if (!(children_value in this.legend))
				continue;
			if (!(children_value in this.sums))
				continue;
			if (this.selected.indexOf(children_value) !== -1)
				element.expand = true;

			var sum = this.sums[children_value];
			if (this.show_sums[children_value] === false)
				sum = (this.counts[children_value] == 0 ? 0 : sum / this.counts[children_value]);

			var children = {
				'title': this.legend[children_value],
				'sums': sum,
				'key': children_value,
				'select': (this.selected.indexOf(children_value) !== -1),
				'parents': (typeof value.sum_by === "undefined" ? false : value.sum_by)
			};
			element.children.push(children);
		}

		if (element.sums == 0)
			continue;

		data.push(element);
	}

	data.sort(function (a, b) { return -(a.sums - b.sums); });
	for (var key in data)
		data[key].children.sort(function (a, b) { return -(a.sums - b.sums); });

	this.content.dynatree({
		'checkbox': true,
		'selectMode': 2,
		'children': data,
		'onClick': function(node, event) {
			if (node.getEventTargetType(event) == "title" && node.data.isFolder !== true)
				node.toggleSelect();
		},
		'onSelect': function (checked, data) {
			that.clickEvent(checked, data);
		},
		'onCustomRender': function (node) {
			html = "<a class='dynatree-title' href='#'>" + node.data.title + "</a>";
			html += "<span class='an-sums'>" + that.Chart.format(node.data.sums, 0) + "</span>";

			return html;
		},
		'onKeydown': function(node, event) {
			if (event.which == 32) {
				node.toggleSelect();
				return false;
			}
		}
	});
	this.dialog.appendTo($(this.options.mainChart.div));
	this.dialog.draggable({
		'containment': "parent",
		'cancel': ".an-item-selector-content, .an-item-selector-search",
		'start': function (event, ui) { $(event.target).css("right", "auto"); }
	});
	this.dialog.resizable();
}

/**
 * Other functions
 */
function handle_right_mouse(a)
{
	if (!this.zoomable && !this.pan && !this.drawing)
		return;
	var b = this.rotate;
	var d = this.chart;
	var e = d.mouseX - this.x;
	var f =	d.mouseY - this.y;

	if (0 >= e || e >= this.width || 0 >= f && f >= this.height || a.button == 2)
		return;
	if (a == "fake")
		return;

	this.setPosition();
	if (this.selectWithoutZooming)
		AmCharts.remove(this.selection);

	if (this.drawing)
	{
		this.drawStartY = f;
		this.drawStartX = e;
		this.drawingNow = true;
		return;
	}

	if (this.pan)
	{
		this.zoomable = false;
		d.setMouseCursor("move");
		this.panning = true;
		this.panClickPos = b ? f : e;
		this.panClickStart = this.start;
		this.panClickEnd = this.end;
		this.panClickStartTime = this.startTime;
		this.panClickEndTime = this.endTime;
		return;
	}

	this.zooming = true;
	if (!this.zoomable || "cursor" != this.type)
	{
		this.initialMouseX = e;
		this.initialMouseY = f;
		this.selectionPosX = e;
		this.selectionPosY = f;
		return;
	}

	this.fromIndex = this.index;
	if (b)
	{
		this.initialMouse = f;
		this.selectionPosY = this.linePos;
	}
	else
	{
		this.initialMouse = e;
		this.selectionPosX = this.linePos;
	}
}

function legend_with_image(a)
{
	if (false === a.visibleInLegend)
		return;

	var b = this.chart,
		c = a.markerType;
	c || (c = this.markerType);
	var d = a.color,
		f = a.alpha;
	a.legendKeyColor && (d = a.legendKeyColor());
	a.legendKeyAlpha &&
		(f = a.legendKeyAlpha());
	var e;
	!0 === a.hidden && (e = d = this.markerDisabledColor);
	var g = a.pattern,
		h = a.customMarker;
	h || (h = this.customMarker);
	var k = this.container,
		l = this.markerSize,
		m = 0,
		n = 0,
		p = l / 2;
	if (this.useGraphSettings)
		if (m = a.type, this.switchType = void 0, "line" == m || "step" == m || "smoothedLine" == m || "ohlc" == m) g = k.set(), a.hidden || (d = a.lineColorR, e = a.bulletBorderColorR), n = AmCharts.line(k, [0, 2 * l], [l / 2, l / 2], d, a.lineAlpha, a.lineThickness, a.dashLength), g.push(n), a.bullet && (a.hidden || (d = a.bulletColorR), n = AmCharts.bullet(k,
			a.bullet, a.bulletSize, d, a.bulletAlpha, a.bulletBorderThickness, e, a.bulletBorderAlpha)) && (n.translate(l + 1, l / 2), g.push(n)), p = 0, m = l, n = l / 3;
		else {
			var r;
			a.getGradRotation && (r = a.getGradRotation());
			m = a.fillColorsR;
			!0 === a.hidden && (m = d);
			if (g = this.createMarker("rectangle", m, a.fillAlphas, a.lineThickness, d, a.lineAlpha, r, g)) p = l, g.translate(p, l / 2);
			m = l;
		} else h ? (b.path && (h = b.path + h), g = k.image(h, 0, 0, l, l)) : (g = this.createMarker(c, d, f, void 0, void 0, void 0, void 0, g)) && g.translate(l / 2, l / 2);
	this.addListeners(g, a);
	k = k.set([g]);
	this.switchable && k.setAttr("cursor", "pointer");
	(e = this.switchType) && "none" != e && ("x" == e ? (r = this.createX(), r.translate(l / 2, l / 2)) : r = this.createV(), r.dItem = a, !0 !== a.hidden ? "x" == e ? r.hide() : r.show() : "x" != e && r.hide(), this.switchable || r.hide(), this.addListeners(r, a), a.legendSwitch = r, k.push(r));
	e = this.color;
	a.showBalloon && this.textClickEnabled && void 0 !== this.selectedColor && (e = this.selectedColor);
	this.useMarkerColorForLabels && (e = d);
	!0 === a.hidden && (e = this.markerDisabledColor);
	d = AmCharts.massReplace(this.labelText, {
		"[[title]]": a.title
	});
	r = this.fontSize;
	g && l <= r && g.translate(p, l / 2 + this.ly - r / 2 + (r + 2 - l) / 2 - n);
	var u;
	var im;
	var image = a.legendImage;

	if (d)
	{
		d = AmCharts.fixBrakes(d);
		a.legendTextReal = d;
		u = AmCharts.text(this.container, d, e, b.fontFamily, r, "start");
		u.translate(this.lx + m, this.ly);
		k.push(u);
		b = u.getBBox().width;

		if (image && this.chart.showLegendImages)
		{
			im = this.container.image(image.url, 0, 0, image.width, image.height);
			im.translate(b + this.lx + 4, this.ly + r - image.height);

			b += im.getBBox().width;
			k.push(im);

			imtext = AmCharts.text(this.container, image.text, e, b.fontFamily, r, "start");
			imtext.translate(b + this.lx + 4, this.ly);
			k.push(imtext);

			b += imtext.getBBox().width;
		}

		if (this.maxLabelWidth < b)
			this.maxLabelWidth = b;
	}

	this.entries[this.index] = k;
	a.legendEntry = this.entries[this.index];
	a.legendLabel = u;
	this.index++;
}

function balloons_show()
{
	var a = this.pointToX;
	var b = this.pointToY;
	var c = this.chart;

	if (AmCharts.VML)
		this.fadeOutDuration = 0;

	this.deltaSignX = this.deltaSignY = 1;

	if (isNaN(a))
		return;

	var d = this.follow;
	var f = c.container;

	if (this.set)
		this.set.setAttr("visibility", "hidden");
	if (!this.show)
		return;

	if (this.created !== true)
	{
		this.set = f.set();
		c.balloonsSet.push(e);

		this.textDiv = document.createElement("div");
		this.textDiv.innerHTML = "<div style='max-width: " + this.maxWidth + "px; font-size: " + c.fontSize + "px; color: " + this.color + "; font-family: " + (this.fontFamily ? this.fontFamily : c.fontFamily) + "; text-shadow: 0 1px 1px rgba(0, 0, 0, 0.8); white-space: nowrap;'> " + this.text + "</div>";
		this.textDiv.style.position = "absolute";

		this.chart.chartDiv.appendChild(this.textDiv);

		this.created = true;
		this.bg = false;
	}

	this.textDiv.childNodes[0].innerHTML = this.text;
	this.textHeight = this.text.split("<br />").length * 20;
	this.textWidth = calc_width(this.text);

	var e = this.set;
	var g = this.l;
	var h = this.t;
	var k = this.r;
	var l = this.b;
	var m = this.balloonColor;
	var n = this.fillColor;
	var p = this.borderColor;
	var r = n;
	void 0 != m && (this.adjustBorderColor ? r = p = m : n = m);
	var u = this.horizontalPadding;
	var q = this.verticalPadding;
	var t = this.pointerWidth;
	var v = this.pointerOrientation;
	var s = this.cornerRadius;

	m = this.textDiv;
	var C = m.style;

	A = this.textWidth;
	w = this.textHeight;

	var w = w + 2 * q;
	var y = A + 2 * u;

	if (window.opera)
		w += 2;

	var z = this.offsetY;
	var J = !1;

	"H" != v ? (A = a - y / 2, b < h + w + 10 && "down" != v ? (J = !0, d && (b += z), z = b + t, this.deltaSignY = -1) : (d && (b -= z), z = b - w - t, this.deltaSignY = 1)) : (2 * t > w && (t = w / 2), z = b - w / 2, a < g + (k - g) / 2 ? (A = a + t, this.deltaSignX = -1) : (A = a - y - t, this.deltaSignX = 1));
	if (z + w >= l)
		z = l - w;

	if (z < h)
		z = h;
	if (A < g)
		A = g;
	if (A + y > k)
		A = k - y;

	var h = z + q;
	var l = A + u;
	var q = this.shadowAlpha;
	var I = this.shadowColor;
	var u = this.borderThickness;
	var B = this.bulletSize;

	if (this.bg !== false)
	{
		this.bg.setAttr("width", y);
		this.bg.setAttr("height", w);

		f = 1 * this.deltaSignX;

		C.transform = "translate(" + l + "px, " + h + "px)";
		C.webkitTransform = "translate(" + l + "px, " + h + "px)";
		C.MozTransform = "translate(" + l + "px, " + h + "px)";

		e.translate(A - f, z);
		this.bottom = z + w + 1;
		this.yPos = 0.5 + z;

		this.prevX = A - f;
		this.prevY = z;
		this.prevTX = l;
		this.prevTY = h;

		this.set.setAttr("visibility", "visible");

		if (this.bgColor === this.balloonColor)
			return;

		this.bg.setAttr("fill", this.balloonColor);
		this.bgColor = this.balloonColor;

		return;
	}

	if (0 < s || 0 === t)
	{
		if (0 < q)
		{
			a = AmCharts.rect(f, y, w, n, 0, u + 1, I, q, this.cornerRadius);
			if (AmCharts.isModern)
				a.translate(1, 1);
			else
				a.translate(4, 4);

			e.push(a);
		}

		n = AmCharts.rect(f, y, w, n, this.fillAlpha, u, p, this.borderAlpha, this.cornerRadius);

		if (this.showBullet)
		{
			Z = AmCharts.circle(f, B, r, this.fillAlpha);
			e.push(Z);
		}
	}
	else
	{
		r = [];
		s = [];

		if ("H" != v)
		{
			g = a - A;

			if (g > y - t)
				g = y - t;
			if (g < t)
				g = t;

			r = [0, g - t, a - A, g + t, y, y, 0, 0];

			if (J)
				s = [0, 0, b - z, 0, 0, w, w, 0];
			else
				[w, w, b - z, w, w, 0, 0, w];
		}
		else
		{
			r = b - z;

			if (r > w - t)
				r = w - t;

			if (r < t)
				r = t;

			s = [0, r - t, b - z, r + t, w, w, 0, 0];

			if (a < g + (k - g) / 2)
				r = [0, 0, A < a ? 0 : a - A, 0, 0, y, y, 0];
			else
				[y, y, A + y > a ? y : a - A, y, y, 0, 0, y];

			if (0 < q)
				a = AmCharts.polygon(f, r, s, n, 0, u, I, q), a.translate(1, 1), e.push(a);
		}

		n = AmCharts.polygon(f, r, s, n, this.fillAlpha, u, p, this.borderAlpha);
	};

	this.bg = n;
	this.set.push(n);

	n.toFront();
	f = 1 * this.deltaSignX;

	e.translate(A - f, z);

	C.transform = "translate(" + l + "px, " + h + "px)";
	C.webkitTransform = "translate(" + l + "px, " + h + "px)";
	C.MozTransform = "translate(" + l + "px, " + h + "px)";

	this.bgColor = this.balloonColor;
	this.bottom = z + w + 1;
	this.yPos = 0.5 + z;

	this.prevX = A - f;
	this.prevY = z;
	this.prevTX = l;
	this.prevTY = h;
}

function balloons_hide() {
	clearInterval(this.interval);

	if (this.set)
		this.set.setAttr("visibility", "hidden");
	if (!this.textDiv)
		return;

	this.textDiv.style.transform = "translate(-10000px, -10000px)";
	this.textDiv.style.webkitTransform = "translate(-10000px, -10000px)";
	this.textDiv.style.MozTransform = "translate(-10000px, -10000px)";
}

function calc_width(text)
{
	var width = 0;
	var container = $("#width_calculator");

	if ((/<span.*?>(.*?)<\/span>/).test(text) === true)
	{
		text = text.match(/<span.*?>(.*?)<\/span>/)[1];

		if ((/\<p[^\>]*\>/).test(text) !== false)
 		{
			text = text.replace(/\<p[^\>]*\>/g, "");
			text = text.replace(/\<[^\>]*p\>/g, "");
		}
	}

	if (!an.fontWidth)
		an.fontWidth = {};

	var sizes = [];
	var rows = text.split("<br />");

	for (var key in rows)
	{
		var size = 0;
		if ((/<i.*?><\/i>/).test(rows[key]) !== false)
		{
			rows[key] = rows[key].replace(/<i.*?><\/i>/, "");
			size += 16;
		}

		var splits = rows[key].split("");

		for (var skey = 0, splits_length = splits.length; skey < splits_length; skey++)
		{
			var value = splits[skey];

			if (an.fontWidth[value] === undefined)
				an.fontWidth[value] = $("#width_calculator").text(value)[0].clientWidth;

			size += an.fontWidth[value];
		}

		sizes[sizes.length] = size;
	}

	return Math.max.apply(null, sizes);
}

function transliterate(word)
{
	var translit = {"Ё":"YO","Й":"I","Ц":"TS","У":"U","К":"K","Е":"E","Н":"N","Г":"G","Ш":"SH","Щ":"SCH","З":"Z","Х":"H","Ъ":"'","ё":"yo","й":"i","ц":"ts","у":"u","к":"k","е":"e","н":"n","г":"g","ш":"sh","щ":"sch","з":"z","х":"h","ъ":"'","Ф":"F","Ы":"I","В":"W","А":"a","П":"P","Р":"R","О":"O","Л":"L","Д":"D","Ж":"ZH","Э":"E","ф":"f","ы":"i","в":"w","а":"a","п":"p","р":"r","о":"o","л":"l","д":"d","ж":"zh","э":"e","Я":"Ya","Ч":"CH","С":"S","М":"M","И":"I","Т":"T","Ь":"'","Б":"B","Ю":"YU","я":"ya","ч":"ch","с":"s","м":"m","и":"i","т":"t","ь":"'","б":"b","ю":"yu"};

	return word.split("").map(function (char) {
		return translit[char] || char;
	}).join("");
}

function detect_scrollbar_width(el)
{
	var scrollDiv = document.createElement("div");

	$(el).append(scrollDiv);

	var scrollbarWidth = $(el).innerWidth() - scrollDiv.clientWidth;

	$(scrollDiv).remove();

	return scrollbarWidth;
}

function set_datepicker(onSelectHandlerBegin, onSelectHandlerEnd)
{
	$("#date_begin").datepicker(
	{
		'changeMonth': true,
		'changeYear': true,
		'minDate': null,
		'yearRange': "-2:+0",
		'gotoCurrent': false,
		'showButtonPanel': true,
		'dateFormat': "dd.mm.yy",
		'onSelect': onSelectHandlerBegin
	});

	$("#date_end").datepicker(
	{
		'changeMonth': true,
		'changeYear': true,
		'minDate': null,
		'yearRange': "-2:+0",
		'gotoCurrent': false,
		'showButtonPanel': true,
		'dateFormat': "dd.mm.yy",
		'onSelect': onSelectHandlerEnd
	});
}

function get_pagination(limit, pos_count, alignment, current, handler)
{
	var	pagination = $("<div />"),
		list = $("<ul />"),
		pages_count = Math.ceil(pos_count / limit),
		list_limit = 11,
		next = 0,
		previous = 0,
		start = 1,
		end,
		range;

	current = parseInt(current);

	range = list_limit;
	pagination.addClass("pagination").addClass("pagination-" + alignment);
	previous = (current === 1 ? 1 : current - 1);
	list.append("<li data-pagenum=\"" + previous + "\"><a href=\"#\">Назад</a></li><li>");

	if (current === 1)
		list.find("li:first-child").addClass("disabled");

	if (current > range - 2)
	{
		range -= 2;
		list.append("<li data-pagenum=\"1\"><a href=\"#\">1</a></li>");
		list.append("<li class=\"disabled\"><a href=\"#\">...</a></li>");
	}

	if (pages_count > list_limit && !(pages_count - range < current))
		range -= 2;

	if (pages_count - range < current && current > range)
		start = pages_count - range + 1;
	else if (current !== pages_count && current > range)
		start = current - Math.floor(range / 2);
	else
		start = (current > range) ? current + 1 - range : 1;

	end = start + range;

	for (var i = start; i <= pages_count && i < end; i++)
	{
		list.append("<li data-pagenum=\"" + i + "\"><a href=\"#\">" + i + "</a></li>");

		if (i === current)
			list.find("li:last-child").addClass("active");
	}

	if (pages_count > list_limit && !(pages_count - range < current))
	{
		list.append("<li class=\"disabled\"><a href=\"#\">...</a></li>");
		list.append("<li data-pagenum=\"" + pages_count + "\"><a href=\"#\">" + pages_count + "</a></li>");
	}

	next = (current === pages_count ? pages_count : current + 1);
	list.append("<li data-pagenum=\"" + next + "\"><a href=\"#\">Вперед</a></li>");

	if (current === pages_count)
		list.find("li:last-child").addClass("disabled");

	pagination.append(list);

	pagination.find("a").click(handler);

	return pagination;
}

function api_pagination_handler(e)
{
	e.preventDefault();

	if ($(this).parent().hasClass("disabled") || $(this).parent().hasClass("active"))
		return;

	$("#api-reports-filter input[name='current_page']").val(parseInt($(this).parent().data("pagenum")));

	an.refreshApiReportsTable();
}

function get_human_languge_period_val(period, precision)
{
	var	weeks = Math.floor(period / 604800),
		days = Math.floor((period - weeks * 604800) / 86400),
		hours = Math.floor((period - weeks * 604800 - days * 86400) / 3600),
		minutes = Math.floor((period - weeks * 604800 - days * 86400 - hours * 3600) / 60),
		seconds = period - weeks * 604800 - days * 86400 - hours * 3600 - minutes * 60,
		period_arr = [];

	precision = (precision === undefined) ? 4 : precision;

	if (weeks > 0)
		period_arr.push(weeks + " " + get_word_form_by_number("неделя", weeks, "f2"));
	if (days > 0 && precision > 0)
		period_arr.push(days + " " + get_word_form_by_number("день", days, "excl"));
	if (hours > 0 && precision > 1)
		period_arr.push(hours + " " + get_word_form_by_number("час", hours, "m1"));
	if (minutes > 0 && precision > 2)
		period_arr.push(minutes + " " + get_word_form_by_number("минута", minutes, "f1"));
	if (seconds > 0 && precision > 3)
		period_arr.push(seconds + " " + get_word_form_by_number("секунда", seconds, "f1"));

	return period_arr.join(", ");
}

function get_word_form_by_number(word, number, type)
{
	var	forms = {
			'm1' : {
				'end' : ["", "а", "ов"]
			},
			'm2' : {
				'end' : ["ь", "я", "ей"]
			},
			'f1' : {
				'end' : ["а", "ы", ""]
			},
			'f2' : {
				'end' : ["я", "и", "ь"]
			},
			'n' : {
				'end' : ["о", "а", ""]
			},
			'excl' : {
				'день' : ["день", "дня", "дней"]
			}
		},
		index = 0,
		rest = number % 10,
		stem = word;

	if ((number > 4 && number < 21) || (number > 19 && rest === 0) || (number > 19 && rest > 4))
		index = 2;
	else if (rest > 1 && rest < 5)
		index = 1;

	if (type === "excl")
		return forms[type][word][index];

	if (type !== "m1")
		stem = word.substring(0, word.length - 1);

	return stem + forms[type]["end"][index];
}

function create_table(headers, rows, id, classAttr)
{
	var	table = $("<table />"),
		thead = $("<thead />"),
		tbody = $("<tbody />"),
		th_tr = $("<tr />"),
		headersLength = 0;

	if (id)
		table.attr("id", id);

	if (classAttr)
		table.addClass(classAttr);

	if (headers)
	{
		table.append(thead.append(th_tr));
		headersLength = headers.length;
	}

	for ( ; headersLength--; )
	{
		th_tr.prepend("<th />");
		th_tr.find("th:first-child").text(headers[headersLength]);
	}

	table.append(tbody);

	if (rows)
		tbody.append(rows);

	return table;
}

$(document).ready(analytics_init);

(function ($) {
	$.fn.uploadInput = function (options) {
		var opts = $.extend({}, $.fn.uploadInput.defaults, options);

		return this.each(function () {
			var obj = {
				el: {},
				parent_form: {},
				binded_submit: [],

				init: function (el) {
					var	events = {},
						i = 0;
					this.el = el;
					this.parent_form = $(this.el).closest("form");
					$(this.el).addClass("ajax-file-input").hide().attr("name", "file_upl");
					$(opts.replace_el).insertBefore(this.el).click(this.clickAdd);
					events = $._data(obj.parent_form[0], "events");
					i = events['submit'].length;
					for (; i--; )
					{
						this.binded_submit.push(events['submit'][i].handler);
						$(this.parent_form).unbind("submit", events['submit'][i].handler);
					}
					$(this.parent_form).submit(this.formSubmit);
				},

				clickAdd: function (e) {
					e.preventDefault();
					$(obj.el).click();
				},

				formSubmit: function (e) {
					e.preventDefault();
					var	files_exists = false,
						bs_length = obj.binded_submit.length;
					$(this).find(".ajax-file-input").each(function () {
						if ($(this).val().length > 0)
							files_exists = true;
					});

					if (files_exists === false && !opts.required)
					{
						for (; bs_length--; )
						{
							obj.binded_submit[bs_length]();
						}
						return;
					}
					if (files_exists === false)
						return obj.onError();

					var hidden_iframe = obj.createIframe().insertAfter(this);
					var hidden_form = obj.createForm().insertAfter(this);
					$(this).find(".ajax-file-input").appendTo(hidden_form);
					hidden_iframe.load(obj.filesSent);
					hidden_form.submit();
				},

				createIframe: function () {
					if ($("#filesHiddenFrame").length > 0)
						$("#filesHiddenFrame").remove();

					var hidden_iframe = $("<iframe />");
					hidden_iframe.hide();
					hidden_iframe.attr("id", "filesHiddenFrame");
					hidden_iframe.attr("name", "filesHiddenFrame");

					return hidden_iframe;
				},

				createForm: function () {
					if ($("#filesHiddenForm").length > 0)
						$("#filesHiddenForm").remove();

					var hidden_form = $("<form />");
					hidden_form.hide();
					hidden_form.attr({
						id: "filesHiddenForm",
						action: opts.action,
						enctype: "multipart/form-data",
						method: "post",
						target: "filesHiddenFrame"
					});
					var max_file_element = $("<input />");
					max_file_element.attr({
						type: "hidden",
						name: "MAX_FILE_SIZE",
						value: opts.max_file_size
					});
					hidden_form.append(max_file_element);

					return hidden_form;
				},

				createFilenameInput: function () {
					$(obj.parent_form).find(".hidden-filename").remove();
					var hidden_input = $("<input />");
					hidden_input.attr({
						type: "hidden",
						name: "filename"
					}).addClass("hidden-filename");

					return hidden_input;
				},

				filesSent: function() {
					var	response = $.parseJSON($(this).contents().text()),
						bs_length = obj.binded_submit.length;
					if (response.success !== true)
					{
						$(obj.el).appendTo(obj.parent_form);
						$("#filesHiddenForm").remove();
						$("#filesHiddenFrame").remove();
						$(obj.parent_form).find(".hidden-filename").remove();
						$(obj.parent_form).bind("submit", obj.formSubmit);
						obj.onError();
						return;
					}

					var filename_input = obj.createFilenameInput();
					filename_input.val(response.filename);
					filename_input.appendTo(obj.parent_form);
					for (; bs_length--; )
					{
						obj.binded_submit[bs_length]();
					}
				},

				onError: function() {
					if (opts.error_handler !== false)
						opts.error_handler();
					else
						alert("Ошибка при загрузке файла!");
				}
			};

			obj.init(this);
		});
	};

	$.fn.uploadInput.defaults = {
		'replace_el' : "<button>ADD FILE</button>",
		'action' : "#",
		'max_file_size' : 10485760,
		'error_handler' : false,
		'required' : false
	};
}(jQuery));