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

use Nette;
use Nette\Http\FileUpload;
use Nette\Utils\Image;
use Nette\Utils\Finder;

/**
 * Image storage
 */
class ImageStorage extends Nette\Object {
    
    const DEFAULT_QUALITY_JPEG = 80;
    const THUMB_EXTENSION = ".jpg";
    
    /**
     * @var FileStorage
     */
    private $fileStorage;
    
    /**
     * @var string
     */
    private $wwwThumbsPath;
    
    /**     
     * @var array
     */
    private $sizes;

    /**     
     * @param string $wwwDir local web root
     * @param string $original relative path to original pictures
     * @param string $thumbs relative path to thumbnails
     * @param array $sizes generate-pre cached sizes
     * @param string $basePath base path to links
     */
    public function __construct($wwwDir, $original, $thumbs, $sizes = array(), $basePath = '') {		
	$this->fileStorage = new FileStorage($wwwDir, $original, $basePath); //original files storage

	$this->wwwThumbsPath = $thumbs;	
	$this->sizes = $sizes;
    }

    /*
     * @param FileUpload $upload
     * @return string
     * @throws FileStorageException
     */
    public function getImageExtension(FileUpload $upload) {
	switch ($upload->getContentType()) {
	    case 'image/gif':
		return 'gif';
	    case 'image/png':
		return 'png';
	    case 'image/jpeg':
		return 'jpg';
	    default:
		throw new FileStorageException('Invalid content type!');
	}
    }

    /**
     * @param string $namespace
     */
    public function setNamespace($namespace) {
	$this->fileStorage->setNamespace($namespace);
    }

    /**
     * @return string
     */
    public function getNamespace() {
	return $this->fileStorage->getNamespace();
    }

    /**
     * @param string $path
     */
    public function setBasePath($path) {
	$this->fileStorage->setBasePath($path);
    }

    /**
     * @return string
     */
    public function getBasePath() {
	return $this->fileStorage->getBasePath();
    }

    /**     
     * @param FileUpload $upload
     * @param string $name
     * @param bool $overwrite
     * @throws FileStorageException
     */
    public function save(FileUpload $upload, $name, $overwrite = FALSE) {
	if ((!FileStorage::isEmptyUpload($upload)) 
		&& (!$upload->isImage())) {
	    throw new FileStorageException("File is not image!");
	}		
	$this->fileStorage->save($upload, $name, $overwrite); //save to "original"
	if ($overwrite) { //try delete old thumbs
	    $this->deleteThumbs($name);
	}
	$this->generateThumbsCache($name);
    }
    
    /**
     * @param string $from
     * @param string $name
     * @throws FileStorageException
     */
    public function copy($from, $name) {
	
	if (!file_exists($from)) {
	    throw new FileStorageException("Source file not exists!");
	}
	
	$mineType = mime_content_type($from);
	if (strpos($mineType, 'image') === FALSE) {
	    throw new FileStorageException("File is not image!");
	}
	
	$this->fileStorage->copy($from, $name);
	$this->generateThumbsCache($name);
    }
            
    /**     
     * @return string
     */
    public function getThumbsPath() {
	return $this->wwwThumbsPath;
    }

    /**
     * @param int|NULL $width
     * @param int|NULL $height
     * @param int|NULL $flag
     * @param int $quality
     * @return string return directory name by image type
     * @throws FileStorageException
     */
    public function getThumbnailsDirectoryName($width, $height, $flag, $quality) {
	
	//base name by dimensions
	if ($width && $height) {
	    $name = "{$width}x{$height}";
	} elseif ($width) {
	    $name = "{$width}x0";
	} elseif ($height) {
	    $name = "0x{$height}";
	} else {
	    throw new FileStorageException("Invalid width or height!");
	}
		
	//flags
	if ($flag & Image::FILL) {
	    $name .= "F";
	}
	if ($flag & Image::EXACT) {
	    $name .= "E";
	}	
	if ($flag & Image::SHRINK_ONLY) {
	    $name .= "SO";
	}	
	if ($flag & Image::STRETCH) {
	    $name .= "ST";
	}
	
	if ($quality != self::DEFAULT_QUALITY_JPEG) {
	    $name .= "q{$quality}";
	}
		
	return $name;
    }
    
    /**
     * @param mixed $namespace
     * @param int|NULL $width
     * @param int|NULL $height
     * @param int $flag
     * @param int $quality
     * @return string relative path to thumb. directory with selected params
     */
    private function getPictureThumbDirectory($namespace, $width, $height, $flag, $quality) {
	$namespace = FileStorage::getNamespaceDescription($namespace);		
	$directory = $this->getThumbnailsDirectoryName($width, $height, $flag, $quality);
	
	return $this->getThumbsPath() . '/' . $namespace . '/' . $directory;
    }
    
