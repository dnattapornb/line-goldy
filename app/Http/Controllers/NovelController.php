<?php

namespace App\Http\Controllers;

use App\Libraries\Doc2Txt;

use PHPePub\Core\EPub;
use PHPePub\Core\EPubChapterSplitter;
use PHPePub\Core\Logger;
use PHPePub\Core\Structure\OPF\DublinCore;
use PHPePub\Core\Structure\OPF\MetaValue;
use PHPePub\Helpers\CalibreHelper;
use PHPePub\Helpers\URLHelper;
use PHPZip\Zip\File\Zip;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Symfony\Component\Yaml\Yaml;

class NovelController extends Controller
{
    private $novelDirectory, $storageDirectory, $novels, $logs, $chapterWords;

    public function __construct()
    {
        // storage/app/public
        $this->setStorageDirectory('public/');

        // > storage/app/file.txt
        // \Storage::put('file.txt', 'Content 1234 !!@@??');

        // > storage/app/file.txt
        // $file = \Storage::get('file.txt');

        // > public/storage/file.txt
        // $url = \Storage::url('file.txt');

        // > /Users/nattaporn.d/Desktop/www/blog/storage/app/file.txt
        // $path = \Storage::path(file.txt);

        $this->init();

        // Fix, Maximum execution time of 60 seconds exceeded
        ini_set('max_execution_time', 180); // 3 minutes
    }

    public function init()
    {
        $this->novels = [
            'lists' => [],
            'seeks' => [],
        ];

        $this->novelConfigurations();

        $this->logs = [];

        $this->chapterWords = [
            'ตอนที่',
            'บทที่',
            'ราชันเร้นลับ',
        ];
    }

    public function index()
    {
        $this->eBookAdaptor(null);
    }

    public function eBookAdaptor($novelDirectory = null)
    {
        if (!isset($novelDirectory)) {
            $novelDirectory = 'sgg';
        }
        $this->setNovelDirectory($novelDirectory);

        $novel = $this->getNovel();

        if (!empty($novel['chapters'])) {
            $this->ePubConverter($novel);
        }
    }

