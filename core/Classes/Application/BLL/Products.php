<?php

namespace Application\BLL;
class Products extends BLL
{
	const IMAGE_SMALL       = 1;
	const IMAGE_NORMAL      = 2;
	const IMAGE_BIG         = 3;
	const IMAGE_ORIGINAL    = 4;

	private static $imageSizes = array(
		self::IMAGE_SMALL => array(
			'_small',
			100,
			100,
		),
		self::IMAGE_NORMAL => array(
			'_normal',
			300,
			300,
		),
		self::IMAGE_BIG => array(
			'_big',
			500,
			500,
		),
	);

	public function update($categoryId, $parentId, $title)
	{
		$this->getDbWeb()->query(
			'INSERT INTO `product_category` (`id`,`parent_id`,`title`) VALUES(?, ?, ?) ON DUPLICATE KEY UPDATE title = ?',
			array(
				$categoryId,
				$parentId,
				$title,
				$title
			)
		);
		return $this->getDbWeb()->lastInsertId() ? $this->getDbWeb()->lastInsertId() : $categoryId;
	}

	public function removeProductImage($productId, $categoryId, $timeStamp)
	{
		foreach(self::$imageSizes as $sizes)
		{
			$imagePath        = $this->application->urlManager->getProductImagePath($productId, $categoryId, $timeStamp, $sizes[0]);
			@unlink($imagePath);
		}
	}

	public function addProductImage($productId, $categoryId, $timeStamp, $fileName)
	{
		$size = getimagesize($fileName);

		foreach(self::$imageSizes as $sizes)
		{
			$settings = array(
				'crop_method'       => 1,
				'width'             => $size[0],
				'height'            => $size[1],
				'width_requested'   => $sizes[1],
				'height_requested'  => $sizes[2],
			);

			$imagePath  = $this->application->urlManager->getProductImagePath($productId, $categoryId, $timeStamp, $sizes[0]);
			$pathInfo   = pathinfo($imagePath);
			@mkdir($pathInfo['dirname'], 0777, true);
			$this->application->imageConverter->resize($fileName, $settings, $imagePath);
		}
	}

	public function deleteProduct($productId)
	{
		return $this->getDbWeb()->query('DELETE FROM `product` WHERE `id` = ?', array($productId));
	}

	public function insertProduct(array $data)
	{

		$this->getDbWeb()->query(
			'INSERT INTO `product` SET
				`id`=?,
				`category_id`=?,
				`title`=?,
				`description`=?,
				`weight`=?,
				`weight_type`=?,
				`price`=?,
				`image`=?
				ON DUPLICATE KEY UPDATE
				`id`=?,
				`category_id`=?,
				`title`=?,
				`description`=?,
				`weight`=?,
				`weight_type`=?,
				`price`=?,
				`image`=?
				',
				array_merge(array_values($data), array_values($data))
		);
		return $this->getDbWeb()->lastInsertId();
	}

	public function deleteById($categoryId)
	{
		return $this->getDbWeb()->query(
			'DELETE FROM `product_category` WHERE `id` = ?',
			array(
				$categoryId
			)
		);
	}

	public function getCategoryProducts($categoryId, $offset = 0, $limit = 100)
	{
		return $this->getDbWeb()->selectAll(
			'SELECT * FROM `product` WHERE `category_id` = ? ORDER BY `id` LIMIT ?, ?',
			array(
				$categoryId,
				$offset,
				$limit
			)
		);
	}

	public function getCategoryProductsCount($categoryId)
	{
		return $this->getDbWeb()->selectSingle(
			'SELECT COUNT(1) FROM `product` WHERE `category_id` = ?',
			array(
				$categoryId
			)
		);
	}

	public function getCategoryById($categoryId)
	{
		return $this->getDbWeb()->selectRow(
			'SELECT * FROM `product_category` WHERE `id` = ?',
			array(
				$categoryId
			)
		);
	}

	public function getProductById($productId)
	{
		return $this->getDbWeb()->selectRow(
			'SELECT * FROM `product` WHERE `id` = ?',
			array(
				$productId
			)
		);
	}


	public function insert($categoryId, $parentId, $title)
	{
		$this->getDbWeb()->query(
			'INSERT INTO `product_category` (`id`,`parent_id`,`title`) VALUES(?, ?, ?)',
			array(
				$categoryId,
				$parentId,
				$title,
			)
		);
		return $this->getDbWeb()->lastInsertId();
	}

	public function getAllCategories()
	{
		return $this->getDbWeb()->selectAll('SELECT * FROM `product_category`');
	}
}