    /**
     * @param string $file
     * @throws FileStorageException
     */
    private function generateThumbsCache($file) {

	if (is_array($this->sizes) && count($this->sizes)) {
	    foreach ($this->sizes as $size) {

		if (!preg_match("#^([0-9]+)x([0-9]+)$#i", $size, $m)) { //check sizes settings
		    throw new FileStorageException("Invalid size - thumbs cache generator!");
		}

		$width = (int) $m[1];
		$height = (int) $m[2];
		$name = pathinfo($file, PATHINFO_FILENAME);

		$namespace = $this->getNamespace();
		$original = $this->fileStorage->getFile($file);
		$thumbDir = $this->getPictureThumbDirectory($namespace, $width, $height, Image::FIT, self::DEFAULT_QUALITY_JPEG);
		$thumbFile = $this->fileStorage->getWebDir() . '/' . $thumbDir . '/' . $name . self::THUMB_EXTENSION;

		if (!$this->createThumb($original, $thumbFile, $width, $height, Image::FIT, self::DEFAULT_QUALITY_JPEG)) {
		    throw new FileStorageException("Error creating thumb {$name}!");
		}
	    }
	}
    }

    /**     
     * @param mixed $namespace
     * @param string $picture
     * @param string|NULL $extension
     * @param int|NULL $width
     * @param int|NULL $height
     * @param int|NULL $flag
     * @param int|NULL $quality
     * @return string
     */
    public function getPictureLink($namespace, $picture, $extension, $width = NULL, $height = NULL, $flag = NULL, $quality = NULL) {
	
	if ($extension === NULL) {
	    if (!preg_match('/(^.*)\.([^.]+)$/D', $picture, $m)) {
		throw new FileStorageException("Must define extension or filename must be in format <name>.<extension>!");
	    }
	    $picture = $m[1];
	    $extension = $m[2];
	}
	
	if ((!$width) && (!$height)) { //no resize, get original
	    return $this->fileStorage->getFileLink($namespace, $picture . "." . $extension);
	}

	$flag = ($flag ? : Image::FIT); //default flag
	$quality = ($quality ? : self::DEFAULT_QUALITY_JPEG); //jpeg default image quality

	$directory = $this->getPictureThumbDirectory($namespace, $width, $height, $flag, $quality);
	$origFile = $this->fileStorage->getFile($picture . '.' . $extension, $namespace);
	$thumbFile = $this->fileStorage->getWebDir() . '/' . $directory . '/' . $picture . self::THUMB_EXTENSION;
	
	if (!file_exists($thumbFile)) { //create thumb
	    if (!$this->createThumb($origFile, $thumbFile, $width, $height, $flag, $quality)) {
		return ""; //error create thumb
	    }
	}

	return $this->getBasePath() . "/" . $directory . "/" . $picture . self::THUMB_EXTENSION;
    }

    /**     
     * @param string $source
     * @param string $destination
     * @param int|NULL $width
     * @param int|NULL $height
     * @param int $flags
     * @param int $quality
     * @return FALSE
     */
    private function createThumb($source, $destination, $width, $height, $flags, $quality) {
	if (!file_exists($source)) { //original not exists
	    return FALSE;
	}

	$dir = dirname($destination);
	if ((!file_exists($dir))
		&& (!mkdir($dir, 0777, TRUE))) { //directory not exists and not posible create it
	    return FALSE;
	}

	try {
	    $image = Image::fromFile($source);
	    $image->resize($width, $height, $flags);
	    $image->save($destination, $quality, Image::JPEG);
	    $image = NULL;
	} catch (\Exception $ex) {
	    return FALSE; //error creating thumb
	}

	return TRUE;
    }

    /**
     * @param string $name
     * @return array
     */
    private function getThumbsFiles($name) {
	$return = array();

	$directory = $this->fileStorage->getWebDir() . '/' . $this->getThumbsPath() . '/' . $this->getNamespace();
	$files = Finder::findFiles($name . self::THUMB_EXTENSION)
		->from($directory)
		->limitDepth(1);

	foreach ($files as $file) {
	    $return[] = $file->getPathname();
	}

	return $return;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function deleteThumbs($name) {
	$success = TRUE;

	$thumbs = $this->getThumbsFiles($name);
	foreach ($thumbs as $file) {
	    if (!unlink($file)) {
		$success = FALSE;
		break;
	    }
	}

	return $success;
    }

    /**
     * @param string $name picture ID
     * @param string $extension picture original extension
     * @throws FileStorageException
     */
    public function deletePicture($name, $extension) {
	if (!$this->deleteThumbs($name)) {
	    throw new FileStorageException('Error delete thumbs!');
	}
	if (!$this->fileStorage->delete($name . "." . $extension)) { //delete originaL
	    throw new FileStorageException('Error delete orig. file!');
	}	
    }
    
    /**
     * @param string $name
     * @return string
     */
    public function getOrignalFile($name) {
	return $this->fileStorage->getFile($name);
    }

}
