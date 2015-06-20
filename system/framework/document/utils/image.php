<?php
/**
 * Image Element - Utility Class
 *
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		June 19, 2015
 * @copyright	Copyright (C) 2015 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 *
 * Copyright (C) 2015 Jeffrey van Harn
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License (License.txt) for more details.
 */

// Define Namespace
namespace Quark\Document\Utils;
use Quark\Bundles\Bundles,
    Quark\Document\Document,
    Quark\Document\IIndependentElement;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Image element that can be used in conjunction with the Resource Manager to automatically reference to bundled images.
 */
class Image implements IIndependentElement {
    /**
     * @var bool|null Whether or not the image should output indented and with a newline, or for inline use.
     */
    public $inline = null;

    /**
     * @var string|null The alternative text-value for this image. (When the image can't be loaded or the user uses a screenreader.)
     */
    public $alt = null;

    /**
     * @var string The url the image points to.
     */
    protected $uri = '';

    protected $width, $height;

    /**
     * @param string $uri
     */
    protected function __construct($uri){
        $this->uri = $uri;
    }

    public function dimensions($width=null, $height=null){
        if(is_integer($width))
            $this->width = $width.'px';
        else if(is_float($width))
            $this->width = $width.'%';
        else if(is_string($width))
            $this->width = $width;

        if(is_integer($height))
            $this->height = $height.'px';
        else if(is_float($height))
            $this->height = $height.'%';
        else if(is_string($height))
            $this->height = $height;

        return $this;
    }

    /**
     * Retrieve the HTML representation of the element
     * @param Document $context The context within which the Element gets saved. (Contains data like encoding, XHTML or not etc.)
     * @param int $depth
     * @return String HTML representation.
     */
    public function save(Document $context=null, $depth=0) {
        $img = '<img '.
            $context->encodeAttribute('src', $this->uri).
            (!empty($this->alt) ? ' '.$context->encodeAttribute('alt', $this->alt):'').
            (!is_null($this->width)?' '.$context->encodeAttribute('width', $this->width):'').
            (!is_null($this->height)?' '.$context->encodeAttribute('height', $this->height):'').' />';
        if($this->inline === true) return $img;
        else return _::line($depth, $img);
    }

    /**
     * Save the image to it's HTML value.
     * @return string HTML representation.
     */
    public function independentSave($depth=0){
        $img = '<img src="'.$this->uri.'"'.
            (!empty($this->alt) ? ' alt="'._::encode($this->alt).'" ':' ').
            (!is_null($this->width)?' width="'.$this->width.'"':'').
            (!is_null($this->height)?' height="'.$this->height.'"':'').'/>';
        if($this->inline === false) return _::line($depth, $img);
        else return $img;
    }

    /**
     * Get the html value for this image.
     * @return string
     */
    public function __toString(){
        return $this->independentSave();
    }

    /**
     * Find an image and return a Image object for that image, or null if it was not found.
     * @param Document $context
     * @param string $image
     * @param string $bundle
     * @param string $version
     * @param string $alt The alternative text-value for this image. (When the image can't be loaded or the user uses a screenreader.)
     * @param bool $inline Whether or not the image should output indented and with a newline, or for inline use.
     * @return null|Image
     */
    public static function find(Document $context, $image, $bundle=null, $version=null, $alt=null, $inline=null){
        $uri = $context->resources->reference($image, Bundles::RESOURCE_TYPE_IMAGE, $bundle, $version, true);
        if(!is_string($uri)) return null;
        $img = new Image($uri);
        $img->alt = $alt;
        $img->inline = $inline;
        return $img;
    }
}