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
 * File storage macros
 */
class FileMacros extends MacroSet {

    /**
     * @param Compiler $compiler
     */
    public static function install(Compiler $compiler) {
	$set = new static($compiler);
	$set->addMacro('file', [$set, 'writeFileLink'], NULL, [$set, 'writeFileAttribute']);
    }

    /**
     * Macro {file param, ...}
     * 
     * @param MacroNode $node
     * @param PhpWriter $writer
     * @return string
     */
    public function writeFileLink(MacroNode $node, PhpWriter $writer) {
	return $writer->write('echo $fileStorage->getFileLink(%node.args);');
    }

    /**
     * Macro n:file="param, ...."
     * 
     * @param MacroNode $node
     * @param PhpWriter $writer
     * @return string
     */
    public function writeFileAttribute(MacroNode $node, PhpWriter $writer) {
	if ($node->htmlNode->name === 'a') {
	    $attr = 'href=';
	} else {
	    $attr = 'src=';
	}

	return $writer->write('echo \' ' . $attr . '"\' . $fileStorage->getFileLink(%node.args) . \'"\';');
    }

}
