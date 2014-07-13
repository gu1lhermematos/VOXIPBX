
function input(minput){
	var content = $(">.content", $(minput).parent().parent());

	$(content).html("<div class='inputdiv'>"+$inputs[$(minput).val()]+"</div><div class='content'></div>");
	var inputadd = $(">div .input", content);

	$(inputadd).change(function(){
		if($inputs[$(this).val()]){
			input($(this));
		}
		else {
			$("> div >.content", $(minput).parent().parent()).html("");
		}
	});
}

function updateOptions(){
	var filters = $(".filter");
	total = filters.length;
	var i = 0;
	while (i <= total){
		$('>.label', filters.eq(i)).html("Title "+(i+1));
		if(total == 1){
			$('.options', filters.eq(i)).html("<a onclick='addFilters()' class='add'></a>");
		}else if(i==total-1){
			$('.options', filters.eq(i)).html("<a onclick='removeFilters($(this))' class='remove'></a><a onclick='addFilters()' class='add'></a>");
		}else{
			$('.options', filters.eq(i)).html("<a onclick='removeFilters($(this))' class='remove'></a>");
		}
		i++;
	}
}

function addFilters(){
	var filter = $("<div class='filter'><div class='label'>Filtro 1</div><div class='inputdiv'>"+$inputs['filter']+"</div><div class='content'></div><div class='options'></div></div>");
	$('.filters').append(filter);
	$(">div .input", filter).change(function(){
		if($inputs[$(this).val()]){
			input($(this));
		}
		else{
			$("> .content", filter).html("");
		}
	});
	updateOptions();
	
}

function removeFilters(element){
	$(element).parent().parent().remove();
	updateOptions();
};