<?php

namespace Quiz\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Quiz\Model\CategoryTest;

class CategoryController extends AbstractActionController
{
	private $authManager;
	private $categoryTable;

	public function __construct($authManager, $categoryTable)
	{
		$this->authManager = $authManager;
		$this->user = $authManager->getUser();
		$this->categoryTable = $categoryTable;
	}

	public function indexAction()
	{

		if(!$this->authManager->isGranted('category-test.manage'))
		{
			return new ViewModel(['error' => 'Bạn không có quyền truy cập']);
		}

		$request = $this->getRequest();
		$view = new ViewModel();

		if($request->isPost())
		{
			$post = $request->getPost();

			if(isset($post['btnAdd']))
			{
				$c = new CategoryTest();
				$c->exchangeArray($post);

				if($this->categoryTable->getName($post['name'])->count() > 0)
				{
					$view->setVariable('error', 'Lỗi trùng tên danh mục');
				}
				else
				{
					$this->categoryTable->insert($c);
					$view->setVariable('message', 'Tạo danh mục thành công');
				}
			}
			else if(isset($post['btnEdit']))
			{
				$c = new CategoryTest();
				$c->exchangeArray($post);
				$result = $this->categoryTable->getName($post['name']);
				if($result->count() > 0)
				{
					foreach($result as $item)
					{
						if($item->id != $post['id'])
							$view->setVariable('error', 'Lỗi trùng tên danh mục');
					}
				}

				if(!$view->getVariable('error'))
				{
					$this->categoryTable->update($c);
					$view->setVariable('message', 'Cập nhật danh mục thành công');
				}
			}
			else if(isset($post['btnDelete']))
			{
				try
				{
					$c_id = $post['id'];
					$this->categoryTable->delete($c_id);
				}
				catch(\Exception $e)
				{
					$view->setVariable('error', 'Còn tồn tại đề thi trong danh mục');
				}
				
			}
		}

		$view->setVariable('list', $this->categoryTable->getAll());

		return $view;
	}
}

?>