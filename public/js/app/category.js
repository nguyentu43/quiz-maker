$().ready(function(){

	function getData()
	{
		$.ajax({
			url: url,
			data: JSON.stringify({ action: 'getlist' }),
			dataType: 'json',
			method: 'post',
			success: function(res){

				$('#table_category tbody').html('');

				res.data.forEach(function(ct, i){

					var tr = '<tr><td>{{i}}</td><td class="category-name">{{category_name}}</td><td>{{question_count}}</td><td><input type="hidden" name="category_id" value="{{category_id}}"/><div class="btn-group"><button type="button" class="btn btn-success btn-sm btnEdit">Sửa</button><button type="button" class="btn btn-danger btn-sm btnDelete">Xoá</button></div><input type="hidden" name="user_id" value="{{user_id}}"</td></tr>';

					if(typeof admin !== 'undefined')
						tr = '<tr><td>{{i}}</td><td class="category-name">{{category_name}}</td><td>{{question_count}}</td><td>{{fullname}}</td><td><input type="hidden" name="category_id" value="{{category_id}}"/><div class="btn-group"><button type="button" class="btn btn-success btn-sm btnEdit">Sửa</button><button type="button" class="btn btn-danger btn-sm btnDelete">Xoá</button></div><input type="hidden" name="user_id" value="{{user_id}}"</td></tr>'
					ct['i'] = i + 1;

					$('#table_category tbody').append(Mustache.render(tr, ct));

				});



				$('#table_category .btnDelete').click(function(e){

					var index = $('#table_category .btnDelete').index(this);

					var category_id = $('#table_category tr input[name=category_id]').eq(index).val();

					$('#modalDelete').modal('show');

					$('#modalDelete .btnConfirm').unbind('click');

					$('#modalDelete .btnConfirm').click(function(e){

						$.ajax({
							url: url,
							data: JSON.stringify({action: 'delete', category_id: category_id}),
							dataType: 'json',
							method: 'post',
							success: function(result)
							{
								if(result.ok == 0) {
									$.notify({message: result.msg, icon: 'fa fa-exclamation-circle'}, {type:'danger'});
									$('#modalDelete').modal('hide');
									return;
								}

								$.notify({message: "Đã xoá thể loại", icon: 'fa fa-check-circle-o'}, {type:'success'});
								getData();
								$('#modalDelete').modal('hide');
							}
						});

					});

				});

				$('#table_category .btnEdit').click(function(e){

					$('#table_category .btnCancel').trigger('click');

					var index = $('#table_category .btnEdit').index(this);

					var category_id = $('#table_category tr input[name=category_id]').eq(index).val();

					var category_name = $('#table_category .category-name').eq(index).text();

					var td = '<div class="col-sm-5"><input type="text" name="category_name" class="form-control" value="' + category_name + '"></div><div class="col-sm-7"><div class="btn-group"><button type="button" class="btn btn-success btn-sm btnOK">OK</button><button type="button" class="btn btn-default btn-sm btnCancel">Huỷ</button></div</div>';

					$('#table_category .category-name').eq(index).html(td);

					$('#table_category .category-name .btnOK').click(function(e){

						category_name = $('#table_category .category-name input').val();

						if(category_name == '')
						{
							alert('Chưa nhập');
							return;
						}

						var data = {action: 'update', data: { category_id: category_id, category_name: category_name }};

						if(typeof admin !== 'undefined')
							data['data']['user_id'] = $('[name=user_id]').eq(index).val();

						$.ajax({
							url: url,
							data: JSON.stringify(data),
							dataType: 'json',
							method: 'post',
							success: function(res){
								
								if(res.ok == 0) return;

								$.notify({message: "Đã cập nhật thể loại", icon: 'fa fa-check-circle-o'}, {type:'success'});

								$('#table_category .category-name').eq(index).text(category_name);
							}
						});

					});

					$('#table_category .category-name .btnCancel').click(function(e){

						$('#table_category .category-name').eq(index).text(category_name);

					});
				});

				$('#btnAdd').click(function(e){

					var category_name = "";

					var td = '<div class="col-sm-5"><input type="text" name="category_name" class="form-control" value="' + category_name + '" required="required"></div><div class="col-sm-7"><div class="btn-group"><button type="button" class="btn btn-success btn-sm btnOK">OK</button><button type="button" class="btn btn-default btn-sm btnCancel">Huỷ</button></div></div>';
					var i = $('#table_category tbody tr').length;

					i++;

					if(typeof admin !== 'undefined')
						$('#table_category tbody').append('<tr><td>' + i +'</td><td class="category-name">' + td +'</td><td>0</td><td></td><td>' + users + '</td></tr>');
	
					else
						$('#table_category tbody').append('<tr><td>' + i +'</td><td class="category-name">' + td +'</td><td>0</td><td></td></tr>');


					var tr = $('#table_category tbody tr').eq(i-1);

					tr.find('.btnOK').click(function(e){

						category_name = tr.find('input').val();

						if(category_name == '')
						{
							alert('Chưa nhập');
							return;
						}

						var data = {action: 'insert', data: { category_name: category_name }};

						if(typeof admin !== 'undefined')
						{
							if($('select').val() == '')
							{
								$.notify({message: 'Bạn chưa chọn tài khoản'}, {type: 'warning'});
								return;
							}
							else
							{
								data['data']['user_id'] = $('select').val();
							}

							$.ajax({
								url: url,
								data: JSON.stringify(data),
								dataType: 'json',
								method: 'post',
								success: function(res){

									if(res.ok)
									{
										$.notify({message: "Đã thêm thể loại", icon: 'fa fa-check-circle-o'}, {type:'success'});
										getData();
									}
									else
										$.notify({message: res.msg, icon: 'fa fa-exclamation-circle'}, {type:'danger'});
								}
							});
						}

						$.ajax({
							url: url,
							data: JSON.stringify({action: 'insert', data: { category_name: category_name }}),
							dataType: 'json',
							method: 'post',
							success: function(res){

								if(res.ok)
								{
									$.notify({message: "Đã thêm thể loại", icon: 'fa fa-check-circle-o'}, {type:'success'});
									getData();
								}
								else
									$.notify({message: res.msg, icon: 'fa fa-exclamation-circle'}, {type:'danger'});
							}
						});

					});

					tr.find('.btnCancel').click(function(e){

						tr.remove();

					});
				});
			}
		});
	}

	getData();

});