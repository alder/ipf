<?php

class IPF_Image
{
    private $image, $width, $height, $type, $path;

    public static function load($path)
    {
        $imageInfo = getimagesize($path);
        if (!$imageInfo)
            throw new Exception('Cannot open '.$path.' image file');

        $type = $imageInfo[2];

        if ($type == IMAGETYPE_JPEG)
            $image = imagecreatefromjpeg($path);
        else if ($type == IMAGETYPE_GIF)
            $image = imagecreatefromgif($path);
        else if ($type == IMAGETYPE_PNG)
            $image = imagecreatefrompng($path);
        else
            throw new Exception('Unknown image format '.$path);

        return new IPF_Image($image, $imageInfo[0], $imageInfo[1], $type, $path);
    }

    public static function create($width, $height)
    {
        $image = imagecreatetruecolor($width, $height);
        return new IPF_Image($image, $width, $height);
    }

    public function __construct($image, $width=null, $height=null, $type=null, $path=null)
    {
        $this->image = $image;
        $this->width = ($width !== null) ? $width : imagesx($image);
        $this->height = ($height !== null) ? $height : imagesy($image);
        $this->type = $type;
        $this->path = $path;
    }

    public function __destruct()
    {
        imagedestroy($this->image);
    }

    private static function detectType($filename)
    {
        if (preg_match('/\.je?pg$/i', $filename))
            return IMAGETYPE_JPEG;
        if (preg_match('/\.gif$/i', $filename))
            return IMAGETYPE_GIF;
        if (preg_match('/\.png$/i', $filename))
            return IMAGETYPE_PNG;
        return null;
    }

    public function save($filename=null)
    {
        $type = null;
        if ($filename) {
            $type = self::detectType($filename);
            if (!$type)
                $type = IMAGETYPE_JPEG;
        } else {
            $filename = $this->path;
            if ($this->type)
                $type = $this->type;
            else
                $type = self::detectType($filename);
        }

        if (!$filename)
            throw new Exception('No filename given.');

        if ($type == IMAGETYPE_JPEG)
            imagejpeg($this->image, $filename);
        else if ($type == IMAGETYPE_GIF)
            imagegif($this->image, $filename);
        else if ($type == IMAGETYPE_PNG)
            imagepng($this->image, $filename);
        else
            throw new Exception('Unknown file type.');
    }

    private function color($color)
    {
        return imagecolorallocatealpha($this->image,
            ($color >> 16) & 0xFF,
            ($color >> 8) & 0xFF,
            $color & 0xFF,
            ($color >> 24) & 0x7F);
    }

    public function fill($x, $y, $width, $height, $color)
    {
        imagefilledrectangle($this->image, $x, $y, $width, $height, $this->color($color));
    }

    public function rotate($angle, $color=0x7F000000)
    {
        $image = imagerotate($this->image, $angle, $color);
        return new IPF_Image($image);
    }

    public function copy(IPF_Image $image, $dstX, $dstY)
    {
        imagecopy($this->image, $image->image, $dstX, $dstY, 0, 0, $image->width, $image->height);
    }

    public function copyPart(IPF_Image $image, $dstX, $dstY, $srcX, $srcY, $width, $height)
    {
        imagecopy($this->image, $image->image, $dstX, $dstY, $srcX, $srcY, $width, $height);
    }

    public function copyScale(IPF_Image $image, $dstX=0, $dstY=0, $dstWidth=null, $dstHeight=null, $srcX=0, $srcY=0, $srcWidth=null, $srcHeight=null)
    {
        if ($dstWidth === null)
            $dstWidth = $this->width;
        if ($dstHeight === null)
            $dstHeight = $this->height;
        if ($srcWidth === null)
            $srcWidth = $image->width;
        if ($srcHeight === null)
            $srcHeight = $image->height;
        imagecopyresampled($this->image, $image->image, $dstX, $dstY, $srcX, $srcY, $dstWidth, $dstHeight, $srcWidth, $srcHeight);
    }

    public function diagonalWatermark(IPF_Image $watermark)
    {
        $result = IPF_Image::create($this->width, $this->height);
        $result->copy($this, 0, 0);

        $w_repeat = ceil(hypot($this->width, $this->height) / (float)$watermark->width);
        $angle = atan2($this->height, $this->width);
        $wmr = $watermark->rotate(-$angle * 180 / M_PI);

        $dx = $watermark->width * cos($angle);
        $dy = $watermark->width * sin($angle);

        for ($i = 0; $i < $w_repeat; ++$i) {
            $result->copy($wmr, $dx * ($i - 0.5), $dy * ($i - 0.5));
        }
        return $result;
    }

    public function fitWidth($width, $expand=true, $shrink=true)
    {
        if ($this->width == $width)
            return $this;
        if (!$expand && $this->width < $width)
            return $this;
        if (!$shrink && $this->width > $width)
            return $this;

        $height = $width * $this->height / $this->width;

        $result = IPF_Image::create($width, $height);
        $result->copyScale($this);
        return $result;
    }

    public function fitHeight($height, $expand=true, $shrink=true)
    {
        if ($this->height == $height)
            return $this;
        if (!$expand && $this->height < $height)
            return $this;
        if (!$shrink && $this->height > $height)
            return $this;

        $width = $height * $this->width / $this->height;

        $result = IPF_Image::create($width, $height);
        $result->copyScale($this);
        return $result;
    }

    public function fit($width, $height, $expand=true, $shrink=true)
    {
        if (!$expand && $this->width < $width && $this->height < $height)
            return $this;
        if (!$shrink && $this->width > $width && $this->height > $height)
            return $this;

        if ($this->height * $width >= $this->width * $height) {
            $w = $height * $this->width / $this->height;
            $h = $height;
        } else {
            $w = $width;
            $h = $width * $this->height / $this->width;
        }

        $result = IPF_Image::create($w, $h);
        $result->copyScale($this);
        return $result;
    }

    public function thumbnailCrop($width, $height, $gravityX=0.5, $gravityY=0.5)
    {
        if ($this->height * $width >= $this->width * $height) {
            $w = $this->width;
            $h = $this->width * $height / $width;
        } else {
            $w = $this->height * $width / $height;
            $h = $this->height;
        }
        $x = ($this->width - $w) * $gravityX;
        $y = ($this->height - $h) * $gravityY;

        $result = IPF_Image::create($width, $height);
        $result->copyScale($this, 0, 0, $width, $height, $x, $y, $w, $h);
        return $result;
    }

    public function thumbnailFill($width, $height, $color=0x7F000000, $gravityX=0.5, $gravityY=0.5)
    {
        if ($this->height * $width >= $this->width * $height) {
            $w = $height * $this->width / $this->height;
            $h = $height;
        } else {
            $w = $width;
            $h = $width * $this->height / $this->width;
        }
        $x = ($width - $w) * $gravityX;
        $y = ($height - $h) * $gravityY;

        $result = IPF_Image::create($width, $height);
        $result->fill(0, 0, $width, $height, $color);
        $result->copyScale($this, $x, $y, $w, $h);
        return $result;
    }
}

