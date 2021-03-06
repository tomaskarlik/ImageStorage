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

use Exception;
use Nette\Http\FileUpload;
use Nette\SmartObject;
use Nette\Utils\Strings;


/**
 * File storage
 */
class FileStorage
{

	use SmartObject;


	/**
	 * @var string
	 */
	private $wwwDir;

	/**
	 * @var string
	 */
	private $wwwFilePath;

	/**
	 * @var string
	 */
	private $basePath;

	/**
	 * @var string|NULL
	 */
	private $namespace;


	/**
	 * @param string $wwwDir local web root directory
	 * @param string $path relative path to files directory
	 * @param string $basePath base path to links
	 * @param string|array $namespace
	 */
	public function __construct($wwwDir, $path, $basePath = '', $namespace = NULL)
	{
		$this->wwwDir = $wwwDir;
		$this->wwwFilePath = $path;
		$this->basePath = $basePath;

		$this->setNamespace($namespace);
	}


	/**
	 * @param mixed $name
	 * @return string
	 */
	public static function getNamespaceDescription($name)
	{
		return (is_array($name) ? implode('/', $name) : $name);
	}


	/**
	 * @param string|array $name
	 * @return self
	 */
	public function setNamespace($name)
	{
		$this->namespace = self::getNamespaceDescription($name);
		return $this;
	}


	/**
	 * @return string|NULL
	 * @throws FileStorageException
	 */
	public function getNamespace()
	{
		if (empty($this->namespace)) {
			throw new FileStorageException("No storage namespace set!");
		}
		return $this->namespace;
	}


	/**
	 * @param string $dir
	 * @return self
	 */
	public function setWebDir($dir)
	{
		$this->wwwDir = $dir;
		return $this;
	}


	/**
	 * @return string
	 */
	public function getWebDir()
	{
		return $this->wwwDir;
	}


	/**
	 * @param string $path
	 * @return self
	 */
	public function setWebFilePath($path)
	{
		$this->wwwFilePath = $path;
		return $this;
	}


	/**
	 * @return string
	 */
	public function getWebFilePath()
	{
		return $this->wwwFilePath;
	}


	/**
	 * @param string $path [basePath = http://www.domain.com]
	 * @return self
	 */
	public function setBasePath($path)
	{
		$this->basePath = rtrim($path, '/');
		return $this;
	}


	/**
	 * @return string
	 */
	public function getBasePath()
	{
		return $this->basePath;
	}


	/**
	 * @param string $val
	 * @return float|FALSE
	 * @throws FileStorageException
	 */
	public static function getBytes($val)
	{
		$val = Strings::trim(Strings::lower($val));

		$matches = NULL;
		if (preg_match("#^([0-9\.]+) ?([GMK])#i", $val, $matches)) {
			$val = (float) $matches[1];
			switch ($matches[2]) {
				case 'g':
					$val *= 1024;
				case 'm':
					$val *= 1024;
				case 'k':
					$val *= 1024;
			}

			return $val;
		}

		return FALSE;
	}


	/**
	 * Return set of upload_max_filesize
	 *
	 * @return mixed
	 */
	public static function getMaxUploadSize()
	{
		return ini_get('upload_max_filesize');
	}


	/**
	 * @param FileUpload $upload
	 * @return bool
	 */
	public static function isEmptyUpload(FileUpload $upload)
	{
		return (( ! $upload) || ($upload->error === UPLOAD_ERR_NO_FILE));
	}


	/**
	 * @param FileUpload $upload
	 * @param mixed $name
	 * @param bool $overwrite
	 * @throws FileStorageException
	 */
	public function save(FileUpload $upload, $name = NULL, $overwrite = FALSE)
	{
		if (self::isEmptyUpload($upload)) {
			return;
		}

		if ( ! $upload->isOk()) {
			throw new FileStorageException("Upload error ({$upload->error})!");
		}

		if ($name === NULL) {
			$name = $upload->getName();
		}

		$destination = $this->getFile($name);
		if (file_exists($destination) && ( ! $overwrite)) {
			throw new FileStorageException("File {$name} is allready exists!");
		}

		try {
			$upload->move($destination);

		} catch (Exception $exception) {
			throw new FileStorageException($exception->getMessage(), 0, $exception);
		}
	}


	/**
	 * @param string $from source file
	 * @param string|NULL $name storage filename
	 * @throws FileStorageException
	 */
	public function copy($from, $name = NULL)
	{
		if ( ! file_exists($from)) {
			throw new FileStorageException("File not exists!");
		}

		if ($name === NULL) { //set default name
			$name = pathinfo($from, PATHINFO_BASENAME);
		}

		$destination = $this->getFile($name);
		if (file_exists($destination)) {
			throw new FileStorageException("File {$name} is allready exists!");
		}

		if ( ! copy($from, $destination)) {
			throw new FileStorageException("Unable copy file!");
		}
	}


	/**
	 * Get file link
	 *
	 * @param mixed $namespace
	 * @param string $file
	 * @return string
	 */
	public function getFileLink($namespace, $file)
	{
		$namespace = self::getNamespaceDescription($namespace);

		return $this->getBasePath() . "/" . $this->getWebFilePath() . "/" . $namespace . "/" . $file;
	}


	/**
	 * @param string $file
	 * @param mixed|NULL $namespace
	 * @return string pathame
	 */
	public function getFile($file, $namespace = NULL)
	{
		return $this->getUploadSavePath($file, $namespace);
	}


	/**
	 * @param string $file
	 * @param mixed|NULL $namespace
	 * @return bool
	 */
	public function delete($file, $namespace = NULL)
	{
		$file = $this->getFile($file, $namespace);
		if (file_exists($file)) {
			return unlink($file);
		}

		return TRUE;
	}


	/**
	 * @param string $filename
	 * @return array name, extension
	 * @throws FileStorageException
	 */
	public static function getFilePartsFromName($filename)
	{
		if ( ! preg_match('/(^.*)\.([^.]+)$/D', $filename, $m)) {
			throw new FileStorageException("Invalid file name - no extension!");
		}

		return [
			'name' => $m[1],
			'extension' => $m[2]
		];
	}


	/**
	 * @param string|NULL $file
	 * @param mixed|NULL $namespace
	 * @return string
	 */
	private function getUploadSavePath($file = NULL, $namespace = NULL)
	{
		$namespace = ($namespace ? self::getNamespaceDescription($namespace) : $this->getNamespace());

		$uploadFilePath = $this->getWebDir() . '/' . $this->getWebFilePath() . '/' . $namespace;
		if ($file !== NULL) {
			$uploadFilePath .= '/' . $file;
		}

		return $uploadFilePath;
	}

}
