(function($, ua) {
	var isAndroid = /android/i.exec(ua),
		hasTouch = 'ontouchstart' in window || isAndroid,
		startEvent = 'touchstart',
		stopEvent = 'touchend touchcancel',
		moveEvent ='touchmove',

		namespace = 'finger',
		rootEl = $('html')[0],

		start = {},
		move = {},
		clicks = 0,
		motion,
		safeguard,
		prevEl,
		prevTime,

		Finger = $.Finger = {
			'doubleTapInterval': 750,
			'motionThreshold': 5
		};

	function page(coord, event)
	{
		return event.originalEvent.touches[0]['page' + coord.toUpperCase()];
	}

	function client(coord, event)
	{
		return event.originalEvent.changedTouches.item(0)["client" + coord.toUpperCase()];
	}

	function trigger(event, evtName, remove)
	{
		var fingerEvent = $.Event(evtName, {'clientX': client("X", event), 'clientY': client("Y", event)});
		$.event.trigger(fingerEvent, { originalEvent: event }, event.target);

		if (fingerEvent.isDefaultPrevented())
			event.preventDefault();

		if (remove)
		{
			$.event.remove(rootEl, moveEvent + '.' + namespace, moveHandler);
			$.event.remove(rootEl, stopEvent + '.' + namespace, stopHandler);
		}
	}

	function startHandler(event)
	{
		var timeStamp = event.timeStamp || +new Date();

		if (safeguard == timeStamp)
			return;

		safeguard = timeStamp;
		start.x = move.x = page('x', event);
		start.y = move.y = page('y', event);
		start.target = event.target;
		motion = false;
		clicks += 1;

		$.event.add(rootEl, moveEvent + '.' + namespace, moveHandler);
		$.event.add(rootEl, stopEvent + '.' + namespace, stopHandler);

		if (Finger.preventDefault)
			event.preventDefault();
	}

	function moveHandler(event)
	{
		move.x = page('x', event);
		move.y = page('y', event);
		move.dx = move.x - start.x;
		move.dy = move.y - start.y;
		move.adx = Math.abs(move.dx);
		move.ady = Math.abs(move.dy);

		motion = move.adx > Finger.motionThreshold || move.ady > Finger.motionThreshold;
	}

	function stopHandler(event)
	{
		var timeStamp = event.timeStamp || +new Date(),
			evtName;

		clicks -= 1;

		if (event.target !== start.target)
			return;
		if (motion)
			return;

		var doubletap = prevEl === event.target && timeStamp - prevTime < Finger.doubleTapInterval && clicks === 0;

		evtName = doubletap ? "doubletap" : "tap";
		prevEl = doubletap ? null : start.target;
		prevTime = timeStamp;
		clicks = 0;
		
		trigger(event, evtName, true);
	}

	if (hasTouch)
		$.event.add(rootEl, startEvent + '.' + namespace, startHandler);

})(jQuery, navigator.userAgent);