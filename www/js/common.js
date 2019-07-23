var datepicker_option = {
	defaultDate : "+w",
	changeMonth : true,
	changeYear: true,
	numberOfMonths : 1,
	dateFormat : 'yy-mm-dd',
	dayNamesMin : [ '일', '월', '화', '수', '목', '금','토' ],
	monthNames : [ '1월', '2월', '3월', '4월', '5월','6월', '7월', '8월', '9월', '10월', '11월','12월' ],
	monthNamesShort : [ '1월', '2월', '3월', '4월','5월', '6월', '7월', '8월', '9월', '10월','11월', '12월' ],
	// showOn : "button",
	// buttonImage : "/images/layout/calendar.gif",
	buttonImageOnly : false
};

var ckeditor_option = {

}

var validation_option = {
	validationEventTrigger:'submit',
	display:'alert',
	promptPosition:'topLeft',
	scroll:false
}

var require = {
	baseUrl: '/js'
};

var HmisCommon = {
	zIndex:100,
	numberFormat: function( number ) {
		if(number==0 || number == null) return 0;
		var sign = (number < 0)?'-':'';
		number = number.toString().replace(/[^0-9]/gi,'');
		number = Number(number);
		var reg = /(^[+-]?\d+)(\d{3})/;
		var n = (number + '');

		while (reg.test(n)) n = n.replace(reg, '$1' + ',' + '$2');

		return sign+n;
	},
	getZindex: function() {
		if(this.zIndex < 1){
			var elements = $('div');
			var highest_zindex = 0;
			var zindex = 0;
			$.each(elements, function(i,e) {
				zindex = $(e).css('zIndex');
				if (parseInt(zindex) > highest_zindex) {
					highest_zindex = zindex;
				}
			});
			alert(highest_zindex);
			this.zIndex = parseInt(highest_zindex);
		}
		else {
			this.zIndex +=2;
		}

		return parseInt(this.zIndex);
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
		var checked = $('input:checkbox[name="'+el_name+'"]:checked');
		return checked;
	},
	setDate: function(ds,de, target_ds, target_de) {
		var target_ds = target_ds || 'sdate_s';
		var target_de = target_de || 'sdate_e';

		$('#'+target_ds).val(ds);
		$('#'+target_de).val(de);
	// 	var date = new Date();
	// 	$('#'+el_end).val(date.toISOString().substring(0,10));
	// 	date.setDate(date.getDate()-parseInt(day));
	// 	$('#'+el_start).val(date.toISOString().substring(0,10));
	},
	createDatepicker : function(){
		$(".datepicker").datepicker(datepicker_option);
	},
	createNumber : function() {
		var me = this;
		$("input.number").attr('placeholder','0');
		$("input.number").attr('autocomplete','off');
		$("input.number").unbind('keyup.number').bind('keyup.number', function(){
			var e = $(this);
			e.val( me.numberFormat(e.val()) );

		})
	},
	searchInit: function(form_id) {
		var form = $('#'+form_id);
		$.each(form.find('div.btn-group'), function(i,e) {
			var el = $(e);
			el.find('label').removeClass("active");
			el.find('label > input:radio:checked').parent('label').addClass('active');
		})
	},
	formReset: function(form_id) {
		var form = $('#'+form_id);

		form[0].reset();

		$.each(form.find('input[type="hidden"], input[type="text"]'), function(i,e) {
			if($(e).data('default')) $(e).val($(e).data('default'));
		})

		$.each(form.find('select'), function(i,e) {
			$(e).val($(e).data('default')).attr("selected", "selected");
		})
		//라디오리셋
		$.each(form.find('div.btn-group'), function(i,e) {
			var el = $(e);
			el.find('label').removeClass("active");
			var v = el.data("default");
			if(v) el.find('label > input:radio[value="'+v+'"]').prop('checked', true);
			else el.find('label > input:radio').first().prop('checked', true);

			el.find('label > input:radio:checked').parent('label').addClass('active');
		})
	},
	getValue: function(el) {
		if (!el.length) return false;
		var tag = el.prop('tagName').toLowerCase();
		var type = el.prop('type').toLowerCase();
		var el_name = el.attr('name');
		var value;
		if (tag == 'input')
		{
			switch(type) {
				case 'text':
				case 'hidden':
				default :
					value = el.val();
				break;
				case 'radio':
					value = $(el).filter(':checked').val();
				break;
				case 'checkbox' :
					value = $(':checkbox[name="'+el_name+'"]:checked').val() || false;
				break;
			}
		}
		else {
			value = el.val();
		}

		return value;
	},
	setValue : function(el, value) {
		if(!el.length) return false;
		var tag = el.prop('tagName').toLowerCase();
		var type = el.prop('type').toLowerCase();
		if (tag == 'input') {
			switch (type){
			case 'text':
			case 'hidden':
				el.val(value);
			break;
			case 'radio':
				el.filter('[value="'+value+'"]').prop("checked", true);
			break;
			case 'checkbox':
				el.filter('[value]').prop("checked", false);
				if(typeof value == 'object') {
					jQuery.each(value, function(i, v) {
						el.filter('[value="'+v+'"]').prop("checked", true);
					});
				}
				else el.filter('[value="'+value+'"]').prop("checked", true);
			break;
			}
		}
		else if (tag == 'select') {
			el.find('option').prop("selected", false);
			el.find('option[value="'+value+'"]').prop("selected", true);
		}
		else {
			el.val(value);
		}
	},
	isStrNull: function(str) {
		if(!str) {
			return '<span class="f-null">미입력</span>';
		}
		else return str;
	},
	post: function(zipcode_elid, address_elid) {
		var modalPost = new HmisModal('modal_post', {width:500, top:100});
		modalPost.open('우편번호 찾기','/common/post', {zipcode:zipcode_elid, address:address_elid});
	},
	treat: function(callback, no, mode) {
		var modalTreat = new HmisModal('modal_treatment', {width:800, top:100});
		modalTreat.open('진료부위 찾기','/common/treat', {callback:callback, no:no, mode:mode});
	},
	resetTreat: function(callback) {
		$('#treat_nav').html('전체');
		$('#treat_cost_no').val('');
		$('#btn_treat_reset').addClass('hide');
		eval(callback+'()');
	},
	loadUser: function(team_code, user_id, prefix) {
		var prefix = prefix || 'sb';
		var team_code = team_code || $('#'+prefix+'_team').val();
		$.ajax({
			url:'/manage/get_user_json',
			data:{
				team_code:team_code
			},
			dataType:'json',
			type:'POST',
			success: function(r) {
				var sb = $('#'+prefix+'_user');
				if(r.success && team_code!=''){
					sb.find('option[value!=""]').remove();
					var selected;
					$.each(r.data, function(i,e){
						selected = (i==user_id)?'selected':'';
						sb.append('<option value="'+i+'" '+selected+'>'+e+'</option>');
					});
					sb.css('display','');
				}
				else {
					sb.val('');
					sb.css('display','none');
				}
			}
		});
	},
	loadTeam: function(dept_code, team_code, prefix) {
		var prefix = prefix || 'sb';
		var sb_team = $('#'+prefix+'_team');

		if(!dept_code) {
			sb_team.val('').css('display','none');
			return false;
		}
		$.ajax({
			url:'/manage/get_team_json',
			data:{
				dept_code:dept_code
			},
			dataType:'json',
			type:'POST',
			success: function(r){

				if(r.success){
					sb_team.find('option[value!=""]').remove();
					var selected;
					$.each(r.data, function(i,e){
						selected = (i==team_code)?'selected':'';
						sb_team.append('<option value="'+i+'" '+selected+'>'+e+'</option>');
					});
					sb_team.css('display','');
				}
				else {
					sb_team.val('').css('display','none');
				}
			}
		});
	},
	reloadBody: function(uri) {
		$('.area-body').load(uri, {scope:'BODY'});
	}
}



