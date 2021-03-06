//Framework Specific
function showMessage(data) {
	if(data.success) $("#success-message").html(stripSlashes(data.success)).show();
	if(data.error) $("#error-message").html(stripSlashes(data.error)).show();
}
function stripSlashes(text) {
	if(!text) return "";
	return text.replace(/\\([\'\"])/,"$1");
}


function ajaxError() {
	alert("Error communicating with server. Please try again");
}
function loading() {
	$("#loading").show();
}
function loaded() {
	$("#loading").hide();
}


function showDateRange() {
	$("#select-date-toggle").toggle();
	$("#select-date-area").toggle();
}

function ChangeAmount() {
	var selected_amount = $(this).val();
	console.log(selected_amount);

	if(selected_amount == 'donuted') {
		$(".donuted").show();
		$(".4k").hide();
		$(".6k").hide();
		$(".8k").hide();
		$(".12k").hide();
		$(".1l").hide();

	}else if(selected_amount == '4k') {
		$(".donuted").hide();
		$(".4k").show();
		$(".6k").hide();
		$(".8k").hide();
		$(".12k").hide();
		$(".1l").hide();

	} else if(selected_amount == '6k') {
		$(".donuted").hide();
		$(".4k").hide();
		$(".6k").show();
		$(".8k").hide();
		$(".12k").hide();
		$(".1l").hide();

	}else if(selected_amount == '8k') {
		$(".donuted").hide();
		$(".4k").hide();
		$(".6k").hide();
		$(".8k").show();
		$(".12k").hide();
		$(".1l").hide();

	} else if(selected_amount == '12k') {
		$(".donuted").hide();
		$(".4k").hide();
		$(".6k").hide();
		$(".8k").hide();
		$(".12k").show();
		$(".1l").hide();

	} else if(selected_amount == '1l') {
		$(".donuted").hide();
		$(".4k").hide();
		$(".6k").hide();
		$(".8k").hide();
		$(".12k").hide();
		$(".1l").show();

	}
}


function siteInit() {
	/*$( "#from" ).datepicker({
      defaultDate: "+1w",
      dateFormat: "yy-mm-dd",
      changeMonth: true,
      numberOfMonths: 3,
      onClose: function( selectedDate ) {
        $( "#to" ).datepicker( "option", "minDate", selectedDate );
      }
    });
    $( "#to" ).datepicker({
      defaultDate: "+1w",
      dateFormat: "yy-mm-dd",
      changeMonth: true,
      numberOfMonths: 3,
      onClose: function( selectedDate ) {
        $( "#from" ).datepicker( "option", "maxDate", selectedDate );
      }
    });
    $("#select-date-toggle").click(showDateRange);
*/
	$("a.confirm").click(function(e) { //If a link has a confirm class, confrm the action
		var action = (this.title) ? this.title : "do this";
		action = action.substr(0,1).toLowerCase() + action.substr(1); //Lowercase the first char.
		
		if(!confirm("Are you sure you want to " + action + "?")) {
			e.stopPropagation();
		}
	});

		// Show selected city's centers. 
	$("#city_id").change(function() {
		var select = "<select id='coach_id'>";
		var city_id = this.value;

		var coaches_in_city = coaches[city_id];
		for(var coach_id in coaches_in_city) {
			select += "<option value='"+coach_id+"'>"+coaches_in_city[coach_id]+"</option>";
		}
		select += '</select>';

		$("#coach_id").html(select);
	});

	$(".donuted").show();
	$(".4k").hide();
	$(".6k").hide();
	$(".8k").hide();
	$(".12k").hide();
	$(".1l").hide();

	$("#amount").change(ChangeAmount);


	if(window.init && typeof window.init == "function") init(); //If there is a function called init(), call it on load
}
$ = jQuery.noConflict();
jQuery(window).load(siteInit);