    private function getNovel()
    {
        $novel = [
            'title'       => 'Unknown title',
            'author'      => 'Unknown author',
            'description' => 'Unknown description',
            'end'         => false,
            'chapters'    => [],
        ];

        try {
            $novelsListsFile = $this->getStorageDirectory().'novel/configs/novels.yaml';
            $novelsLists = Yaml::parse(\Storage::get($novelsListsFile));
            foreach ($novelsLists as $novelsList) {
                if ($this->getNovelDirectory() === $novelsList['code']) {
                    $novel['title'] = $novelsList['title'];
                    $novel['author'] = $novelsList['author'];
                    $novel['description'] = $novelsList['description'];
                    if (isset($novelsList['optional']['end']) && !empty($novelsList['optional']['end'])) {
                        if (boolval($novelsList['optional']['end'])) {
                            $novel['end'] = true;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            dd($e->getCode(), $e->getMessage(), $e);
        }

        // get novel chapters from "xhtml" | "html"
        $novelChaptersDirectory = $this->getStorageDirectory().'novel/lists/'.$this->getNovelDirectory().'/chapters/xhtml/';
        $xhtmlLists = $this->getChaptersLists($novelChaptersDirectory);
        // dd($xhtmlLists);

        // get novel chapters from "text"
        $novelChaptersDirectory = $this->getStorageDirectory().'novel/lists/'.$this->getNovelDirectory().'/chapters/text/';
        $textLists = $this->getChaptersLists($novelChaptersDirectory);
        // dd($textLists);

        $chaptersLists = array_merge($textLists, $xhtmlLists);
        ksort($chaptersLists, SORT_NATURAL);
        // dd($chaptersLists);

        if (!empty($chaptersLists)) {
            foreach ($chaptersLists as $chapter) {
                $id = $chapter['id'];
                $extension = $chapter['extension'];
                $file = $chapter['file'];
                $chapterData = $this->getChapterData($file, $extension);
                // dd($chapterData);

                if (in_array($extension, ['html', 'xhtml'])) {
                    $chapterData[0]['id'] = $id;
                }

                foreach ($chapterData as $data) {
                    try {
                        $__Data = $this->getChapterDuplicate($novel['chapters'], $data, 0);
                        $chapterIndex = $this->getNovelDirectory().''.$__Data['id'];
                        $novel['chapters'][$chapterIndex] = $__Data;
                    } catch (\Exception $e) {
                        dump($data);
                        dd($e->getCode(), $e->getMessage(), $e);
                    }
                }
            }
        }

        return $novel;
    }

    private function getChaptersLists(string $novelChaptersDirectory)
    {
        $filesException = ['.DS_Store',];

        $files = [];
        try {
            $files = \Storage::allFiles($novelChaptersDirectory);
            sort($files, SORT_NATURAL);
        } catch (\Exception $e) {
            dd($e->getCode(), $e->getMessage(), $e);
        }

        $chaptersLists = [];
        foreach ($files as $file) {
            $fileName = basename($file);
            if (!in_array($fileName, $filesException)) {
                /*
                 [
                    0 => "543-1.xhtml"
                    1 => "543"
                    2 => "-"
                    3 => "1"
                    4 => "xhtml"
                 ]
                 */
                $pattern = '/(\d+)([\-|\.]?)(\d*)\.(.*?)$/';
                preg_match($pattern, $fileName, $matches);
                if (!empty($matches)) {
                    $_INDEX_ = intval($matches[1]);
                    if (isset($matches[2]) && !empty($matches[2]) && isset($matches[3]) && !empty($matches[3])) {
                        $_INDEX_ .= $matches[2];
                        $_INDEX_ .= intval($matches[3]);
                    }

                    $chaptersLists[$this->getNovelDirectory().$_INDEX_] = [
                        'id'        => $_INDEX_,
                        'extension' => $matches[4],
                        'file'      => $file,
                    ];
                }
            }
        }

        return $chaptersLists;
    }

    private function getChapterData(string $filePath, $extension)
    {
        $chapterData = [];
        $exists = \Storage::exists($filePath);
        if ($exists) {
            if (in_array($extension, ['html', 'xhtml', 'txt'])) {
                if (in_array($extension, ['html', 'xhtml'])) {
                    $chapterData = $this->getXhtmlData($filePath, $extension);
                }
                elseif ($extension === 'txt') {
                    $chapterData = $this->getTextData($filePath, $extension);
                }
            }
        }

        return $chapterData;
    }

    private function getXhtmlData($filePath, $extension)
    {
        $chapterData = [];

        $contents = \Storage::get($filePath);
        if ($filePath == 'public/novel/lists/lotm/chapters/xhtml/002.xhtml') {
            dd($filePath, $contents);
        }

        // Get, Chapter name from "html" | "xhtml"
        // "<h3>" > 'ตอนที่', 'บทที่'
        $pattern = '/<h3\s*class=\"chapter_heading\".*?>(.*?)<\/h3>/';
        preg_match($pattern, $contents, $matches);
        // dd($matches);
        if (!empty($matches)) {
            $content = $matches[1];
            /*
             [
                0 => "ตอนที่ 951 ราชาใบไม้สีทองตัวจริงกับตัวปลอม (1)"
                1 => "ตอนที่"
                2 => "951"
                3 => "ราชาใบไม้สีทองตัวจริงกับตัวปลอม (1)"
             ]
             */
            $pattern = '/.?(';
            $pattern .= implode('|', $this->chapterWords);
            $pattern .= ')';
            $pattern .= '\s*(\d+)\s*\:?\s*(.*?)$/';
            // $pattern = '/.?(ตอนที่|บทที่|ราชันเร้นลับ)\s*(\d+)\s*\:?\s*(.*?)$/';
            preg_match($pattern, $content, $matches);
            // dd($matches);
            if (!empty($matches)) {
                $chapterData = [
                    [
                        'id'       => $matches[2],
                        'name'     => $matches[1].' '.$matches[2].' '.trim($matches[3]),
                        'type'     => $extension,
                        'contents' => $filePath,
                    ],
                ];
            }
            else {
                /*
                [
                   0 => "บทนำ : ฉันอยากจะเป็นดารา"
                   1 => "บทนำ"
                   2 => "ฉันอยากจะเป็นดารา"
                ]
                */
                $pattern = '/^(บทนำ)\s*\:?\s*(.*?)$/';
                preg_match($pattern, $content, $matches);
                if (!empty($matches)) {
                    $chapterData = [
                        [
                            'id'       => 0,
                            'name'     => trim($matches[1]).' '.trim($matches[2]),
                            'type'     => $extension,
                            'contents' => $filePath,
                        ],
                    ];
                }
            }
        }

        return $chapterData;
    }

    private function getTextData($filePath, $extension)
    {
        $chapterData = [];

        $contents = \Storage::get($filePath);

        // check end of file doesn't has "\r\n" or "\n"
        $pattern = '/(.*?)\r?\n$/';
        preg_match($pattern, $contents, $matches);
        if (empty($matches)) {
            $contents .= "\n";
        }

        // filter empty string
        $pattern = '/(.*?)\r?\n/';
        preg_match_all($pattern, $contents, $matches);
        if (!empty($matches)) {
            $array_contents = $matches[1];
            $index = -1;

            foreach ($array_contents as $content) {
                if (!empty($content) && strlen($content) > 0) {
                    // check url ref, "https://fictionlog.co/c/5db14d7bcff050001a10acd6"
                    $pattern = '/http[s]?\:/';
                    preg_match($pattern, $content, $matches);
                    if (!empty($matches)) {
                    }
                    else {
                        // Get, Chapter name from "txt"
                        // > 'ตอนที่', 'บทที่'
                        /*
                         [
                            0 => "ตอนที่ 951 ราชาใบไม้สีทองตัวจริงกับตัวปลอม (1)"
                            1 => "ตอนที่"
                            2 => "951"
                            3 => "ราชาใบไม้สีทองตัวจริงกับตัวปลอม (1)"
                         ]
                         */
                        $pattern = '/.?(';
                        $pattern .= implode('|', $this->chapterWords);
                        $pattern .= ')';
                        $pattern .= '\s*(\d+)\s*\:?\s*(.*?)$/';
                        // $pattern = '/.?(ตอนที่|บทที่)\s*(\d+)\s*\:?\s*(.*?)$/';
                        preg_match($pattern, $content, $matches);
                        if (!empty($matches)) {
                            $index++;
                            $chapterData[$index] = [
                                'id'       => $matches[2],
                                'name'     => $matches[1].' '.$matches[2].' '.trim($matches[3]),
                                'type'     => $extension,
                                'contents' => [],
                            ];
                        }
                        else {
                            try {
                                array_push($chapterData[$index]['contents'], $content);
                            } catch (\Exception $e) {
                                dump($filePath);
                                dd($e->getCode(), $e->getMessage(), $e);
                            }
                        }
                    }
                }
            }
        }

        return $chapterData;
    }

    private function getChapterDuplicate($chapters, $chapter, $subId, $str = 'init')
    {
        $chapterId = $chapter['id'];
        if ($subId > 0) {
            $chapterId = $chapter['id'].'-'.$subId;
        }
        $subId++;
        $chapterIndex = $this->getNovelDirectory().''.$chapterId;
        $chapterName = $chapter['name'];
        $chapterType = $chapter['type'];

        $chapter['id'] = $chapterId;

        if (isset($chapters[$chapterIndex]) && !empty($chapters[$chapterIndex])) {
            if ($chapters[$chapterIndex]['id'] == $chapterId) {
                if ($chapters[$chapterIndex]['type'] !== $chapterType) {
                    if (in_array($chapterType, ['html', 'xhtml'])) {
                        return $chapter;
                    }
                    else {
                        return $chapters[$chapterIndex];
                    }
                }
                else {
                    if ($chapters[$chapterIndex]['name'] !== $chapterName) {
                        // dump('if');
                        // dump($chapter);
                        return $this->getChapterDuplicate($chapters, $chapter, $subId, 'recursive');
                    }
                    else {
                        // dump('else');
                        // dump($chapter);
                        return $chapter;
                    }
                }
            }
            else {
                // $chapter['id'] = $chapterId;
                // dump('else else');
                // dump($chapter);
                return $chapter;
            }
        }
        else {
            // $chapter['id'] = $chapterId;
            // dump('else else else');
            // dump($chapter);
            return $chapter;
        }
    }

    public function file()
    {
        $seeks = [];
        foreach ($this->novels['seeks'] as $seek) {
            foreach ($seek as $item) {
                if (!in_array($item, $seeks)) {
                    array_push($seeks, $item);
                }
            }
        }

        try {
            $extensions = [
                'txt',
                'doc',
                'docx',
                'odt',
                'pdf',
                'epub',
                'zip',
                'rar',
            ];
            $exception = [
                'directory' => ['_demo_', 'lms'],
                'file'      => [
                    '.',
                    '..',
                    '.DS_Store',
                    '.localized',
                ],
            ];

            $files = \Storage::disk('download')->files('');
            // dd($files);
            foreach ($files as $file) {
                if (!in_array($file, $exception['file'])) {
                    $pattern = '';
                    $pattern .= '/';
                    $pattern .= '(.*?)';
                    $pattern .= '\.';
                    $pattern .= '(';
                    $pattern .= implode('|', $extensions);
                    $pattern .= ')';
                    $pattern .= '$/i';
                    preg_match($pattern, $file, $matches);
                    // dd($matches);
                    if (!empty($matches)) {
                        // dd($file, $pattern, $matches);

                        // $file = $matches[0];
                        $fileSeek = null;
                        $fileName = $matches[1];
                        $fileExtension = $matches[2];
                        $fileExtension = strtolower($fileExtension);

                        if (isset($fileExtension) && !empty($fileExtension)) {
                            $__FileExtensions = ['txt', 'doc', 'docx', 'odt'];
                            if (in_array($fileExtension, $__FileExtensions)) {
                                $pattern = '';
                                $pattern .= '/';
                                $pattern .= '(';
                                $pattern .= implode('|', $seeks);
                                $pattern .= ')';
                                $pattern .= '\s*';
                                $pattern .= '((\d+)\s*(\-?)\s*(\d*))';
                                $pattern .= '/i';
                                preg_match($pattern, $fileName, $matches);
                                // dd($matches);
                                if (!empty($matches)) {
                                    // dd($file, $pattern, $matches);

                                    $fileCode = null;
                                    $fileSeek = $matches[1];
                                    $fileName = $matches[2];
                                    $fileFrom = $matches[3];
                                    $fileSeparate = $matches[4];
                                    $fileTo = $matches[5];

                                    $chapterCount = 0;
                                    $novelFile = trim($fileName).'.'.$fileExtension;

                                    if (isset($fileFrom) && !empty($fileFrom)) {
                                        $novelFile = trim($fileFrom).trim($fileSeparate).trim($fileTo).'.'.$fileExtension;
                                        $chapterCount = 1;
                                        if (isset($fileTo) && !empty($fileTo)) {
                                            $chapterCount = ($fileTo - $fileFrom) + 1;
                                        }
                                    }

                                    foreach ($this->novels['seeks'] as $code => $lists) {
                                        if (in_array($fileSeek, $lists)) {
                                            $fileCode = $code;
                                        }
                                    }

                                    $size = \Storage::disk('download')->size($file);
                                    $data = [
                                        'path'      => $file,
                                        'fullPath'  => \Storage::disk('download')->path($file),
                                        'name'      => $novelFile,
                                        'code'      => $fileCode,
                                        'fileFrom'  => $fileFrom,
                                        'fileTo'    => $fileTo,
                                        'extension' => $fileExtension,
                                        'mimeType'  => \Storage::disk('download')->mimeType($file),
                                        'size'      => [
                                            $size,
                                            $this->bytes2units($size),
                                        ],
                                        'count'     => $chapterCount,
                                    ];

                                    if (isset($fileCode) && !empty($fileCode)) {
                                        $__FileExtensions = [
                                            'doc',
                                            'docx',
                                            'odt',
                                        ];
                                        if ($fileExtension === 'txt') {
                                            $data['convert'] = $this->txtConverter($data, $fileExtension);
                                        }
                                        elseif (in_array($fileExtension, $__FileExtensions)) {
                                            $data['convert'] = $this->txtConverter($data, $fileExtension);
                                        }
                                    }
                                    else {
                                        throw new \Exception('Invalid, file code', 600);
                                    }

                                    if (!isset($this->logs[$fileCode]) || empty($this->logs[$fileCode])) {
                                        $this->logs[$fileCode] = [];
                                    }

                                    array_push($this->logs[$fileCode], $data);
                                }
                                else {
                                    $__FileExtensions = ['doc', 'docx', 'odt'];
                                    $targetFilePath = null;
                                    if ($fileExtension === 'txt') {
                                        $targetFilePath = 'tmp/__TXT/'.$file;
                                    }
                                    elseif (in_array($fileExtension, $__FileExtensions)) {
                                        $targetFilePath = 'tmp/__DOC/'.$file;
                                    }
                                    else {
                                        $targetFilePath = 'tmp/__ELSE/'.$file;
                                    }

                                    $srcFilePath = \Storage::disk('download')->path($file);
                                    $dstFilePath = \Storage::path($this->getStorageDirectory().$targetFilePath);
                                    $copy = copy($srcFilePath, $dstFilePath);;
                                    if ($copy) {
                                        $delete = \Storage::disk('download')->delete($file);
                                    }
                                }
                            }
                            else {
                                $targetFilePath = null;
                                if ($fileExtension === 'epub') {
                                    $targetFilePath = 'tmp/__EPUB/'.$file;
                                }
                                elseif ($fileExtension === 'pdf') {
                                    $targetFilePath = 'tmp/__PDF/'.$file;
                                }
                                elseif (in_array($fileExtension, [
                                    'zip',
                                    'rar',
                                ])) {
                                    $targetFilePath = 'tmp/__ZIP/'.$file;
                                }
                                else {
                                    $targetFilePath = 'tmp/__ELSE/'.$file;
                                }

                                $srcFilePath = \Storage::disk('download')->path($file);
                                $dstFilePath = \Storage::path($this->getStorageDirectory().$targetFilePath);
                                $copy = copy($srcFilePath, $dstFilePath);;
                                if ($copy) {
                                    $delete = \Storage::disk('download')->delete($file);
                                }
                            }
                        }
                        else {
                            throw new \Exception('Unknown, file extension', 500);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            dump($e->getCode(), $e->getMessage(), $e);
        }

        dd($this->logs);
    }

    /*
     * Function.
     * ------------------------------------------------------------- */
    private function novelConfigurations()
    {
        try {
            $data = Yaml::parse(\Storage::get($this->getStorageDirectory().'novel/configs/novels.yaml'));
            foreach ($data as $item) {
                $novel = [
                    'code'        => null,
                    'title'       => null,
                    'author'      => null,
                    'description' => null,
                    'image'       => ['url' => null,],
                    'end'         => false,
                    'chapters'    => [],
                ];

                if (isset($item['code']) && !empty($item['code'])) {
                    $novel['code'] = $item['code'];

                    // Images
                    $imagePath = $this->getStorageDirectory().'novel/lists/'.$item['code'].'/cover/cover.jpg';
                    $exists = \Storage::exists($imagePath);
                    // dd($exists);
                    if ($exists) {
                        $novel['image']['url'] = \Storage::url($imagePath);
                    }
                }

                if (isset($item['title']) && !empty($item['title'])) {
                    $novel['title'] = $item['title'];
                }

                if (isset($item['author']) && !empty($item['author'])) {
                    $novel['author'] = $item['author'];
                }

                if (isset($item['description']) && !empty($item['description'])) {
                    $novel['description'] = $item['description'];
                }

                if (isset($item['optional']['end']) && !empty($item['optional']['end'])) {
                    if (boolval($item['optional']['end'])) {
                        $novel['end'] = true;
                    }
                }

                if (isset($novel['code'])) {
                    array_push($this->novels['lists'], $novel);

                    // Seeks
                    if (!isset($this->novels['seeks'][$item['code']]) || empty($this->novels['seeks'][$item['code']])) {
                        $this->novels['seeks'][$item['code']] = [$item['code']];
                    }

                    if (isset($item['optional']['codes']) && !empty($item['optional']['codes'])) {
                        foreach ($item['optional']['codes'] as $code) {
                            if (!in_array($code, $this->novels['seeks'][$item['code']])) {
                                array_push($this->novels['seeks'][$item['code']], $code);
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            dd($e->getCode(), $e->getMessage(), $e);
        }
    }

    private function txt2chapters($text)
    {
        $chapters = [];
        $extension = 'txt';

        // remove whitespace from the beginning and end of a string
        $text = trim($text);
        // dd($text);

        // add newline "\n"
        $matches = null;
        $pattern = '/(.*?)\r?\n$/';
        preg_match($pattern, $text, $matches);
        if (empty($matches)) {
            $text .= "\n";
        }
        // dd($text);

        // filter empty string
        $matches = null;
        $pattern = '/(.*?)\r?\n/';
        preg_match_all($pattern, $text, $matches);
        // dd($matches);
        if (!empty($matches)) {
            $contents = $matches[1];
            $index = -1;

            foreach ($contents as $content) {
                $content = json_decode(str_replace('\ufeff', '', json_encode(trim($content))));
                if (isset($content) && !empty($content) && strlen(trim($content)) > 0) {
                    if (!$this->isUrl($content)) {
                        $chapter = $this->isChapter($content);
                        if (!empty($chapter)) {
                            $index++;
                            $id = $chapter[2];
                            if (isset($chapter[3]) && !empty($chapter[3])) {
                                $id = $chapter[2].'-'.$chapter[3];
                            }
                            $chapters[$index] = [
                                'id'       => $id,
                                'name'     => $chapter[1].' '.$id.' '.trim($chapter[4]),
                                'type'     => $extension,
                                'contents' => [],
                            ];
                        }
                        else {
                            try {
                                array_push($chapters[$index]['contents'], trim($content));
                            } catch (\Exception $e) {
                                dump($content, $this->isChapter($content, true));
                                dd($e->getCode(), $e->getMessage(), $e);
                            }
                        }
                    }
                }
            }
        }

        // dd($chapters);

        return $chapters;
    }

    private function isUrl($text)
    {
        // check url ref, "https://fictionlog.co/c/5db14d7bcff050001a10acd6"
        $matches = null;
        $pattern = '/http[s]?\:/';
        preg_match($pattern, $text, $matches);
        // dd($matches);
        if (!empty($matches)) {
            return true;
        }

        return false;
    }

    private function isChapter($text, $error = false)
    {
        // $text = 'บทที่ 1042 : ต้องสู้ถึงจะชนะ (ต้น)!';
        // Get, Chapter name from "txt"
        // > 'ตอนที่', 'บทที่'
        /*
         [
            0 => "ตอนที่ 951 ราชาใบไม้สีทองตัวจริงกับตัวปลอม (1)"
            1 => "ตอนที่"
            2 => "951"
            3 => ""
            4 => "ราชาใบไม้สีทองตัวจริงกับตัวปลอม (1)"
         ]

         [
            0 => "บทที่ 657.3 ช่วยคนสำเร็จ"
            1 => "บทที่"
            2 => "657"
            3 => "3"
            4 => "ช่วยคนสำเร็จ"
         ]
         */
        $pattern = '/^(';
        $pattern .= implode('|', $this->chapterWords);
        $pattern .= ')';
        $pattern .= '\s*(\d+)\.?(\d+)*\s*\:?\s*(.*?)$/i';
        preg_match($pattern, $text, $matches);
        if ($error) {
            dd($text, $pattern, $matches, count($matches));
        }

        return $matches;
    }

    private function ePubConverter(array $novel)
    {
        $htmlContentHeader = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\" \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n"."<html xmlns=\"http://www.w3.org/1999/xhtml\">\n"."<head>\n"."<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n"."<link href=\"../Styles/style.css\" rel=\"stylesheet\" type=\"text/css\"/>\n"."<title></title>\n"."</head>\n"."<body>\n";
        $htmlContentFooter = "</body>\n</html>\n";

        $book = new EPub();

        // Title and Identifier are mandatory!
        $book->setTitle($novel['title']);
        $book->setIdentifier($novel['title'], EPub::IDENTIFIER_URI);
        $book->setLanguage('en');
        $book->setDescription('Test Description');
        $book->setAuthor($novel['author'], $novel['author']);
        $book->setPublisher('', '');
        $book->setDate(time());
        $book->setRights('Copyright and licence information specific for the book.');
        $book->setSourceURL('');

        // Insert custom meta data to the book, in this case, Calibre series index information.
        CalibreHelper::setCalibreMetadata($book, $novel['title'], '5');

        // A book need styling, in this case we use static text, but it could have been a file.
        $cssPath = $this->getStorageDirectory().'novel/configs/styles/style.css';
        $css = \Storage::get($cssPath);
        $book->addCSSFile('Styles/style.css', 'css1', $css);

        $fontsPath = $this->getStorageDirectory().'novel/configs/fonts/THSarabunNewest2.otf';
        $fonts = \Storage::get($fontsPath);
        $book->addFile('Fonts/THSarabunNewest2.otf', 'font1', $fonts, 'application/vnd.ms-opentype');

        $fontsBoldPath = $this->getStorageDirectory().'novel/configs/fonts/THSarabunNewest2-Bold.otf';
        $fontsBold = \Storage::get($fontsBoldPath);
        $book->addFile('Fonts/THSarabunNewest2-Bold.otf', 'font2', $fontsBold, 'application/vnd.ms-opentype');

        $fontsItalicPath = $this->getStorageDirectory().'novel/configs/fonts/THSarabunNewest2-Italic.otf';
        $fontsItalic = \Storage::get($fontsItalicPath);
        $book->addFile('Fonts/THSarabunNewest2-Italic.otf', 'font3', $fontsItalic, 'application/vnd.ms-opentype');

        $fontsBoldItalicPath = $this->getStorageDirectory().'novel/configs/fonts/THSarabunNewest2-BoldItalic.otf';
        $fontsBoldItalic = \Storage::get($fontsBoldItalicPath);
        $book->addFile('Fonts/THSarabunNewest2-BoldItalic.otf', 'font4', $fontsBoldItalic, 'application/vnd.ms-opentype');

        // Add cover page
        try {
            $coverFile = $this->getStorageDirectory().'novel/lists/'.$this->getNovelDirectory().'/cover/cover.jpg';
            $cover = \Storage::get($coverFile);
            $book->setCoverImage('Images/cover.jpg', $cover, 'image/jpeg');
        } catch (\Exception $e) {
            dd($e->getCode(), $e->getMessage(), $e);
        }

        // Add description
        try {
            $descriptionFile = $this->getStorageDirectory().'novel/lists/'.$this->getNovelDirectory().'/description/xhtml/description.xhtml';
            $description = \Storage::get($descriptionFile);
            $book->addChapter($novel['title'], 'Text/description.xhtml', $description, true, EPub::EXTERNAL_REF_ADD);
        } catch (\Exception $e) {
            dd($e->getCode(), $e->getMessage(), $e);
        }

        foreach ($novel['chapters'] as $chapter) {
            if (!isset($chapter['name'])) {
                $err = '';
                $err .= 'Novel : '.$this->getNovelDirectory();
                $err .= ', ';
                $err .= 'Data : '.json_encode($chapter);
                throw new \Exception($err);
            }
            $chapterId = $chapter['id'];
            $chapterName = $chapter['name'];
            $chapterType = $chapter['type'];
            $chapterContents = $chapter['contents'];
            // $fileName = 'Text/chapter'.$chapterId.'.xhtml';
            $fileName = 'Text/'.$chapterId.'.xhtml';

            $chapterData = null;
            if ($chapterType === 'txt') {
                $chapterData = '';
                $chapterData .= $htmlContentHeader;
                $chapterData .= '<h3 class="chapter_heading">'.$chapterName.'</h3>';
                foreach ($chapterContents as $index => $content) {
                    $chapterData .= '<p class="p_normal">'.$content.'</p>';
                }
                $chapterData .= $htmlContentFooter;
            }
            elseif (in_array($chapterType, ['html', 'xhtml'])) {
                $chapterData = \Storage::get($chapterContents);
            }

            $book->addChapter($chapterName, $fileName, $chapterData, true, EPub::EXTERNAL_REF_ADD);
        }

        $book->finalize(); // Finalize the book, and build the archive.

        // Send the book to the client. ".epub" will be appended if missing.
        $bookName = $novel['title'];
        if ($novel['end']) {
            $end = '[END]';
            $bookName = $bookName.' '.$end;
            $bookName = trim($bookName);
        }
        else {
            $chapterNumbers = $this->getChapterNumbers($novel);
            if (isset($chapterNumbers)) {
                $bookName = $bookName.' '.$chapterNumbers;
                $bookName = trim($bookName);
            }
        }
        $zipData = $book->sendBook($bookName);
    }

    private function getChapterNumbers($novel)
    {
        $chapterNumbers = '';
        if (isset($novel['chapters']) && !empty($novel['chapters'])) {
            $tmpChapters = array_values($novel['chapters']);
            $cnt = count($tmpChapters);
            $from = $tmpChapters[0]['id'];
            $to = $tmpChapters[$cnt - 1]['id'];
            if (intval($from) == 0) {
                $from = 1;
            }
            $chapterNumbers = $from.'-'.$to;
        }

        return $chapterNumbers;
    }

    private function txtConverter($data, $fileExtension)
    {
        $convert = [
            'text' => [],
            'raw'  => [],
        ];
        if (isset($data['code']) && !empty($data['code'])) {
            try {
                $txt = '';
                if ($fileExtension === 'txt') {
                    $txt = \Storage::disk('download')->get($data['path']);
                }
                elseif (in_array($fileExtension, ['doc', 'docx', 'odt'])) {
                    $docObj = new Doc2Txt($data['fullPath']);
                    $txt = $docObj->convertToText();
                }
                // dd($txt);

                $chapters = $this->txt2chapters($txt);
                // dd($chapters);

                $directory = $this->getStorageDirectory().'novel/lists/'.$data['code'].'/chapters';
                if (is_array($chapters) && !empty($chapters)) {
                    foreach ($chapters as $chapter) {
                        $id = $chapter['id'];
                        $name = $chapter['name'];
                        $extension = $chapter['type'];
                        $contents = $chapter['contents'];

                        $fileName = $id.'.'.$extension;
                        $filePath = $directory.'/text/'.$fileName;

                        if (!\Storage::exists($filePath)) {
                            \Storage::put($filePath, $name."\n");
                        }
                        else {
                            if (\Storage::delete($filePath)) {
                                \Storage::put($filePath, $name."\n");
                            }
                        }

                        if (\Storage::exists($filePath)) {
                            foreach ($contents as $content) {
                                \Storage::append($filePath, $content."\n");
                            }
                        }

                        $size = \Storage::size($filePath);
                        $text = [
                            'path'      => $filePath,
                            'fullPath'  => \Storage::path($filePath),
                            'name'      => $fileName,
                            'extension' => $extension,
                            'mimeType'  => \Storage::mimeType($filePath),
                            'size'      => [
                                $size,
                                $this->bytes2units($size),
                            ],
                        ];
                        array_push($convert['text'], $text);
                    }
                }

                if ($data['count'] == count($convert['text'])) {
                    $fileName = $data['name'];
                    $filePath = $directory.'/raw/'.$fileName;
                    $contents = \Storage::disk('download')->get($data['path']);
                    // dd($contents);
                    if ($contents) {
                        $put = \Storage::put($filePath, $contents);
                        if ($put) {
                            $delete = \Storage::disk('download')->delete($data['path']);
                            $size = \Storage::size($filePath);
                            $convert['raw'] = [
                                'path'      => $filePath,
                                'fullPath'  => \Storage::path($filePath),
                                'name'      => $fileName,
                                'extension' => $data['extension'],
                                'mimeType'  => \Storage::mimeType($filePath),
                                'size'      => [
                                    $size,
                                    $this->bytes2units($size),
                                ],
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                dd($e->getCode(), $e->getMessage(), $e);
            }
        }
        else {
            throw new \Exception('Invalid, file code', 600);
        }

        return $convert;
    }

    private function bytes2units($bytes)
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

    private function units2bytes(string $from)
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

    /*
     * Configuration.
     * ------------------------------------------------------------- */
    private function setNovelDirectory(string $novelDirectory)
    {
        $this->novelDirectory = $novelDirectory;
    }

    private function getNovelDirectory()
    {
        return $this->novelDirectory;
    }

    private function setStorageDirectory(string $storageDirectory)
    {
        $this->storageDirectory = $storageDirectory;
    }

    private function getStorageDirectory()
    {
        return $this->storageDirectory;
    }
}
