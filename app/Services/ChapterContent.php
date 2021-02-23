<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class ChapterContent
{
    /** @var string */
    private $id;
    /** @var string */
    private $title = '';
    /** @var string */
    private $name;
    /** @var array */
    private $contents = [];
    /** @var PathFile */
    private $target;

    public function __construct()
    {
    }

    /**
     * @return string
     */
    public function getId():string
    {
        return $this->id;
    }

    /**
     * @param  string  $id
     */
    public function setId(string $id):void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getTitle():string
    {
        return $this->title;
    }

    /**
     * @param  string  $title
     */
    public function setTitle(string $title):void
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getName():string
    {
        return $this->name;
    }

    /**
     * @param  string  $name
     */
    public function setName(string $name):void
    {
        $this->name = $name;
    }

    /**
     * @return array
     */
    public function getContents():array
    {
        return $this->contents;
    }

    /**
     * @param  array  $contents
     */
    public function setContents(array $contents):void
    {
        $this->contents = $contents;
    }

    /**
     * @param  string  $content
     */
    public function addContents(string $content):void
    {
        $this->contents[] = $content;
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

    public function toArray()
    {
        return [
            'id'       => $this->getId(),
            'title'    => $this->getTitle(),
            'name'     => $this->getName(),
            'contents' => $this->getContents(),
            'target'   => $this->getTarget()->toArray(),
        ];
    }
}
