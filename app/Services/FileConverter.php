<?php

namespace App\Services;

use App\Services\PathFile;
use Illuminate\Support\Facades\Storage;

class FileConverter
{
    /** @var string */
    private static $text = null;
    /** @var string */
    private $code;
    /** @var PathFile */
    private $source;
    /** @var PathFile */
    private $target;
    /** @var Chapter */
    private $chapter;

    public function __construct()
    {
        $this->code = null;
        $this->chapter = new Chapter();
    }

    /**
     * @return string|null
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param  string|null  $code
     */
    public function setCode(string $code):void
    {
        $this->code = $code;
    }

    /**
     * @return \App\Services\PathFile
     */
    public function getSource():\App\Services\PathFile
    {
        return $this->source;
    }

    /**
     * @param  \App\Services\PathFile  $source
     */
    public function setSource(\App\Services\PathFile $source):void
    {
        $this->source = $source;
    }

    /**
     * @return \App\Services\PathFile
     */
    public function getTarget():\App\Services\PathFile
    {
        return $this->target;
    }

    /**
     * @param  \App\Services\PathFile  $target
     */
    public function setTarget(\App\Services\PathFile $target):void
    {
        $this->target = $target;
    }

    /**
     * @return \App\Services\Chapter
     */
    public function getChapter():\App\Services\Chapter
    {
        return $this->chapter;
    }

    /**
     * @param  \App\Services\Chapter  $chapter
     */
    public function setChapter(\App\Services\Chapter $chapter):void
    {
        $this->chapter = $chapter;
    }

    /**
     * @return string
     */
    public function getText():string
    {
        return $this::$text;
    }

    /**
     * @param  string  $text
     */
    public function setText(string $text):void
    {
        $this::$text = $text;
    }
}
