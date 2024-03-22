<?php

namespace App\Services;

use File;
use Illuminate\Support\Facades\Storage;

class PathFile
{
    /** @var string */
    private $disks;
    /** @var string */
    private $base;
    /** @var string|null */
    private $relative;
    /** @var string|null */
    private $file;
    /** @var string|null */
    private $extensions;
    /** @var string|null */
    private $mimeType;
    /** @var array */
    private $size = ['bytes' => null, 'units' => null];
    /** @var array */
    private $lastModified = ['timestamp' => null, 'datetime' => null];

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
     * @return string|null
     */
    public function getRelativePath():?string
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
     * @return string|null
     */
    public function getFile():?string
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
        /*$a = 'novel/lists/apo/chapters/raw';
        $exists = Storage::disk($this->getDisks())->exists($a);
        if(!$exists) {
            $b = File::makeDirectory($this->getBase().$a, 0777, true);
            // $b = Storage::disk($this->getDisks())->makeDirectory($this->getRelativeFilePath());
            $exists1 = Storage::disk($this->getDisks())->exists($a);
            dump($this->getBase());
            dump($a, $exists);
            dd($b, $exists1);_
        }*/
        $exists = Storage::disk($this->getDisks())->exists($this->getRelativeFilePath());
        /*if(!$exists) {
            $pattern = '/novel\/lists\/(.*?)\/cover\/cover\.(jpg|jpeg)$/i';
            preg_match($pattern, $this->getRelativeFilePath(), $matches);
            if (!empty($matches)) {
                $code = $matches[1];
                $file = 'cover.jpeg';
                if($matches[2] === 'jpg') {
                    $file = 'cover.jpeg';
                }
                elseif($matches[2] === 'jpeg') {
                    $file = 'cover.jpg';
                }
                $this->setFile($file);
                $exists = Storage::disk($this->getDisks())->exists($this->getRelativeFilePath());
            }

        }
        if(!$exists) {
            dd($this->getRelativeFilePath());
        }*/
        if ($exists) {
            $this->mimeType = Storage::disk($this->getDisks())->mimeType($this->getRelativeFilePath());

            $size = Storage::disk($this->getDisks())->size($this->getRelativeFilePath());
            $this->size['bytes'] = $size;
            $this->size['units'] = $this->bytes2units($size);

            $lastModified = Storage::disk($this->getDisks())->lastModified($this->getRelativeFilePath());
            $d = new \DateTime();
            $d->setTimestamp($lastModified);
            $this->lastModified['timestamp'] = $d->getTimestamp();
            $this->lastModified['datetime'] = $d->format('c');

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
     * @return string|null
     */
    public function getExtensions():?string
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
     * @return string|null
     */
    public function getMimeType():?string
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
     * @return array
     */
    public function getLastModified():array
    {
        return $this->lastModified;
    }

    /**
     * @param  array  $lastModified
     */
    public function setLastModified(array $lastModified):void
    {
        $this->lastModified = $lastModified;
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

    public function toArray()
    {
        return [
            'disks'            => $this->getDisks(),
            'base'             => $this->getBase(),
            'relative'         => $this->getRelativePath(),
            'relativeWithFile' => $this->getRelativeFilePath(),
            'file'             => $this->getFile(),
            'fullPath'         => $this->getFullPath(),
            'extensions'       => $this->getExtensions(),
            'mimeType'         => $this->getMimeType(),
            'size'             => $this->getSize(),
            'LastModified'     => $this->getLastModified(),
        ];
    }
}