var HmisModal = function(modal_id, settings) {
	this.modal_id = 'HmisModal_'+modal_id;
	this.tpl = '<div id="'+this.modal_id+'" class="modal"><div class="modal-content"><div class="modal-header"><button type="button"  class="btn modal-close close"><i class="fa fa-times"></i></button><h6 class="modal-title">제목</h6></div><div class="modal-body">';
	this.tpl += '<div style="text-align:center"><img src="/images/common/loading_large.gif" style="height:100px;"/></div>';
	this.tpl += '</div><div class="modal-footer"></div></div></div><div id="'+this.modal_id+'_backdrop" class="modal-backdrop"></div>';
	var zIndex = HmisCommon.getZindex();

	this.settings = settings || {zIndex:zIndex, location:document};
	this.init();
};

HmisModal.prototype = {
	modal:'',
	duration:200,
	settings:{},
	init:function() {
		if(!$('#'+this.modal_id, top.document).length) {
			$("body", this.settings.location).append(this.tpl);
		}
		this.modal = $('#'+this.modal_id, top.document);
		this.modal.find('button.modal-close').click(this.close.bind(this));
		this.backdrop = $('#'+this.modal_id+'_backdrop', this.settings.location);
	},
	open:function(title, url, param, callback) {
		var zIndex = HmisCommon.getZindex();
			this.backdrop.css('z-index', zIndex);
			this.modal.css('z-index', parseInt(zIndex)+1);
		if(this.settings.width) this.setSize(this.settings.width);
		if(this.settings.height) this.setSize(null, this.settings.height);

		this.backdrop.fadeIn(this.duration);
		this.title(title);
		this._load(url, param, callback);
		this.modal.draggable({
			cursor:'move',
			handle:'.modal-header'
		});

		//최대 높이 지정
		var top  = this.modal.css('top').replace(/[^-\d\.]/g, '');
		var maxHeight = $(window).height()-(top+100);
		this.modal.find('.modal-body').css('max-height', maxHeight+'px');

		/*
   	 	this.modal.resizable({
			// alsoResize:true,
			// aspectRatio: true,
			minWidth:this.settings.width,
			start: function(e, ui) {
				ui.element.find('.modal-body').css('max-height','');
			}

		});
		*/

		$.scrollLock(true);
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

		// $("body").css('overflow','auto');
		$.scrollLock(false);
	},
	html:function(html) {
		var spot = this.modal.find('.modal-body');
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
		var body = this.modal;//.find('.modal-body');
		if(width.toString().indexOf('%')>0) {
			body.css('width', width);
		}
		else {
			body.css('width', width+'px');
		}
		// if(width)

		if(height) body.css('height', height+'px');
	},
	title:function(title) {
		this.modal.find('.modal-title').html(title||'modal');
	},
	_load:function(url, param, callback) {
		var self = this;
		var spot = this.modal.find('.modal-body');

		$.ajax({
			url:url,
			data:param,
			dataType:'html',
			type:'POST',
			success: function(r) {
				spot.html(r);

				self.modal.find('.modal-close').click(self.close.bind(self)); //모달창 안에 있는 닫기 버튼
				if($.isFunction(callback)) callback();
				setTimeout(function() {self._center();}, 100);
				$('[data-rel~="tooltip"]').tooltip();
			}
		});
	},
	_center:function(){
   	 	var self = this;

   	 	this._position();
  	 	// $(window).resize(function() { self._position() });
	},
	_position: function() {
		var e = this.modal;

		var half = {width: e.outerWidth() / 2, height: e.outerHeight() / 2}

		if(this.settings.top) e.css({top: this.settings.top+'px', left: '50%', marginTop:0, marginLeft: -(half.width)+'px'});
		else {
			e.css({top: '50%', left: '50%', marginTop: -(half.height)+'px', marginLeft: -(half.width)+'px'});
		}

		var pos = e.position();
		if(pos.left<half.width) e.css({left:0, marginLeft:0});
		if(!this.settings.top && pos.top<half.height) e.css({top:0, marginTop:0});
	}
}

