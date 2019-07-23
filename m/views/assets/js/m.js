var validation_option = {
	promptPosition:'centerRight',
	scroll:false
}

var HJCommon = {
	numberFormat: function( number ) {
		if(number==0) return 0;
		number = number.toString().replace(/[^0-9]/gi,'');
		number = Number(number);
		var reg = /(^[+-]?\d+)(\d{3})/;
		var n = (number + '');

		while (reg.test(n)) n = n.replace(reg, '$1' + ',' + '$2');

		return n;
	},
	getZindex: function() {
		var elements = $('div, a, input');
		var highest_zindex = 0;
		var zindex = 0;
		$.each(elements, function(i,e) {
			zindex = $(e).css('zIndex');
			if (parseInt(zindex) > highest_zindex) {

				highest_zindex = zindex;
			}
		});

		return parseInt(highest_zindex);
	},
	toggleCheck: function(e, el_name) {
		if($(e).prop('checked')) {
			this.checkAll(el_name);
		}
		else {
			this.uncheckAll(el_name);
		}
	},
	checkAll: function(el_name) {
		$('input:checkbox[name="'+el_name+'"]').not(':disabled').prop('checked',true);
	},
	uncheckAll : function(el_name) {
		$('input:checkbox[name="'+el_name+'"]').not(':disabled').prop('checked',false);
	},
	getChecked: function(el_name) {
		var checked = $('input[name="'+el_name+'"]:checked');
		return checked;
	},
	formReset: function(form_id) {
		var form = $('#'+form_id);

		form[0].reset();
		form.find('.btn-group > label').removeClass('active');
		form.find('.btn-group > label > input:radio:checked').parent('label').addClass('active');
	},
	download: function(no) {
		document.location.href="/common/download/"+no;
	}
}

var HJModal = function(modal_id, settings) {
	this.modal_id = 'hjmodal_'+modal_id;
	var cls = (settings.cls)?settings.cls:'';
	this.tpl = '<div id="'+this.modal_id+'" class="hjmodal '+cls+'"><div class="hjmodal-content"><div class="hjmodal-header"><button type="button"  class="btn hjmodal-close close"><i class="fa fa-times"></i></button><h6 class="hjmodal-title">제목</h6></div><div class="hjmodal-body"></div><div class="hjmodal-footer"></div></div></div><div id="'+this.modal_id+'_backdrop" class="modal-backdrop"></div>';
	var zIndex = HJCommon.getZindex();

	this.settings = settings || {zIndex:zIndex, location:document};
	this.init();
};

HJModal.prototype = {
	modal:'',
	duration:100,
	settings:{},
	init:function() {
		if(!$('#'+this.modal_id, top.document).length) {
			$("body", this.settings.location).append(this.tpl);
		}
		this.modal = $('#'+this.modal_id, top.document);
		this.modal.find('button.hjmodal-close').click(this.close.bind(this));
		this.backdrop = $('#'+this.modal_id+'_backdrop', this.settings.location);
	},
	open:function(title, url, param, callback) {
		var zIndex = HJCommon.getZindex();
			this.backdrop.css('z-index', zIndex);
			this.modal.css('z-index', parseInt(zIndex)+1);
		if(this.settings.width) this.setSize(this.settings.width);
		if(this.settings.height) this.setSize(null, this.settings.height);

		this.backdrop.fadeIn(this.duration);
		this.title(title);
		this._load(url, param, callback);

		// this.modal.draggable({
		// 	cursor:'move',
		// 	handle:'.hjmodal-header'
		// });

		this.modal.find('.hjmodal-body').css('max-height',$(window).height()-(this.settings.top+128));

//		$("body").bind('scroll mousewheel touchmove', function(e){e.preventDefault()});
	},
	close: function(evt, rs) {
		var me = this;
		var rs = rs || {};
		this.modal.fadeOut(this.duration, function() {
			$(this).remove();
			if($.isFunction(me.callback)) me.callback(rs);
		});
		this.backdrop.fadeOut(this.duration, function() {
			$(this).remove();

			if($.isFunction(me.settings.close)) {
				me.settings.close();
			}
		});

//		$("body").unbind('scroll touchmove mousewheel');
	},
	html:function(html) {
		var spot = this.modal.find('.hjmodal-body');
		spot.html(html);
	},
	show:function() {
		this._center();
		this.backdrop.fadeIn(this.duration);
	},
	hide: function() {
		this.modal.addClass('hide');
		this.backdrop.addClass('hide');
	},
	remove: function() {

	},
	setSize: function(width, height) {
		var body = this.modal.find('.hjmodal-body');
		if(width) body.css('width', width+'px');
		if(height) body.css('height', height+'px');
	},
	title:function(title) {
		this.modal.find('.hjmodal-title').html(title||'modal');
	},
	_load:function(url, param, callback) {
		var self = this;
		var spot = this.modal.find('.hjmodal-body');
		$.ajax({
			url:url,
			data:param,
			dataType:'html',
			type:'POST',
			success: function(r) {
				spot.html(r);
				self._center();
				self.modal.find('.hjmodal-close').click(self.close.bind(self)); //모달창 안에 있는 닫기 버튼
				if($.isFunction(callback)) callback();
				// $('[data-rel="tooltip"]').tooltip();
			}
		});
	},
	_center:function(){
   	 	var self = this;
   	 	this._position();
  	 	$(window).resize(function() { self._position() });
	},
	_position: function() {
		var e = this.modal;
   	 	var half = {width: e.outerWidth() / 2, height: e.outerHeight() / 2}
   	 	//
		if(this.settings.top) e.css({top: this.settings.top+'px', left: '50%', marginTop:0, marginLeft: -(half.width)+'px'});
		else e.css({top: '50%', left: '50%', marginTop: -(half.height)+'px', marginLeft: -(half.width)+'px'});
		//
		var pos = e.position();
		if(pos.left<half.width) e.css({left:0, marginLeft:0});
		if(!this.settings.top && pos.top<half.height) e.css({top:0, marginTop:0});
	}
}


