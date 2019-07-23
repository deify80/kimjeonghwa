<?php /* Template_ 2.2.8 2016/10/28 16:54:38 /home/hbps/html/www/views/member/login.html 000002554 */ ?>
<style>
.login-box {background:url('/views/assets/images/member/login_bg.gif') no-repeat;width:630px;height:331px;margin:10px auto 130px;
	padding-top:150px;
}
.login-contents {
	margin:0 auto;
	border-collapse: separate;
	width:500px;
	border-spacing: 10px;
}

.login-contents th {
	font-weight: 400;
	font-size: 16px;
}

.btn-login {
	background-color:#E36E5A;color:#FFF;
	border-radius: 0px;
	width:130px;
	height:78px;
	font-size:18px;
}

.btn-login:hover{
	background-color: #EA9283;
	color:#FFF;
	/*transition: background-color 0.2s ease-in-out 0s;*/
}

.login-contents input[type=checkbox].hj + .lbl::before {
	vertical-align: 2px;
}
</style>
<div class="intro-title">로그인</div>
<div class="intro-title-map">HOME <span class="arrow"></span> 로그인</div>

<div class="login-box">

	<form id="FrmLogin">
	<table class="login-contents" border="0">
		<colgroup>
			<col style="width:100px" />
			<col />
			<col style="width:130px" />
		</colgroup>
		<tr>
			<th>아이디</th>
			<td>
				<input type="text" name="user_id" value="" autocomplete="off" class="validate[required]" data-errormessage-value-missing="아이디를 입력하세요." style="width:100%;padding:5px;">
			</td>
			<td rowspan="2">
				<button type="submit" class="btn btn-login">로그인</button>
			</td>
		</tr>
		<tr>
			<th>비밀번호</th>
			<td><input type="password" name="user_pwd" value="" autocomplete="off" class="validate[required]" data-errormessage-value-missing="비밀번호를 입력하세요." style="width:100%;padding:5px;"></td>
		</tr>
		<tr>
			<td></td>

			<td colspan="3" style="color:#898989;font-size:14px">
				<label><input type="checkbox" name="save_id" value="yes" class="hj" ><span class="lbl" > 아이디저장</span></label>
				<span style="margin-left:30px"><a href="/member/join">회원가입</a></span>
			</td>
		</tr>
	</table>
	</form>
</div>


<script type="text/javascript">
var Login = {
	init: function() {
		var me = this;
		var option = $.extend({},validation_option, {
			display:'alert',
			showOneMessage:true,
			validationEventTrigger:'submit',
			onValidationComplete: function(form, status){
				if(status) me.login();
			}
		});
		$("#FrmLogin").validationEngine('attach',option);
	},
	login: function() {
		HJAlert.alert('등록된 아이디가 없습니다.')
	}
}

$(function() {
	Login.init();
})
</script>