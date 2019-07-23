<?php /* Template_ 2.2.8 2016/10/26 16:28:06 /home/hbps/html/www/views/landing/hbps001.html 000004991 */ ?>
<div>
	<img src="<?php echo $TPL_VAR["path"]?>/img1_<?php echo $TPL_VAR["layout"]["device"]?>.jpg" />
</div>
<div class="landing-wrap ">
	<form id="FrmLanding">
	<input type="hidden" name="media_code" value="<?php echo $TPL_VAR["media"]?>" />
	<input type="hidden" name="url" value="<?php echo $TPL_VAR["url"]?>" />
	<input type="hidden" name="memo" value="" />
		<div class="landing-form">
			<dl class="inline">
				<dt>이름</dt>
				<dl><input type="text" name="name" class="validate[required]" data-errormessage-value-missing="이름을 입력하세요." value=""/></dl>
				<dt>연락처</dt>
				<dl><input type="text" name="tel" class="validate[required]" data-errormessage-value-missing="연락처를 입력하세요." value="" /></dl>
				<dt style="height:120px">희망수술부위<div style="margin-top:0px;font-size:12px;line-height: 1.2em">(중복체크가능)</div></dt>
				<dl>
					<div style="padding-top:5px">
<?php if(is_array($TPL_R1=$TPL_VAR["cfg"]["category"])&&!empty($TPL_R1)){foreach($TPL_R1 as $TPL_V1){?>
					<label><input type="checkbox" name="category[]" value="<?php echo $TPL_V1["category"]?>" class="hj validate[minCheckbox[1]]" data-errormessage-range-underflow="희망수술부위를 1개 이상 선택해주세요."><span class="lbl"> <?php echo $TPL_V1["name"]?></span></label>
<?php }}?>
					</div>
				</dl>
				<dt>희망수술일자</dt>
				<dl>
					<select name="hope_date" class="validate[required]" data-errormessage-value-missing="희망수술일자를 선택해주세요.">
						<option value="">희망수술일자를 선택해주세요.</option>
						<option value="즉시진행">즉시진행</option>
						<option value="1개월이내">1개월이내</option>
						<option value="3개월이내">3개월이내</option>
						<option value="3개월이후">3개월이후</option>
					</select>
				</dl>
				<dt>나이</dt>
				<dl><input type="text" name="age" class="validate[required, custom[onlyNumber]]"  data-errormessage-value-missing="나이를 입력하세요." value="" /></dl>
				<dt >성별</dt>
				<dl>
					<div style="padding-top:5px">
					<label><input type="radio" name="gender" value="female" class="hj" checked><span class="lbl"> 여자</span></label>
					<label><input type="radio" name="gender" value="male" class="hj"><span class="lbl"> 남자</span></label>
					</div>
					<div style="margin-top:10px;margin-left:100px">
					<input type="checkbox" name="agree" value="yes" class="hj validate[minCheckbox[1]]" data-errormessage-range-underflow="개인정보처리방침에 동의해주세요." checked><span class="lbl"  style="padding-right:0px;color:#83868B;"> <a href="javascript:;" onclick="Landing.agree()" style="text-decoration: underline">개인정보처리방침</a>에 동의합니다</span>
					</div>
				</dl>
			</dl>
			<div style="margin:10px 0px">
				<button type="submit" class="btn-landing">리얼모델신청하기</button>
			</div>
		</div>

	</form>
</div>
<div>
	<img src="<?php echo $TPL_VAR["path"]?>/img2_<?php echo $TPL_VAR["layout"]["device"]?>.jpg" />
</div>
<div>
	<img src="<?php echo $TPL_VAR["path"]?>/img3_<?php echo $TPL_VAR["layout"]["device"]?>.jpg" />
</div>

<div id="agree" class="agree-wrap hbps001">
	<div class="agree-box">
		<div class="agree-title">개인정보처리방침
			<div style="position:absolute;right:0px;top:-5px;color:#2080D0;" ><i class="fa fa-times" style="font-size:18px;cursor:pointer" onclick="Landing.agree()
			"></i></div>
		</div>
		<div class="agree-contents">
			<?php echo $TPL_VAR["cfg"]["privacy"]?>

		</div>
	</div>
</div>
<div id="backdrop" class="modal-backdrop"></div>
<script type="text/javascript">
var Landing = {
	init: function() {
		var me = this;
		var option = $.extend({},validation_option, {
			display:'alert',
			showOneMessage:true,
			validationEventTrigger:'submit',
			onValidationComplete: function(form, status){
				if(status) me.save();
			}
		});
		$("#FrmLanding").validationEngine('attach',option);
	},
	agree: function() {
		var visible = $('#agree').is(':visible');
		if(visible) {
			$('#backdrop').fadeOut(200);
			$('#agree').fadeOut(200);
		}
		else {
			$('#backdrop').fadeIn(200);
			$('#agree').fadeIn(200);
		}

	},
	save: function() {
		var formdata = $('#FrmLanding').serialize();
		$.ajax({
			url:'/Landing_proc/input',
			data:formdata,
			dataType:'json',
			type:'POST',
			success: function(r) {
				HJAlert.alert(r.msg);
				if(r.success){
					Landing.cpa(r.data.no);
				}

				$("#FrmLanding")[0].reset();

			}
		})
	},
	cpa: function(no) {
		$.ajax({
			url:'/Landing_proc/cpa',
			data:{no:no},
			dataType:'json',
			type:'POST',
			success: function(r) {
				console.log(r);
			}
		})
	}
}

$(function() {
	Landing.init();
})
</script>