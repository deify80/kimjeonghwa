<?php /* Template_ 2.2.8 2016/10/31 11:11:58 /home/hbps/html/www/views/member/join.html 000004556 */ ?>
<style>
.join-wrap {
	width:1233px;
	margin:10px auto 100px;
}

.join-table {
	width:100%;
	border-spacing: 10px;
	border-top:solid 2px #2C2E39;
	margin-top:30px;
}

.join-table td, .join-table th {padding:10px 20px;border-bottom:solid 1px #E8E8E8;}

.btn-join {
	background-color:#E36E5A;color:#FFF;
	border-radius: 0px;
	width:130px;
	padding:10px 30px;
	font-size:16px;
}

.btn-join-cancel {
	background-color:#FFF;
	color:#000;
	border:solid 1px #000;
	border-radius: 0px;
	width:130px;
	padding:10px 30px;
	font-size:16px;
}

.agreement {
	border:solid 1px #000;border-top:solid 2px #000;height:200px;overflow-y: scroll;padding:10px;
	color:#828282;
	margin-bottom:5px;
}



.join-wrap input[type=checkbox].hj + .lbl::before {
	vertical-align: 1px;
}

.join-wrap input[type=checkbox].hj:checked + .lbl::before {
	background-color:#E36E5A;
	border-color:#E36E5A;
	color:#FFF;
}

</style>
<div class="intro-title">회원가입</div>
<div class="intro-title-map">HOME <span class="arrow"></span> 회원가입</div>

<form id="FrmJoin">
<div class="join-wrap">
	<div class="agreement">
		<?php echo $TPL_VAR["layout"]["cfg"]["agreement"]?>

	</div>
	<div>
		<label><input type="checkbox" name="agree_use" value="yes" class="hj validate[required]"  data-errormessage-value-missing="회원가입약관에 동의해주세요." /><span class="lbl"> 회원가입약관에 동의합니다.</span></label>
	</div>

	<div class="agreement" style="margin-top:20px">
		<?php echo $TPL_VAR["layout"]["cfg"]["privacy"]?>

	</div>
	<div style="position:relative">
		<label><input type="checkbox" name="agree_privacy" value="yes"  class="hj validate[required]"  data-errormessage-value-missing="개인정보처리방침에 동의해주세요." /><span class="lbl"> 개인정보 처리방침에 동의합니다.</span></label>
		<div style="position:absolute;top:0px;right:0px">
			<label ><input type="checkbox" id="agree_all" value="yes" class="hj" ><span class="lbl" style="padding-right:0px"> 전체동의</span></label>
		</div>
	</div>
	<div>

	</div>

	<table class="join-table">
		<colgroup>
			<col style="width:200px" />
			</colgroup>
		</colgroup>
		<tr>
			<th>*아이디</th>
			<td><input type="text" name="user_id" value="" class="input validate[required]" data-errormessage-value-missing="아이디를 입력하세요." /></td>
		</tr>
		<tr>
			<th>*비밀번호</th>
			<td><input type="text" class="input validate[required]" data-errormessage-value-missing="아이디를 입력하세요." /></td>
		</tr>
		<tr>
			<th>*비밀번호 확인</th>
			<td><input type="text" class="input validate[required]" /></td>
		</tr>
		<tr>
			<th>*이름</th>
			<td><input type="text" class="input validate[required]" /></td>
		</tr>
		<tr>
			<th>*생년월일</th>
			<td>
				<input type="text" name="" value="" class="input" style="width:80px" /> 년
				<input type="text" name="" value="" class="input" style="width:60px;margin-left:10px"/> 월
				<input type="text" name="" value="" class="input" style="width:60px;margin-left:10px"/> 일
			</td>
		</tr>
		<tr>
			<th>*휴대전화</th>
			<td>
				<input type="text" name="" value="" class="input" style="width:60px;"/> -
				<input type="text" name="" value="" class="input" style="width:60px;"/> -
				<input type="text" name="" value="" class="input" style="width:60px;"/>
			</td>
		</tr>
		<tr>
			<th>거주지역</th>
			<td>
				<select name="address" class="select">
					<option value="">선택하세요</option>
				</select>
			</td>
		</tr>
	</table>

	<div style="text-align:center;margin-top:20px">
		<button type="submit" class="btn btn-join">회원가입</button>
		<button type="button" class="btn btn-join-cancel" style="margin-left:10px"> 취소</button>
	</div>
</div>
</form>

<script type="text/javascript">
var Join = {
	init: function() {
		$('#agree_all').on('click',function(){
			if(this.checked) {
				$('.hj').prop('checked','checked');
			}
		});

		var me = this;
		var option = $.extend({},validation_option, {
			display:'alert',
			showOneMessage:true,
			validationEventTrigger:'submit',
			onValidationComplete: function(form, status){
				if(status) me.join();
			}
		});
		$("#FrmJoin").validationEngine('attach',option);

	},
	join: function() {

	}
}

$(function() {
	Join.init();
})
</script>