var HmisAlert = {
	btn:{
		ok:'<button class="btn btn-theme"></button>',
		cancel:'<button class="btn btn-gray"></button>'
	},
	init: function() {
		if($('div.hmis-alert').length>0) {
			return false;
		}
		else {
			this.e = $('<div class="hmis-alert"><div class="alert-content"></div><div class="alert-button"></div>');
			this.backdrop = $('<div id="hmisalert_backdrop" class="modal-backdrop"></div>');
			return true;
		}
	},
	alert: function(msg, callback){
		if(this.init()) {
			this.create('alert', msg, callback);
		}
		this.show();
	},
	confirm: function(msg, callback) {
		if(this.init()) {
			this.create('confirm', msg, callback);
		}
		this.show();
	},
	create: function(type, msg, callback) {
		var me = this;
		var btn_ok =$(this.btn.ok);
		btn_ok.html('확인').on('click.ok', function() {
			if($.isFunction(callback)) callback(true);
			me.hide();
		});

		this.e.find('.alert-content').html(msg);
		this.e.find('.alert-button').append(btn_ok);


		if(type=='confirm') {
			var btn_cancel =$(this.btn.cancel);
			btn_cancel.html('취소').on('click.cancel', function() {
				callback(false);
				me.hide();
			});
			this.e.find('.alert-button').append(btn_cancel);
		}
	},
	show: function() {
		var alert = this.e;
		var zIndex = HmisCommon.getZindex();

		$('#hmisalert_backdrop').remove();

		$("body").append(alert);
		$("body").append(this.backdrop);
		var half = {width: alert.outerWidth() / 2, height: alert.outerHeight() / 2}

		this.backdrop.css({
			zIndex:zIndex,
			display:'block'
		});
		alert.css({
			position:'fixed',
			zIndex:zIndex+1,
			top: '50%',
			left: '50%',
			marginTop: -(half.height+100)+'px',
			marginLeft: -(half.width)+'px',
			display:'block'
		});

		alert.find('button').first().focus(); //버튼 포커스
	},
	hide: function() {
		$('.hmis-alert').remove();
		$('#hmisalert_backdrop').fadeOut('fast', function() {
			$(this).remove();
			// if($.isFunction(me.callback)) me.callback();
		});

	}
}

