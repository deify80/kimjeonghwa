<?php /* Template_ 2.2.8 2016/11/04 17:16:01 /home/cmltd/html/m/views/consulting/input.html 000003596 */ ?>
<section class="panel panel-default">
	<div class="panel-heading"><strong><i class="fa fa-1 fa-th"></i> DB정보</strong></div>
	<div class="panel-body">
		<div class="row">
			<div class="col-md-8 col-md-offset-2">

				<form id="FrmInput" class="form-horizontal ng-pristine ng-valid" role="form">

				<div class="form-group">
					<label for="inputName" class="col-sm-2 control-label">이름</label>
					<div class="col-sm-10">
						<?php echo $TPL_VAR["row"]["name"]?>

					</div>
				</div>
				<div class="form-group">
					<label for="inputTel" class="col-sm-2 control-label">연락처</label>
					<div class="col-sm-10">
						<?php echo $TPL_VAR["row"]["tel"]?>

					</div>
				</div>
				<div class="form-group">
					<label for="inputPath" class="col-sm-2 control-label">유입경로</label>
					<div class="col-sm-10">
						<?php echo $TPL_VAR["row"]["path_txt"]?>

					</div>
				</div>
				<div class="form-group">
					<label for="inputMedia" class="col-sm-2 control-label">미디어</label>
					<div class="col-sm-10">

						<?php echo $TPL_VAR["row"]["media"]?>

					</div>
				</div>
				<div class="form-group">
					<label for="inputCharacter" class="col-sm-2 control-label">고객성향</label>
					<div class="col-sm-10">
						<?php echo $TPL_VAR["row"]["character"]?>

					</div>
				</div>
				</form>
			</div>
		</div>
	</div>
</section>
<section class="panel panel-default">
	<div class="panel-heading"><strong><i class="fa fa-1 fa-th"></i> 메모</strong></div>
	<div class="panel-body">
		<div class="row">
			<div class="col-md-8 col-md-offset-2">

				<form id="FrmMemo" class="form-horizontal ng-pristine ng-valid" role="form">
				<input type="hidden" name="cst_seqno" value="<?php echo $TPL_VAR["row"]["cst_seqno"]?>" />
					<div class="form-group">
					<label for="inputMemo" class="col-sm-2 control-label">메모</label>
					<div class="col-sm-10">
						<textarea name="memo" id="inputMemo" placeholder="메모" class="validate[required]" data-errormessage-value-missing="메모를 입력하세요." style="height:100px"></textarea>
					</div>
				</div>

				<div class="form-group" style="text-align:center">
					<button type="submit" class="btn btn-success btn-w-md">메모 저장하기</button>
					<button type="button" class="btn btn-info btn-w-md" onclick="document.location.href='/consulting/lists'">목록</button>
				</div>
				</form>
			</div>
		</div>

		<div id="memo_lists"></div>
	</div>
</section>

<script type="text/javascript">
var ConsultingInput = {
	init: function() {
		var me = this;
		var option = $.extend({},validation_option, {
			display:'alert',
			showOneMessage:true,
			validationEventTrigger:'submit',
			onValidationComplete: function(form, status){
				if(status) me.saveMemo();
			}
		});
		$("#FrmMemo").validationEngine('attach',option);

		this.loadMemo();
	},
	saveMemo: function() {
		var formdata = $('#FrmMemo').serialize();
		$.ajax({
			url:'/Consulting_proc/input_memo',
			data:formdata,
			dataType:'json',
			type:'POST',
			success:function(r) {
				HJAlert.alert(r.msg);
				if(r.success) {
					$("#FrmMemo")[0].reset();
					ConsultingInput.loadMemo();
				}

			}
		})
	},
	loadMemo: function() {
		$('#memo_lists').load('/consulting/memo',{cst_seqno:'<?php echo $TPL_VAR["row"]["cst_seqno"]?>'});
	}
}

$(function() {
	ConsultingInput.init();
})
</script>