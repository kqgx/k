define(['jquery'], function($, bootstrap) {
    if ($('.select2').length > 0) {
		require(['select2'], function() {
			$('.select2').each(function() {
				$(this).select2({})
			})
		})
	}
	if ($('.js-switch').length > 0) {
		require(['switchery'], function() {
			$('.js-switch').switchery()
		})
	}
	if ($('.js-clip').length > 0) {
		require(['clipboard'], function(Clipboard) {
			var clipboard = new Clipboard('.js-clip', {
				text: function(e) {
					return $(e).data('url') || $(e).data('href')
				}
			});
			clipboard.on('success', function(e) {
				tip.msgbox.suc('复制成功')
			})
		})
	}
	if ($('.datetimepicker').length > 0) {
    	require(["datetimepicker"], function(){
    	    $('.datetimepicker').each(function(){
    	        var option = {
    				lang : "zh",
    				step : 5,
    				timepicker : true,
    				closeOnDateSelect : true,
    				format : $(this).data('format') || "Y-m-d H:i"
    			};
    			$(this).datetimepicker(option);
    	    })
    	});
	}
	if ($('.daterange.daterange-time').length > 0) {
    	require(["daterangepicker"], function(){
			$(".daterange.daterange-time").each(function(){
				var elm = this;
                var container =$(elm).parent().prev();
				$(this).daterangepicker({
					format : $(this).data('format') || "YYYY-MM-DD HH:mm",
					timePicker: true,
					timePicker12Hour : false,
					timePickerIncrement: 1,
					minuteStep: 1
				}, function(start, end){
					$(elm).find(".date-title").html(start.toDateTimeStr() + " 至 " + end.toDateTimeStr());
					container.find(":input:first").val(start.toDateTimeStr());
					container.find(":input:last").val(end.toDateTimeStr());
				});
			});
    	});
        function clearTime(obj){
            $(obj).prev().html("<span class=date-title>" + $(obj).attr("placeholder") + "</span>");
            $(obj).parent().prev().find("input").val("");
        }
	}
	if ($('.multi-img-details').length > 0) {
        require(['jquery.ui'],function(){
            $('.multi-img-details').sortable({scroll:'false'});
            $('.multi-img-details').sortable('option', 'scroll', false);
        })
	}
	if ($('form.form-validate').length > 0 || $('form.form-modal').length > 0) {
		require(['form'], function(form) {
			form.init()
		})
	}
	if($('textarea[data-plugin=editor]').length > 0){
	    $('textarea[data-plugin=editor]').each(function(i, item){
	        var e = $(this).attr('id'), t = $(this).data('options');
	        if (!e && "" != e) return "";
    		var o = "string" == typeof e ? e : e.id;
    		o || (o = "editor-" + Math.random(), e.id = o);
    		var n = {
    			height: "200",
    			dest_dir: "",
    			image_limit: "1024",
    			allow_upload_video: 1,
    			audio_limit: "1024",
    			callback: null
    		};
    		$.isFunction(t) && (t = {
    			callback: t
    		}), t = $.extend({}, n, t);
    		var a = function(n, a) {
    				var r = {
    					autoClearinitialContent: !1,
    					toolbars: [
    						["fullscreen", "source", "preview", "|", "bold", "italic", "underline", "strikethrough", "forecolor", "backcolor", "|", "justifyleft", "justifycenter", "justifyright", "|", "insertorderedlist", "insertunorderedlist", "blockquote", "emotion", "link", "removeformat", "|", "rowspacingtop", "rowspacingbottom", "lineheight", "indent", "paragraph", "fontfamily", "fontsize", "|", "inserttable", "deletetable", "insertparagraphbeforetable", "insertrow", "deleterow", "insertcol", "deletecol", "mergecells", "mergeright", "mergedown", "splittocells", "splittorows", "splittocols", "|", "anchor", "map", "print", "drafts"]
    					],
    					elementPathEnabled: !1,
    					catchRemoteImageEnable: !1,
    					initialFrameHeight: t.height,
    					focus: !1,
    					maximumWords: 99999
    				},
    					l = {
    						type: "image",
    						direct: !1,
    						multiple: !0,
    						tabs: {
    							upload: "active",
    							browser: "",
    							crawler: ""
    						},
    						path: "",
    						dest_dir: t.dest_dir,
    						global: !1,
    						thumb: !1,
    						width: 0,
    						fileSizeLimit: 1024 * t.image_limit
    					};
    				if (n.registerUI("myinsertimage", function(e, t) {
    					e.registerCommand(t, {
    						execCommand: function() {
    							a.show(function(t) {
    								if (0 != t.length) if (1 == t.length) e.execCommand("insertimage", {
    									src: t[0].url,
    									_src: t[0].attachment,
    									width: "100%",
    									alt: t[0].filename
    								});
    								else {
    									var o = [];
    									for (i in t) o.push({
    										src: t[i].url,
    										_src: t[i].attachment,
    										width: "100%",
    										alt: t[i].filename
    									});
    									e.execCommand("insertimage", o)
    								}
    							}, l)
    						}
    					});
    					var o = new n.ui.Button({
    						name: "插入图片",
    						title: "插入图片",
    						cssRules: "background-position: -726px -77px",
    						onclick: function() {
    							e.execCommand(t)
    						}
    					});
    					return e.addListener("selectionchange", function() {
    						var i = e.queryCommandState(t); - 1 == i ? (o.setDisabled(!0), o.setChecked(!1)) : (o.setDisabled(!1), o.setChecked(i))
    					}), o
    				}, 19), n.registerUI("myinsertvideo", function(e, i) {
    					e.registerCommand(i, {
    						execCommand: function() {
    							a.show(function(i) {
    								if (i) {
    									var t = i.isRemote ? "iframe" : "video";
    									e.execCommand("insertvideo", {
    										url: i.url,
    										width: 300,
    										height: 200
    									}, t)
    								}
    							}, {
    								fileSizeLimit: 1024 * t.audio_limit,
    								type: "video",
    								allowUploadVideo: t.allow_upload_video
    							})
    						}
    					});
    					var o = new n.ui.Button({
    						name: "插入视频",
    						title: "插入视频",
    						cssRules: "background-position: -320px -20px",
    						onclick: function() {
    							e.execCommand(i)
    						}
    					});
    					return e.addListener("selectionchange", function() {
    						var t = e.queryCommandState(i); - 1 == t ? (o.setDisabled(!0), o.setChecked(!1)) : (o.setDisabled(!1), o.setChecked(t))
    					}), o
    				}, 20), o) {
    					var d = n.getEditor(o, r);
    					$("#" + o).removeClass("form-control"), $("#" + o).data("editor", d), $("#" + o).parents("form").submit(function() {
    						d.queryCommandState("source") && d.execCommand("source")
    					}), $.isFunction(t.callback) && t.callback(e, d)
    				}
    			};
    		require(["ueditor", "fileUploader"], function(e, i) {
    			a(e, i)
    		}, function(e) {
    			
    		})
	    })
	}
	$("img").error(function() {
		$(this).attr('src', G.static + 'default-pic.png')
	})
})