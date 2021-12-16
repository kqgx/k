define(['jquery'], function() {
	$(document).on("click", '[data-toggle="ajaxModal"]', function(e) {
		e.preventDefault();
		var obj = $(this), confirm = obj.data("confirm");
		var handler = function() {
				$("#ajaxModal").remove(), e.preventDefault();
				var url = obj.data("href") || obj.attr("href"),
					data = obj.data("set"),
					modal;
				$.ajax(url, {
					type: "get",
					dataType: "html",
					cache: false,
					data: data
				}).done(function(html) {
					if (html.substr(0, 8) == '{"status') {
						json = eval("(" + html + ')');
						if (json.status == 0) {
							msg = typeof(json.result) == 'object' ? json.result.message : json.result;
							tip.msgbox.err(msg || tip.lang.err);
							return
						}
					}
					modal = $('<div class="modal fade" id="ajaxModal"><div class="modal-body "></div></div>');
					$(document.body).append(modal), modal.modal('show');
					require(['jquery.gcjs'], function() {
						modal.append2(html, function() {
							var form_validate = $('form.form-validate', modal);
							if (form_validate.length > 0) {
								$("button[type='submit']", modal).length && $("button[type='submit']", modal).attr("disabled", true);
								require(['web/form'], function(form) {
									form.init();
									$("button[type='submit']", modal).length && $("button[type='submit']", modal).removeAttr("disabled")
								})
							}
						})
					})
				})
			},
			a;
		if (confirm) {
			tip.confirm(confirm, handler)
		} else {
			handler()
		}
	}), $(document).on("click", '[data-toggle="ajaxPost"]', function(e) {
		e.preventDefault();
		var obj = $(this),
			confirm = obj.data("confirm"),
			url = obj.data('href') || obj.attr('href'),
			data = obj.data('set') || {},
			html = obj.html();
		handler = function() {
			e.preventDefault();
			if (obj.attr('submitting') == '1') {
				return
			}
			obj.html('<i class="fa fa-spinner fa-spin"></i>').attr('submitting', 1);
			$.post(url, {
				data: data
			}, function(ret) {
				ret = eval("(" + ret + ")");
				if (ret.status == 1) {
					tip.msgbox.suc(ret.result.message || tip.lang.success, ret.result.url)
				} else {
					tip.msgbox.err(ret.result.message || tip.lang.error, ret.result.url), obj.removeAttr('submitting').html(html)
				}
			}).fail(function() {
				obj.removeAttr('submitting').html(html), tip.msgbox.err(tip.lang.exception)
			})
		};
		confirm && tip.confirm(confirm, handler);
		!confirm && handler()
	}),  $(document).on("click", '[data-toggle="ajaxEdit"]',
        function (e) {
            var _this = $(this)
            $(this).addClass('hidden')
            var obj = $(this).parent().find('a'),
                    url = obj.data('href') || obj.attr('href'),
                    data = obj.data('set') || {},
                    html = $.trim(obj.text()),
                    required = obj.data('required') || true,
                    edit = obj.data('edit') || 'input';
            var oldval = $.trim($(this).text());
            e.preventDefault();

            submit = function () {
                e.preventDefault();
                var val = $.trim(input.val());
                if (required) {
                    if (val == '') {
                        tip.msgbox.err(tip.lang.empty);
                        return;
                    }
                }
                if (val == html) {
                    input.remove(), obj.html(val).show();
                    //obj.closest('tr').find('.icow').css({visibility:'visible'})
                    return;
                }
                if (url) {
                    $.post(url, {
                        value: val
                    }, function (ret) {
                        ret = eval("(" + ret + ")");
                        if (ret.status == 1) {
                            obj.html(val).show();

                        } else {
                            tip.msgbox.err(ret.result.message, ret.result.url);
                        }
                        input.remove();
                    }).fail(function () {
                        input.remove(), tip.msgbox.err(tip.lang.exception);
                    });
                } else {
                    input.remove();
                    obj.html(val).show();
                }
                obj.trigger('valueChange', [val, oldval]);
            },
                    obj.hide().html('<i class="fa fa-spinner fa-spin"></i>');
            var input = $('<input type="text" class="form-control input-sm" style="width: 80%;display: inline;" />');
            if (edit == 'textarea') {
                input = $('<textarea type="text" class="form-control" style="resize:none;" rows=3 width="100%" ></textarea>');
            }
            obj.after(input);

            input.val(html).select().blur(function () {
                submit(input);
                _this.removeClass('hidden')

            }).keypress(function (e) {
                if (e.which == 13) {
                    submit(input);
                    _this.removeClass('hidden')
                }
            });

        }), $(document).on("click", '[data-toggle="ajaxSwitch"]', function(e) {
		e.preventDefault();
		var obj = $(this),
			confirm = obj.data('msg') || obj.data('confirm'),
			othercss = obj.data('switch-css'),
			other = obj.data('switch-other'),
			refresh = obj.data('switch-refresh') || false;
		if (obj.attr('submitting') == '1') {
			return
		}
		var value = obj.data('switch-value'),
			value0 = obj.data('switch-value0'),
			value1 = obj.data('switch-value1');
		if (value === undefined || value0 === undefined || value1 === undefined) {
			return
		}
		var url, css, text, newvalue, newurl, newcss, newtext;
		value0 = value0.split('|');
		value1 = value1.split('|');
		if (value == value0[0]) {
			url = value0[3], css = value0[2], text = value0[1], newvalue = value1[0], newtext = value1[1], newcss = value1[2]
		} else {
			url = value1[3], css = value1[2], text = value1[1], newvalue = value0[0], newtext = value0[1], newcss = value0[2]
		}
		var html = obj.html();
		var submit = function() {
				$.post(url).done(function(data) {
					data = eval("(" + data + ")");
					if (data.status == 1) {
						if (other && othercss) {
							if (newvalue == '1') {
								$(othercss).each(function() {
									if ($(this).data('switch-value') == newvalue) {
										this.className = css;
										$(this).data('switch-value', value).html(text || html)
									}
								})
							}
						}
						obj.data('switch-value', newvalue);
						obj.html(newtext || html);
						obj[0].className = newcss;
						refresh && location.reload()
					} else {
						obj.html(html), tip.msgbox.err(data.result.message || tip.lang.error, data.result.url)
					}
					obj.removeAttr('submitting')
				}).fail(function() {
					obj.removeAttr('submitting');
					obj.button('reset');
					tip.msgbox.err(tip.lang.exception)
				})
			},
			a;
		if (confirm) {
			tip.confirm(confirm, function() {
				obj.html('<i class="fa fa-spinner fa-spin"></i>').attr('submitting', 1), submit()
			})
		} else {
			obj.html('<i class="fa fa-spinner fa-spin"></i>').attr('submitting', 1), submit()
		}
	}), $(document).on('click', '[data-toggle="selectUrl"]', function() {
		$("#selectUrl").remove();
		var _input = $(this).data('input');
		var _full = $(this).data('full');
		var _platform = $(this).data('platform');
		var _callback = $(this).data('callback') || false;
		var _cbfunction = !_callback ? false : eval("(" + _callback + ")");
		if (!_input && !_callback) {
			return
		}
		var merch = $(".diy-phone").data("merch");
		var url = biz.url('util/selecturl', null, merch);
		var store = $(".diy-phone").data("store");
		if (store) {
			url = biz.url('store/diypage/selecturl')
		}
		if (_full) {
			url = url + "&full=1"
		}
		if (_platform) {
			url = url + "&platform=" + _platform
		}
		$.ajax(url, {
			type: "get",
			dataType: "html",
			cache: false
		}).done(function(html) {
			modal = $('<div class="modal fade" id="selectUrl"></div>');
			$(document.body).append(modal), modal.modal('show');
			modal.append2(html, function() {
				$(document).off("click", '#selectUrl nav').on("click", '#selectUrl nav', function() {
					var _href = $.trim($(this).data("href"));
					if (_input) {
						$(_input).val(_href).trigger('change')
					} else if (_cbfunction) {
						_cbfunction(_href)
					}
					modal.find(".close").click()
				})
			})
		})
	}), $(document).on('click', '[data-toggle="selectImg"]', function() {
		var _input = $(this).data('input');
		var _img = $(this).data('img');
		var _full = $(this).data('full');
		require(['jquery', 'util'], function($, util) {
			util.image('', function(data) {
				var imgurl = data.attachment;
				if (_full) {
					imgurl = data.url
				}
				if (_input) {
					$(_input).val(imgurl).trigger('change')
				}
				if (_img) {
					$(_img).attr('src', data.url)
				}
			})
		})
	}), $(document).on('click', '[data-toggle="selectIcon"]', function() {
		var _input = $(this).data('input');
		var _element = $(this).data('element');
		if (!_input && !_element) {
			return
		}
		var merch = $(".diy-phone").data("merch");
		var url = biz.url('util/selecticon', null, merch);
		$.ajax(url, {
			type: "get",
			dataType: "html",
			cache: false
		}).done(function(html) {
			modal = $('<div class="modal fade" id="selectIcon"></div>');
			$(document.body).append(modal), modal.modal('show');
			modal.append2(html, function() {
				$(document).off("click", '#selectIcon nav').on("click", '#selectIcon nav', function() {
					var _class = $.trim($(this).data("class"));
					if (_input) {
						$(_input).val(_class).trigger('change')
					}
					if (_element) {
						$(_element).removeAttr("class").addClass("icon " + _class)
					}
					modal.find(".close").click()
				})
			})
		})
	}), $(document).on('click', '[data-toggle="selectAudio"]', function() {
		var _input = $(this).data('input');
		var _full = $(this).data('full');
		require(['jquery', 'util'], function($, util) {
			util.audio('', function(data) {
				var audiourl = data.attachment;
				if (_full) {
					audiourl = data.url
				}
				if (_input) {
					$(_input).val(audiourl).trigger('change')
				}
			})
		})
	}), $(document).on('click', '[data-toggle="selectVideo"]', function() {
		var _input = $(this).data('input');
		var _full = $(this).data('full');
		require(['jquery', 'util'], function($, util) {
			util.audio('', function(data) {
				var audiourl = data.attachment;
				if (_full) {
					audiourl = data.url
				}
				if (_input) {
					$(_input).val(audiourl).trigger('change')
				}
			}, {
				type: 'video'
			})
		})
	}), $(document).on('click', '[data-toggle="previewVideo"]', function() {
		var videoelm = $(this).data('input');
		if (!videoelm) {
			return
		}
		var video = $(videoelm).data('url');
		if (!video || video == '') {
			tip.msgbox.err('未选择视频');
			return
		}
		if ($('#previewVideo').length < 1) {
			$('body').append('<div class="modal fade" id="previewVideo"><div class="modal-dialog" style="min-width: 400px !important;"><div class="modal-content"><div class="modal-header"><button data-dismiss="modal" class="close" type="button">×</button><h4 class="modal-title">视频预览</h4></div><div class="modal-body" style="padding: 0; background: #000;"><video src="' + video + '" style="height: 450px; width: 100%; display: block;" controls="controls"></video></div></div></div></div>')
		} else {
			$("#previewVideo video").attr("src", video)
		}
		$("#previewVideo").modal();
		$("#previewVideo").on("hidden.bs.modal", function() {
			$(this).find("video")[0].pause()
		})
	}), $(document).on('click', '[data-toggle=colorpicker]', function() {
    	var elm = this;
    	util.colorpicker(elm, function(color){
    		$(elm).parent().prev().prev().val(color.toHexString());
    		$(elm).parent().prev().css("background-color", color.toHexString());
    	});    
    }), $(document).on('click', '[data-toggle=colorclean]', function() {
    	$(this).parent().prev().prev().val("");
    	$(this).parent().prev().css("background-color", "#FFF");
    })	
})