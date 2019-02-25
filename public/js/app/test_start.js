$().ready(function(e){
	var listQuestion;
	var listResponse = {};
	var time_left = time_limit * 60;
	var index_primary = 0;
	var interval_time;
	var icon = ['#FFEB3B', '#BDBDBD', '#FF9800'];
	var is_complete = false;
	var list_file_upload_delete = [];

	//setup table-ranks
	$.each($('.table-ranks tbody tr'), function(i, tr){

		if(i <= 3)
		{
			var text = $(tr).find('td').eq(1).text();
			$(tr).find('td').eq(1).html(text + ' <i class="fa fa-star" aria-hidden="true"></i>');
			$(tr).find('td').eq(1).find('i').css('color', icon[i]);
		}
		
	});

	//define question object
	function Question(data)
	{
		this.category_id = data.category_id;
		this.index = data.index;
		this.question_id = data.question_id;
		this.question_options = data.question_options;
		this.question_text = data.question_text;
		this.question_type_id = data.question_type_id;
		this.user_id = data.user_id;
		this.question_settings = data.question_settings;
	}

	Question.prototype.checkType = function(type)
	{
		return this.question_type_id == type;
	}

	Question.Type = {
		Check: 1,
		MultiCheck: 2,
		Sort: 3,
		MergeCol: 4,
		Group: 5,
		ShortText: 6,
		Paragraph: 7,
		FillWord: 8,
		File: 9
	};

	Question.prototype.tooltip = function()
	{
		switch(parseInt(this.question_type_id))
		{
			case Question.Type.MultiCheck:
				return ''
			case Question.Type.Paragraph:
				return 'Số từ tối đa: ' + this.question_settings.word_count;
		}
	}

	Question.prototype.renderOptions = function()
	{
		var html = '';
		var selectorOptions = '#table-option-' + this.question_id + ' tbody';
		var data = { question_options: this.question_options, question_id: this.question_id };
		var response = listResponse[this.question_id];
		switch(parseInt(this.question_type_id))
		{
			case Question.Type.Check:
				$(selectorOptions).html(Mustache.render($('#tmplOption1').html(), data ));

				if(typeof response !== 'undefined')
				{
					response.forEach(function(item){
						$('input[data-id=' + item + ']').prop('checked', 'checked');
					});
				}
				break;
			case Question.Type.MultiCheck:
				$(selectorOptions).html(Mustache.render($('#tmplOption2').html(), data ));

				if(typeof response !== 'undefined')
				{
					response.forEach(function(item){
						$('input[data-id=' + item + ']').prop('checked', 'checked');
					});
				}
				break;
			case Question.Type.Sort:

				if(typeof response !== 'undefined')
				{
					var options = [];

					response.forEach(function(item){

						data.question_options.forEach(function(option){

							if(item == option.id)
								options.push(option);

						});

					});

					data.question_options = options;

				}

				$(selectorOptions).html(Mustache.render($('#tmplOption3').html(), data ));
				$(selectorOptions).sortable();
				break;
			case Question.Type.MergeCol:
				$(selectorOptions).html(Mustache.render($('#tmplOption4').html(), data ));

				var input = {
					'localization': {

					},
					'options': {
						"byName" : true,
						"lineStyle":"square-ends",
						"effectHover": "on"
					},
					'listA': {
						'name': 'Cột A',
						'list': data.question_options.source
					},
					'listB': {
						'name': 'Cột B',
						'list': data.question_options.target
					}
				};

				if(typeof response !== 'undefined')
				{
					input.existingLinks = response;
				}

				var self = this;
				setTimeout(function(){
					$(selectorOptions).find('.load-linker').hide();
					self.plugin = $(selectorOptions).find('.linker').fieldsLinker('init', input);
					com.wiris.js.JsPluginViewer.parseElement($('#divQuestion')[0], true, function(){});
				}, 500);
				break;
			case Question.Type.Group:
				$(selectorOptions).html(Mustache.render($('#tmplOption5').html(), data ));
				$(selectorOptions).find('.droppable').droppable({
					drop: function(e, ui){
						ui.draggable.attr("data-drop", $(e.target).attr('data-id'));
					},
					over: function(e, ui){
						ui.draggable.attr("data-drop", "");
					}
				});
				$(selectorOptions).find('.draggable').draggable();

				if(typeof response !== 'undefined')
				{
					var positionDrop = {};

					$('.droppable').each(function(i, drop){
						positionDrop[$(drop).data('id')] = $(drop).offset();
					});

					response.forEach(function(option){

						option.data.forEach(function(item){

							$('.draggable').each(function(i, drag){

								if($(drag).text().trim() == item && ($(drag).data('drop') == '' || $(drag).data('drop') == undefined))
								{
									$(drag).attr('data-drop', option.id);
									var offset = positionDrop[option.id];
									var top = offset.top + 30 + Math.random() * 25;
									var left = offset.left + Math.random() *25

									$(drag).offset({top: top, left: left});
								}

							});

						});

					});
				}

				break;
			case Question.Type.ShortText:
				$(selectorOptions).html(Mustache.render($('#tmplOption6').html(), data ));

				if(typeof response !== 'undefined')
				{
					$('input').val(response);
				}

				break;
			case Question.Type.Paragraph:
				$(selectorOptions).html(Mustache.render($('#tmplOption7').html(), data ));

				if(typeof response !== 'undefined')
				{
					$('textarea').val(response);
				}

				tinyMCE.init({
					selector: '#textarea-' + this.question_id,
					images_upload_url: urlResult + '/upload',
					images_upload_handler: function (blobInfo, success, failure) {

				    	var formData = new FormData();
				    	formData.append('file', blobInfo.blob(), blobInfo.filename());
				    	formData.append('result_id', result_id);

				    	$.ajax({
				    		url: urlResult + '/upload',
				    		data: formData,
				    		processData: false,
				    		contentType: false,
							type: 'POST',
							success: function(data){

								if(data.ok == 1)
							    	success(urlResult + '/download?result_id=' + data.result_id + '&name=' + data.file.name);
							    else
							    	failure(data.message);
							}
				    	});
				    }
				});
				break;
			case Question.Type.FillWord:
				$(selectorOptions).html(Mustache.render($('#tmplOption8').html(), data ));

				if(typeof response !== 'undefined')
				{
					response.forEach(function(item){
						$('tr[data-id=' + item.id + ']').find('input').each(function(i, input){

							$(input).val(item.data[i]);

						});
					});
				}

				break;
			case Question.Type.File:
				$(selectorOptions).html(Mustache.render($('#tmplOption9').html(), data ));

				var self = this;

				this.plugin = $(selectorOptions + ' .upload').uploadFile({
					url: urlResult + '/upload',
					dragDropSt: 'Kẻo thả file để tải lên',
					uploadStr: 'Tải file lên',
					formData: { 'result_id': result_id },
					returnType: 'json',
					showDelete: true,
					maxFileSize: this.question_settings.file_size * 1024 * 1024,
					maxFileCount: this.question_settings.file_count,
					allowedTypes: this.question_settings.file_type,
					showDownload: true,
					onLoad:function(obj)
				    {
				    	$('#load-file').show();
				    	setTimeout(function(){
				    		if(typeof response !== 'undefined')
							{
								response.forEach(function(item){
									obj.createProgress(item.file.name, item.file.path, item.file.size);
								});
							}
							$('#load-file').hide();
				    	}, 500);
				    },
					deleteCallback: function (data, pd) {

						var filename;
						if(Array.isArray(data))
						{
							filename = data[0];
						}
						else
						{
							filename = data.file.name;
						}

				        $.post(urlResult + '/upload', {'action': 'delete', 'result_id': result_id, 'name': filename},
				            function (resp,textStatus, jqXHR) {

				            	list_file_upload_delete.push(filename);

				            	if(resp.ok == 1)
				                	alert("File Deleted");
				                else
				                	alert(resp.message);
				            });

				        var response = listResponse[self.question_id];
				        if(typeof response !== 'undefined')
				        {
				        	response = response.filter(function(item){

				        		return item.file.name != filename;

				        	});
				        }

					    pd.statusbar.hide();
					},
					downloadCallback:function(data,pd)
					{
						var filename;
						if(Array.isArray(data))
						{
							filename = data[0];
						}
						else
						{
							filename = data.file.name;
						}

						window.open(urlResult + '/download?result_id=' + result_id + '&name=' + filename);
					}
				});
				break;
		}
	}

	function startTime()
	{
		time_left -= time_offset;

		interval_time = setInterval(function(){

			time_left--;

			if(time_left <= 0)
			{
				clearInterval(interval_time);
				$.notify({message: "Hết thời gian làm bài!", icon: 'fa fa-exclamation-circle'}, {type: 'warning'});
				submit();
			}

			$('#time .progress-bar').css('width', (time_left/((time_limit)*60))*100 + '%');

			$('#time_span').text(timeToString(time_left));

			//if(typeof preview !== "undefined") clearInterval(interval_time);

		}, 1000);
	}

	function timeToString(time)
	{
		var h = Math.floor(time / 3600);
		var m = Math.floor((time % 3600) / 60);
		var s = (time % 3600) % 60;

		return h + ':' + (m > 9 ? m : '0' + m) + ':' + (s > 9 ? s : '0' + s);
	}

	function afterStart(){

		$(window).on('beforeunload', function()
		{
			var msg = "Bạn có muốn tải lại trang?";
			return msg;
		});

		$('#divTest').hide();
		$('#divAnswer').show();

		if(time_limit > 0)
		{
			$('#time').show();
			startTime();
		}

		$('#btnSubmit').click(function(){

			function handleSubmit()
			{
				$('#modalCheck').modal('show');

				if($('.pagination-scroll .active').length == listQuestion.length)
					$('#modalCheck #message_not_done').hide();
				else
					$('#modalCheck #message_not_done').show();

				$('#modalCheck .btnConfirm').off('click');
				$('#modalCheck .btnConfirm').on('click', function(){
					submit();
				});
			}

			var question = listQuestion[listQuestion.length - 1];
			getResponse(question);
			
			var response = listResponse[question.question_id];

			if(typeof response === 'undefined')
				response = null;

			if(typeof preview === 'undefined')
			{
				var notify_submit_answer = $.notify({message: 'Đang nộp đáp án', icon: 'fa fa-spinner'}, {type: 'info'});

				$.ajax({
				url: urlTest + "/submit",
				data: JSON.stringify({
					test_id: $('input[name=test_id]').val(), 
					question_id: question.question_id, 
					result_id: result_id,
					response: response
				}),
				dataType: 'json',
				method: 'post',
				success: function(result){
						if(result.ok == 1)
						{

							notify_submit_answer.close();
							handleSubmit();
							
						}
						else
						{
							$.notify({message: 'Lỗi nộp đáp án', icon: 'fa fa-exclamation-circle'}, {type: 'danger'});
						}
					}
				});
			}
			else
			{
				handleSubmit();
			}
		});

		var li = '';
		listQuestion.forEach(function(q, i){
			li += "<li data-id='" + q.question_id + "'' id='li-q" + q.index +"'><a href='#'>" + q.index + "</a></li>";
		});

		renderQuestion(listQuestion[0]);

		$('.input_fill_word').blur(function(e){

			var length = $(e.target).val().length;

			if(length >= 7)
				$(e.target).css('width', length * 9 + 'px');
			else
				$(e.target).css('width', '70px');

		});

		$('.pagination-scroll .pagination').html(li);
		$('.pagination-scroll .pagination li').on('click', function(){
			var i = parseInt($(this).text());
			i--;

			if(i == index_primary) return;

			var question = listQuestion[index_primary];
			getResponse(question);
			
			var response = listResponse[question.question_id];

			if(typeof response === 'undefined')
				response = null;

			if(typeof preview === 'undefined')
			{

				var notify_submit_answer = $.notify({message: 'Đang nộp đáp án', icon: 'fa fa-spinner'}, {type: 'info'});

				$.ajax({
				url: urlTest + "/submit",
				data: JSON.stringify({
					test_id: $('input[name=test_id]').val(), 
					question_id: question.question_id, 
					result_id: result_id,
					response: response
				}),
				dataType: 'json',
				method: 'post',
				success: function(result){
						if(result.ok == 1)
						{

							notify_submit_answer.close();

							renderQuestion(listQuestion[i]);
							index_primary = i;

							if(i + 1 == listQuestion.length)
							{
								$('#divSubmit').show();
							}
							else
							{
								$('#divSubmit').hide();
							}
						}
						else
						{
							$.notify({message: 'Lỗi nộp đáp án', icon: 'fa fa-exclamation-circle'}, {type: 'danger'});
						}
					}
				});
			}
			else
			{
				renderQuestion(listQuestion[i]);
				index_primary = i;

				if(i + 1 == listQuestion.length)
				{
					$('#divSubmit').show();
				}
				else
				{
					$('#divSubmit').hide();
				}
			}

		});

		window.scrollTo(0, 0);
	}

	function resumeResult(){

		getListQuestion().then(function(){

			var notify_resume = $.notify({message: 'Đang tải các câu trả lời', icon: 'fa fa-spinner'}, {type: 'info'});

			$.ajax({
			url: urlTest + "/resume",
			method: 'post',
			data: { result_id: result_id },
			success: function(result)
			{
				if(result.ok == 1)
				{
					listResponse = result.data;
					afterStart();

					for(var key in listResponse)
					{
						$('.pagination li[data-id=' + key +']').addClass('active');
					}

					notify_resume.close();
				}
				else
				{
					$.notify({message: result.msg, icon: 'fa fa-exclamation-circle'}, {type: 'danger'});
				}
			},
			error: function(){
					$.notify({message: "Đã xảy ra lỗi", icon: 'fa fa-info-circle'}, {type: 'danger'});
				}
			});

		});

	};

	function getListQuestion()
	{

		var notify_load_question = $.notify({icon: 'fa fa-spinner fa-pluse', message: 'Đang tải câu hỏi'}, {type: 'info'});

		var data = {test_id: $('input[name=test_id]').val(), action: 'start'};

		if(result_id != -1)
		{
			data = {
				test_id: $('input[name=test_id]').val(),
				action: 'resume',
				result_id: result_id
			};
		}

		return $.ajax({
				url: urlQuestion + '/getlist',
				data: JSON.stringify(data),
				dataType: 'json',
				method: 'post',
				success: function(res){

				let i = 1;

				res.questions.forEach(function(q){
					q.index = i++;
				});

				listQuestion = res.questions.map(function(question){
					return new Question(question);
				});

				if(res.questions.length == 0)
				{
					notify_load_question.close();
					$.notify({message: "Đề thi không có câu hỏi nào", icon: 'fa fa-info-circle'}, {type: 'info'});
					return;
				}
				
			},
			error: function(){
				$.notify({message: "Đã xảy ra lỗi", icon: 'fa fa-info-circle'}, {type: 'danger'});
			}
		});
	}

	function renderQuestion(q)
	{
		var tmpl = $('#template').html();
		q.total_q = listQuestion.length;
		tinyMCE.remove();
		$('#divQuestion').html(Mustache.render(tmpl, q));
		q.renderOptions();
		com.wiris.js.JsPluginViewer.parseElement($('#divQuestion')[0], true, function(){});
	}

	function getResponse(q)
	{
		var index = q.question_id;
		var selectorOptions = '#table-option-' + q.question_id + ' tbody';
		q.done = false;
		switch(parseInt(q.question_type_id))
		{
			case Question.Type.Check:
				var arr = [];
				$(selectorOptions).find('input[type=radio]:checked').each(function(i, v){
					arr.push(parseInt($(v).attr('data-id')));
				});
				listResponse[index] = arr;

				if(listResponse[index].length > 0)
					q.done = true;

				break;
			case Question.Type.MultiCheck:
				var arr = [];
				$(selectorOptions).find('input[type=checkbox]:checked').each(function(i, v){
					arr.push(parseInt($(v).attr('data-id')));
				});
				listResponse[index] = arr;

				if(listResponse[index].length > 0)
					q.done = true;

				break;
			case Question.Type.Sort:
				listResponse[index] = $(selectorOptions).sortable('toArray', { attribute: 'data-id' });
				listResponse[index] = listResponse[index].map(function(item){ return parseInt(item) });
				if(listResponse[index].length > 0)
					q.done = true;

				break;
			case Question.Type.MergeCol:
				listResponse[index] = q.plugin.fieldsLinker("getLinks").links;

				if(listResponse[index].length > 0)
					q.done = true;

				break;
			case Question.Type.Group:
				listResponse[index] = [];
				$(selectorOptions).find('.droppable').each(function(i, drop){

					var arr = [];
					var id = $(drop).attr('data-id');

					$(selectorOptions).find('.draggable').each(function(i, drag){
						if($(drag).attr('data-drop') == id)
						{
							arr.push($(drag).text().trim());
						}
					});
					listResponse[index].push({ 'id': parseInt(id), data: arr });
				});

				q.done = listResponse[index].every(function(item){ return item.data.length > 0; });

				break;
			case Question.Type.ShortText:

				listResponse[index] = $(selectorOptions).find('input').val().trim();

				if(listResponse[index].length > 0)
					q.done = true;

				break;
			case Question.Type.Paragraph:

				var body = tinyMCE.editors['textarea-' + q.question_id].getBody();
				var text = tinyMCE.trim(body.innerText || body.textContent);
				var word_count = text == "" ? 0 : text.split(/\s+/).length;

				listResponse[index] = '';

				if(word_count > q.question_settings.word_count)
				{
					$(selectorOptions).find('.alert').show();
				}
				else
				{
					$(selectorOptions).find('.alert').hide();

					if(word_count > 0)
						listResponse[index] = tinyMCE.editors['textarea-' + q.question_id].getContent();
				}

				if(listResponse[index].length > 0)
					q.done = true;

				break;
			case Question.Type.FillWord:
				listResponse[index] = [];
				$(selectorOptions).find('tr').each(function(i, tr){

					var arr = [];
					var id = $(tr).attr('data-id');

					$(tr).find('input').each(function(i_input, input){

						arr.push($(input).val().trim());

					});

					listResponse[index].push({ 'id': parseInt(id), data: arr });
				});

				q.done = listResponse[index].every(function(item){ 
					return item.data.every(function(str){ return str.length > 0; });
				});

				break;
			case Question.Type.File:

				if(typeof listResponse[index] !== 'undefined')
				{
					listResponse[index] = listResponse[index].concat(q.plugin.getResponses());
				}
				else
				{
					listResponse[index] = q.plugin.getResponses();
				}

				listResponse[index] = listResponse[index].filter(function(item){

					return list_file_upload_delete.indexOf(item.file.name) == -1;

				});

				list_file_upload_delete = [];

				if(listResponse[index].length > 0)
					q.done = true;
				break;
		}

		if(q.done)
		{
			$('.pagination-scroll').find('#li-q' + q.index).addClass('active');
		}
		else
		{
			delete listResponse[index];
			$('.pagination-scroll').find('#li-q' + q.index).removeClass('active');
		}
	}

	function submit()
	{
		console.log(listResponse);

		if(typeof preview !== 'undefined')
		{
			$.notify({message: 'Bạn đang ở chế độ xem trước', icon: 'fa fa-info-circle'}, {type: 'info'});
			return;
		}

		$.ajax({
			url: urlTest + "/submit",
			data: JSON.stringify({action: 'submit', test_id: $('input[name=test_id]').val(), result_id: result_id}),
			dataType: 'json',
			method: 'post',
			success: function(result)
			{
				if(result.ok == 1)
				{
					$.notify({message: 'Nộp bài thành công!', icon: 'fa fa-check-circle-o'}, {type: 'success'});

					$(window).off('beforeunload');

					setTimeout(function(){
						window.location.href = urlResult + '/detail/' + result_id;
					}, 500);
				}
				else
				{
					$.notify({message: result.msg, icon: 'fa fa-exclamation-circle'}, {type: 'danger'});
					if(interval_time)
						clearInterval(interval_time);
				}
			}
		});
	}

	//add event
	$('#modalResume .btnConfirm').click(function(){
		resumeResult();
	});

	$('#btnStart').click(function(e){

		if(!$('#formUser')[0].checkValidity())
		{
			$.notify({message: "Bạn chưa nhập đủ thông tin", icon: 'fa fa-info-circle'}, {type: 'danger'});
			return;
		}

		getListQuestion().then(function(){

			if(listQuestion.length == 0) return;

			if(typeof preview !== 'undefined')
			{
				afterStart();
				return;
			}

			var data = {
				fullname: $('#fullname').val(),
				information: $('#information').val(),
				questions: listQuestion.map(function(item) { return item.question_id })
			};

			$.ajax({
				url: urlTest + "/start/" + $('input[name=test_id]').val(),
				method: 'post',
				data: JSON.stringify(data),
				success: function(result)
				{
					if(result.ok == 1)
					{
						result_id = result.result_id;
						afterStart();
					}
					else
					{
						$.notify({message: result.msg, icon: 'fa fa-exclamation-circle'}, {type: 'danger'});
					}
				},
				error: function(){
					$.notify({message: "Đã xảy ra lỗi", icon: 'fa fa-info-circle'}, {type: 'danger'});
				}
			});

		});
	});

});
