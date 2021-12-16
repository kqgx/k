define(['jquery', 'bootstrap'], function($, bs) {
    var System = {
        uri: {
            default: 'admin/home/main'
        }
    };
	window.redirect = function(url) {
		location.href = url
	}, $(document).on('click', '[data-toggle=refresh]', function(e) {
		e && e.preventDefault();
		var url = $(e.target).data("href");
		url ? window.location = url : window.location.reload()
	}), $(document).on('click', '[data-toggle=back]', function(e) {
		e && e.preventDefault();
		var url = $(e.target).data("href");
		url ? window.location = url : window.history.back()
	});
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
    $(window).resize(function() {
		var width = $(window).width();
		if (width <= 1440) {
			$(".wb-panel-fold").removeClass('in').html('<i class="icow icow-info"></i> 消息提醒');
			$(".wb-panel").removeClass('in');
			$('.wb-container').addClass('right-panel')
		} else {
			$(".wb-panel-fold").addClass('in').html('<i class="fa fa-angle-double-right"></i> 收起面板');
			$(".wb-panel").addClass('in');
			$('.wb-container').removeClass('right-panel')
		}
	});
	$(window).scroll(function() {
		if ($(window).scrollTop() > 200) {
			$('.fixed-header').addClass('active')
		} else {
			$('.fixed-header').removeClass('active')
		}
	});
	$('.wb-nav-fold').click(function() {
		var nav = $(this).closest(".wb-nav");
		if (nav.hasClass('fold')) {
			nav.removeClass('fold');
			$(".wb-header .logo").removeClass('small');
			$(".fast-nav").removeClass('indent');
			util.cookie.set('foldnav', 0)
		} else {
			nav.addClass('fold');
			$(".wb-header .logo").addClass('small');
			$(".fast-nav").addClass('indent');
			util.cookie.set('foldnav', 1)
		}
	});
	$('.wb-subnav-fold').click(function() {
		var subnav = $(this).closest(".wb-subnav");
		if (subnav.hasClass('fold')) {
			subnav.removeClass('fold')
		} else {
			subnav.addClass('fold')
		}
	});
	$('.menu-header').click(function() {
		if ($(this).hasClass('active')) {
			$(this).next('ul').eq(0).hide();
			$(this).find('.menu-icon').removeClass('fa-caret-down').addClass('fa-caret-right');
			$(this).removeClass('active')
		} else {
			$(this).next('ul').eq(0).show();
			$(this).find('.menu-icon').removeClass('fa-caret-right').addClass('fa-caret-down');
			$(this).addClass('active')
		}
	});

	$(".wb-panel-fold").click(function() {
		$(this).toggleClass('in');
		$(".wb-panel").toggleClass('in');
		if (!$(this).hasClass('in')) {
			$(this).html('<i class="icow icow-info"></i> 消息提醒');
			$('.wb-container').addClass('right-panel')
		} else {
			$(this).html('<i class="fa fa-angle-double-right"></i> 收起面板');
			$('.wb-container').removeClass('right-panel')
		}
	});
	
	$('.nav-go').click(function(e){
	    e.preventDefault();
	    $(this).addClass('active').siblings('.nav-go').removeClass('active');
	    $('.nav-item').hide();
	    var t = $('.nav-item-'+$(this).data('nav'));
	    t.show();
	    location.hash = t.find('.iframe-go a').attr('href');
	})
	$('.iframe-go').click(function(){
	    $('.iframe-go').removeClass('active');
	    $(this).addClass('active');
	})
	function url_admin(uri){
	    var url = '/admin.php?', uri_array  = uri.replace("#", "").split("/");
	    if(uri_array[0]=='admin'){
	        url += 'c='+uri_array[1]+'&m='+uri_array[2]+url_admin_query(uri_array)
	    } else {
	        url += 's='+uri_array[0]+'&c='+uri_array[2]+'&m='+uri_array[3]+url_admin_query(uri_array)
	    }
	    return url;
	}
	function url_admin_query(uri_array){
	    switch (uri_array.length) {
	        case 5:
	            return '&'+uri_array[3]+'='+uri_array[4];
	            break;
	       case 6:
	            return '&'+uri_array[4]+'='+uri_array[5];
	            break;	           
	        case 7:
	            return '&'+uri_array[3]+'='+uri_array[4]+'&'+uri_array[5]+'='+uri_array[6];
	        default:
	            return '';
	    }
	}
	function iframe_show(uri){
	    $('#page-loading').show();
	    $("#iframe").attr("src", url_admin(uri));
	}
	function iframe_init(){
        iframe_show(location.hash.length>0?location.hash:System.uri.default);
	}
	window.onhashchange = function(){
        iframe_init();
	}
    iframe_init();
});