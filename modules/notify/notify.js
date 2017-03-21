/**
 * Notify
 *
 * @uses jquery-noty
 * @version 1.0.0
 */
var Notify = function()
{
	Notify.prototype.warning = notify_warning;
	Notify.prototype.error = notify_error;
	Notify.prototype.simple = notify_simple;

	Notify.prototype.init = notify_init;

	this.init();
}

function notify_init()
{
	$.noty.defaults = {
		'layout': "bottomRight",
		'theme': "bottomTheme",
		'type': "warning",
		'text': "",
		'dismissQueue': true,
		'template': "<div class='noty_message'><span class='noty_text'></span><div class='noty_close'></div></div>",
		'animation': {
			'open': {'height': "toggle"},
			'close': {'height': "toggle"},
			'easing': "swing",
			'speed': 300
		},
		'timeout': 2500,
		'force': false,
		'modal': false,
		'closeWith': ['click'],
		'callback': {
			onShow: function() {},
			afterShow: function() {},
			onClose: function() {},
			afterClose: function() {}
		},
		'buttons': false
	};
}

function notify_warning(message, log)
{
	console.log(log);
	return noty({
		'text': message,
		'type': "warning"
	});
}

function notify_error(message, log)
{
	console.log(log);
	return noty({
		'text': message,
		'type': "error"
	});
}

function notify_simple(message)
{
	return noty({
		'text': message,
		'type': "information"
	});
}