var HmisLoading = {
	show: function(target) {
		var target = $(target);
		var position = target.offset();
		var zIndex = HmisCommon.getZindex()+1;

		if($('#HmisLoading').length>0) {
			this.el = $('#HmisLoading');
		}
		else {
			this.el = $('<div id="HmisLoading" class="" style="background-color:#FFF;height:500px;opacity:.6;position:absolute;text-align:center;margin-top:1px"><img src="/images/common/loading_large.gif" style="height:100px"/></div>');
			$("body").append(this.el)
		}

		this.el.css({'height':target.outerHeight(), 'width':target.outerWidth(), 'top':position.top, 'left':position.left, 'zIndex':zIndex});
		var margin = (this.el.height()-100)/2;
		if(margin>100) {
			this.el.find('img').css({'margin-top':margin+'px'})
		}
		else {
			this.el.find('img').css({'margin-top':'0px'})
		}

		this.el.fadeIn(100);
	},
	hide : function() {
		this.el.fadeOut(100);
	}
}


Number.prototype.number_format = function(round_decimal) {
	return this.toFixed(round_decimal).replace(/(\d)(?=(\d{3})+$)/g, "$1,");
};


function bbs_input(bbs_code) {
	document.location = "/bbs_input/" + bbs_code;
}

function bbs_view(bbs_code, id) {
	document.location = "/bbs_view/" + bbs_code + "/" + id;
}

function open_bbs(bbs_code) {
	window.open("/bbs/"+bbs_code, "sms_popup", "toolbar=no, scrollbars=yes, resizable=no, top=0, left=0, width=850, height=650");
}


