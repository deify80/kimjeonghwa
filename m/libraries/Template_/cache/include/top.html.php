<?php /* Template_ 2.2.8 2017/02/22 14:44:15 /home/cmltd/html/m/views/include/top.html 000001364 */ ?>
<style>
.top-wrap {font-size:16px;}
</style>
<div class="top-wrap">
	<div class="pull-right">
		<?php echo $TPL_VAR["layout"]["login"]["info"]["ss_name"]?>님 로그인중
		<a href="javascript:;" onclick="Top.logout()"><i class="fa fa-1 fa-power-off" style="margin-left:10px;color:#E84E40"></i></a>
		<!-- 20170220 kruddo : 대표님 모바일 버전 로그인 시 PC버전 보기 기능 추가 -->
<?php if($TPL_VAR["layout"]["login"]["info"]["ss_user_id"]=="kakwon"){?>
		<button class="btn" onclick="Top.device_link('PC')">PC버전</button>
<?php }?>
		<!-- 20170220 kruddo : 대표님 모바일 버전 로그인 시 PC버전 보기 기능 추가 -->
		</div>

	<a href="/" style="color:#FFF"><i class="fa fa-1 fa-home"></i> HB성형외과</a>
</div>

<script type="text/javascript">
var Top = {
	logout: function() {
		document.location.href='/Login_proc/logout';
	},

	// 20170220 kruddo : 대표님 모바일 버전 로그인 시 PC버전 보기 기능 추가
	device_link: function(d) {
		if(d=="M"){
			location.href="http://m.cmltd.kr/main";
		}
		else{
			location.href="http://cmltd.kr/treat/schedule";
		}
	}
	// 20170220 kruddo : 대표님 모바일 버전 로그인 시 PC버전 보기 기능 추가
}
</script>