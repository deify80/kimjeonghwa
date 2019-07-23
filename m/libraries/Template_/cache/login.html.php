<?php /* Template_ 2.2.8 2016/11/02 13:38:13 /home/cmltd/html/m/views/login.html 000002100 */ ?>
<div style="text-align:center;margin-top:100px">
	<img src="http://cmltd.kr/images/layout/login_logo.png">
</div>

<style>
.input-login {
	padding:10px;
	width:100%;
	border:solid 1px #D3D3D3;
	border-radius:0px;

	-webkit-appearance: none;
	-moz-appearance: none;
	appearance: none;
}
.btn-login {
	background-color:#8BC34A;
	color:#FFF;
	width:100%;
	border:none;
	border-radius: 4px;
	padding:5px 0px;
	font-size:16px;
	padding:8px;
}

.login-wrap {
	width:90%;
	margin:30px auto;
	text-align:center;
}

.login-wrap > div {
	margin:5px 0px;
}
</style>

<form id="FrmLogin">
<input type="hidden" name="hst_code" id="hst_code" value="<?php echo $TPL_VAR["hst_code"]?>">
<div class="login-wrap">
	<div>
		<input type="text" name="user_id" value="" class="input-login validate[required]" placeholder="아이디" data-errormessage-value-missing="아이디를 입력해주세요." />
	</div>
	<div>
		<input type="password" name="passwd" value="" class="input-login validate[required]" placeholder="비밀번호" data-errormessage-value-missing="비밀번호를 입력해주세요." />
	</div>
	<div>
		<button type="submit" class="btn-login">로그인</button>
	</div>
</div>
</form>

<script type="text/javascript">
var Login = {
	init : function() {
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
	login : function() {

		var formdata = $('#FrmLogin').serialize();

		$.ajax("/login_proc/login", {
			type: "POST",
			dataType: "json",
			data : formdata,
			success: function (r) {
				if(r.success) {
					document.location.href="/main/";
				}
				else {
					HJAlert.alert(r.msg);
				}
			}
		});

		return false;
	}
};

$(function(){
	Login.init();
});
</script>