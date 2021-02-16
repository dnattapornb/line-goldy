<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class Novel
{
    /** @var array */
    private static $extensions = ['jpg', 'jpeg'];
    /** @var array */
    private static $directories = ['chapters', 'cover', 'description'];
    /** @var string */
    private $code;
    /** @var string */
    private $title = null;
    /** @var string */
    private $author = null;
    /** @var string */
    private $description = null;
    /** @var PathFile[] */
    private $path;
    /** @var boolean */
    private $end = false;
    /** @var array */
    private $chapterNames = [];
    /** @var array */
    private $fileNames = [];
    /** @var ChapterContent[] */
    private $chapters = [];

    public function __construct($code)
    {
        $this->setCode($code);
        $this->addFileName($code);
        $this->path = [
            'xhtml'       => new PathFile('public', 'novel/lists/'.$code.'/chapters/xhtml/'),
            'text'        => new PathFile('public', 'novel/lists/'.$code.'/chapters/text/'),
            'cover'       => new PathFile('public', 'novel/lists/'.$code.'/cover/', 'cover.jpg'),
            'description' => new PathFile('public', 'novel/lists/'.$code.'/description/xhtml/', 'description.xhtml'),
        ];
        $exist = Storage::disk($this->getPath()['cover']->getDisks())->exists($this->getPath()['cover']->getRelativeFilePath());
        if (!$exist) {
            $this->getPath()['cover']->setFile('');
            $imageName = 'cover';
            foreach ($this::$extensions as $extension) {
                $relativeFilePath = $this->getPath()['cover']->getRelativePath().$imageName.'.'.$extension;
                $exist = Storage::disk($this->getPath()['cover']->getDisks())->exists($relativeFilePath);
                if ($exist) {
                    $this->getPath()['cover']->setFile($imageName.'.'.$extension);
                    $this->getPath()['cover']->checkFile();
                    break;
                }
            }
        }
    }

    /**
     * @return string
     */
    public function getCode():string
    {
        return $this->code;
    }

    /**
     * @param  string  $code
     */
    public function setCode(string $code):void
    {
        $this->code = $code;
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
    public function getAuthor():string
    {
        return $this->author;
    }

    /**
     * @param  string  $author
     */
    public function setAuthor(string $author):void
    {
        $this->author = $author;
    }

    /**
     * @return string
     */
    public function getDescription():string
    {
        return $this->description;
    }

    /**
     * @param  string  $description
     */
    public function setDescription(string $description):void
    {
        $this->description = $description;
    }

    /**
     * @return \App\Services\PathFile[]
     */
    public function getPath():array
    {
        return $this->path;
    }

    /**
     * @param  \App\Services\PathFile[]  $path
     */
    public function setPath(array $path):void
    {
        $this->path = $path;
    }

    /**
     * @return bool
     */
    public function getEnd():bool
    {
        return $this->end;
    }

    /**
     * @param  bool  $end
     */
    public function setEnd(bool $end):void
    {
        $this->end = $end;
    }

    /**
     * @return bool
     */
    public function isEnd():bool
    {
        return (boolean) $this->end;
    }

    /**
     * @return array
     */
    public function getChapterNames():array
    {
        return $this->chapterNames;
    }

    /**
     * @param  array  $chapterNames
     */
    public function setChapterNames(array $chapterNames):void
    {
        $this->chapterNames = $chapterNames;
    }

    /**
     * @return array
     */
    public function getFileNames():array
    {
        return $this->fileNames;
    }

    /**
     * @param  array  $fileNames
     */
    public function setFileNames(array $fileNames):void
    {
        $this->fileNames = $fileNames;
    }

    /**
     * @param  array  $fileNames
     */
    public function addFileNames(array $fileNames):void
    {
        if (sizeof($fileNames) > 0) {
            foreach ($fileNames as $fileName) {
                if (!in_array($fileName, $this->fileNames)) {
                    $this->addFileName($fileName);
                }
            }
        }
    }

    /**
     * @param  string  $fileName
     */
    public function addFileName(string $fileName):void
    {
        $this->fileNames[] = $fileName;
    }

    /**
     * @return \App\Services\ChapterContent[]
     */
    public function getChapters():array
    {
        return $this->chapters;
    }

    /**
     * @param  \App\Services\ChapterContent[]  $chapters
     */
    public function setChapters(array $chapters):void
    {
        $this->chapters = $chapters;
    }

    /**
     * @param  \App\Services\ChapterContent  $chapter
     */
    public function addChapters(\App\Services\ChapterContent $chapter):void
    {
        $this->chapters[] = $chapter;
    }
}

