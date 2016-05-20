# ImageStorage and FileStorage
Keep original file without changes.

Requirements
------------

ImageStorage / FileStorage requires PHP 5.4 or higher.

- [Nette Framework](https://github.com/nette/nette)

Usage
-----

Presenter
```php
class MyPresenter extends BasePresenter { 
  use TStoragePresenter; //or inject dependency to template manualy
  
}
```
config.neon
```text
parameters:
	images:	
		original: 'pictures/original' #relative from %wwwDir%
		thumbs: 'pictures/tn'
		sizes: ['100x100'] #pre-cached sizes
	uploads: 'uploads'

services:
  - TomasKarlik\Storages\FileStorage(%wwwDir%, %uploads%)
  - TomasKarlik\Storages\ImageStorage(%wwwDir%, %images.original%, %images.thumbs%, %images.sizes%)

nette:
	latte:
		macros:
			- TomasKarlik\Storages\Macros\FileMacros::install
			- TomasKarlik\Storages\Macros\ImageMacros::install
			
			
```

Template
```latte
<img n:img="'namespace', $item->picture, NULL, 320, 240, \Nette\Utils\Image::EXACT" alt="{$item->name}">
<img n:img="'namespace', 'picture', 'jpg'"> <!-- define extension of original file sepratly -->
```

Save image
```php
 $this->image->setNamespace('myNamespace');
 $this->image->save($upload, $filename);

```
