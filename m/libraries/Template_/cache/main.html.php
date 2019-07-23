<?php /* Template_ 2.2.8 2017/04/05 15:55:27 /home/cmltd/html/m/views/main.html 000001017 */ ?>
<style>
.main-menu {
	border-radius:4px;
	font-size:20px;
	text-align:center;
	padding:20px 0px;
	color:#FFF;
}
ul > li {margin:0px 0px 10px;}
</style>
<section class="panel panel-default">
	<div class="panel-body">
		<ul style="margin-bottom:-10px">
			<li>
				<div class="main-menu" style="background-color:#03A9F4" onclick="Main.href('/consulting/lists')">DB 목록보기</div>
			</li>
			<li>
				<div class="main-menu" style="background-color:#E84E40" onclick="Main.href('/consulting/db_input')">신규 DB등록하기</div>
			</li>
<?php if($TPL_VAR["auth"]["mobile_pc"]){?>
			<li>
				<div class="main-menu" style="background-color:#000000" onclick="Main.href('http://cmltd.kr/main/msg?ver=pc')">PC버전</div>
			</li>
<?php }?>


		</ul>
	</div>
</section>

<script type="text/javascript">
var Main = {
	href: function(href) {
		document.location.href=href;
	}
}
</script>