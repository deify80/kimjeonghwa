<?php /* Template_ 2.2.8 2016/10/25 15:52:02 /home/hbps/html/www/views/include/quick.html 000011371 */ ?>
<style>
ul.quick {margin-top:20px;}
ul.quick li {padding-bottom:20px;cursor:pointer;height:90px;}

ul.quick li {background-repeat: no-repeat;}
ul.quick li.quick1 {background-image:url('<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/quick/quick1.jpg');}
ul.quick li.quick1.on, ul.quick li.quick1.fix {background-image:url('<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/quick/quick1_on.jpg');}

ul.quick li.quick2 {background-image:url('<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/quick/quick2.jpg');}
ul.quick li.quick2.on, ul.quick li.quick2.fix {background-image:url('<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/quick/quick2_on.jpg');}

ul.quick li.quick3 {background-image:url('<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/quick/quick3.jpg');}
ul.quick li.quick3.on {background-image:url('<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/quick/quick3_on.jpg');}

ul.quick li.quick4 {background-image:url('<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/quick/quick4.jpg');}
ul.quick li.quick4.on {background-image:url('<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/quick/quick4_on.jpg');}

ul.quick li.quick5 {background-image:url('<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/quick/quick5.jpg');}
ul.quick li.quick5.on {background-image:url('<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/quick/quick5_on.jpg');}

ul.quick li.quick6 {background-image:url('<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/quick/quick6.jpg');}
ul.quick li.quick6.on {background-image:url('<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/quick/quick6_on.jpg');}

ul.quick li.quick7 {background-image:url('<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/quick/quick7.jpg');}
ul.quick li.quick7.on, ul.quick li.quick7.fix {background-image:url('<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/quick/quick7_on.jpg');}

</style>
<div id="quick" class="quick-menu quick-collpase" style="overflow:hidden">
	<div class="quick" style="float:left">
		<img src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/quick/quick.jpg" />

		<ul class="quick">
			<li class="quick1" data-contents="quick1" onclick="Quick.show(this);"></li>
			<li class="quick2" data-contents="quick2"onclick="Quick.show(this);"></li>
			<li class="quick3" onclick="Quick.link('/community/photo');"></li>
			<li class="quick4" onclick="Quick.link('/community/realstory');"></li>
			<li class="quick5" onclick="Quick.link('/community/self');"></li>
			<li class="quick6" onclick="Quick.link('/community/review');"></li>
			<li class="quick7" data-contents="quick7" onclick="Quick.show(this);"></li>
		</ul>
		<img src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/quick/quick8.jpg" style="margin:20px 0px"/>
	</div>
	<div class="quick-contents">
		<div id="quick1">
			<div style="text-align:right"><a href="javascript:;" onclick="Quick.hide()"><img src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/quick/quick_close.png" /></a></div>
			<div>
				<img src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/quick/quick1_1.jpg" />
			</div>
			<div style="width:250px;margin:20px auto">
				<form id="FrmKakao">
				<input type="hidden" name="media_code" value="homeK" />
				<input type="hidden" name="url" value="<?php echo $TPL_VAR["layout"]["page"]["url"]?>" />
				<input type="hidden" name="hope_date" value="" />
				<input type="hidden" name="age" value="" />
				<input type="hidden" name="gender" value="" />
				<ul class="kakao">
					<li>
						<input type="text" name="name" value="" class="validate[required] input-kakao" placeholder="이름" data-errormessage-value-missing="이름을 입력하세요." />
					</li>
					<li>
						<input type="text" name="tel" value="" class="validate[required] input-kakao" placeholder="휴대폰번호" data-errormessage-value-missing="연락처를 입력하세요."/>
					</li>
					<li>
						<select name="category" class="input-kakao" />
							<option>상담부위선택</option>
<?php if(is_array($TPL_R1=$TPL_VAR["layout"]["api"]["category"])&&!empty($TPL_R1)){foreach($TPL_R1 as $TPL_K1=>$TPL_V1){?>
							<option value="<?php echo $TPL_K1?>"><?php echo $TPL_V1?></option>
<?php }}?>
						</select>
					</li>
					<li>
						<textarea name="memo" placeholder="문의사항을 남겨주시면 상담원과 대화가 가능합니다."></textarea>
					</li>
					<li>
						<label><input type="checkbox" name="" value="" class="hj validate[required]" checked  data-errormessage-value-missing="개인정보처리방침 및 사용에 동의해 주세요." ><span class="lbl" style="font-size:12px"> 개인정보처리방침 및 사용동의 <a href="javascript:;" onclick="Quick.kakao.agree()" style="color:#333">[보기]</a></span></label>
					</li>
					<li>
						<input type="image"  src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/quick/quick1_btn.jpg" style="margin-top:10px"/>
					</li>
				</ul>
				</form>
			</div>
		</div>
		<div id="quick2" class="hide">
			<div style="text-align:right"><a href="javascript:;" onclick="Quick.hide()"><img src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/quick/quick_close.png" /></a></div>
			<div>
				<img src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/quick/quick2_1.jpg" />
			</div>
			<div style="width:250px;margin:20px auto"call_time>
				<form id="FrmCost">
				<input type="hidden" name="media_code" value="homeC" />
				<input type="hidden" name="url" value="<?php echo $TPL_VAR["layout"]["page"]["url"]?>" />
				<input type="hidden" name="gender" value="" />
				<ul class="kakao">
					<li>
						<input type="text" name="name" value="" class="validate[required] input-kakao" placeholder="이름" data-errormessage-value-missing="이름을 입력하세요."  />
					</li>
					<li>
						<input type="text" name="age" value="" class="validate[required, custom[onlyNumber]] input-kakao" placeholder="나이" data-errormessage-value-missing="나이를 입력하세요."  />
					</li>
					<li>
						<input type="text" name="tel" value="" class="validate[required] input-kakao" placeholder="휴대폰번호" data-errormessage-value-missing="휴대폰번호를 입력하세요."  />
					</li>
					<li>
						<select name="call_time" class="validate[required] input-kakao" data-errormessage-value-missing="상담시간을 선택하세요." >
							<option value="">상담시간선택</option>
							<option>오전9시-10시</option>
							<option>오전10시-11시</option>
							<option>오전11시-12시</option>
							<option>오후12시-1시</option>
							<option>오후1시-2시</option>
							<option>오후2시-3시</option>
							<option>오후3시-4시</option>
							<option>오후4시-5시</option>
							<option>오후5시-6시</option>
							<option>오후6시-7시</option>
						</select>

					</li>
					<li>
						<select name="category" class="input-kakao" data-errormessage-value-missing="시술항목을 선택하세요."  />
							<option value="">시술항목 선택</option>
