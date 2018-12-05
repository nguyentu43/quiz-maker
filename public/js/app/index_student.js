$().ready(function(){

	var data_table = [];
	var table = $('#table-result').DataTable({
		rowGroup: {
			dataSrc: "1",
			startRender: function(rows, group){

				var i = 0;
				for(; i<rows.count(); ++i)
				{
					var r = rows.data()[i];
					r[0] = i + 1;
					data_table.push(r);
				}

				return "Đề thi: " + group; 
			},
			endRender: function(rows, group){
				return "<div class='text-right'>Tổng số bài: " + rows.count() + "</div>";
			}
		},
		columnDefs: [
			{targets: [1], visible: false}
		],
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

	$.each($('.table tbody tr[role=row]'), function(i, r){
		table.row(this).data(data_table[i]);
	});

});