<?php
namespace Application\Module\Admin;

use Application\Module\Base;
use Classes\Paging;

class Products extends Base
{
	public function doEditCategory()
	{
		$categoryId     = $this->application->request->getPostParam('categoryId', 0);
		$parentId       = $this->application->request->getPostParam('parentId', 0);
		$title          = $this->application->request->getPostParam('title', '');
		if(!trim($title))
		{
			throw new \Exception('too small title');
		}
		$delete         = $this->application->request->getPostParam('delete', 0);

		if($delete)
		{
			$this->application->bll->products->deleteById($categoryId);
			$this->application->request->redirect($this->application->routing->getUrl('admin/menu/'));
			return;
		}

		if(!$categoryId)
		{
			$categoryId = $this->application->bll->products->insert(
				$categoryId,
				$parentId,
				$title
			);
		}
		else
		{
			$categoryId = $this->application->bll->products->update(
				$categoryId,
				$parentId,
				$title
			);
		}

		$this->application->request->redirect($this->application->routing->getUrl('admin/menu/edit?categoryId=' . $categoryId));
	}

	public function actionListAdmin()
	{
		$categories = $this->application->bll->products->getAllCategories();
		$data = array(
			'categories' => $categories
		);
		return $data;
	}

	public function doEditProduct()
	{
		$categoryId = $this->application->request->getPostParam('categoryId');
		$productId  = $this->application->request->getPostParam('productId');
		$delete     = $this->application->request->getPostParam('delete', 0);

		if($delete)
		{
			$this->application->bll->products->deleteProduct($productId);
			$this->application->request->redirect($this->application->routing->getUrl('admin/menu/edit?categoryId=' . $categoryId));
			return;
		}

		$product    = $this->application->bll->products->getProductById($productId);
		$hasImage   = $product['image'];

		if(isset($_FILES['image']) && !($_FILES['image']['error']) && $_FILES['image']['size'])
		{
			$oldImage   = $hasImage;
			$hasImage   = time();
		}

		$productData = array(
			'id'            => $productId,
			'categoryId'    => $categoryId,
			'title'         => $this->application->request->getPostParam('title'),
			'description'   => $this->application->request->getPostParam('description'),
			'weight'        => $this->application->request->getPostParam('weight'),
			'weight_type'   => $this->application->request->getPostParam('weight_type'),
			'price'         => $this->application->request->getPostParam('price'),
			'image'         => $hasImage ? $hasImage : 0
		);

		$productId = $this->application->bll->products->insertProduct($productData);
		if(!$productId)
		{
			$productId = $product['id'];
		}

		if(isset($_FILES['image']) && !($_FILES['image']['error']) && $_FILES['image']['size'])
		{
			$this->application->bll->products->removeProductImage($productId, $categoryId, $oldImage);
			$this->application->bll->products->addProductImage($productId, $categoryId, $hasImage, $_FILES['image']['tmp_name']);
		}


		$this->application->request->redirect($this->application->routing->getUrl('admin/menu/products/edit?categoryId=' . $categoryId .'&productId=' . $productId));
	}

	public function actionListCategoryProducts()
	{
		$categoryId     = $this->application->request->getQueryParam('categoryId');
		$category       = $this->application->bll->products->getCategoryById($categoryId);
		$totalCount     = $this->application->bll->products->getCategoryProductsCount($categoryId);
		$paging         = new Paging(
			$this->application->request->getQueryParam('p', 1),
			9,
			$totalCount,
			$this->application
		);

		$products       = $this->application->bll->products->getCategoryProducts($categoryId, $paging->getOffset(), $paging->getLimit());

		foreach($products as &$product)
		{
			$product['editUrl']         = $this->application->routing->getUrl('admin/menu/products/edit?categoryId=' . $categoryId .'&productId=' . $product['id']);
			$product['deleteUrl']       = '#';
			$product['price_compiled']  = sprintf('%2.2f', $product['price']) . ' руб.';
			$product['weight_compiled'] = sprintf('%d', $product['weight']) . ' гр.';
			$product['image_url'] = '/static/upload/' .  $categoryId .'/'. $product['id'] % 100 .'/'. $product['image'] % 100 .'/'. $product['image'] . '_small.jpg';
		}
		unset($product);

		$data = array(
			'categoryId'    => $categoryId,
			'category'      => $category,
			'products'      => $products,
			'addUrl'        => $this->application->routing->getUrl('admin/menu/products/edit?categoryId=' . $categoryId .'&productId=0'),
			'paging'        => $paging->getPages()
		);
		return $data;
	}

	public function actionListCategories()
	{
		$categories = $this->application->bll->products->getAllCategories();
		foreach($categories as &$category)
		{
			$category['url'] = $this->application->routing->getUrl('admin/menu/edit?categoryId=' . $category['id']);
		}
		unset($category);
		$data['categories']     = $categories;
		$data['categoryId']     = $this->application->request->getQueryParam('categoryId', 0);
		$data['addUrl']         = $this->application->routing->getUrl('admin/menu/edit?categoryId=0');
		return $data;
	}

	public function actionEditProduct()
	{
		$categoryId     = $this->application->request->getQueryParam('categoryId');
		$productId      = $this->application->request->getQueryParam('productId');
		$category       = $this->application->bll->products->getCategoryById($categoryId);
		$product        = $this->application->bll->products->getProductById($productId);

		if($product && $product['image'])
		{
			$product['image_url'] = '/static/upload/' . $categoryId . '/' . $productId % 100 . '/' . $product['image'] % 100 . '/' . $product['image'] . '_small.jpg';
		}

		$data = array(
			'categoryId'    => $categoryId,
			'category'      => $category,
			'product'       => $product
		);
		return $data;
	}


	public function actionEditCategory()
	{
		$categoryId     = $this->application->request->getQueryParam('categoryId');
		$parentId       = $this->application->request->getQueryParam('parentId', 0);
		$category       = $this->application->bll->products->getCategoryById($categoryId);

		$data = array(
			'categoryId'    => $categoryId,
			'parentId'      => $parentId,
			'category'      => $category,
		);
		return $data;
	}
}