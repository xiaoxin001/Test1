<?php
/* * File: SimpleImage.php * Author: Simon Jarvis * Copyright: 2006 Simon Jarvis * Date: 08/11/06 * Link: http://www.white-hat-web-design.co.uk/articles/php-image-resizing.php * * This program is free software; you can redistribute it and/or * modify it under the terms of the GNU General Public License * as published by the Free Software Foundation; either version 2 * of the License, or (at your option) any later version. * * This program is distributed in the hope that it will be useful, * but WITHOUT ANY WARRANTY; without even the implied warranty of * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the * GNU General Public License for more details: * http://www.gnu.org/licenses/gpl.html * */
namespace Ejiayou\PHP\Utils\Image;

class SimpleImage {
    var $image;
    var $image_type;

    function load($filename) {
        $image_info = getimagesize($filename);
        $this->image_type = $image_info[2];
        if( $this->image_type == IMAGETYPE_JPEG ) {
            $this->image = imagecreatefromjpeg($filename);
        } elseif( $this->image_type == IMAGETYPE_GIF ) {
            $this->image = imagecreatefromgif($filename);
        } elseif( $this->image_type == IMAGETYPE_PNG ) {
            $this->image = imagecreatefrompng($filename);
        }
    }

    function save($filename, $image_type=IMAGETYPE_JPEG, $compression=75, $permissions=null) {
        if( $image_type == IMAGETYPE_JPEG ) {
            imagejpeg($this->image,$filename,$compression);
        } elseif( $image_type == IMAGETYPE_GIF ) {
            imagegif($this->image,$filename);
        } elseif( $image_type == IMAGETYPE_PNG ) {
            imagepng($this->image,$filename);
        }

        if( $permissions != null) {
            chmod($filename,$permissions);
        }
    }

    function output($image_type=IMAGETYPE_JPEG) {
        if( $image_type == IMAGETYPE_JPEG ) {
            header('Content-type:image/jpeg');
            imagejpeg($this->image);
            imagedestroy($this->image);
        } elseif( $image_type == IMAGETYPE_GIF ) {
            header('Content-type:image/gif');
            imagegif($this->image);
            imagedestroy($this->image);
        } elseif( $image_type == IMAGETYPE_PNG ) {
            header('Content-type:image/png');
            imagepng($this->image);
            imagedestroy($this->image);
        }
    }

    function getWidth() {
        return imagesx($this->image);
    }

    function getHeight() {
        return imagesy($this->image);
    }

    function resizeToHeight($height) {
        $ratio = $height / $this->getHeight();
        $width = $this->getWidth() * $ratio;
        $this->resize($width,$height);
    }

    function resizeToWidth($width) {
        $ratio = $width / $this->getWidth();
        $height = $this->getheight() * $ratio;
        $this->resize($width,$height);
    }

    function scale($scale) {
        $width = $this->getWidth() * $scale/100;
        $height = $this->getheight() * $scale/100;
        $this->resize($width,$height);
    }

    function resize($width,$height) {
        $new_image = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($new_image, 255, 255, 255);
        imagefill($new_image, 0, 0, $white);

        imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
        $this->image = $new_image;
    }

    function resizecrop($width, $height = null) {

        // Determine height
        $height = $height ?: $width;

        // Determine aspect ratios
        $current_aspect_ratio = $this->getHeight() / $this->getWidth();
        $new_aspect_ratio = $height / $width;

        // Fit to height/width
        if ($new_aspect_ratio > $current_aspect_ratio) {
            $this->fit_to_height($height);
        } else {
            $this->fit_to_width($width);
        }
        $left = floor(($this->getWidth() / 2) - ($width / 2));
        $top = floor(($this->getHeight() / 2) - ($height / 2));

        // Return trimmed image
        return $this->crop($left, $top, $width + $left, $height + $top);

    }

    function fit_to_height($height) {

        $aspect_ratio = $this->getHeight() / $this->getWidth();
        $width = $height / $aspect_ratio;

        return $this->resize($width, $height);
    }

    function fit_to_width($width) {

        $aspect_ratio = $this->getHeight() / $this->getWidth();
        $height = $width * $aspect_ratio;

        return $this->resize($width, $height);

    }

    function crop($x1, $y1, $x2, $y2) {
        // Determine crop size
        if ($x2 < $x1) {
            list($x1, $x2) = array($x2, $x1);
        }
        if ($y2 < $y1) {
            list($y1, $y2) = array($y2, $y1);
        }
        $crop_width = $x2 - $x1;
        $crop_height = $y2 - $y1;

        // Perform crop
        $new_image = imagecreatetruecolor($crop_width, $crop_height);
        $white = imagecolorallocate($new_image, 255, 255, 255);
        imagefill($new_image, 0, 0, $white);
        imagecopyresampled($new_image, $this->image, 0, 0, $x1, $y1, $crop_width, $crop_height, $crop_width, $crop_height);

        $this->image = $new_image;

        return $this;
    }
}