var HJAlert = {
	btn:{
		ok_txt:'확인',
		cancel_txt:'취소',
		ok:'<button class="btn btn-theme btn-sm"></button>',
		cancel:'<button class="btn btn-gray btn-sm"></button>'
	},
	init: function() {
		if($('div.hj-alert').length>0) {
			return false;
		}
		else {
			this.e = $('<div class="hj-alert"><div class="alert-content"></div><div class="alert-button"></div>');
			this.backdrop = $('<div id="hjalert_backdrop" class="modal-backdrop"></div>');
			return true;
		}
	},
	alert: function(msg, callback){
		if(this.init()) {
			this.create('alert', msg, callback);
		}
		this.show();
	},
	confirm: function(msg, callback, ok_txt) {
		if(this.init()) {
			this.create('confirm', msg, callback);
		}
		if(ok_txt) {

		}
		this.show();
	},
	create: function(type, msg, callback) {
		var me = this;
		var btn_ok =$(this.btn.ok);
		btn_ok.html(this.btn.ok_txt).on('click.ok', function() {
			if($.isFunction(callback)) callback(true);
			me.hide();
		});

		this.e.find('.alert-content').html(msg);
		this.e.find('.alert-button').append(btn_ok);


		if(type=='confirm') {
			var btn_cancel =$(this.btn.cancel);
			btn_cancel.html(this.btn.cancel_txt).on('click.cancel', function() {
				callback(false);
				me.hide();
			});
			this.e.find('.alert-button').append(btn_cancel);
		}
	},
	show: function() {
		var alert = this.e;
		var zIndex = HJCommon.getZindex();
		console.log(zIndex);
		$('#hjalert_backdrop').remove();

		$("body").append(alert);
		$("body").append(this.backdrop);
		var half = {width: alert.outerWidth() / 2, height: alert.outerHeight() / 2}

		this.backdrop.css({
			zIndex:100,
			display:'block'
		});
		var zIndex = 100;
		alert.css({
			position:'fixed',
			zIndex:parseInt(zIndex)+10,
			top: '50%',
			left: '50%',
			marginTop: -(half.height)+'px',
			marginLeft: -(half.width)+'px',
			display:'block'
		});

		alert.find('button').first().focus(); //버튼 포커스
	},
	hide: function() {
		$('.hj-alert').remove();
		$('#hjalert_backdrop').fadeOut('fast', function() {
			$(this).remove();
			// if($.isFunction(me.callback)) me.callback();
		});

	}
}


var HJLoading = {
	show: function(target) {
		var target = $(target);
		var offset = target.offset();
		var zIndex = HJCommon.getZindex()+1;

		this.target = target;
		if($('#HJLoading').length>0) {
			this.el = $('#HJLoading');
		}
		else {
			this.el = $('<div id="HJLoading" class="" style="background-color:#FFF;height:500px;opacity:.3;position:absolute;text-align:center;"><img src="/admin/views/assets/images/loading.gif" /></div>');
			$("body").append(this.el)
		}

		this.el.css({'height':target.outerHeight(), 'width':target.outerWidth(), 'top':offset.top, 'left':offset.left, 'zIndex':zIndex});
		// var padding = (this.el.height()-200)/2;
		this.el.find('img').css({'padding-top':'100px'})
		this.el.fadeIn(100);
	},
	hide : function() {
		this.el.fadeOut(100);
		this.target.fadeIn();
	}
}
