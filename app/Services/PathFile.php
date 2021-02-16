<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class PathFile
{
    /** @var string */
    private $disks;
    /** @var string */
    private $base;
    /** @var string */
    private $relative;
    /** @var string */
    private $file;
    /** @var string */
    private $extensions;
    /** @var string */
    private $mimeType;
    /** @var array */
    private $size = ['bytes' => null, 'units' => null];

    public function __construct($disks, $relative = null, $file = null)
    {
        $this->disks = $disks;
        $this->base = Storage::disk($disks)->path('');
        $this->relative = $relative;
        $this->file = $file;

        if (isset($file) && is_string($file) && strlen($file) > 0) {
            $this->checkFile();
        }
    }

    /**
     * @return string
     */
    public function getDisks():string
    {
        return $this->disks;
    }

    /**
     * @param  string  $disks
     */
    public function setDisks(string $disks):void
    {
        $this->disks = $disks;
    }

    /**
     * @return string
     */
    public function getBase():string
    {
        return $this->base;
    }

    /**
     * @return string
     */
    public function getRelativePath():string
    {
        return $this->relative;
    }

    /**
     * @param  string  $relative
     */
    public function setRelativePath(string $relative):void
    {
        $this->relative = $relative;
    }

    /**
     * @return string
     */
    public function getFile():string
    {
        return $this->file;
    }

    /**
     * @param  string  $file
     */
    public function setFile(string $file):void
    {
        $this->file = $file;
    }

    public function checkFile():void
    {
        $exists = Storage::disk($this->getDisks())->exists($this->getRelativeFilePath());
        if ($exists) {
            $this->mimeType = Storage::disk($this->getDisks())->mimeType($this->getRelativeFilePath());

            $size = Storage::disk($this->getDisks())->size($this->getRelativeFilePath());
            $this->size['bytes'] = $size;
            $this->size['units'] = $this->bytes2units($size);

            /**
             * $pattern = "/(.*?)\.(.*?)$/i"
             * $file = "Godly Stay-Home Dad 1554-1563.docx"
             * $matches = [
             *      0 => "Godly Stay-Home Dad 1554-1563.docx"
             *      1 => "Godly Stay-Home Dad 1554-1563"
             *      2 => "docx"
             * ]
             */
            $pattern = '';
            $pattern .= '/';
            $pattern .= '(.*?)';
            $pattern .= '\.';
            $pattern .= '(.*?)';
            $pattern .= '$/i';
            preg_match($pattern, $this->getFile(), $matches);
            // dd($matches);
            if (!empty($matches)) {
                $this->extensions = $matches[2];
            }
        }
    }

    /**
     * @return string
     */
    public function getRelativeFilePath():string
    {
        return $this->relative.$this->file;
    }

    public function getFullPath():string
    {
        return $this->base.$this->relative.$this->file;
    }

    /**
     * @return string
     */
    public function getExtensions():string
    {
        return $this->extensions;
    }

    /**
     * @param  string  $extensions
     */
    public function setExtensions(string $extensions):void
    {
        $this->extensions = $extensions;
    }

    /**
     * @return string
     */
    public function getMimeType():string
    {
        return $this->mimeType;
    }

    /**
     * @param  string  $mimeType
     */
    public function setMimeType(string $mimeType):void
    {
        $this->mimeType = $mimeType;
    }

    /**
     * @return array
     */
    public function getSize():array
    {
        return $this->size;
    }

    /**
     * @param  array  $size
     */
    public function setSize(array $size):void
    {
        $this->size = $size;
    }

    /**
     * @param  float  $bytes
     *
     * @return string
     */
    public function bytes2units(float $bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2).' GB';
        }
        elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2).' MB';
        }
        elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2).' KB';
        }
        elseif ($bytes > 1) {
            $bytes = $bytes.' bytes';
        }
        elseif ($bytes == 1) {
            $bytes = $bytes.' byte';
        }
        else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }

    /**
     * @param  string  $from
     *
     * @return float|int|string|string[]|null
     */
    public function units2bytes(string $from)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $number = substr($from, 0, -2);
        $suffix = strtoupper(substr($from, -2));

        //B or no suffix
        if (is_numeric(substr($suffix, 0, 1))) {
            return preg_replace('/[^\d]/', '', $from);
        }

        $exponent = array_flip($units)[$suffix] ?? null;
        if ($exponent === null) {
            return null;
        }

        return $number * (1024 ** $exponent);
    }
}
