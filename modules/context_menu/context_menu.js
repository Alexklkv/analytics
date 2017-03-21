/**
 * ContextMenu
 * @version 1.0.2
 */
Loader.styles(["context_menu"]);

var ContextMenu = function(options)
{
	// Public
	ContextMenu.prototype.toggle		= context_menu_toggle;
	ContextMenu.prototype.keytoggle 	= context_menu_toggle_by_key;

	// Private
	ContextMenu.prototype.init		= context_menu_init;
	ContextMenu.prototype.show		= context_menu_show;
	ContextMenu.prototype.hide		= context_menu_hide;
	ContextMenu.prototype.mouseup		= context_menu_mouseup;
	ContextMenu.prototype.mousedown		= context_menu_mousedown;
	ContextMenu.prototype.contextmenu	= context_menu_contextmenu;
	ContextMenu.prototype.element		= context_menu_element;
	ContextMenu.prototype.button		= context_menu_button;

	this.init(options);
}

function context_menu_init(options)
{
	this.options	= options;
	this.menu	= null;
	this.owner	= null;

	if (typeof this.options['menu'] == "undefined")
		throw "Need menu option";

	if (typeof this.options['width'] == "undefined")
		this.options['width'] = 150;

	this.menu = $("#" + this.options['menu']);
	this.menu.width(this.options['width']);

	if (typeof this.options['bind_right'] != "undefined")
	{
		var bind_right = function (instance, elements)
		{
			if (window.opera && !("oncontextmenu" in document.createElement("foo")))
			{
				$(document).on("mouseup", elements, function(event)
				{
					instance.mouseup(event, this);
					return false;
				});
				$(document).on("mousedown", elements, function(event)
				{
					instance.mousedown(event);
					return true;
				});
			}
			else
			{
				$(document).on("contextmenu", elements, function(event)
				{
					instance.contextmenu(event, this);
					return false;
				});
			}
		};

		bind_right(this, this.options['bind_right']);
	}

	if (typeof this.options['bind_left'] != "undefined")
	{
		var bind_left = function(instance, elements)
		{
			$(document).on("click", elements, function(event)
			{
				instance.show(event, this);
				return false;
			});
		};

		bind_left(this, this.options['bind_left']);
	}

	if (typeof this.options['bind_touch'] != "undefined")
	{
		var bind_touch = function(instance, elements)
		{
			$(document).on("doubletap", elements, function(event) {
				instance.contextmenu(event, this);
				return false;
			});
		}

		bind_touch(this, this.options['bind_touch']);
	}

	var init = function (instance)
	{
		$("body").bind("click tap", function(event)
		{
			if (instance.menu.has(event.target).length > 0)
				return;

			instance.hide();
		});
	};

	init(this);

	if (typeof this.options['handlers'] == "undefined")
		return;

	var handlers = this.options['handlers'];

	var bind = function (instance, element, handler, action)
	{
		element.bind("click", function()
		{
			if (element.prop('disabled'))
				return;

			instance.hide();
			handler.call(instance.owner, action);
			return false;
		});
	};

	for (var id in handlers)
	{
		var element = this.element(id);
		bind(this, element, handlers[id], id);
	}
}

function context_menu_toggle(id, status)
{
	var element = this.element(id);
	element.toggle(status);
}

function context_menu_toggle_by_key(id, enable)
{
	var element = this.element(id);

	if (enable)
		element.removeClass("context_menu_disabled").prop('disabled', false);
	else
		element.addClass("context_menu_disabled").prop('disabled', true);
}

function context_menu_hide()
{
	this.menu.hide();
}

function context_menu_element(id)
{
	return $("#" + this.options['menu'] + "_" + id);
}

function context_menu_show(event, owner)
{
	this.owner = owner;

	if (typeof this.options['show'] != "undefined")
		this.options['show'].call(this);

	this.menu.css("left", event.clientX + $(document).scrollLeft() + 5 + "px");
	this.menu.css("top", event.clientY + $(document).scrollTop() + 5 + "px");
	this.menu.show();
}

function context_menu_button(add)
{
	var button_name = this.options['menu'] + "_button";

	var button = $("#" + button_name);
	if (button.length)
		return button;

	if (!add)
		return false;

	$("body").append("<input type='button' id=" + button_name + " />");

	return $("#" + button_name);
}

function context_menu_mousedown(event)
{
	if (event.button != 2)
		return;

	var button = this.button(true);

	button.css("position", "absolute");
	button.css("opacity", 0.01);
	button.css("width", 5);
	button.css("height", 5);
	button.css("left", event.clientX + $(document).scrollLeft() - 2);
	button.css("top", event.clientY + $(document).scrollTop() - 2);
}

function context_menu_mouseup(event, owner)
{
	if (event.button != 2)
		return;

	var button = this.button();
	if (button != false)
		button.remove();

	this.show(event, owner);
}

function context_menu_contextmenu(event, owner)
{
	this.show(event, owner);
}