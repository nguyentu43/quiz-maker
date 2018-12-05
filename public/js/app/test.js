$().ready(function(){

	$('[name=formTest] #is_enable_time').change(function(e){

		var par = $('input[name=time_limit]').parent().parent().parent();
		par.toggle();

		if(par.css('display') != 'none')
		{
			$('input[name=time_limit]').val(1);
		}
		else
		{
			$('input[name=time_limit]').val(0);
		}
	});

	$('input[name=start_time]').parent().datetimepicker({
		date: moment($('input[name=start_time]').val())
	});

	$('input[name=end_time]').parent().datetimepicker({
		date: moment($('input[name=end_time]').val())
	});

	$('input[name=start_time]').parent().on("dp.change", function (e) {
        $('input[name=end_time]').parent().data("DateTimePicker").minDate(e.date);
    });
    $('input[name=end_time]').parent().on("dp.change", function (e) {
        $('input[name=start_time]').parent().data("DateTimePicker").maxDate(e.date);
    });

	$('[name=formTest] #is_enable_schedule').change(function(e){

		$('input[name=start_time]').parent().parent().parent().toggle();
		$('input[name=end_time]').parent().parent().parent().toggle();

		if($('input[name=start_time]').parent().parent().parent().css('display') == 'none')
		{
			$('input[name=start_time]').val('');
			$('input[name=end_time]').val('');
		}

	});

	$('[name=formTest] #is_attemps').change(function(e){

		var par = $('input[name=attemps]').parent().parent();
		par.toggle();

		if(par.css('display') != 'none')
		{
			$('input[name=attemps]').val(1);
		}
		else
		{
			$('input[name=attemps]').val(0);
		}

	});

	$('#btnDeleteTest').click(function(e){
		$.ajax({
			url: urlTest + '/delete',
			data: {delete: '', test_id: defaultTest.test_id},
			method: 'post',
			success: function(res){
				if(res.ok)
				{
					location.href = urlTest;
				}
			}
		});
	});
	
});