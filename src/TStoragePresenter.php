<?php

/**
 * This file is part of the ImageStorage
 *
 * Copyright (c) 2016 Tomáš Karlík (http://tomaskarlik.cz)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace TomasKarlik\Storages;

use Nette\Application\UI\ITemplate;
use TomasKarlik\Storages\FileStorage;
use TomasKarlik\Storages\ImageStorage;


/**
 * FileStorage
 * inject service to presenter
 */
trait TStoragePresenter
{

	/**
	 * @inject
	 * @var ImageStorage
	 */
	public $imageStorage;

	/**
	 * @inject
	 * @var FileStorage
	 */
	public $fileStorage;


	/**
	 * @param ITemplate $template
	 * @return ITemplate
	 */
	public function createTemplate($template = NULL)
	{
		$template = $template ? : parent::createTemplate();
		$basePath = $this->getHttpRequest()->getUrl()->getBaseUrl();

		$this->imageStorage->setBasePath($basePath);
		$template->imageStorage = $this->imageStorage;

		$this->fileStorage->setBasePath($basePath);
		$template->fileStorage = $this->fileStorage;

		return $template;
	}

}
