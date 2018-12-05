$().ready(function(){

	var questions = [];

	var questionType = [];
	var categories = [];
	var inTest = $('form[name=formTest]').length > 0;
	var addNewCategory = false;

	var ctlPage = {
		page: 1,
		per_page: 10,
		total_page: 1
	};

	var tagifyOptions = {
		delimiters: ';',
		duplicates: false
	}

	tinyMCE.defaultSettings.plugins += ' responsivefilemanager';
	tinyMCE.defaultSettings.toolbar1 += ' responsivefilemanager';
	tinyMCE.defaultSettings.external_filemanager_path = "/filemanager/";
	tinyMCE.defaultSettings.filemanager_title = "Responsive Filemanager";
	tinyMCE.defaultSettings.external_plugins = { "filemanager" : "/filemanager/plugin.min.js"};

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

		var q = this;

		categories.forEach(function(item){
			if(item.category_id == q.category_id)
			{
				q.category_name = item.category_name;
				return;
			}
		});

		questionType.forEach(function(item){
			if(item.question_type_id == q.question_type_id)
			{
				q.question_type_name = item.question_type_name;
				return;
			}
		});
	}

	Question.prototype.toJSON = function(){

		return JSON.stringify({ question: {
				question_id: this.question_id,
				category_id: this.category_id,
				user_id: this.user_id,
				question_type_id: this.question_type_id,
				question_text: this.question_text,
				question_options: this.question_options,
				question_settings: this.question_settings
			}
		});

	};

	Question.prototype.indexOptions = function()
	{
		this.question_options.forEach(function(op, i){
			op.index = String.fromCharCode(i + 65);
		});
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

	Question.DefaultSetting = {
		point: 1,
		feedback: '',
		word_count: 400,
		file_type: '*',
		file_size: 4,
		file_count: 1
	}

	Question.create = function()
	{
		var q = {
			'index': questions.length + 1,
			category_id: -1,
			question_text: 'Nhập nội dung câu hỏi',
			question_type_id: Question.Type.Check,
			question_options: [
				{ id: 1, index: 'A', option_text: '', is_correct: false},
				{ id: 2, index: 'B', option_text: '', is_correct: false},
				{ id: 3, index: 'C', option_text: '', is_correct: false},
				{ id: 4, index: 'D', option_text: '', is_correct: false}
			],
			question_settings: { point: 1, feedback: 'Phản hồi trả lời câu hỏi' }
		};

		return new Question(q);
	}

	Question.prototype.changeType = function(){

		this.question_settings = {};

		if(this.checkType(Question.Type.Check) || this.checkType(Question.Type.MultiCheck))
		{
			this.question_options = this.question_options.map(function(item, i){

				if(item.is_correct !== 'undefined')
				{
					return item;
				}
				else
				{
					return { option_text: '', id: i, is_correct: false };
				}
			});

			return;
		}

		this.question_options = [];

		if(this.checkType(Question.Type.Sort) || this.checkType(Question.Type.ShortText))
		{

			for(var i=0; i<4; ++i)
				this.question_options.push({
					id: i,
					option_text: ''
				});
		}

		if(this.checkType(Question.Type.FillWord))
		{
			for(var i=0; i<4; ++i)
				this.question_options.push({
					id: i,
					option_text: '',
					fill_words: []
				});
		}

		if(this.checkType(Question.Type.Group))
		{

			for(var i=0; i<4; ++i)
				this.question_options.push({
					id: i,
					group_text: '',
					group_items: ''
				});
		}

		if(this.checkType(Question.Type.MergeCol))
		{
			for(var i=0; i<4; ++i)
				this.question_options.push({
					id: i + i*2,
					source: { id: i + i*2 + 1, data: '' },
					target: { id: i + i*2 + 2, data: ''}
				});
		}

		this.indexOptions();
	}

	Question.prototype.addOption = function()
	{

		var maxIndex = -1;

		this.question_options.forEach(function(option){

			maxIndex = maxIndex < option.id ? option.id : maxIndex;

		});

		maxIndex++;

		if(this.checkType(Question.Type.Sort) || this.checkType(Question.Type.ShortText))
		{

			this.question_options.push({
				id: maxIndex,
				option_text: '',
			});
		}

		if(this.checkType(Question.Type.FillWord))
		{
			this.question_options.push({
				id: maxIndex,
				option_text: '',
				fill_words: []
			});
		}

		if(this.checkType(Question.Type.Group))
		{

			this.question_options.push({
				id: maxIndex,
				group_text: '',
				group_items: ''
			});
		}

		if(this.checkType(Question.Type.Check) || this.checkType(Question.Type.MultiCheck))
		{
			this.question_options.push({
				id: maxIndex,
				option_text: '',
				is_correct: false
			});
		}

		if(this.checkType(Question.Type.MergeCol))
		{
			this.question_options.push({
				id: maxIndex + maxIndex * 2,
				source: { id: maxIndex + maxIndex * 2 + 1, data: '' },
				target: { id: maxIndex + maxIndex * 2 + 2, data: '' }
			});
		}	
	}

	Question.prototype.getSetting = function()
	{
		return function(prop)
		{
			return this.question_settings[prop] || Question.DefaultSetting[prop];
		}
	}

	Question.prototype.toSettingHtml = function()
	{

		var html = '<div>Điểm: <label class="label label-primary"> ' + this.question_settings.point + 'đ</label></div>';
		html += '<div>Phản hồi: ' + (this.question_settings.feedback.length == 0 ? 'Không có' : this.question_settings.feedback) + '</div>';

		if(this.checkType(Question.Type.Paragraph))
		{
			html += '<div>Số từ: <label class="label label-primary"> ' + this.question_settings.word_count + '</label></div>';
		}

		if(this.checkType(Question.Type.File))
		{
			html += '<div>Loại file: <label class="label label-primary"> ' + this.question_settings.file_type + '</label></div>';
			html += '<div>Kích thước: <label class="label label-primary"> ' + this.question_settings.file_size + 'MB</label></div>';
			html += '<div>Số lượng file: <label class="label label-primary"> ' + this.question_settings.file_count + '</label></div>';
		}

		return html;
	}

	function init()
	{
		$.ajax({
			url: urlQuestion + "/questiontype",
			success: function(result){

				questionType = result.data;

				questionType.forEach(function(qt){
					$('select[name=filter_question_type]').append("<option value='" + qt['question_type_id'] + "'>" + qt['question_type_name'] + "</option>");
				});

				$('select[name=filter_question_type]').append("<option value='0' selected>Tất cả</option>");
			}
		}).then(function(){

			getCategory(getListQuestion);

		});

		$('input[name=search_text]').change(function(){
			filterQuestion();
		});

		$('input[name=index_question]').change(function(){

			if(parseInt($(this).val()) > 0 && parseInt($(this).val()) <= questions.length)
			{
				ctlPage.page = Math.ceil(parseInt($(this).val()) / ctlPage.per_page);
				paginateQuestion();
				location.href = '#q' + $(this).val();
			}
		});

		$("select[name^=filter]").change(function(){
			filterQuestion();
		});

		$('#menuAdd li').click(function(e){
			var item = $(this).find('a').attr('href');

			if(item == '#addNew')
			{
				createQuestion();
			}
			else if(item == '#modalAddQuestion')
			{
				modalAddQuestion();
			}
			else if(item == '#modalImportFromTest')
			{
				modalImportFromTest();
			}
			else
			{

				var url = urlQuestion + '/import';
				if(inTest)
					url += '/' + $('input[name=test_id]').val();

				window.location.href = url;
			}
		});
	}

	function getCategory(callback)
	{
		var queryCategory = {action: 'getlist'};

		if(inTest)
		{
			queryCategory.test_id = $('input[name=test_id]').val();
		}

		$('select[name=filter_category]').html('');

		$.ajax({
			url: urlQuestion + "/category",
			data: JSON.stringify(queryCategory),
			dataType: 'json',
			method: 'post',
			success: function(result){

				categories = result.data;
				categories.forEach(function(ct){
					$('select[name=filter_category]').append("<option value='" + ct['category_id'] + "'>" + ct['category_name'] + "</option>");
				});
				$('select[name=filter_category]').append("<option value='0' selected>Tất cả</option>");
			}
		})
		.then(function(){
			callback();
		});
	}

	function getListQuestion()
	{
		if(inTest)
			queryData = {'test_id': $('input[name=test_id]').val(), 'action': 'edit'};
		else
			queryData = {'action': 'getAll'};

		$.ajax({
			url: urlQuestion + "/getlist",
			data: JSON.stringify(queryData),
			dataType: 'json',
			method: 'post',
			success: function(result){

				if(!result.ok)
				{
					$.notify({message: result.msg, icon: 'fa fa-check-circle-o'}, {type:'danger'});
					return;
				}

				result.questions.sort(function(a, b){ 

					if(a.index < b.index) return -1;

					if(a.index > b.index) return 1;

					return 0;

				});

				questions = result.questions.map(function(item){ return new Question(item); });

				renderListQuestion();
			}
		});
	}

	init();

	function filterQuestion()
	{
		var qt_id = $('select[name=filter_question_type] option:selected').val();
		var ct_id = $('select[name=filter_category] option:selected').val();
		var q_text = $('input[name=search_text]').val();
		var filter_user = $('select[name=filter_user] option:selected').val();

		questions.forEach(function(q){

			if((qt_id > 0 && q['question_type_id'] != qt_id) || (ct_id > 0 && q['category_id'] != ct_id) || (q['question_text'].indexOf(q_text) == -1 && q_text.length > 0) || (filter_user > 0 && q['user_id'] != filter_user))
			{
				q.hide = true;
			}
			else
			{
				q.hide = false;
			}

		});

		renderListQuestion();
	}

	//render
	function renderListQuestion()
	{

		$('#listQuestion').html('');

		questions.forEach(function(question){
			if(!question.hide)
				renderQuestion(question);
		});

		$('.badge.number_total_question').text(questions.length);

		if(questions.length > 0 && $('.div-question').length > 0)
			renderPagination();

		if($('.div-question').length == 0)
			$('#listQuestion').append('Không có câu hỏi nào');
	}

	function renderContentQuestion(question, target)
	{
		var tmplQuestion = $('#tmplQuestion').html();
		var selectQuestion = '[data-question-id=' + question.question_id + ']';

		$(target).append(Mustache.render(tmplQuestion, question));

		var tmplOption = '';

		if(question.checkType(Question.Type.Check) || question.checkType(Question.Type.MultiCheck))
		{
			tmplOption = $('#tmplOption1').html();
		}

		if(question.checkType(Question.Type.Sort))
		{
			tmplOption = $('#tmplOption3').html();
		}

		if(question.checkType(Question.Type.MergeCol))
		{
			tmplOption = $('#tmplOption4').html();
		}

		if(question.checkType(Question.Type.Group))
		{
			tmplOption = $('#tmplOption5').html();
		}

		if(question.checkType(Question.Type.ShortText))
		{
			tmplOption = $('#tmplOption6').html();
		}

		if(question.checkType(Question.Type.FillWord))
		{
			tmplOption = $('#tmplOption8').html();

			setTimeout(function(){

				$(selectQuestion).find('.input_fill_word').each(function(i, input){

					var text = $(input).val();
					var width = text.length > 7 ? text.length * 9 : 70;

					$(input).css('width', width + 'px');

				});

			}, 500);
		}

		$(selectQuestion).find('.table-option tbody').html(Mustache.render(tmplOption, question));

		if(question.checkType(Question.Type.Sort))
		{
			$(selectQuestion).find('.table-option tbody').sortable({
				disabled: true
			});
		}

		if(question.checkType(Question.Type.Group))
		{
			$(selectQuestion).find('.group_items').tagify(tagifyOptions);
			$(selectQuestion).find('.tagify .tagify__input').attr('contenteditable', 'false');
			$(selectQuestion).find('.tagify x').remove();
		}

		if(question.checkType(Question.Type.FillWord))
		{
			$(selectQuestion).find('.table-option tbody tr .option-text').each(function(index, value){

				$(value).find('input').each(function(index_input, input){

					$(input).val(question.question_options[index].fill_words[index_input]);
					$(input).prop('disabled', 'disabled');

				});

			});
		}

		com.wiris.js.JsPluginViewer.parseElement($('#q' + question.index)[0], true, function(){});
	}

	function renderQuestion(question, target='#listQuestion')
	{
		renderContentQuestion(question, target);

		var selectQuestion = '[data-question-id=' + question.question_id + ']';

		if(inTest)
		{
			$(selectQuestion).find('#btn-group-' + question.index)
			.prepend('<div class="btn-group"><button href="#" class="btnMoveUp btn btn-default btn-sm"><i class="fa fa-arrow-up" aria-hidden="true"></i></button><button href="#" class="btnMoveDown btn btn-default btn-sm"><i class="fa fa-arrow-down" aria-hidden="true"></i></button></div> <a href="#" class="btnMoveTo">Di chuyển tới </a>');

			$(selectQuestion).find('.btnMoveTo').click(function(e){

				e.preventDefault();

				var value = parseInt(prompt('Nhập vị trí cần di chuyển đến'));

				if(Number.isNaN(value) || value <= 0 || value > questions.length )
				{
					alert('Nhập vị trí không hợp lệ');
					return;
				}

				var index = parseInt($(this).parent().attr('id').substr(10));

				if(value == index) return;

				$.ajax({
					url: urlTest + '/ajax',
					data: JSON.stringify({
						'action': 'swapIndexQuestion',
						'data': {
							'test_id': $('input[name=test_id]').val(),
							'question_id_a': $('#q' + index).attr('data-question-id'),
							'index_a': index,
							'question_id_b': $('#q' + value).attr('data-question-id'),
							'index_b': value
						}
					}),
					method: 'post',
					success:function()
					{
						getListQuestion();
					}
				});

			});
		}

		$(selectQuestion).find('.btnCopy, .btnAssign, .btnDelete, .btnEdit, .btnMoveUp, .btnMoveDown, .btnStatistical').click(function(e){
			e.preventDefault();

			var index;

			if($(this).hasClass('btnMoveUp') || $(this).hasClass('btnMoveDown'))
				index = parseInt($(this).parent().parent().attr('id').substr(10)) - 1;
			else
				index = parseInt($(this).parent().attr('id').substr(10)) - 1;
			
			if($(this).hasClass('btnCopy'))
			{
				copyQuestion(index);
			}
			else if($(this).hasClass('btnAssign'))
			{
				modalAssignQuestion(index);
			}
			else if($(this).hasClass('btnDelete'))
			{
				deleteQuestion(index);
			}
			else if($(this).hasClass('btnEdit'))
			{
				renderEditQuestion(index);
			}
			else if($(this).hasClass('btnMoveUp'))
			{
				index++;
				if(index == 1) return;

				$.ajax({
					url: urlTest + '/ajax',
					data: JSON.stringify({
						'action': 'swapIndexQuestion',
						'data': {
							'test_id': $('input[name=test_id]').val(),
							'question_id_a': $('#q' + index).attr('data-question-id'),
							'index_a': index,
							'question_id_b': $('#q' + (--index)).attr('data-question-id'),
							'index_b': index
						}
					}),
					method: 'post',
					success: function()
					{
						getListQuestion();
					}
				});
			}
			else if($(this).hasClass('btnMoveDown'))
			{
				index++;
				if((index + 1) == questions.length) return;
				$.ajax({
					url: urlTest + '/ajax',
					data: JSON.stringify({
						'action': 'swapIndexQuestion',
						'data': {
							'test_id': $('input[name=test_id]').val(),
							'question_id_a': $('#q' + index).attr('data-question-id'),
							'index_a': index,
							'question_id_b': $('#q' + (++index)).attr('data-question-id'),
							'index_b': index
						}
					}),
					method: 'post',
					success: function()
					{
						getListQuestion();
					}
				});
			}
			else if($(this).hasClass('btnStatistical'))
			{
				$.ajax({
					url: urlQuestion + '/ajax',
					method: 'post',
					data: JSON.stringify({ action: 'statistical', question_id: questions[index].question_id }),
					success: function(res){

						$('#modalStatistical').modal('show');

						$('#pie-chart').html('');
						$('#bar-chart').html('');

						if(res.data.total == 0)
						{
							$('#pie-chart').append('Chưa có câu trả lời nào');
							return;
						}

						google.charts.load('current', {'packages':['corechart']});
					    google.charts.setOnLoadCallback(drawChart);

					    function drawChart() {

					        var data1 = google.visualization.arrayToDataTable([
					          ['Kết quả', 'Tỉ lệ'],
					          ['Đúng',     parseInt(res.data.count)],
					          ['Sai',      parseInt(res.data.total) - parseInt(res.data.count)]
					        ]);
					        var options1 = {
					          title: 'Thống kê trả lời'
					        };
					        var chart1 = new google.visualization.PieChart(document.getElementById('pie-chart'));
					        chart1.draw(data1, options1);

					        if(res.data.detail.length == 0) return;

					        var data = [
					          ['Đáp án', 'Số lượt trả lời', { role: 'style'}, {role: 'tooltip', type: 'string', 'p': {'html': true}}]
					        ];

					        function getRandomColor() {
							  var letters = '0123456789ABCDEF';
							  var color = '#';
							  for (var i = 0; i < 6; i++) {
							    color += letters[Math.floor(Math.random() * 16)];
							  }
							  return color;
							}

					        res.data.detail.forEach(function(item){

					        	list = '<strong>Danh sách tài khoản</strong><ol>';

					        	item.list.forEach(function(user){

					        		list += '<li>' + user + '</li>';

					        	});

					        	list += '</ol>';

					        	data.push([ item.name, item.count, 'color: ' + getRandomColor(), list ]);
					        });

					        var data2 = google.visualization.arrayToDataTable(data);
					        var options2 = {
					          title: 'Chi tiết đáp án',
					          tooltip: { isHtml: true }
					        };
					        var chart2 = new google.visualization.BarChart(document.getElementById('bar-chart'));
					        chart2.draw(data2, options2);
					    }
					}
				});
			}
		});

		if(inTest)
		{
			$(selectQuestion).find('.btnAssign').hide();
			$(selectQuestion).find('.btnDelete').html('<i class="fa fa-trash" aria-hidden="true"></i> Bỏ câu hỏi khỏi đề');
		}

		if(!question.question_id)
		{
			$(selectQuestion).find('.btnAssign').hide();
			$(selectQuestion).find('.btnCopy').hide();
		}
	}

	function renderPagination()
	{
		if($('#listQuestion .pagination').length > 0)
			$('#listQuestion .pagination').remove();
		var pagination = '<div class="col-sm-12 text-center"><ul class="pagination"><li class="prev"><a href="#">&laquo;</a></li><li><a href="#">1</a></li><li class="next"><a href="#">&raquo;</a></li></ul></div>';
		$('#listQuestion').append(pagination);

		var li = '';

		var sum = 0;

		var t_max = questions.length / ctlPage.per_page;

		if($('.div-question').length < questions.length)
			t_max = $('.div-question').length / ctlPage.per_page;

		ctlPage.total_page = Math.ceil(t_max);

		if(ctlPage.page > ctlPage.total_page)
			ctlPage.page--;

		for(var i = 2; i<=ctlPage.total_page; ++i)
		{
			li += '<li><a href="#">' + i + '</a></li>';
		}

		$('#listQuestion .pagination li').eq(1).after(li);

		paginateQuestion();

		$('#listQuestion .pagination li a').click(function(e){

			e.preventDefault();

			if($(e.target).parent().hasClass('prev'))
			{
				if(ctlPage.page > 1)
				{
					ctlPage.page--;
					paginateQuestion();
				}
			}
			else if($(e.target).parent().hasClass('next'))
			{
				if(ctlPage.page < ctlPage.total_page)
				{
					ctlPage.page++;
					paginateQuestion();
				}
			}
			else
			{
				ctlPage.page = parseInt($(e.target).text());
				paginateQuestion();
			}
		});
	}

	function paginateQuestion(){

		$.each($('#listQuestion .pagination li'), function(i, li){

			if(!($(li).hasClass('prev') || $(li).hasClass('next')))
			{
				if(parseInt($(li).find('a').text()) == ctlPage.page)
				{
					if(!$(li).hasClass('active'))
						$(li).addClass('active');
				}
				else
				{
					if($(li).hasClass('active'))
						$(li).removeClass('active');
				}
			}

		});

		$.each($('.div-question'), function(i, q){

			var start = (ctlPage.page - 1) * ctlPage.per_page;
			var end = ctlPage.page * ctlPage.per_page;

			if(i >= start && i < end)
			{
				$(q).show();
			}
			else
			{
				$(q).hide();
			}

		});

		window.scrollTo(0, 0);
	}

	function renderEditQuestion(id)
	{

		var loading_edit = $.notify({message: 'Loading editor', icon: 'fa fa-spinner'}, {type: 'info'});

		if($('.table-option-edit tbody').length > 0)
		{
			$('#btnClose').trigger('click');
		}

		var q = $().extend(true, {}, questions[id]);

		var tmplEdit = $('#tmplEdit').html();
		$('#q' + (id + 1)).html(Mustache.render(tmplEdit, q));

		if(typeof admin !== 'undefined' && typeof q.question_id === 'undefined')
		{
			$('#q' + (id + 1) + ' form').append(users);
		}

		tinyMCE.init({
			selector: '#question_text'
		});

		questionType.forEach(function(qt){

			if(qt['question_type_id'] == q['question_type_id'])
				$('select[name=question_type]').append("<option value='" + qt['question_type_id'] + "' selected>" + qt['question_type_name'] + "</option>");
			else
				$('select[name=question_type]').append("<option value='" + qt['question_type_id'] + "'>" + qt['question_type_name'] + "</option>");

		});

		var data = {action: 'getlist'};

		if(typeof admin !== 'undefined' && typeof q.question_id !== 'undefined')
		{
			data['user_id'] = q.user_id;
		}

		$.ajax({
			url: urlQuestion + '/category',
			data: JSON.stringify(data),
			dataType: 'json',
			method: 'post',
			success: function(result){

				result.data.forEach(function(ct){

					if(ct['category_id'] == q['category_id'])
						$('select[name=category]').append("<option value='" + ct['category_id'] + "' selected>" + ct['category_name'] + "</option>");
					else
						$('select[name=category]').append("<option value='" + ct['category_id'] + "'>" + ct['category_name'] + "</option>");
				});

				if(q['category_id'] == -1)
					$('select[name=category]').append("<option value='-1' selected>Chọn danh mục</option>");
				else
					$('select[name=category]').append("<option value='-1'>Chọn danh mục</option>");

				$('#modalAddCategory .btnAdd').click(function(e){

					e.preventDefault();

					if($('[name=add_category]').val().trim() == "")
					{
						$.notify({message: "Bạn chưa nhập tên", icon: 'fa fa-exclamation-circle'}, {type:'warning'});
						return;
					}

					var data = {action: 'insert', data: { category_name: $('[name=add_category]').val() }};

					if(typeof admin !== 'undefined')
					{
						if(typeof q.question_id !== 'undefined')
						{
							data['data']['user_id'] = $('select[name=users]').val();
						}
						else
						{
							data['data']['user_id'] = q.user_id;
						}

						if(data['data']['user_id'] == '')
						{
							$.notify({message: "Bạn chưa chọn người tạo câu hỏi", icon: 'fa fa-exclamation-circle'}, {type:'warning'});
							return;
						}
					}

					$.ajax({
						url: urlQuestion + '/category',
						data: JSON.stringify(data),
						dataType: 'json',
						method: 'post',
						success: function(res){

							if(res.ok)
							{
								$.notify({message: "Đã thêm danh mục mới", icon: 'fa fa-check-circle-o'}, {type:'success'});
								$('select[name=category]').append("<option value='" + res.category_id + "' selected>" + $('[name=add_category]').val() + "</option>");
								addNewCategory = true;
								$('#modalAddCategory').modal('hide');
							}
							else
							{
								$.notify({message: res.msg, icon: 'fa fa-exclamation-circle'}, {type:'danger'});
							}
						}
					});
				});
			}
		});

		setTimeout(function(){
			renderEditSetting(q);
			renderEditOption(q);
		}, 500);

		
		
		//add event button, select question options
		//filter type
		$('select[name=question_type]').change(function(e){
			if(q.checkType(Question.Type.Check) || q.checkType(Question.Type.MultiCheck) || q.checkType(Question.Type.Sort))
			{
				$('.table-option-edit tbody').sortable("destroy");
			}

			q.question_type_id = $(this).val();
			q.changeType();
			renderEditSetting(q);
			renderEditOption(q);
		});

		//check before save question
		$('#btnSaveQuestion').click(function(e){

			if(!$('#form-setting')[0].checkValidity())
			{
				$.notify({message: "Bạn thiết lập cài đặt thêm chưa đúng", icon: 'fa fa-exclamation-circle'}, {placement: { from: 'top', align: 'center'}, type:'danger'});
				return;
			}

			q.question_settings.point = $('#point').val();
			q.question_settings.feedback = tinyMCE.editors['feedback'].getContent();

			if(q.checkType(Question.Type.Paragraph))
				q.question_settings.word_count = $('#word-count').val();

			if(q.checkType(Question.Type.File))
			{
				q.question_settings.file_type = $('#file-type').val();
				q.question_settings.file_size = $('#file-size').val();
			}

			if(q.checkType(Question.Type.Paragraph))
			{
				q.question_settings.word_count = $('#word-count').val();
			}

			if(q.checkType(Question.Type.File))
			{
				q.question_settings.file_type = $('#file-type').val();
				q.question_settings.file_size = $('#file-size').val();
				q.question_settings.file_count = $('#file-count').val();
			}		

			q.indexOptions();

			if(q.checkType(Question.Type.Check))
			{
				$('.table-option-edit input[type=radio]').each(function(i, radio){

					q.question_options[i].is_correct = $(radio).is(':checked');

				});
			}

			if(q.checkType(Question.Type.MultiCheck))
			{
				$('.table-option-edit input[type=checkbox]').each(function(i, checkbox){

					q.question_options[i].is_correct = $(checkbox).is(':checked');

				});
			}

			if(q.checkType(Question.Type.Check) || q.checkType(Question.Type.MultiCheck) || q.checkType(Question.Type.Sort) || q.checkType(Question.Type.FillWord))
			{
				$('.table-option-edit .option-text').each(function(index, value){
					q.question_options[index]['option_text'] = tinyMCE.editors[$(value).prop('id')].getContent();
				});
			}

			if(q.checkType(Question.Type.Group))
			{
				$('.table-option-edit .group-text').each(function(index, value){

					q.question_options[index]['group_text'] = $(value).val();
					q.question_options[index]['group_items'] = $(value).parent().find('.tagify tag').toArray().map(function(item){
						return item.title;
					}).join(';');
				});
			}

			if(q.checkType(Question.Type.ShortText))
			{
				$('.table-option-edit .option-text').each(function(index, value){
					q.question_options[index]['option_text'] = $(value).val();
				});
			}

			if(q.checkType(Question.Type.FillWord))
			{
				$.each(q.question_options, function(index, value){

					value.fill_words = [];

					value.fill_words = $(value.option_text).find('strong').toArray().map(function(i){
						return i.innerText;
					});

					value.option_text.match(/<strong>(.*?)<\/strong>/g).forEach(function(item){

						if($(item).text() != "")
						{
							value.option_text = value.option_text.replace(item, '<input class="input_fill_word"/>');
						}

					});
				});
			}

			if(q.checkType(Question.Type.MergeCol))
			{
				$('.table-option-edit .source').each(function(index, value){

					q.question_options[index]['source']['data'] = tinyMCE.editors[$(value).prop('id')].getContent();
					q.question_options[index]['target']['data'] = tinyMCE.editors[$(value).parent().parent().find('.target').prop('id')].getContent();

				});
			}
			
			q.category_id = $('select[name=category]').val();
			if(q.category_id == '-1')
			{
				$.notify({message: "Bạn chưa chọn danh mục", icon: 'fa fa-exclamation-circle'}, {placement: { from: 'top', align: 'center'}, type:'danger'});
				return;
			}

			q.question_text = tinyMCE.editors['question_text'].getContent();
			if(q.question_text == "" || !q.question_text)
			{
				$.notify({message: "Bạn chưa nhập nội dung câu hỏi", icon: 'fa fa-exclamation-circle'}, {placement: { from: 'top', align: 'center'}, type:'danger'});
				return;
			}

			if(q.question_options.length == 0 && !q.checkType(Question.Type.Paragraph) && !q.checkType(Question.Type.File))
			{
				$.notify({message: "Bạn chưa thêm đáp án cho câu hỏi", icon: 'fa fa-exclamation-circle'}, { placement: { from: 'top', align: 'center'}, type:'danger'});
				return;
			}

			if(!q.checkType(Question.Type.Paragraph) && !q.checkType(Question.Type.FillWord) && !q.checkType(Question.Type.File))
			{
				if(q.question_options.length == 1)
				{
					$.notify({message: "Đáp án cho câu hỏi phải nhiều hơn một", icon: 'fa fa-exclamation-circle'}, { placement: { from: 'top', align: 'center'}, type:'danger'});
					return;
				}
			}

			if(q.checkType(Question.Type.Check) || q.checkType(Question.Type.MultiCheck))
			{

				var sum_option_is_correct = 0;

				q.question_options.forEach(function(o){

					if(o.is_correct)
						sum_option_is_correct++;

				});

				if(sum_option_is_correct == q.question_options.length)
				{
					$.notify({message: "Bạn chọn quá nhiều đáp án đúng", icon: 'fa fa-exclamation-circle'}, {placement: { from: 'top', align: 'center'}, type:'danger'});
					return;
				}

				if(sum_option_is_correct == 0)
				{
					$.notify({message: "Bạn chưa chọn đáp án đúng", icon: 'fa fa-exclamation-circle'}, {placement: { from: 'top', align: 'center'}, type:'danger'});
					return;
				}
			}

			var empty = false;

			if(!(q.checkType(Question.Type.Paragraph) || q.checkType(Question.Type.Group)))
			{

				q.question_options.forEach(function(o){

					if(o.option_text == "")
					{
						$.notify({message: "Bạn chưa nhập nội dung lựa chọn đáp án " + o['index'], icon: 'fa fa-exclamation-circle'}, {placement: { from: 'top', align: 'center'}, type:'danger'});
						empty = true;
						return;
					}

				});

				if(empty == true) return;
			}

			if(q.checkType(Question.Type.Group))
			{
				empty = false;

				q.question_options.forEach(function(o){

					if(o.group_text == "")
					{
						$.notify({message: "Bạn chưa nhập tên nhóm " + o['index'], icon: 'fa fa-exclamation-circle'}, {placement: { from: 'top', align: 'center'}, type:'danger'});
						empty = true;
						return;
					}

					if(o.group_items == "")
					{
						$.notify({message: "Bạn chưa nhập tên thành phần nhóm " + o['index'], icon: 'fa fa-exclamation-circle'}, {placement: { from: 'top', align: 'center'}, type:'danger'});
						empty = true;
						return;
					}

				});

				if(empty == true) return;
			}

			if(q.checkType(Question.Type.FillWord))
			{
				empty = false;

				q.question_options.forEach(function(o){

					if(o.fill_words.length == 0)
					{
						$.notify({message: "Bạn chưa chọn từ điền khuyết " + o['index'], icon: 'fa fa-exclamation-circle'}, {placement: { from: 'top', align: 'center'}, type:'danger'});
						empty = true;
						return;
					}

				});

				if(empty == true) return;
			}

			if(q.checkType(Question.Type.MergeCol))
			{
				empty = false;

				q.question_options.forEach(function(o){

					if(o.source.length == 0 || o.target.length == 0)
					{
						$.notify({message: "Bạn chưa nhập đủ thông tin " + o['index'], icon: 'fa fa-exclamation-circle'}, {placement: { from: 'top', align: 'center'}, type:'danger'});
						empty = true;
						return;
					}

				});

				if(empty == true) return;
			}

			tinyMCE.remove();

			if(inTest)
				tinyMCE.init({
					selector: '#test-description'
				});

			if(!q.question_id)
			{
				insertQuestion(q, id);
			}
			else
			{
				updateQuestion(q);
			}
		});

		$('#btnClose').click(function(e){

			if(!questions[id].question_id)
			{
				deleteQuestion(id);
				return;
			}

			tinyMCE.remove();
			if(inTest)
				tinyMCE.init({
					selector: '#test-description'
				});

			renderListQuestion();

			window.location.hash = 'q' + q.index;

		});

		loading_edit.close();
	}

	function renderEditOption(q)
	{

		$('.div-option-edit').show();

		if(q.checkType(Question.Type.Paragraph) || q.checkType(Question.Type.File))
		{
			$('.div-option-edit').hide();
			return;
		}

		var html = $('#tmplEditOption' + q.question_type_id).html();

		if(q.checkType(Question.Type.FillWord))
		{
			$.each(q.question_options, function(index, value){

				$.each(value.fill_words, function(index_word, word)
				{
					value.option_text = value.option_text.replace('<input class="input_fill_word"/>', '<strong>' + word +  '</strong>');
				});
			});
		}

		$('.table-option-edit tbody').html(Mustache.render(html, q));

		if(q.checkType(Question.Type.Check) || q.checkType(Question.Type.Sort) || q.checkType(Question.Type.MultiCheck))
		{
			$('.table-option-edit tbody').sortable({ 
				placeholder: 'ui-sortable-placeholder',
				update: function(e, ui)
				{
					var options = [];

					$('.table-option-edit tbody tr').each(function(i, tr){

						q.question_options.forEach(function(option){

							if(option.index == $(tr).attr('data-id'))
								options.push($().extend(true, {}, option));
						});

					});

					q.question_options = options;
				},
				start: function (e, ui) {
				  $(ui.item).find('textarea').each(function () {
				     tinymce.execCommand('mceRemoveEditor', false, $(this).attr('id'));
				  });
				},
				stop: function (e, ui) {
				  $(ui.item).find('textarea').each(function () {
				     tinymce.execCommand('mceAddEditor', true, $(this).attr('id'));
				  });
				}
			});
		}

		tinyMCE.init({
			selector: '.table-option-edit textarea.option-text',
			entity_encoding: 'named',
			menubar: false
		});

		tinyMCE.init({
			selector: '.table-option-edit textarea.source',
			menubar: false
		});

		tinyMCE.init({
			selector: '.table-option-edit textarea.target',
			menubar: false
		});

		$('.table-option-edit .group-items').tagify(tagifyOptions);

		if(q.checkType(Question.Type.Check) || q.checkType(Question.Type.MultiCheck))
		{
			var f = false;

			q.question_options.forEach(function(op, i){

				if(!f)
				{
					if(op.is_correct)
					{
						f = true;
						$($('.table-option-edit input:radio')[i]).prop('checked', true)
					}
				}
				else
					op.is_correct = false;

			});
		}

		$('#btnAddOption').off("click");

		$('#btnAddOption').on("click", function(e){

			if(q.question_options.length > 10)
			{
				$.notify({message: 'Đáp án vượt mức cho phép. (Tối đa 10 đáp án)', icon: 'fa fa-exclamation-circle'}, {type: 'warning'});
				return;
			}

			var index = String.fromCharCode(q.question_options.length + 65);

			q.addOption();

			if(q.checkType(Question.Type.Check) || q.checkType(Question.Type.MultiCheck))
			{

				if(q.checkType(Question.Type.Check))
				{
					$('.table-option-edit').append('<tr data-id="' + index + '"><td class="text-center"><i class="fa fa-sort fa-2x" aria-hidden="true"></i> <input type="radio" name="radio' + q.index + '"/></td><td><textarea class="form-control option-text" placeholder="Nhập đáp án"></textarea></td><td style="padding-left: 5px"><button type="button" class="btn btn-danger btn-xs btnDeleteOption">Xoá</button></td></tr>');
				}
				else if(q.checkType(Question.Type.MultiCheck))
				{
					$('.table-option-edit').append('<tr data-id="' + index + '"><td class="text-center"><i class="fa fa-sort fa-2x" aria-hidden="true"></i> <input type="checkbox"/></td><td><textarea class="form-control option-text" placeholder="Nhập đáp án"></textarea></td><td style="padding-left: 5px"><button type="button" class="btn btn-danger btn-xs btnDeleteOption">Xoá</button></td></tr>');
				}
			}
			else if(q.checkType(Question.Type.Sort))
			{
				$('.table-option-edit').append('<tr data-id="' + index + '"><td class="text-center"><i class="fa fa-sort fa-2x" aria-hidden="true"></i><td><textarea class="form-control option-text" placeholder="Nhập đáp án"></textarea></td><td style="padding-left: 5px"><button type="button" class="btn btn-danger btn-xs btnDeleteOption">Xoá</button></td></tr>');
			}
			else if(q.checkType(Question.Type.FillWord))
			{
				$('.table-option-edit').append('<tr data-id="' + index + '"><td><textarea class="form-control option-text" placeholder="Nhập đáp án"></textarea></td><td style="padding-left: 5px"><button type="button" class="btn btn-danger btn-xs btnDeleteOption">Xoá</button></td></tr>');
			}
			else if(q.checkType(Question.Type.Group) || q.checkType(Question.Type.ShortText))
			{
				$('.table-option-edit').append('<tr data-id="' + index + '"><td></td><td>Tên nhóm: <input class="form-control group-text" placeholder="Nhập tên nhóm" value=""><br/>Các thành phần: <input class="group-items" placeholder="Nhập tên các thành phần" value=""></td><td style="padding-left: 5px"><button type="button" class="btn btn-danger btn-xs btnDeleteOption">Xoá</button></td></tr>')
				$('.table-option-edit .group-items:last-child').tagify(tagifyOptions);
			}
			else if(q.checkType(Question.Type.MergeCol))
			{
				$('.table-option-edit').append('<tr data-id="' + index + '" class="row"><td class="col-sm-5"><textarea class="source"></textarea></td><td><td class="col-sm-1 text-center"><i class="fa fa-long-arrow-right fa-2x"></i></td></td><td class="col-sm-5"><textarea class="target"></textarea></td><td class="col-sm-1"><button type="button" class="btn btn-danger btn-xs btnDeleteOption">Xoá</button></td></tr>')
			}

			tinyMCE.init({
				selector: '.table-option-edit textarea.source:last-child',
				menubar: false
			});

			tinyMCE.init({
				selector: '.table-option-edit textarea.target:last-child',
				menubar: false
			});

			tinyMCE.init({
				selector: '.table-option-edit textarea.option-text:last-child',
				entity_encoding: 'named',
				menubar: false
			});

			$('.table-option-edit .btnDeleteOption:last-child').click(function(e){
				deleteOption(this);
			});

		});

		function deleteOption(op)
		{
			var index = $('.table-option-edit .btnDeleteOption').index(op);
			$(op).parent().parent().remove();

			if(index < 0) return;

			q.question_options.splice(index, 1)[0];

			q.indexOptions();
		}

		$('.table-option-edit .btnDeleteOption').click(function(e){
			deleteOption(this);
		});
	}

	function renderEditSetting(q)
	{
		var tmplSetting = $('#tmplEditSetting1').html();
		$('#form-setting').html(Mustache.render(tmplSetting, q));

		if(tinyMCE.editors["feedback"])
			tinyMCE.editors["feedback"].remove();

		tinyMCE.init({
			selector: '#feedback',
			menubar: false
		});

		if(q.checkType(Question.Type.Paragraph))
		{
			$('#form-setting').append(Mustache.render($('#tmplEditSetting8').html(), q));
		}

		if(q.checkType(Question.Type.File))
		{
			$('#form-setting').append(Mustache.render($('#tmplEditSetting9').html(), q));
		}
	}

	//add action new, copy, edit, delete, assign
	function createQuestion(){

		q = Question.create();

		questions.push(q);

		tinyMCE.remove();
		if(inTest)
			tinyMCE.init({
				selector: '#test-description'
			});

		renderListQuestion();

		renderEditQuestion(questions.length - 1);

		ctlPage.page = ctlPage.total_page;
		renderPagination();
		window.location.hash = '#q' + q.index;
	}

	function insertQuestion(question, index)
	{
		var notify = $.notify({ message: 'Đang thêm câu hỏi mới', icon: 'fa fa-spinner fa-spin' }, { type: 'info' });

		$.ajax({
			url: urlQuestion + '/insert',
			data: question.toJSON(),
			dataType: 'json',
			method: 'post',
			success: function(result){

				if(result.ok != 1) {
					notify.update({ message: "Đã xảy ra lỗi thêm câu hỏi mới", icon: 'fa fa-exclamation-circle', type:'danger'});
					return;
				}

				notify.update({message: "Đã thêm một câu hỏi", icon: 'fa fa-check-circle-o', type:'success'});

				question['question_id'] = result.question_id;
				question['index'] = index + 1;

				questions[index] = new Question(question);

				if(inTest)
					assignQuestion($('input[name=test_id]').val(), index);

				if(addNewCategory)
				{
					getCategory(function(){ 
						renderListQuestion();
					});
				}
					
				renderListQuestion();

				window.location.hash = 'q' + (index + 1);

				addNewCategory = false;
			}
		});
	}

	function updateQuestion(question)
	{
		var question_update = $().extend(true, {}, question);
		var index = question_update['index'];

		var notify = $.notify({ message: 'Đang cập nhật câu hỏi ' + index, icon: 'fa fa-spinner fa-spin' }, { type: 'info' });

		$.ajax({
			url: urlQuestion + '/update',
			data: question_update.toJSON(),
			dataType: 'json',
			method: 'post',
			success: function(result){

				if(result.ok != 1) {
					notify.update({ message: "Đã xảy ra lỗi cập nhật câu hỏi", icon: 'fa fa-exclamation-circle', type:'danger'});
					return;
				}

				notify.update({ message: 'Đã cập nhật câu hỏi ' + index, icon: 'fa fa-check-circle-o', type: 'success'});

				var j = 0;

				questions[index - 1] = new Question(question_update);

				if(addNewCategory)
				{
					getCategory(function(){
						renderListQuestion();
					});
				}

				renderListQuestion();

				window.location.hash = 'q' + index;

				addNewCategory = false;
			}
		});
	}

	function copyQuestion(id)
	{
		var question = $().extend({}, questions[id]);

		if(typeof admin !== 'undefined' && !inTest)
			question['user_id'] = $('[name=admin_user_id]').val();
		delete question["question_id"];
		insertQuestion(question, questions.length);
	}

	function deleteQuestion(id)
	{
		if(!questions[id]["question_id"])
		{
			questions.splice(id, 1);

			var i = id;

			for(; i<questions.length; i++)
			{
				questions[i]['index'] = i + 1;
			}

			renderListQuestion();

			return;
		}

		if(inTest)
		{
			unassignQuestion($('input[name=test_id]').val(), id);
		}
		else
		{
			$('#modalDelete').modal('show');

			$('#modalDelete .btnConfirm').unbind('click');

			$('#modalDelete .btnConfirm').click(function(e){

				var question = questions[id];
				$.ajax({
					url: urlQuestion + '/delete',
					data: JSON.stringify({question_id: question['question_id']}),
					dataType: 'json',
					method: 'post',
					success: function(result){

						if(result.ok == 0) {
							$.notify({message: result.msg, icon: 'fa fa-exclamation-circle'}, {type:'danger'});
							$('#modalDelete').modal('hide');
							return;
						}

						$.notify({message: "Đã xoá câu hỏi " + question.index, icon: 'fa fa-check-circle-o'}, {type:'success'});

						questions.splice(id, 1);

						var i = id;

						for(; i<questions.length; i++)
						{
							questions[i]['index'] = i + 1;
						}

						renderListQuestion();

						$('#modalDelete').modal('hide');
					}
				});

			});
		}
		
	}


	function modalAddQuestion()
	{
		$('#table_question_import tbody').html('');
		$.ajax({
			url: urlQuestion + "/category",
			data: JSON.stringify({action: 'getlist'}),
			dataType: 'json',
			method: 'post',
			success: function(res)
			{
				$('#category_import').html('');

				$('#category_import').append('<option value="-1" selected>Chọn danh mục câu</option>');

				res.data.forEach(function(ct){
					$('#category_import').append('<option value=" ' + ct.category_id + '">' + ct.category_name + '</option>');
				});

				$('#category_import').append('<option value="0">Tất cả</option>');

				$('#modalAddQuestion .btnAdd').click(function(e){

					var list = [];

					list = $('input[name=select_question]:checked').map(function(){
						return $(this).val();
					}).get();

					if(list.length == 0)
					{
						return;
					}

					$.ajax({
						url: urlTest + '/ajax',
						data: JSON.stringify({action: 'addQuestion', data: {test_id: $('input[name=test_id]').val(), list: list} }),
						dataType: 'json',
						method: 'post',
						success: function(res)
						{
							$.notify({message: "Đã thêm các câu hỏi vào đề thi", icon: 'fa fa-check-circle-o'}, {type: 'success'});

							getCategory(getListQuestion);

							$('#modalAddQuestion').modal('hide');
						}
					});

				});

				$('#category_import').change(function(e){

					var category_id = $(this).val();

					$('#table_question_import tbody').html('');

					$.ajax({

						url: urlQuestion + '/getlist',
						data: JSON.stringify({ test_id: $('input[name=test_id]').val(), category_id: category_id, action: 'import_by_category'}),
						dataType: 'json',
						method: 'post',
						success: function(res){

							$('#table_question_import tbody').html('');

							if(res.questions.length == 0)
							{
								$('#table_question_import tbody').append("<tr><td>Không có câu hỏi</td></tr>");
								return;
							}

							res.questions.forEach(function(q){

								var tr = '<tr><td style="width: 40px"><input name="select_question" type="checkbox" value="{{question_id}}"/></td><td style="width: 70px">Câu {{index}}: </td><td>{{&question_text}}</td></tr>';

								$('#table_question_import tbody').append(Mustache.render(tr, q));

							});

						}

					});

				});
				
			}
		});
	}

	function assignQuestion(test_id, index)
	{
		var question = questions[index];

		$.ajax({
			url: urlTest + '/ajax',
			data: JSON.stringify({action: 'addQuestion', data:{ test_id: test_id, question_id: question.question_id}}),
			dataType:'json',
			method: 'post',
			success: function(result){

				if(!result.ok)
				{
					$.notify({message: "Đã xảy ra lỗi khi thêm câu hỏi " + question.index + " vào đề", icon: 'fa fa-exclamation-circle' }, {type: 'danger'});
					return;
				}

				$.notify({message: "Đã thêm câu hỏi " + question.index + " vào đề", icon: 'fa fa-check-circle-o'}, {type: 'success'});

			}
		});
	}

	function unassignQuestion(test_id, index)
	{
		var question = questions[index];

		$.ajax({
			url: urlTest + '/ajax',
			data: JSON.stringify({action: 'removeQuestion', data:{test_id: test_id, question_id: question["question_id"]}}),
			dataType:'json',
			method: 'post',
			success: function(result){

				if(!result.ok)
				{
					$.notify({message: "Đã xảy ra lỗi khi bỏ câu hỏi " + question.index + " khỏi đề", icon: 'fa fa-exclamation-circle'}, {type: 'danger'});
					return;
				}

				$.notify({message: "Đã bỏ câu hỏi " + question.index + " khỏi đề", icon: 'fa fa-check-circle-o'}, {type: 'success'});

				if(inTest)
				{
					questions.splice(index, 1);

					var i = index;

					for(; i<questions.length; i++)
					{
						questions[i]['index'] = i + 1;
					}

					renderListQuestion();
				}
			}
		});
	}

	function modalAssignQuestion(id)
	{
		var q = questions[id];

		$.ajax({
			url: urlTest + '/ajax',
			data: JSON.stringify({action: "getTestByQuestion", question_id: q['question_id'], user_id: q['user_id']}),
			dataType: 'json',
			method: 'post',
			success: function(res){

				$('#modalAssign .modal-body').html('<div class="panel panel-default panel-border-top"><div class="panel-body"><select id="selectTest" multiple="multiple" name="selectTest[]" class="form-control"></select></div></div>');

				res.data.forEach(function(item, i){
					var tmpl = "<option value='{{test_id}}' {{#in_test}}selected{{/in_test}}>{{test_name}}</option>";
					$('#modalAssign .modal-body select').append(Mustache.render(tmpl, item));
				});

				$('#modalAssign .modal-body select').multiSelect({
					selectableHeader: '<strong>Đề thi chưa thêm</strong>',
					selectionHeader: '<strong>Đề thi đã thêm</strong>',
					afterSelect: function(value)
					{
						assignQuestion(value[0], id);
					},
					afterDeselect: function(value)
					{
						unassignQuestion(value[0], id);
					}
				});
			}
		})
	}

	function getTestPublic(offset)
	{
		var str = $('#search_name').val();

		if(str == "") return;

		$.ajax({
			url: urlTest + '/ajax',
			method: 'post',
			data: JSON.stringify({ action: 'searchTestPublic', search_name: str, 'offset': offset}),
			dataType: 'json',
			success: function(res){
				
				$('#modalImportFromTest .listTest').html('');

				if(res.tests.length == 0) $('#modalImportFromTest .listTest').html('Không có đề thi nào phù hợp với từ khoá tìm kiếm');

				res.tests.forEach(function(value, index){

					value.getUrl = function(){
						return urlTest + '/public?user=' + value.user_id;
					};

					var tmp= '<div class="panel panel-default panel-border-top" data-test-id={{ test_id }}><div class="panel-body"><div class="test_name"><strong>{{ test_name }}</strong></div><h5>Người tạo: <a href="{{ getUrl }}" target="_blank">{{ name_create }}</a></h5><h5>Số câu: {{ question_count }}</h5>';

					panel = Mustache.render(tmp, value);

					$('#modalImportFromTest .listTest').append(panel);

				});

				$('#modalImportFromTest .listTest').append('<ul class="pager"><li><a href="#prevTest" class="prevTest">Trước</a></li><li><a href="#nextTest" class="nextTest" style="margin-left: 5px">Sau</a></li></ul>');

				if(offset == 0)
					$('#modalImportFromTest .listTest .prevTest').parent().addClass('disabled');
				else
				{
					$('#modalImportFromTest .listTest .prevTest').click(function(e){
						getTestPublic(offset - 10);
					});
				}

				if($('#modalImportFromTest .listTest .panel').length < 10)
					$('#modalImportFromTest .listTest .nextTest').parent().addClass('disabled');
				else
				{
					$('#modalImportFromTest .listTest .nextTest').click(function(e){
						getTestPublic(offset + 10);
					});
				}

				$('#modalImportFromTest .listTest .panel').css('cursor', 'pointer');

				$('#modalImportFromTest .listTest .panel').click(function(e){

					var title = 'Danh sách các câu hỏi ' +  $(this).find('.test_name').text();

					$('#modalImportFromTest .title-list').text(title);

					$.ajax({
						url: urlQuestion + '/getlist',
						method: 'post',
						data: JSON.stringify({ test_id : $(this).data('test-id'), action: 'import_by_test' }),
						dataType: 'json',
						success: function(res){
							
							var target = '#modalImportFromTest .list';

							$(target).html('');

							res.questions.forEach(function(item){
								var question = new Question(item);
								renderQuestion(question, target);

								com.wiris.js.JsPluginViewer.parseElement($('#q' + question.index)[0], true, function(){});
							});

							$(target + ' .div-question h4').each(function(index, div){

								var id = $(div).parent().parent().parent().data('question-id');
								$(div).parent()
								.prepend('<input type="checkbox" class="question_select" value="' + id + '">')

							});

							$(target + ' [id^=btn-group]').remove();
						}
					})
				});

				$('#modalImportFromTest .btnAdd').click(function(){
					var list = $('#modalImportFromTest input:checkbox:checked.question_select').map(function(){
						return this.value;
					}).get();

					$.ajax({
						url: urlQuestion + '/insert',
						data:JSON.stringify({list: list, test_id: $('input[name=test_id]').val()}),
						dataType: 'json',
						method: 'post',
						success: function(res){
							if(res.ok == 1)
							{
								$.notify({message: 'Đã thêm thành công', icon: 'fa fa-exclamation-circle'}, { type: 'success' });
								window.location.reload();
							}
							else
							{
								$.notify({message: res.msg, icon: 'fa fa-exclamation-circle'}, { type: 'danger' });
							}
						}
					});
				});
			}
		});
	}

	function modalImportFromTest()
	{
		$('#modalImportFromTest .list').html('Nhập từ khoá để tìm kiếm đề thi');
		$('#modalImportFromTest .input-group .btnSearch').click(function(e){
			if($('#search_name').val() != '')
				e.preventDefault();
			getTestPublic(0);
		});
	}
});