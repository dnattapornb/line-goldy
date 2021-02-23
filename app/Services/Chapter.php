<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class Chapter
{
    /** @var int */
    private $start;
    /** @var int */
    private $end;
    /** @var int */
    private $count;
    /** @var ChapterContent[] */
    public $chapterContents = [];

    public function __construct()
    {
    }

    /**
     * @return int
     */
    public function getStart():int
    {
        return $this->start;
    }

    /**
     * @param  int  $start
     */
    public function setStart(int $start):void
    {
        $this->start = $start;
    }

    /**
     * @return int
     */
    public function getEnd():int
    {
        return $this->end;
    }

    /**
     * @param  int  $end
     */
    public function setEnd(int $end):void
    {
        $this->end = $end;
    }

    /**
     * @return int
     */
    public function getCount():int
    {
        return $this->count;
    }

    /**
     * @param  int  $count
     */
    public function setCount(int $count):void
    {
        $this->count = $count;
    }

    /**
     * @return \App\Services\ChapterContent[]
     */
    public function getChapterContents():array
    {
        return $this->chapterContents;
    }

    /**
     * @param  \App\Services\ChapterContent[]  $chapterContents
     */
    public function setChapterContents(array $chapterContents):void
    {
        $this->chapterContents = $chapterContents;
    }

    /**
     * @param  \App\Services\ChapterContent  $chapterContent
     */
    public function addChapterContents(ChapterContent $chapterContent):void
    {
        $this->chapterContents[] = $chapterContent;
    }

    public function toArray()
    {
        $toArray = [
            'start'           => $this->getStart(),
            'end'             => $this->getEnd(),
            'count'           => $this->getCount(),
            'chapterContents' => [],
        ];
        if (sizeof($this->getChapterContents()) > 0) {
            foreach ($this->getChapterContents() as $chapterContent) {
                $toArray['chapterContents'][] = $chapterContent->toArray();
            }
        }

        return $toArray;
    }
}
