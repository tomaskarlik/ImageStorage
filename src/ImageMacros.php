<?php

/**
 * This file is part of the ImageStorage
 *
 * Copyright (c) 2016 Tomáš Karlík (http://tomaskarlik.cz)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace TomasKarlik\Storages\Macros;

use Latte\Macros\MacroSet;
use Latte\Compiler;
use Latte\MacroNode;
use Latte\PhpWriter;

/**
 * Image storage macros
 */
class ImageMacros extends MacroSet {

    /**
     * @param Compiler $compiler
     */
    public static function install(Compiler $compiler) {
	$set = new static($compiler);
	$set->addMacro('img', [$set, 'writeImageLink'], NULL, [$set, 'writeImageAttribute']);
    }

    /**
     * Macro {img param, ...}
     * 
     * @param MacroNode $node
     * @param PhpWriter $writer
     * @return string
     */
    public function writeImageLink(MacroNode $node, PhpWriter $writer) {
	return $writer->write('echo $imageStorage->getPictureLink(%node.args);');
    }

    /**
     * Macro n:img="param, ...."
     * 
     * @param MacroNode $node
     * @param PhpWriter $writer
     * @return string
     */
    public function writeImageAttribute(MacroNode $node, PhpWriter $writer) {
	if ($node->htmlNode->name === 'a') {
	    $attr = 'href=';
	} else {
	    $attr = 'src=';
	}

	return $writer->write('echo \' ' . $attr . '"\' . $imageStorage->getPictureLink(%node.args) . \'"\';');
    }

}
