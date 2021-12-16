define(['jquery', 'bootstrap', 'util', 'constant', 'toggle', 'app'], function($, bootstrap) {
	function _bindCssEvent(events, callback) {
		var dom = this;
		function fireCallBack(e) {
			if (e.target !== this) {
				return
			}
			callback.call(this, e);
			for (var i = 0; i < events.length; i++) {
				dom.off(events[i], fireCallBack)
			}
		}
		if (callback) {
			for (var i = 0; i < events.length; i++) {
				dom.on(events[i], fireCallBack)
			}
		}
	}
	$.fn.animationEnd = function(callback) {
		_bindCssEvent.call(this, ['webkitAnimationEnd', 'animationend'], callback);
		return this
	};
	$.fn.transitionEnd = function(callback) {
		_bindCssEvent.call(this, ['webkitTransitionEnd', 'transitionend'], callback);
		return this
	};
	$.fn.transition = function(duration) {
		if (typeof duration !== 'string') {
			duration = duration + 'ms'
		}
		for (var i = 0; i < this.length; i++) {
			var elStyle = this[i].style;
			elStyle.webkitTransitionDuration = elStyle.MozTransitionDuration = elStyle.transitionDuration = duration
		}
		return this
	};
	$.fn.transform = function(transform) {
		for (var i = 0; i < this.length; i++) {
			var elStyle = this[i].style;
			elStyle.webkitTransform = elStyle.MozTransform = elStyle.transform = transform
		}
		return this
	};
	$.toQueryPair = function(key, value) {
		if (typeof value == 'undefined') {
			return key
		}
		return key + '=' + encodeURIComponent(value === null ? '' : String(value))
	};
	$.toQueryString = function(obj) {
		var ret = [];
		for (var key in obj) {
			key = encodeURIComponent(key);
			var values = obj[key];
			if (values && values.constructor == Array) {
				var queryValues = [];
				for (var i = 0, len = values.length, value; i < len; i++) {
					value = values[i];
					queryValues.push($.toQueryPair(key, value))
				}
				ret = concat(queryValues)
			} else {
				ret.push($.toQueryPair(key, values))
			}
		}
		return ret.join('&')
	};
	$.fn.append2 = function(html, callback) {
		var len = $("body").html().length;
		this.append(html);
		var e = 1,
		interval = setInterval(function() {
			e++;
			var clear = function() {
					clearInterval(interval);
					callback && callback()
				};
			if (len != $("body").html().length || e > 1000) {
				clear()
			}
		}, 1)
	};	
});