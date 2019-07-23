var HmisTreat = {
	loadTreatItem: function(region_code, item_code, prefix) {
		var prefix = prefix || 'sb';
		var region_code = region_code || $('#'+prefix+'_treat_region').val();
		$.ajax({
			url:'/patient/get_treat_item',
			data:{
				region_code:region_code
			},
			dataType:'json',
			type:'POST',
			success: function(r) {
				var sb = $('#'+prefix+'_treat_item');
				if(r.success && region_code!=''){
					sb.find('option[value!=""]').remove();
					var selected;
					$.each(r.data, function(i,e){
						selected = (i==item_code)?'selected':'';
						sb.append('<option value="'+i+'" '+selected+'>'+e.title+'</option>');
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
	//진료항목추가
	addTreat: function(d) {
		var id = d.no;
		var text = d.nav.join(' &gt ');
		var target = $('#treat_list');

		if(target.find('span#'+id).length>0) {
			HmisAlert.alert('동일한 진료항목이 이미 선택되었습니다.');
			return false;
		}

		var tbl = $('<span class="label label-default" style="" id="'+id+'">'+text+' <i class="fa fa-times fa-1" style="margin-left:10px;cursor:pointer;vertical-align: -1px"></i></label>');
		tbl.find('i').on('click.treat', function() {HmisTreat.removeTreat(id);});
		target.append(tbl);
		this.buildTreat();
	},
	//진료항목제거
	removeTreat: function(id) {
		$('#treat_list > span#'+id).remove();
		this.buildTreat();
	},
	buildTreat: function() {
		if($('#treat_list > span').length>0) {
			$('#treat_null').css('display','none');
		}
		else {
			$('#treat_null').css('display','');
		}
	}
}
