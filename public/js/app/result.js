$().ready(function(){

	var title = $('.title').text();

	var imageChart;

	var btnCommon = {
		title: title,
		exportOptions: {
			columns: [0, 1, 2, 3, 4, 5, 6]
		}
	};

	var data_table = [];

	var table = $('#table-result').DataTable({
		/*rowGroup: {
			dataSrc: "2",
			startRender: function(rows, group){
				var name = rows.data()[0][1];

				var i = 0;
				for(; i<rows.count(); ++i)
				{
					var r = rows.data()[i];
					r[0] = i + 1;
					data_table.push(r);
				}

				return 'Họ và tên: ' + name + ' (' + group + ')'; 
			},
			endRender: function(rows, group){
				return '<div class="text-right">Tổng số bài: ' + rows.count() + '</div>';
			}
		},*/
		paging: false,
		dom: "Bfrtip",
		buttons: [
			$.extend(true, {}, btnCommon, {
				extend: 'excelHtml5',
				customize: function(xlsx){
					var sheet = xlsx.xl.worksheets['sheet1.xml'];
					
					var avg = parseFloat($('.table tfoot tr th').eq(1).text().substring(0, $('.table tfoot tr th').eq(1).text().length - 1));
					avg = (avg / 100).toPrecision(4);

					console.log(sheet);

					var count_row = $('row', sheet).length + 1;
					var total_row = '<row r="' + count_row + '">';
					total_row += '<c t="inlineStr" r="C' + count_row +'"><is><t>Trung bình</t></is></c>';
					total_row += '<c r="D' + count_row +'" s="56"><v>' + avg + '</v></c>';
					total_row += '</row>';

					sheet.childNodes[0].childNodes[1].innerHTML += total_row;
				}
			}),
			$.extend(true, {}, btnCommon, {
				extend: 'pdfHtml5',
				customize: function(doc)
				{
					doc.content[1].table.widths = [20, '*', 'auto', 'auto', 'auto', 'auto', 'auto'];

					doc.content[1].table.body.push([{colSpan: 3, text:'Trung bình: ', style: 'tableFooter', alignment: 'right'}, {}, {}, {text: $('.table tfoot tr th').eq(1).text(), style: 'tableFooter'}, '', '', '']);

					delete doc.content[1].layout;

					doc.content.splice(1, 0, {text: 'Ngày in: ' + moment().format("DD/MM/YYYY HH:mm:ss"), alignment: 'right', margin: [0, 5, 0, 5]});

					doc.content.push({
						image: imageChart,
						fit: [400, 400],
						alignment: 'center',
						margin: [0, 10, 0, 0]
					});

					doc.footer = function(currentPage, pageCount){
						return { text: currentPage.toString() + '/' + pageCount.toString(), alignment: 'center'};
					}

					doc.styles.tableHeader = {
						bold: true,
						fontSize: 11,
						color: 'black',
						fillColor: '#BDBDBD'
					}

					doc.styles.tableFooter = {
						bold: true,
						fontSize: 11,
						color: 'black'
					}
				}
			})
		],
		footerCallback: function(row, data, start, end, display){
			var api = this.api(), data;

			function getValue(i)
			{
				var value = parseFloat(i.substring(0, i.length));
				return typeof value != "NaN" ? value: 0;
			}

			var total = 0;

			data.forEach(function(r){

				total+=getValue(r[3]);

			});

			var per = data.length > 0 ? (total/data.length).toFixed(2) + '%' : '-';

			$(api.column(3).footer()).html(per);
			$(api.column(2).footer()).html('Trung bình: ');
		},
		language: {
			'lengthMenu': 'Hiện thị _MENU_ kết quả trên bảng',
			'zeroRecords': 'Không kết quả nào',
			'info': 'Hiển thị _PAGE_ trên _PAGES_',
			'search': 'Tìm kiếm',
			'paginate': {
				"first": "Đầu",
				"last": "Cuối",
				"previous": "Trước",
				"next": "Sau"
			}
		}
	});

	/*$.each($('.table tbody tr[role=row]'), function(i, r){

		table.row(this).data(data_table[i]);

	});*/

	$('.table .group-start').next().addClass('active');

	$('.btnDeleteItem').click(function(e){

		var result_id = $(this).parent().data('result-id');
		var btn = this;

		$('#modalDelete').modal('show');

		$('#modalDelete .btnConfirm').unbind('click');

		$('#modalDelete .btnConfirm').click(function(e){

			$.ajax({
				url: urlDelete,
				data: {'delete': 'item', result_id: result_id},
				method: 'post',
				success: function(res){
					
					if(res.ok)
					{
						table.row($(btn).parents('tr')).remove().draw();
						$('#modalDelete').modal('hide');
						window.location.reload();
					}
				}
			});

		});
	});

	google.charts.load('current', {packages: ['corechart', 'bar']});
	google.charts.setOnLoadCallback(drawChart);

	function drawChart()
	{
		var data = [
			['Số câu đúng', 'Số lượng bài'],
			['0', 0]
		];

		var table_data = table.rows().data();
		var group = "";

		if(table_data.length == 0) return;

		var i = 0;
		var Max = 0;

		for(; i<table_data.length; ++i)
		{
			var item = table_data[i];
			var count = parseInt(item[4].split('/')[1]);

			if(count > Max)
				Max = count;
		}

		i = 1;

		for(; i<=Max; ++i)
		{
			data.push([i + '', 0]);
		}

		i = 0;

		for(; i<table_data.length; ++i)
		{
			var item = table_data[i];
			var count = parseInt(item[4].split('/')[0]);
			data[(count + 1) + ''][1]++;
		};

		if(data.length == 2 && data[1][1] == 0) return;

		var dataTable = new google.visualization.arrayToDataTable(data);

		var options = {
			title: 'Biểu đồ cột thống kê số câu đúng',
			bar: {groupWidth: "40%"},
			hAxis: {
				title: 'Số câu đúng'
			},
			vAxis: {
				title: 'Số lượng bài',
				gridlines: {
					count: -1
				}
			}
		}; 

		var chart = new google.visualization.ColumnChart(document.getElementById('chartColumn'));

		chart.draw(dataTable, options);

		imageChart = chart.getImageURI();
	}
});