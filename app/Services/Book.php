<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\Yaml\Yaml;

use DateTime;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class Book
{
    /** @var PathFile[] */
    private $path;
    /** @var array */
    private $extensions = [];
    /** @var array */
    private $chapterNames = [];
    /** @var Novel[] */
    private $novels = [];
    /** @var FileConverter[] */
    private $fileConverters = [];

    public function __construct()
    {
        $this->path = [
            'configs' => new PathFile('public', 'novel/configs/', 'configs.yaml'),
            // 'configs' => new PathFile('public', 'book/configs/', 'configs.yaml'),
            'source'  => new PathFile('download'),
            'target'  => new PathFile('public', 'novel/lists/'),
            // 'target'  => new PathFile('public', 'book/novel/'),
        ];

        $this->extensions = [
            'txt',
            'doc',
            'docx',
            'odt',
            'pdf',
            'epub',
            'zip',
            'rar',
        ];

        $exist = Storage::disk($this->path['configs']->getDisks())->exists($this->path['configs']->getRelativeFilePath());
        if ($exist) {
            $novels = [];
            try {
                $configs = Yaml::parse(Storage::disk($this->path['configs']->getDisks())->get($this->path['configs']->getRelativeFilePath()));
                $this->chapterNames = $configs['default']['chapterNames'] ?? [];
                $novels = $configs['novels'] ?? [];
            } catch (Exception $e) {
                dump('Novel Configurations!');
                dd($e->getCode(), $e->getMessage(), $e);
            }

            if (sizeof($novels) > 0) {
                foreach ($novels as $item) {
                    $novel = new Novel($item['code']);
                    $novel->setTitle($item['title'] ?? '');
                    $novel->setAuthor($item['author'] ?? '');
                    $novel->setDescription($item['description'] ?? '');
                    $novel->setEnd($item['optional']['end'] ?? false);
                    $novel->setChapterNames($item['optional']['chapterNames'] ?? []);
                    $novel->addFileNames($item['optional']['fileNames'] ?? []);
                    $this->addNovel($novel);
                }
            }
        }
    }

    /**
     * @return PathFile[]
     */
    public function getPath():array
    {
        return $this->path;
    }

    /**
     * @return array
     */
    public function getExtensions():array
    {
        return $this->extensions;
    }

    /**
     * @param  Novel  $novel
     */
    public function addNovel(Novel $novel):void
    {
        $this->novels[] = $novel;
    }

    /**
     * @param  null  $code
     *
     * @return Novel[]
     */
    public function getNovels($code = null)
    {
        $novels = $this->novels;
        if (isset($code) && !empty($code)) {
            if (sizeof($this->novels) > 0) {
                foreach ($this->novels as $novel) {
                    if ($novel->getCode() === $code) {
                        $novels = [];
                        $novels[] = $novel;
                        break;
                    }
                }
            }
        }

        return $novels;
    }

    /**
     * @param  string|null  $code
     *
     * @return array
     */
    public function getFileNames($code = null):array
    {
        $fileNames = [];
        $novels = $this->getNovels($code);
        foreach ($novels as $novel) {
            $fileNames = array_merge($fileNames, $novel->getFileNames());
        }

        return $fileNames;
    }

    public function getChapterNames($code = null)
    {
        $chapterNames = $this->chapterNames;
        if (isset($code) && !empty($code)) {
            $novels = $this->getNovels($code);
            foreach ($novels as $novel) {
                $chapterNames = array_merge($chapterNames, $novel->getChapterNames());
            }
        }

        return $chapterNames;
    }

    /**
     * @param $fileName
     *
     * @return string|null
     */
    public function getNovelCode($fileName)
    {
        $code = null;
        $novels = $this->getNovels($code);
        foreach ($novels as $novel) {
            if (in_array($fileName, $novel->getFileNames())) {
                $code = $novel->getCode();
                break;
            }
        }

        return $code;
    }

    /**
     * @return \App\Services\FileConverter[]
     */
    public function getFileConverters():array
    {
        return $this->fileConverters;
    }

    /**
     * @param  \App\Services\FileConverter  $fileConverters
     */
    public function addFileConverters(FileConverter $fileConverters):void
    {
        $this->fileConverters[] = $fileConverters;
    }

    public function toArray()
    {
        $toArray = [
            'path'           => [],
            'extensions'     => $this->getExtensions(),
            'chapterNames'   => $this->getChapterNames(),
            'novels'         => [],
            'fileConverters' => [],
        ];
        if (sizeof($this->getPath()) > 0) {
            foreach ($this->getPath() as $name => $path) {
                $toArray['path'][$name] = $path->toArray();
            }
        }
        if (sizeof($this->getNovels()) > 0) {
            foreach ($this->getNovels() as $novel) {
                $toArray['novels'][] = $novel->toArray();
            }
        }
        if (sizeof($this->getFileConverters()) > 0) {
            foreach ($this->getFileConverters() as $fileConverter) {
                $toArray['fileConverters'][] = $fileConverter->toArray();
            }
        }

        return $toArray;
    }
}