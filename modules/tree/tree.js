/**
 * Tree
 * @uses Ajax
 * @version 1.0.6
 */
Loader.scripts(["ajax"]);

var Tree = function(options)
{
	// Public
	Tree.prototype.get			= tree_get;
	Tree.prototype.add			= tree_add;
	Tree.prototype.remove			= tree_remove;
	Tree.prototype.is			= tree_is;
	Tree.prototype.state			= tree_state;
	Tree.prototype.toggle			= tree_toggle;
	Tree.prototype.show			= tree_show;
	Tree.prototype.hide			= tree_hide;
	Tree.prototype.select			= tree_select;
	Tree.prototype.filter			= tree_filter;

	// Private
	Tree.prototype.init			= tree_init;
	Tree.prototype.lazy			= tree_lazy;
	Tree.prototype.action			= tree_action;
	Tree.prototype.click			= tree_click;
	Tree.prototype.insert			= tree_insert;
	Tree.prototype.load			= tree_load;
	Tree.prototype.sub			= tree_sub;

	this.init(options);
}

function tree_init(options)
{
	this.options		= options;
	this.actions		= {};
	this.filter_value	= "";
	this.root		= null;
	this.templates		= {
		'element': "<a href='#' name='{name}'>{caption}</a>",
		'sub': "<div class='sub'></div>"
	};

	if (!("container" in this.options))
		throw "Need container option";

	if ("actions" in this.options)
		this.actions = this.options['actions'];

	if ("templates" in this.options)
		this.templates = this.options['templates'];

	this.container = $(this.options['container']);

	if ("root" in this.options)
	{
		var root_data = {
			'name': "",
			'caption': this.options['root'],
			'state': {'locked': true}
		};

		this.root = this.insert(this.container, root_data, "append");
	}

	this.lazy();

	var bind = function(instance, elements)
	{
		$(document).on("click", elements, function()
		{
			instance.click(this);
			return false;
		});

		$(document).on("dblclick", elements, function()
		{
			instance.action("dblclick", this);
			return false;
		});
	};

	bind(this, this.options['container'] + " a");
}

function tree_filter(filter)
{
	if (filter === undefined)
		return this.filter_value;

	if (this.filter_value == filter)
		return true;

	this.filter_value = filter;
	this.lazy();

	return true;
}

function tree_get(name)
{
	var links = this.container.find("a[name='" + name + "']");

	for (var i = 0; i < links.length; i++)
	{
		var link = links.eq(i);

		if (link.attr("name") == name)
			return link;
	}

	return null;
}

function tree_add(page, delimeter)
{
	var link, cur_name = "", parent_sub = this.container;

	var pieces = page.split(delimeter);
	for (var piece = 0; piece < pieces.length; piece++)
	{
		if (cur_name != "")
			cur_name += delimeter;
		cur_name += pieces[piece];

		link = this.get(cur_name);
		if (link != null)
		{
			if (piece == pieces.length - 1)
			{
				if (!this.is(link, "editable"))
					this.state(link, "editable", true);
				continue;
			}

			parent_sub = this.sub(link);

			if (this.is(link, "final"))
			{
				this.state(link, "final", false);
				this.show(link);
				continue;
			}

			if (parent_sub.children().length != 0)
			{
				this.show(link);
				continue;
			}

			this.lazy(link, false);
			continue;
		}

		var found_sub = null;

		var links = parent_sub.children("a");
		for (var k = 0; k < links.length; k++)
		{
			link = links.eq(k);

			var name = link.text();
			if (name.localeCompare(pieces[piece]) <= 0)
				continue;

			found_sub = this.sub(link);
			break;
		}

		var data = {
			'name':		cur_name,
			'caption':	pieces[piece]
		};

		if (piece == pieces.length - 1)
			data['state'] = {'final': true, 'editable': true};

		if (found_sub != null)
			link = this.insert(found_sub, data, "before");
		else
			link = this.insert(parent_sub, data, "append");

		if (piece != pieces.length - 1)
			parent_sub = this.sub(link);
	}
}