<?php if(is_array($TPL_R1=$TPL_VAR["layout"]["api"]["category"])&&!empty($TPL_R1)){foreach($TPL_R1 as $TPL_K1=>$TPL_V1){?>
							<option value="<?php echo $TPL_K1?>"><?php echo $TPL_V1?></option>
<?php }}?>
						</select>
					</li>
					<li>
						<input type="text" name="hope_cost" value="" class="input-kakao" placeholder="예상비용" data-errormessage-value-missing="예상비용을 입력하세요."  />
					</li>
					<li>
						<select name="hope_date" class="validate[required] input-kakao" data-errormessage-value-missing="희망수술일자를 선택해주세요.">
						<option value="">희망수술일자를 선택해주세요.</option>
						<option value="즉시진행">즉시진행</option>
						<option value="1개월이내">1개월이내</option>
						<option value="3개월이내">3개월이내</option>
						<option value="3개월이후">3개월이후</option>
					</select>
					</li>
					<li>
						<textarea name="memo" placeholder="문의사항을 남겨주시면 상담원과 대화가 가능합니다."></textarea>
					</li>
					<li>
						<label><input type="checkbox" name="" value="" class="hj validate[required]" checked data-errormessage-value-missing="개인정보처리방침 및 사용에 동의해 주세요." ><span class="lbl" style="font-size:12px"> 개인정보처리방침 및 사용동의 <a href="javascript:;" onclick="Quick.cost.agree()" style="color:#333">[보기]</a></span></label>
					</li>
					<li>
						<input type="image" src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/quick/quick2_btn.jpg" style="margin-top:10px"/>
					</li>
				</ul>
				</form>
			</div>
		</div>
		<div id="quick7" class="hide">
			<div style="text-align:right"><a href="javascript:;" onclick="Quick.hide()"><img src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/quick/quick_close.png" /></a></div>
			<div style="text-align:center">
				<img src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/quick/quick7_1.jpg" />
				<img src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/quick/quick7_2.jpg" style="margin-top:30px"/>

				<img src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/quick/quick7_btn1.jpg" />
				<img src="<?php echo $TPL_VAR["layout"]["path"]["assets"]?>/images/quick/quick7_btn2.jpg" />
			</div>

		</div>
	</div>
</div>

<script type="text/javascript">
var Quick = {
	init: function() {

		$('.quick > li').on('mouseover', function() {
			$(this).addClass('on');
		});
		$('.quick > li').on('mouseout', function() {
			$(this).removeClass('on');
		});

		$("#quick").mousewheel(function(e) {
			var scrollgap = 30;
			var top = $(this).scrollTop() - e.deltaY * scrollgap ;
			if(top < 0) top = 0;
			$(this).scrollTop(top);
			return false;
		});

		var me = this;

		//카톡상담
		this.kakao = new CPA('FrmKakao');
		var option = $.extend({},validation_option, {
			display:'alert',
			showOneMessage:true,
			validationEventTrigger:'submit',
			onValidationComplete: function(form, status){
				if(status) me.kakao.save();
			}
		});
		this.kakao.frm.validationEngine('attach',option);


		//비용문의
		this.cost = new CPA('FrmCost');
		var option = $.extend({},validation_option, {
			display:'alert',
			showOneMessage:true,
			validationEventTrigger:'submit',
			onValidationComplete: function(form, status){
				if(status) me.cost.save();
			}
		});
		this.cost.frm.validationEngine('attach',option);

	},
	show: function(e) {

		$('.quick li').removeClass('fix');
		$(e).addClass('fix');

		$('.quick-contents > div').addClass('hide');
		var contents = $(e).data('contents');
		$('#'+contents).removeClass('hide');

		$('#quick').animate({
			width: 407
		},100)
	},
	hide: function() {
		$('.quick li').removeClass('fix');
		$('#quick').animate({
			width: 89
		},100)
	},
	link: function(href) {
		document.location.href=href;
	}
}

$(function() {
	Quick.init();
})
</script>