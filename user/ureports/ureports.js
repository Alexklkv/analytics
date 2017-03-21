/**
 * Analytics
 * Аналитика для компьютерных игр
 *
 * @link https://bigstat.net
 * @copyright © 2013-2015 ROCKSTONE (ООО "ИТ Решения")
 */

Loader.scripts(["datepicker"]);

$(document).ready(function () {
	var urerports_obj = new Ureports();

	function Ureports()
	{
		if (!(this instanceof Ureports))
			return new Ureports();

		var formula = new Formula();

		init_metric_dialog();

		this.open_metric_dialog = function (e) {
			e.stopPropagation();
			e.preventDefault();

			if ($("#addRawMetric").attr("disabled") === "disabled")
				return;

			get_metric_dialog().dialog("open");

			$("#addRawMetric").attr("disabled", "disabled");
			$("#addConst").attr("disabled", "disabled");
		};

		this.change_metric_dialog = function (level) {
			update_metric_dialog(level);
		};

		this.close_metric_form = function (e) {
			e.stopPropagation();
			e.preventDefault();
			do_close_metric_form();
		};

		this.clear_metric_form = function (e) {
			e.stopPropagation();
			e.preventDefault();
			do_clear_metric_form();
		};

		this.add_user_metric = function (e) {
			e.stopPropagation();
			e.preventDefault();
			do_add_user_metric();
		};

		this.show_const_input = function (e) {
			e.stopPropagation();
			e.preventDefault();

			if ($("#addConst").attr("disabled") === "disabled")
				return;

			var const_inp = $("<li id=\"constInpWrap\"><div class=\"input-append\"><input class=\"span4\" id=\"constInp\" type=\"text\"><button class=\"btn\" type=\"button\" id=\"constInpBtn\">Добавить в формулу</button></div></li>");

			$("#addRawMetric").attr("disabled", "disabled");
			$("#addConst").attr("disabled", "disabled");
			$("#addFormulaList").append(const_inp);

		};

		this.add_const = function (e) {
			e.stopPropagation();
			e.preventDefault();

			if ($("#constInpBtn").attr("disabled") === "disabled")
				return;

			var value = parseFloat($("#constInp").val());

			if (value === 0)
				return;

			$("#constInpBtn").attr("disabled", "disabled");
			$("#constInp").attr("disabled", "disabled");
			formula.set_const(value);

			$("#addFormulaList").append(create_op_select_el());
		};

		this.add_operator = function (e) {
			e.stopPropagation();
			e.preventDefault();

			var value = $("#opSel").val();

			formula.set_operator(value);

			$("#constInpWrap, #opSelWrap, #funcSelWrap").remove();
			$("#addRawMetric, #addConst").removeAttr("disabled", "disabled");

		};

		this.add_function = function (e) {
			e.stopPropagation();
			e.preventDefault();

			if ($("#funcSelBtn").attr("disabled") === "disabled")
				return;

			var value = $("#funcSel").val();

			$("#funcSelBtn").attr("disabled", "disabled");
			formula.set_function(value);

			$("#addFormulaList").append(create_op_select_el());
		};

		this.remove_report = function (e) {
			e.stopPropagation();
			e.preventDefault();

			if (!confirm("Точно удалить отчёт?"))
				return;

			var	row = $(this).parent().parent()
				id = $(this).data("id");

			$.ajax({
				'url': "/?module=ureports&action=remove_report",
				'data': {
					'id': id
				},
				'dataType': "json",
				'type': "POST",
				'success': function (data) {
					if (data === null || data['success'] === undefined)
						return;

					row.remove();

					if ($("#ureportsTable tbody tr").length == 0)
						window.location.href = window.location.href;
				},
				'error': function () {
					//TODO - как-то сообщить пользователю
				}
			});
		};

		this.search_metric_form = function () {
			var el = create_metric_select_el();

			$("#selectMetric, #createMetric").attr("disabled", "disabled");
			$("#umetrics_list").append(el);
			$("#metricSel").typeahead({
				'source': get_umetrics_matched
			});
		};

		this.cancel_metric_search = function () {
			$("#metricSelWrap").remove();
			$("#selectMetric, #createMetric").removeAttr("disabled");
		};

		this.add_umetric_from_search = function () {
			$("#metricSelWrap").remove();
			$("#selectMetric, #createMetric").removeAttr("disabled");
		};

		function get_metric_dialog()
		{
			return $("#select_metric");
		}

		function init_metric_dialog()
		{
			var menu = get_metric_dialog();
			if (menu.length === 0)
				return;

			update_metric_dialog(0);
			menu.dialog({
				'autoOpen': false,
				'modal': true,
				'title': "Добавить метрику",
				'buttons': {
					'Добавить': add_metric_to_formula,
					'Отмена': menu.dialog.bind(menu, "close")
				},
				'close': function () {
					update_metric_dialog(0);
					if ($("#addFormulaList").children().length == 1)
						$("#addRawMetric, #addConst").removeAttr("disabled", "disabled");
				}
			});
		}

		function add_metric_to_formula()
		{
			$("#addFormulaList").append(create_func_select_el());
			formula.set_raw_metric();
			get_metric_dialog().dialog("close");
		}

		function update_metric_dialog(level)
		{
			switch (level)
			{
				case 0:
					update_metric_dialog_service();
					break;
				case 1:
					update_metric_dialog_report();
					break;
				case 2:
					update_metric_dialog_graph();
					break;
				case 3:
					update_metric_dialog_metric();
					break;
			}
		}

		function update_metric_dialog_service()
		{
			$("#service_selector").empty();
			for (var service in services_list)
				$("#service_selector").append("<option value='" + service + "'>" + services_list[service] + "</option>");

			$("#service_selector").children().first().attr("selected", "selected");
			update_metric_dialog_report();
		}

		function update_metric_dialog_report()
		{
			var	active_service = $("#service_selector").val(),
				point = reports_list[active_service];

			$("#report_selector").empty();

			for (var report in point)
				$("#report_selector").append("<option point='" + report + "' value='" + point[report]['path'] + "'>" + point[report]['title'] + "</option>");

			$("#report_selector").children().first().attr("selected", "selected");
			update_metric_dialog_graph();
		}

		function update_metric_dialog_graph()
		{
			var	active_service = $("#service_selector").val(),
				active_report = $("#report_selector :selected").attr("point"),
				point = reports_list[active_service][active_report]['graphs'];

			$("#graph_selector").empty();
			for (var graph in point)
				$("#graph_selector").append("<option value='" + graph + "'>" + point[graph]['title'] + "</option>");

			$("#graph_selector").children().first().attr("selected", "selected");
			update_metric_dialog_metric();
		}

		function update_metric_dialog_metric()
		{
			var	active_service = $("#service_selector").val(),
				active_report = $("#report_selector :selected").attr("point"),
				active_graph = $("#graph_selector").val(),
				point = reports_list[active_service][active_report]['graphs'][active_graph]['legend'];

			$("#type_selector").empty();
			for (var type in point)
				$("#type_selector").append("<option value='" + type + "'>" + point[type] + "</option>");

			$("#type_selector").children().first().attr("selected", "selected");
		}

		function do_clear_metric_form()
		{
			$(":input", "#createMetricForm")
				.not(":button, :submit, :reset")
				.val("")
				.removeAttr("checked")
				.removeAttr("selected");

			$("#createMetricForm select").children().first().attr("selected", "selected");
			$("#formulaStrInp").html("");
			$("#constInpWrap, #opSelWrap, #funcSelWrap").remove();
			$("#addRawMetric, #addConst").removeAttr("disabled", "disabled");
			formula.clear();
		}

		function do_close_metric_form()
		{
			do_clear_metric_form();
			$("#createMetricForm").hide();
		}

		function do_add_user_metric()
		{
			var	name = $("#umetric_name").val(),
				formula_inp = $("#formulaInp").val(),
				description = $("#umetric_description").val();

			if (name == "" || formula_inp == "" || description == "")
				return false;
			if (formula.get_last_token_type() === "operator")
				return false;

			$.ajax({
				'url': "/?module=ureports&action=add_user_metric",
				'data': {
					'name': name,
					'formula': formula_inp,
					'description': description
				},
				'dataType': "json",
				'type': "POST",
				'success': function (data) {
					if (data === null || data['umetric_id'] === undefined)
						return;

					var umetric_inp = $("<input type='hidden' name='umetrics[]' />");

					umetric_inp.val(data['umetric_id']);

					$("#umetrics_list").prepend("<li>" + name + "</li>");
					$("#umetrics_list").children().first().append(umetric_inp);

					do_close_metric_form();
				},
				'error': function () {
					//TODO - как-то сообщить пользователю
				}
			});
		}

		function get_umetrics_matched(query, process)
		{
			return $.post('/?module=ureports&action=get_umetrics_matched', { 'query': query }, function (data) {
				if (data === null || data['options'] === undefined)
					return;

				return process(data['options']);
			}, "json");
		}

		function create_op_select_el()
		{
			return $("<li id=\"opSelWrap\"><select class=\"span2\" id=\"opSel\"><option value=\"+\">+</option><option value=\"-\">-</option><option value=\"*\">*</option><option value=\"/\">/</option></select><button class=\"btn\" id=\"opSelBtn\">Добавить в формулу</button></li>");
		}

		function create_func_select_el()
		{
			return $("<li id=\"funcSelWrap\"><select class=\"span2\" id=\"funcSel\"><option value=\"sum\" selected=\"selected\">SUM</option><option value=\"avg\">AVG</option></select><button class=\"btn\" id=\"funcSelBtn\">Добавить в формулу</button></li>");
		}

		function create_metric_select_el()
		{
			return $("<li id=\"metricSelWrap\"><div class=\"input-append\"><input id=\"metricSel\" type=\"text\" class=\"span8\" placeholder=\"Ключевое слово для поиска\" autocomplete=\"off\" /><button class=\"btn\" id=\"metricSelBtn\">Добавить</button><button class=\"btn\" id=\"metricCancelBtn\">Отменить</button></div></li>");
		}
	}

	function Formula ()
	{
		if (!(this instanceof Formula))
			return new Formula();

		var tokens = [];

		this.set_raw_metric = function () {
			var	metric_name = [
					$("#service_selector :selected").html(),
					$("#report_selector :selected").html(),
					$("#graph_selector :selected").html(),
					$("#type_selector :selected").html()
				].join(", "),
				data = $("#select_metric select");

			$.ajax({
				'url': "/?module=ureports&action=get_raw_metric_id",
				'data': data,
				'dataType': "json",
				'type': "POST",
				'success': function (data) {
					var token = {};

					if (data === null || data['metric_id'] === undefined)
						return;

					token.type = "metric";
					token.func = "sum";
					token.func_str = "SUM";
					token.metric_str = metric_name;
					token.metric = data['metric_id'];
					tokens.push(token);
					update_inputs();
				},
				'error': function () {
					//TODO - как-то сообщить пользователю
				}
			});
		};

		this.set_const = function (val) {
			var token = {};

			token.type = "const";
			token.val = val;
			tokens.push(token);
			update_inputs();
		};

		this.set_operator = function (operator) {
			var token = {};

			token.type = "operator";
			token.operator = operator;
			tokens.push(token);
			update_inputs();
		};

		this.set_function = function (func) {
			var i = tokens.length - 1;

			tokens[i].func = func;
			tokens[i].func_str = func.toUpperCase();
			update_inputs();
		};

		this.get_last_token_type = function () {
			var i = tokens.length - 1;

			return tokens[i].type;
		};

		this.clear = function () {
			tokens = [];
		};

		function update_inputs()
		{
			var	i = tokens.length,
				formula_str = "",
				formula = "";

			for (; i--; )
			{
				switch (tokens[i].type)
				{
					case "metric":
						formula_str = tokens[i].func_str + "(" + tokens[i].metric_str + ")" + formula_str;
						formula = tokens[i].func + "(" + tokens[i].metric + ")" + formula;
						break;
					case "const":
						formula_str = tokens[i].val + formula_str;
						formula = tokens[i].val + formula;
						break;
					case "operator":
						formula_str = " " + tokens[i].operator + " " + formula_str;
						formula = tokens[i].operator + formula;
						break;
				}
			}
			$("#formulaStrInp").html(formula_str);
			$("#formulaInp").val(formula);
		}
	}

	$("#init_date").datepicker();
	$("#ureportsTable").on("click", ".download", function (e) {
		e.stopPropagation();
		e.preventDefault();
		var	id = $(this).data("id"),
			form = $("<form style='display: none;' method='post' action='/?module=ureports&action=download'></form>");

		form.append("<input type='hidden' name='id' value='" + id + "' />");
		$("body").append(form);
		form.submit().remove();
	});
	$("#ureportsTable").on("click", ".remove", urerports_obj.remove_report);
	$("#createUserReport").submit(function (e) {
		e.stopPropagation();
		e.preventDefault();

		var data = $(this).find("input, textarea, select");

		$.ajax({
			'url' : "/?module=ureports&action=save",
			'method' : "post",
			'dataType' : "json",
			'data' : data,
			'success' : function (data) {
				if (data === null || data['success'] === undefined)
					return;

				window.location.href = window.location.href;
			}
		});
	});
	$("#saveReportBtn").click(function (e) {
		e.stopPropagation();
		e.preventDefault();

		$("#createUserReport").submit();
	});
	$("#selectMetric").click(function (e) {
		e.stopPropagation();
		e.preventDefault();

		if ($(this).attr("disabled") == "disabled")
			return;

		urerports_obj.close_metric_form(e);
		urerports_obj.search_metric_form();
	});
	$("#createMetric").click(function (e) {
		e.stopPropagation();
		e.preventDefault();

		if ($(this).attr("disabled") === "disabled")
			return;

		$("#createMetricForm").show();
	});
	$("#addRawMetric").click(urerports_obj.open_metric_dialog);
	$("#service_selector").change(urerports_obj.change_metric_dialog.bind(null, 1));
	$("#report_selector").change(urerports_obj.change_metric_dialog.bind(null, 2));
	$("#graph_selector").change(urerports_obj.change_metric_dialog.bind(null, 3));
	$("#clearMetricForm").click(urerports_obj.clear_metric_form);
	$("#closeMetricForm").click(urerports_obj.close_metric_form);
	$("#saveMetricBtn").click(urerports_obj.add_user_metric);
	$("#addConst").click(urerports_obj.show_const_input);
	$("#addFormulaList").on("click", "#constInpBtn", urerports_obj.add_const);
	$("#addFormulaList").on("click", "#opSelBtn", urerports_obj.add_operator);
	$("#addFormulaList").on("click", "#funcSelBtn", urerports_obj.add_function);
	$("#umetrics_list").on("click", "#metricCancelBtn", urerports_obj.cancel_metric_search);
	$("#umetrics_list").on("click", "#metricSelBtn", urerports_obj.add_umetric_from_search);
});