function tree_remove(page, delimeter)
{
	if (!("state_url" in this.options))
		return;

	var pieces = page.split(delimeter);
	for (; pieces.length != 0; pieces.length--)
	{
		var cur_name = pieces.join("/");

		var link = this.get(cur_name);
		if (link == null)
			continue;

		var state = Ajax.post(this.options['state_url'], {'name': cur_name});

		if ($("final", state).length)
		{
			this.state(link, {'final': true, 'opened': false});
			break;
		}

		if ($("exist", state).length)
		{
			this.state(link, "editable", false);
			break;
		}

		var parent = link.parent();

		var sub = this.sub(link, false);
		if (sub != null)
			sub.remove();
		link.remove();

		while (parent.length != 0)
		{
			link = parent.prev("a");
			if (this.is(link, "locked"))
				break;

			if (parent.children().length)
				break;

			if (this.is(link, "editable"))
			{
				this.state(link, {'final': true, 'opened': false});
				break;
			}

			sub = parent;
			parent = parent.parent();

			sub.remove();
			link.remove();
		}

		break;
	}
}

function tree_is(element, name)
{
	return this.state(element, name);
}

function tree_state(element, name, state)
{
	element = $(element);

	var names = {};
	if (typeof name == "string")
	{
		if (state === undefined)
			return element.hasClass("tree_item_" + name);

		names[name] = state;
	}
	else
		names = name;

	for (var key in names)
	{
		if (names[key])
			element.addClass("tree_item_" + key);
		else
			element.removeClass("tree_item_" + key);
	}

	return this;
}

function tree_lazy(link, async)
{
	if (!("lazy_url" in this.options))
		return;

	if (link === undefined)
		link = this.root;

	var name = "";
	if (link != null)
		name = link.attr("name");

	var sub = this.sub(link);
	sub.empty();

	if (async === undefined)
		async = true;

	var lazy = function(instance, prefix, link)
	{
		$.ajax(
		{
			'url': instance.options['lazy_url'],
			'type': "POST",
			'data':
			{
				'prefix': prefix,
				'filter': instance.filter()
			},
			'async': async,
			'success': function(data)
			{
				var answer = $("answer", data);

				instance.load(link, answer);
			},
			'dataType': "xml"
		});
	};

	lazy(this, name, link);
}

function tree_load(link, xml)
{
	var elements = xml.children("element");
	if (!elements.length)
		return;

	var sub = this.sub(link);

	for (var i = 0; i < elements.length; i++)
	{
		var element = elements.eq(i);

		var data = {
			'name':		element.attr("name"),
			'caption':	element.attr("caption"),
			'state': {
				'final':	element.attr("final"),
				'editable':	element.attr("editable")
			}
		};

		var current = this.insert(sub, data, "append");

		this.load(current, element);
	}

	this.show(link);
}

function tree_insert(sub, data, type)
{
	var code = this.templates['element'];
	code = code.replace("{name}", data['name']);
	code = code.replace("{caption}", data['caption']);

	var element = $(code);

	if ("state" in data)
		this.state(element, data['state']);

	switch (type)
	{
		case "append":
			sub.append(element);
			break;
		case "before":
			sub.before(element);
			break;
	}

	return element;
}

function tree_action(action, element)
{
	if (!(action in this.actions))
		return;

	this.actions[action].call(this, element);
}

function tree_click(element)
{
	var link = $(element);

	if (this.is(link, "locked"))
		return true;

	if (this.is(link, "final"))
	{
		this.action("click", element);
		return true;
	}

	var sub = this.sub(link);

	if (sub.children().length)
	{
		this.toggle(link);
		return false;
	}

	this.lazy(link);
	return false;
}

function tree_toggle(link)
{
	if (this.is(link, "opened"))
		this.hide(link);
	else
		this.show(link);
}

function tree_show(link)
{
	var sub = this.sub(link, false);
	if (sub == null)
		return;

	sub.slideDown("fast");

	if (!this.is(link, "opened"))
		this.state(link, "opened", true);
}

function tree_hide(link)
{
	var sub = this.sub(link, false);
	if (sub == null)
		return;

	sub.slideUp("fast");

	if (this.is(link, "opened"))
		this.state(link, "opened", false);
}

function tree_select(element)
{
	var hrefs = this.container.find("a");
	this.state(hrefs, "selected", false);

	if (typeof element == "string")
		element = this.get(element);

	if (element === undefined || element === null)
		return;

	this.state(element, "selected", true);
}

function tree_sub(link, create)
{
	if (link == null)
		return this.container;

	var sub = link.next("div.sub");
	if (sub.length != 0)
		return sub;

	if (create === undefined)
		create = true;

	if (!create)
		return null;

	link.after(this.templates['sub']);

	return link.next("div.sub");
}