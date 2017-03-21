AmCharts.ready(function () {
	AmCharts.shortMonthNames = [
		"Янв", "Фев", "Март", "Апр", "Май", "Июнь",
		"Июль", "Авг", "Сент", "Окт", "Ноя", "Дек"
	];

	getByList();

	$(window).resize(function () {
		var	width = 0,
			height = 0;
		width = window.innerWidth;
		height = window.innerHeight - $("#lineName").height();
		$("#chartdiv").width(width);
		$("#chartdiv").height(height);
	});
});

function getByList() {
	var	strArr = window.location.href.split("="),
		service = strArr[strArr.length - 1];

	$.ajax({
		url : "/?module=bigscreen&action=big_screen_list&service=" + service,
		type : "POST",
		dataType : "json",
		success : function (data) {
			var	index = 0,
				length = data.length;
			update(data, index, length);
		}
	});
}

function update(send, index, length) {
	var	strArr = window.location.href.split("="),
		service_name = strArr[strArr.length - 1],
		data = {
			'service_name' : service_name,
			'service' : send[index].service,
			'report' : send[index].report,
			'chart' : send[index].chart,
			'type' : send[index].type
		};

	$.ajax({
		url : "/?module=bigscreen&action=big_screen_data",
		type : "POST",
		dataType : "json",
		data : data,
		success : function (data) {
			if (!data || data.length === 0)
				return window.setTimeout(update, 300000, send, index, length);

			var	chartData = [],
				width = 0,
				height = 0,
				last = data.length - 1;
			$("#chartdiv").empty();

			for (var key in data) {
				if (send[index].showLast !== undefined && send[index].showLast === "0" && last === parseInt(key))
					break;

				chartData.push({
					date : data[key].date,
					value : data[key].value
				});
			}

			var chart = new AmCharts.AmSerialChart();
			var axis = new AmCharts.ValueAxis();
			axis.title = send[index].axisTitle;
			chart.addValueAxis(axis);
			chart.dataProvider = chartData;
			chart.categoryField = "date";
			chart.fontSize = 30;
			chart.addClassNames = true;
			chart.categoryAxis.autoGridCount = false;
			chart.categoryAxis.gridCount = 10;
			chart.categoryAxis.parseDates = true;
			var graph = new AmCharts.AmGraph();

			if (send[index].show_sum !== undefined) {
				var legend = new AmCharts.AmLegend();
				legend.align = "center";
				legend.valueWidth = 200;
				legend.fontSize = 40;
				graph.legendPeriodValueText = "[[value.sum]]";
				legend.labelText = "Суммарное значение: ";
				chart.addLegend(legend);
			}

			graph.valueField = "value";
			graph.type = "smoothedLine";
			graph.lineThickness = 20;
			graph.lineColor = "#326195";
			chart.addGraph(graph);
			chart.titleField = "legend";
			chart.color = "#326195";
			chart.addListener("drawn", function () {
				$("#chartdiv svg g:eq(0) path:eq(1)").attr("fill-opacity", "1");
				$("#chartdiv svg g:eq(0) path:eq(1)").attr("fill", "#ecf0f1");
			});
			chart.write("chartdiv");
			$("#lineName, #currentValue").remove();
			$("<div id=\"lineName\" />").insertBefore("#chartdiv");
			$("<div id=\"currentValue\" />").insertBefore("#chartdiv");
			$("#chartdiv a").hide();
			$("#lineName").text(send[index].lineName);
			$("#currentValue").text("Текущее значение: " + data[last].value);
			width = window.innerWidth;
			height = window.innerHeight - $("#lineName").height();
			$("#chartdiv").width(width);
			$("#chartdiv").height(height);
			if (index + 1 < length)
				index++;
			else
				index = 0;

			window.setTimeout(update, 300000, send, index, length);
		}
	});
}