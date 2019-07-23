<?php /* Template_ 2.2.8 2016/11/04 14:49:22 /home/cmltd/html/m/views/consulting/lists.html 000004763 */ ?>
<style>
.table-center th, .table-center td{
	text-align: center;
}
</style>

<form id="FrmConsultingSearch" onSubmit="Consulting.search(); return false;">
<input type="hidden" name="page" value="1" />
<input type="hidden" name="permanent_status" value="" data-default=""/>
<input type="hidden" name="team_code" value="" />
<input type="hidden" name="type" value="<?php echo $TPL_VAR["type"]?>" />
<input type="hidden" name="page" value="<?php echo $TPL_VAR["page"]?>" />
<input type="hidden" name="grant_biz" value="<?php echo $TPL_VAR["layout"]["page"]["grant_biz"]?>" /><!-- 사업장권한 -->


<div class="input-group">
	<input type="text" class="" name="word" placeholder="검색어(이름 or 전화번호)">
	<span class="input-group-btn">
		<button class="btn btn-default" type="button" onclick="Consulting.search();"><i class="fa fa-1 fa-search"></i></button>
	</span>
</div>
</form>

<div style="margin-top:10px;font-size:11px">
	총 DB: <span id="cnt_total"></span>건 <span class="split">|</span> 검색된 DB : <span id="cnt_search"></span>건
</div>

<div class="panel panel-default" style="margin-top:10px">
	<table class="table table-center table-striped">
		<thead>
			<tr>
				<th>번호</th>
				<th>이름</th>
				<th>연락처</th>
				<th>접수일</th>
			</tr>
		</thead>
		<tbody id="consulting_list">
		</tbody>
	</table>
</div>

<!-- 페이징:S -->
<div class="area-pagination" id="pagination" style="text-align:center">
</div>
<!-- 페이징:E -->

<textarea id="tmpl_list" style="display:none">
	<tr data-no="${cst_seqno}" data-grant="${grant_view}">
		<td data-link="no">{if accept_date} ${idx} {else} <i class="fa fa-1 fa-send" style="color:#FF7070" data-toggle="tooltip" title="신규DB"></i> {/if}</td>
		<td>${name}</td>
		<td>${tel}</td>
		<td>${reg_date}</td>
	</tr>
</textarea>

<textarea id="tmpl_pagination" style="display:none">
	<ul class="pagination">
		<li><a href="javascript:;" onclick="Consulting.load(${first})"><i class="fa fa-angle-double-left"></i></a></li>
		<li><a href="javascript:;" onclick="Consulting.load(${prev})"><i class="fa fa-angle-left"></i></a></li>
		{for p in block}
		<li class="{if p.equal==1} active {/if}"><a href="javascript:;" onclick="Consulting.load(${p.num})">${p.num}</a></li>
		{/for}
		<li><a href="javascript:;" onclick="Consulting.load(${next})"><i class="fa fa-angle-right"></i></a></li>
		<li><a href="javascript:;" onclick="Consulting.load(${last})"><i class="fa fa-angle-double-right"></i></a></li>
	</ul>
</textarea>


<script language="javascript" src="/views/assets/js/template.js"></script>
<script type="text/javascript">
var Consulting = {
	page:1,
	type:'<?php echo $TPL_VAR["type"]?>',
	init: function() {
		this.load($('input[name="page"]').val());
	},
	search: function() {
		Consulting.setPage(1);
		Consulting.load();
	},
	setPage: function(p) {
		var p = p || 1;
		$('input[name="page"]').val(p);
		this.page = p;
	},
	createClick: function() {
		$('#consulting_list > tr > td[data-link!="no"]').on('click',function(event) {
			var parent = $(this).parents();
			if(parent.data('grant') != 'Y') {
				HJAlert.alert('선택한 DB에 접근할 권한이 없습니다.');
				return false;
			}
			// document.location.reload();
			document.location.href = "/consulting/input/"+parent.data('no');
		})
	},
	load: function(page) {
		var p = page || this.page;
		Consulting.setPage(p);

		var me = this;
		var search = $('#FrmConsultingSearch').serialize();
		$.ajax({
			url:'/consulting/consulting_list_paging',
			data:{
				limit:10,
				type:this.type,
				search:search
			},
			dataType:'json',
			type:'POST',
			success: function(r) {
				var tmpl = TrimPath.parseDOMTemplate('tmpl_list');
				var target = $('#consulting_list');
				target.empty();
				if(r.success) {
					var html = '';
					$.each(r.data.list, function(i,e){
						html += tmpl.process(e);
					});
					target.append(html);
				}
				else {
					var td_cnt = $('table#consulting > thead > tr > th').length;
					target.append('<tr><td colspan="'+td_cnt+'">검색된 DB가 없습니다.</td></tr>');
				}

				me.createClick();

				var tmpl_page = TrimPath.parseDOMTemplate('tmpl_pagination');
				$('#pagination').empty().append(tmpl_page.process(r.data.paging));

				$('#cnt_total').html(HJCommon.numberFormat(r.data.count.total));
				$('#cnt_search').html(HJCommon.numberFormat(r.data.count.search));


			},
			complete: function() {
				//HmisLoading.hide();
			}
		})
	},
}

$(function() {
	Consulting.init();
})
</script>