function is_checked(parent_id) {

	parent_id = "#" + parent_id;
	var checked_num = 0;

	for ( var i = 0; i < $(parent_id).find("input:checkbox[name='chk[]']").length; i++) {
		if ($(parent_id).find("input:checkbox[name='chk[]']")[i].checked) checked_num++;
	}

	return checked_num;

}

function select_all(parent_id, check_id) {
	parent_id = "#" + parent_id;
	check_id = "#" + check_id;

	var checkValue = true;
	if ($(check_id).is(":checked") == false) {
		checkValue = false;
	}
	for ( var i = 0; i < $(parent_id).find("input:checkbox").length; i++) {
		if ($(parent_id).find("input:checkbox")[i].checked != checkValue) {
			if ($(parent_id).find("input:checkbox")[i].disabled == false) $(parent_id).find("input:checkbox")[i].checked = checkValue;
		}
	}
}

function zero_fill(str, cnt) {
	str = '0000000000000000000'+str;
	return str.substr(str.length-cnt, cnt);
}

function display_list() {
	var data = $('#srch-frm').serialize();
	var url = $('#list-url').val();
	$.ajax({
		type : "GET",
		url : url,
		data : data,
		success : function(result_data) {
			$('#list-area').html(result_data);
			$("#list-area").fadeIn();
		}
	});
}

function open_sms(MODE) {
	//var url = "/sms/main";
	var url = "/sms/popup";
	window.open(url, "sms_popup", "toolbar=no, scrollbars=yes, resizable=no, top=0, left=0, width=920, height=580");

	if (MODE == "LIST") {
		$("#list-frm").attr("target", "sms_popup");
		$("#list-frm").attr("action", url);
		$("#list-frm").attr("method", "POST");
		$("#list-frm").submit();
	}
}


$(document).ready(function() {

    if($('body').find('.countdown').length > 0) {
        setInterval(function() {
            $.each($('body').find('.countdown'), function() {
                t = $(this);
                h = t.text().substr(0,2) * 1;
                m = t.text().substr(3,2) * 1;
                s = t.text().substr(6,2) * 1;
                if(h > 0 || (h == 0 && m > 0) || (h == 0 && m == 0 && s > 0)) {
                    if(s == 0) {
                        s = 59;
                        if(m == 0) {
                            m = 59;
                            if(h == 0) {
                                h = 24;
                            } else {
                                h = h - 1;
                            }
                        } else {
                            m = m - 1;
                        }
                    } else {
                        s = s - 1;
                    }
                }

            	var str = 2;
                t.text(zero_fill(h, 2)+ ':' +zero_fill(m, 2)+ ':' + zero_fill(s, 2));
            })
        }, 1000);
    }
});



function add_favorites(url, title, e){
	alert("Ctrl+D키를 누르시면 즐겨찾기에 추가하실 수 있습니다");
	return false;
	if(window.sidebar) {
		if(window.sidebar.addPanel) window.sidebar.addPanel(title, url, "");
		else {
			$(e).attr('href',url);
			$(e).attr('title',title);
			$(e).attr('rel','sidebar');
		}
	}
	else if(window.opera && window.print) {
		var elem = document.createElement('a');
		elem.setAttribute('href',url);
		elem.setAttribute('title',title);
		elem.setAttribute('rel','sidebar');
		elem.click();
	}
	else if(document.all) window.external.AddFavorite(url, title);
	else if(navigator.userAgent.match(/Chrome/)){
		alert("Ctrl+D키를 누르시면 즐겨찾기에 추가하실 수 있습니다");
	}
}


function disableSelection(target) {
	if (typeof target.onselectstart != "undefined") {
		target.onselectstart = function() {
			return false
		}

	} else if (typeof target.style.MozUserSelect != "undefined") {
		target.style.MozUserSelect = "none"

	} else {
		target.onmousedown = function() {
			return false
		}
		target.style.cursor = "default";
	}
}


function print_contents() {
	window.print();
}
