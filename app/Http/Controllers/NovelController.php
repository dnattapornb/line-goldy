<?php

namespace App\Http\Controllers;

use App\Libraries\Doc2Txt;

use App\Services\NovelService;
use App\Services\Novel;
use App\Services\FileConverter;
use App\Services\ChapterContent;
use App\Services\PathFile;

use PHPePub\Core\EPub;
use PHPePub\Core\EPubChapterSplitter;
use PHPePub\Core\Logger;
use PHPePub\Core\Structure\OPF\DublinCore;
use PHPePub\Core\Structure\OPF\MetaValue;
use PHPePub\Helpers\CalibreHelper;
use PHPePub\Helpers\URLHelper;
use PHPZip\Zip\File\Zip;
use Sunra\PhpSimple\HtmlDomParser;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Yaml\Yaml;

use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class NovelController extends Controller
{
    private $novelService;

    public function __construct()
    {
        // Fix, Maximum execution time of 180 seconds exceeded
        ini_set('max_execution_time', 180);

        $this->novelService = new NovelService();
        // dd($this->novelService);
    }

    public function index()
    {
        $novels = $this->novelService->getNovels();
        dd($novels);
    }

    public function show(string $code)
    {
        $novels = $this->novelService->getNovels($code);
        try {
            foreach ($novels as $novel) {
                /** @var ChapterContent[] $chapters */
                $chapters = [];
                foreach ($novel->getPath() as $k => $path) {
                    if (in_array($k, ['xhtml', 'text'])) {
                        $exists = Storage::disk($path->getDisks())->exists($path->getRelativeFilePath());
                        if ($exists) {
                            $files = Storage::disk($path->getDisks())->files($path->getRelativeFilePath());
                            foreach ($files as $file) {
                                $fileName = basename($file);
                                if (!in_array($fileName, ['.DS_Store'])) {
                                    /**
                                     * $pattern = "/(\d+)([\-|\.]?)(\d*)\.(.*?)$/"
                                     * $file = "0001.xhtml"
                                     * $matches = [
                                     *      0 => "543-1.xhtml"
                                     *      1 => "543"
                                     *      2 => "-"
                                     *      3 => "1"
                                     *      4 => "xhtml"
                                     * ]
                                     */
                                    $pattern = '/(\d+)\s*([\-|\.]?)\s*(\d*)\.(.*?)$/';
                                    preg_match($pattern, $fileName, $matches);
                                    if (!empty($matches)) {
                                        $_INDEX_ = $this->convertZeroFill(intval($matches[1]));
                                        if (isset($matches[2]) && !empty($matches[2]) && isset($matches[3]) && !empty($matches[3])) {
                                            $_INDEX_ .= $matches[2];
                                            $_INDEX_ .= intval($matches[3]);
                                        }
                                        $_KEY_ = $novel->getCode().$_INDEX_;
                                        $extension = $matches[4] ?? '';

                                        // first priority is "xhtml" extension
                                        if (!isset($chapters[$_KEY_]) || empty($chapters[$_KEY_])) {
                                            $text = Storage::disk($path->getDisks())->get($file);
                                            $chapters[$_KEY_] = $this->document2ChapterContents($code, $text, $extension)[0];
                                            $chapters[$_KEY_]->setId($_INDEX_);
                                            $target = new PathFile($path->getDisks(), $path->getRelativeFilePath(), $fileName);
                                            $chapters[$_KEY_]->setTarget($target);
                                            $chapters[$_KEY_]->getTarget()->checkFile();
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                $novel->setChapters($chapters);
            }
        } catch (Exception $e) {
            dd($e->getCode(), $e->getMessage(), $e);
        }

        foreach ($novels as $novel) {
            try {
                $this->epubConverter($novel);
            } catch (FileNotFoundException $e) {
                dd($e->getCode(), $e->getMessage(), $e);
            } catch (Exception $e) {
                dd($e->getCode(), $e->getMessage(), $e);
            }
        }
    }

    public function file()
    {
        try {
            $files = Storage::disk($this->novelService->getPath()['source']->getDisks())->files('');
            // dd($files);
            foreach ($files as $file) {
                /**
                 * $pattern = "/(.*?)\.(txt|doc|docx|odt|pdf|epub|zip|rar)$/i"
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
                $pattern .= '(';
                $pattern .= implode('|', $this->novelService->getExtensions());
                $pattern .= ')';
                $pattern .= '$/i';
                preg_match($pattern, $file, $matches);
                // dd($matches);
                if (!empty($matches)) {
                    // dd($file, $pattern, $matches);
                    $fileName = $matches[1];
                    $fileExtension = strtolower($matches[2]);

                    $fileConverter = new FileConverter();
                    $source = new PathFile($this->novelService->getPath()['source']->getDisks(), '', $file);
                    $fileConverter->setSource($source);

                    if (isset($fileExtension) && !empty($fileExtension)) {
                        $documentExtensions = ['txt', 'doc', 'docx', 'odt'];
                        if (in_array($fileExtension, $documentExtensions)) {
                            /**
                             * $pattern = "/(lohp|atg|sgg|kog|awe)\s*((\d+)\s*(\-?)\s*(\d*))/i"
                             * $fileName = "Godly Stay-Home Dad 1554-1563"
                             * $matches = [
                             *      0 => "Godly Stay-Home Dad 1554-1563"
                             *      1 => "Godly Stay-Home Dad"
                             *      2 => "1554-1563"
                             *      3 => "1554"
                             *      4 => "-"
                             *      5 => "1563"
                             * ]
                             */
                            $pattern = '';
                            $pattern .= '/';
                            $pattern .= '(';
                            $pattern .= implode('|', $this->novelService->getNovelFileNames());
                            $pattern .= ')';
                            $pattern .= '\s*';
                            $pattern .= '((\d+)\s*(\-?)\s*(\d*))';
                            $pattern .= '/i';
                            preg_match($pattern, $fileName, $matches);
                            // dd($matches);
                            if (!empty($matches)) {
                                // dd($file, $pattern, $matches);
                                $fileName = $matches[1];
                                $start = $matches[3];
                                $separate = $matches[4];
                                $end = $matches[5];

                                $code = $this->novelService->getCodeFromFileName($fileName);
                                if (isset($code) && !empty($code)) {
                                    $relative = 'novel/lists/'.$code.'/chapters/raw/';
                                    $fileConverter->setCode($code);

                                    if (isset($start) && !empty($start)) {
                                        if (!isset($end) || empty($end)) {
                                            $end = $start;
                                        }
                                        if (intval($start) <= intval($end)) {
                                            $targetName = trim($start).trim($separate).trim($end).'.'.$fileExtension;
                                            if ($start == $end) {
                                                $targetName = trim($start).trim($separate).trim('').'.'.$fileExtension;
                                            }
                                            $count = ($end - $start) + 1;

                                            $fileConverter->getChapter()->setStart($start);
                                            $fileConverter->getChapter()->setEnd($end);
                                            $fileConverter->getChapter()->setCount($count);

                                            $target = new PathFile($this->novelService->getPath()['target']->getDisks(), $relative, $targetName);
                                            $target->setExtensions($fileExtension);
                                            $fileConverter->setTarget($target);

                                            $this->fileBuilder($fileConverter);
                                        }
                                        else {
                                            throw new Exception('Error, chapter start less than chapter end on : ['.$file.']', 7000);
                                        }
                                    }
                                    else {
                                        $relative = 'tmp/';
                                        if ($fileExtension === 'txt') {
                                            $relative .= '__TXT/';
                                        }
                                        elseif (in_array($fileExtension, [
                                            'doc',
                                            'docx',
                                            'odt',
                                        ])) {
                                            $relative .= '__DOC/';
                                        }
                                        else {
                                            $relative .= '__ELSE/';
                                        }

                                        $target = new PathFile($this->novelService->getPath()['target']->getDisks(), $relative, $file);
                                        $target->setExtensions($fileExtension);
                                        $fileConverter->setTarget($target);
                                    }
                                }
                                else {
                                    throw new Exception('Error, novel code not found on : ['.$file.']', 7000);
                                }
                            }
                            else {
                                $relative = 'tmp/';
                                if ($fileExtension === 'txt') {
                                    $relative .= '__TXT/';
                                }
                                elseif (in_array($fileExtension, [
                                    'doc',
                                    'docx',
                                    'odt',
                                ])) {
                                    $relative .= '__DOC/';
                                }
                                else {
                                    $relative .= '__ELSE/';
                                }

                                $target = new PathFile($this->novelService->getPath()['target']->getDisks(), $relative, $file);
                                $target->setExtensions($fileExtension);
                                $fileConverter->setTarget($target);
                            }
                        }
                        else {
                            $relative = 'tmp/';
                            if ($fileExtension === 'epub') {
                                $relative .= '__EPUB/';
                            }
                            elseif ($fileExtension === 'pdf') {
                                $relative .= '__PDF/';
                            }
                            elseif (in_array($fileExtension, ['zip', 'rar',])) {
                                $relative .= '__ZIP/';
                            }
                            else {
                                $relative .= '__ELSE/';
                            }

                            $target = new PathFile($this->novelService->getPath()['target']->getDisks(), $relative, $file);
                            $target->setExtensions($fileExtension);
                            $fileConverter->setTarget($target);
                        }
                    }
                    else {
                        throw new Exception('Error, empty file extension on : ['.$file.']', 7000);
                    }
                    $this->novelService->addFileConverters($fileConverter);
                }
            }

            if (sizeof($this->novelService->getFileConverters()) > 0) {
                foreach ($this->novelService->getFileConverters() as $fileConverter) {
                    $backup = true;
                    if ($fileConverter->getCode()) {
                        $backup = false;

                        $countChapter = $fileConverter->getChapter()->getCount();
                        $sizeofChapterLists = sizeof($fileConverter->getChapter()->getChapterContents());
                        if ($sizeofChapterLists > 0) {
                            foreach ($fileConverter->getChapter()->getChapterContents() as $chapter) {
                                if (Storage::disk($chapter->getTarget()->getDisks())->exists($chapter->getTarget()->getRelativeFilePath())) {
                                    $delete = Storage::disk($chapter->getTarget()->getDisks())->delete($chapter->getTarget()->getRelativeFilePath());
                                    if (!$delete) {
                                        throw new Exception('Error, delete "'.$chapter->getTarget()->getFullPath().'"]" is fails', 7000);
                                    }
                                }
                                Storage::disk($chapter->getTarget()->getDisks())->put($chapter->getTarget()->getRelativeFilePath(), $chapter->getName()."\n");

                                foreach ($chapter->getContents() as $content) {
                                    Storage::disk($chapter->getTarget()->getDisks())->append($chapter->getTarget()->getRelativeFilePath(), $content."\n");
                                }

                                $fileConverter->getTarget()->checkFile();
                            }

                            if ($countChapter === $sizeofChapterLists) {
                                $backup = true;
                            }
                        }
                    }

                    // backup file "source" to "target"
                    if ($backup) {
                        $fileConverter->getTarget()->getDisks();
                        $fileConverter->getTarget()->getRelativeFilePath();

                        $sourceFilePath = Storage::disk($fileConverter->getSource()->getDisks())->path($fileConverter->getSource()->getRelativeFilePath());
                        $targetFilePath = Storage::disk($fileConverter->getTarget()->getDisks())->path($fileConverter->getTarget()->getRelativeFilePath());
                        $copy = copy($sourceFilePath, $targetFilePath);
                        if ($copy) {
                            $fileConverter->getTarget()->checkFile();
                            $delete = Storage::disk($fileConverter->getSource()->getDisks())->delete($fileConverter->getSource()->getRelativeFilePath());
                            if (!$delete) {
                                throw new Exception('Error, delete "'.$targetFilePath.'"]" is fails', 7000);
                            }
                        }
                        else {
                            throw new Exception('Error, copy "'.$sourceFilePath.'"]" to "'.$targetFilePath.'"', 7000);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            dump($e->getCode(), $e->getMessage(), $e);
        }

        dd($this->novelService, $this->novelService->getFileConverters());
    }

    /*
     * Function.
     * ------------------------------------------------------------- */
    /**
     * @param  \App\Services\FileConverter  $fileConverter
     */
    private function fileBuilder(FileConverter $fileConverter)
    {
        $docExtensions = ['doc', 'docx', 'odt'];
        try {
            $txt = null;
            if (in_array($fileConverter->getSource()->getExtensions(), $docExtensions)) {
                $docObj = new Doc2Txt($fileConverter->getSource()->getFullPath());
                $txt = $docObj->convertToText();
            }
            elseif ($fileConverter->getSource()->getExtensions() === 'txt') {
                $txt = Storage::disk($fileConverter->getSource()->getDisks())->get($fileConverter->getSource()->getRelativeFilePath());
            }
            else {
                throw new Exception('Error, file extension is not allowed : ['.$fileConverter->getSource()->getExtensions().']', 500);
            }

            if (isset($txt) && is_string($txt) && strlen($txt) > 0) {
                $fileConverter->setText($txt);
            }
            else {
                throw new Exception('Error, text file content is empty', 500);
            }

            $chapterContents = $this->document2ChapterContents($fileConverter->getCode(), $fileConverter->getText(), 'txt');
            $fileConverter->getChapter()->setChapterContents($chapterContents);
        } catch (Exception $e) {
            dd($e->getCode(), $e->getMessage(), $e);
        }
    }

    /**
     * @param  string  $code
     * @param  string  $text
     * @param  string  $extension
     *
     * @return array
     * @throws Exception
     */
    private function document2ChapterContents(string $code, string $text, string $extension = 'txt')
    {
        $chapterContents = [];

        if (!isset($code) || empty($code)) {
            throw new Exception('Invalid, novel code', 8000);
        }

        if (!isset($text) || empty($text)) {
            throw new Exception('Error, text file is empty', 7000);
        }

        if (!isset($extension) || empty($extension)) {
            throw new Exception('Invalid, file extension', 8000);
        }

        if ($extension === 'txt') {
            // remove whitespace from the beginning and end of a string
            $text = trim($text);

            // adding "\n"(new line)
            $matches = null;
            $pattern = '/(.*?)\r?\n$/';
            preg_match($pattern, $text, $matches);
            if (empty($matches)) {
                $text .= "\n";
            }

            // filter empty string
            $matches = null;
            $pattern = '/(.*?)\r?\n/';
            preg_match_all($pattern, $text, $matches);
            if (!empty($matches)) {
                $index = -1;
                $contents = $matches[1];
                foreach ($contents as $content) {
                    $content = json_decode(str_replace('\ufeff', '', json_encode(trim($content))));
                    if (isset($content) && !empty($content) && strlen(trim($content)) > 0) {
                        if (!$this->isUrl($content)) {
                            $chapter = $this->isChapter($content, $code);
                            if (!empty($chapter)) {
                                $index++;
                                $chapter = $this->validateChapter($chapter, $extension);
                                $relative = $this->novelService->getPath()['target']->getRelativePath().$code.'/chapters/text/';

                                $chapterContents[$index] = new ChapterContent();
                                $chapterContents[$index]->setId($chapter['id']);
                                $chapterContents[$index]->setName($chapter['name']);
                                $chapterContents[$index]->setContents([]);
                                $target = new PathFile($this->novelService->getPath()['target']->getDisks(), $relative, $chapter['fileName']);
                                $target->setExtensions($extension);
                                $chapterContents[$index]->setTarget($target);
                            }
                            else {
                                $chapterContents[$index]->addContents($content);
                            }
                        }
                    }
                }
            }
        }
        elseif (in_array($extension, ['html', 'xhtml'])) {
            $index = 0;
            $dom = HtmlDomParser::str_get_html($text);
            if ($dom) {
                $body = $dom->find('body', 0);

                $title = null;
                if ($body->find('h1.chapter_hidden', 0)) {
                    $title = $body->find('h1.chapter_hidden', 0)->plaintext;
                }

                $name = null;
                if ($body->find('h3.chapter_heading', 0)) {
                    $name = $body->find('h3.chapter_heading', 0)->plaintext;
                }
                else {
                    throw new Exception('Error, chapter name ['.$text.'], on ['.$code.']', 7000);
                }

                $chapter = $this->isChapter($name, $code);
                $chapter = $this->validateChapter($chapter, $extension);
                $relative = $this->novelService->getPath()['target']->getRelativePath().$code.'/chapters/text/';

                $chapterContents[$index] = new ChapterContent();
                $chapterContents[$index]->setId($chapter['id']);
                if ($title && strlen($title) > 0) {
                    $chapterContents[$index]->setTitle($title);
                }
                $chapterContents[$index]->setName($chapter['name']);
                $chapterContents[$index]->setContents([]);
                $target = new PathFile($this->novelService->getPath()['target']->getDisks(), $relative, $chapter['fileName']);
                $target->setExtensions($extension);
                $chapterContents[$index]->setTarget($target);
                foreach ($body->find('p') as $element) {
                    $content = trim($element->plaintext);
                    $chapterContents[$index]->addContents($content);
                }
            }
        }
        else {
            throw new Exception('Error, file extension is ['.$extension.']', 7000);
        }

        return $chapterContents;
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

    /**
     * @param  string       $text
     * @param  string|null  $code
     * @param  boolean      $error
     *
     * @return array
     * @throws Exception
     */
    private function isChapter(string $text, $code = null, $error = false)
    {
        /**
         * $pattern = "/^(ตอนที่|บทที่)\s*(\d+)\s*\.?\-?\s*(\d+)*\s*\:?\s*(.*?)$/i"
         * $text = "บทที่ 1 ชื่อของเขาคือป๋ายเสี่ยวฉุน"
         * $matches = [
         *      0 => "บทที่ 1 ชื่อของเขาคือป๋ายเสี่ยวฉุน"
         *      1 => "บทที่"
         *      2 => "1"
         *      3 => ""
         *      4 => "ชื่อของเขาคือป๋ายเสี่ยวฉุน"
         * ]
         *
         * $text = "ตอนที่ 951 ราชาใบไม้สีทองตัวจริงกับตัวปลอม (1)"
         * $matches = [
         *      0 => "ตอนที่ 951 ราชาใบไม้สีทองตัวจริงกับตัวปลอม (1)"
         *      1 => "ตอนที่"
         *      2 => "951"
         *      3 => ""
         *      4 => "ราชาใบไม้สีทองตัวจริงกับตัวปลอม (1)"
         * ]
         *
         * $text = "บทที่ 657.3 ช่วยคนสำเร็จ"
         * $matches = [
         *      0 => "บทที่ 657.3 ช่วยคนสำเร็จ"
         *      1 => "บทที่"
         *      2 => "657"
         *      3 => "3"
         *      4 => "ช่วยคนสำเร็จ"
         * ]
         */
        $pattern = '/^(';
        $pattern .= implode('|', $this->novelService->getChapterNames($code));
        $pattern .= ')';
        $pattern .= '\s*(\d+)\s*\.?\-?\s*(\d+)*\s*\:?\s*(.*?)$/i';
        preg_match($pattern, $text, $matches);
        if ($error) {
            if (!isset($matches[2])) {
                throw new Exception('Error, chapter ['.$text.'], on ['.$code.']', 7000);
            }
        }

        return $matches;
    }

    /**
     * @param  array   $chapter
     * @param  string  $extension
     *
     * @return null[]
     * @throws Exception
     */
    private function validateChapter(array $chapter, $extension = 'txt')
    {
        $data = [
            'id'       => null,
            'name'     => null,
            'fileName' => null,
        ];
        if (empty($chapter)) {
            throw new Exception('Error, empty chapter regx ['.serialize($chapter).']', 7000);
        }
        else {
            if (!isset($chapter[1]) || empty($chapter[1])) {
                throw new Exception('Error, empty chapter[1] ['.serialize($chapter).']', 7000);
            }
            elseif (!isset($chapter[2])) {
                throw new Exception('Error, empty chapter[2] ['.serialize($chapter).']', 7000);
            }

            // 567
            $data['id'] = $chapter[2];

            // 567-0, 567-1
            if (isset($chapter[3])) {
                $data['id'] = $chapter[2].'-'.$chapter[3];
            }

            // บทที่ 567-1 การเปลี่ยนแปลง (1)
            $data['name'] = $chapter[1].' '.$data['id'].' '.trim($chapter[4]);

            // 567-1.xhtml
            $data['fileName'] = $data['id'].'.'.$extension;
        }

        return $data;
    }

    /**
     * @param       $number
     * @param  int  $length
     *
     * @return string
     * @throws Exception
     */
    private function convertZeroFill($number, int $length = 4)
    {
        /**
         * $pattern = "/(\d+)([\-|\.]?)(\d*)$/"
         * $file = "0091.1"
         * $matches = [
         *      0 => "0091.1"
         *      1 => "0091"
         *      2 => "."
         *      3 => "1"
         * ]
         */
        $pattern = '/(\d+)\s*([\-|\.]?)\s*(\d*)$/';
        preg_match($pattern, $number, $matches);
        if (!empty($matches)) {
            $number = str_pad(intval($matches[1]), $length, '0', STR_PAD_LEFT);
            if (isset($matches[2]) && !empty($matches[2]) && isset($matches[3]) && !empty($matches[3])) {
                $number .= $matches[2];
                $number .= intval($matches[3]);
            }
        }

        return $number;
    }

    /**
     * @param  \App\Services\Novel  $novel
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException|Exception
     */
    private function epubConverter(Novel $novel)
    {
        $htmlContentHeader = $this->getHtmlContentHeader();
        $htmlContentFooter = $this->getHtmlContentFooter();

        $book = new EPub();

        // Title and Identifier are mandatory!
        $book->setTitle($novel->getTitle());
        $book->setIdentifier($novel->getTitle(), EPub::IDENTIFIER_URI);
        $book->setLanguage('en');
        $book->setDescription('Test Description');
        $book->setAuthor($novel->getAuthor(), $novel->getAuthor());
        $book->setPublisher('', '');
        $book->setDate(time());
        $book->setRights('Copyright and licence information specific for the book.');
        $book->setSourceURL('');

        // Insert custom meta data to the book, in this case, Calibre series index information.
        CalibreHelper::setCalibreMetadata($book, $novel->getTitle(), '5');

        // A book need styling, in this case we use static text, but it could have been a file.
        $css = Storage::disk($this->novelService->getPath()['configs']->getDisks())->get($this->novelService->getPath()['configs']->getRelativePath().'styles/style.css');
        $book->addCSSFile('Styles/style.css', 'css1', $css);

        $fonts = Storage::disk($this->novelService->getPath()['configs']->getDisks())->get($this->novelService->getPath()['configs']->getRelativePath().'fonts/THSarabunNewest2.otf');
        $book->addFile('Fonts/THSarabunNewest2.otf', 'font1', $fonts, 'application/vnd.ms-opentype');

        $fontsBold = Storage::disk($this->novelService->getPath()['configs']->getDisks())->get($this->novelService->getPath()['configs']->getRelativePath().'fonts/THSarabunNewest2-Bold.otf');
        $book->addFile('Fonts/THSarabunNewest2-Bold.otf', 'font2', $fontsBold, 'application/vnd.ms-opentype');

        $fontsItalic = Storage::disk($this->novelService->getPath()['configs']->getDisks())->get($this->novelService->getPath()['configs']->getRelativePath().'fonts/THSarabunNewest2-Italic.otf');
        $book->addFile('Fonts/THSarabunNewest2-Italic.otf', 'font3', $fontsItalic, 'application/vnd.ms-opentype');

        $fontsBoldItalic = Storage::disk($this->novelService->getPath()['configs']->getDisks())->get($this->novelService->getPath()['configs']->getRelativePath().'fonts/THSarabunNewest2-BoldItalic.otf');
        $book->addFile('Fonts/THSarabunNewest2-BoldItalic.otf', 'font4', $fontsBoldItalic, 'application/vnd.ms-opentype');

        // Add cover page
        try {
            $cover = Storage::disk($novel->getPath()['cover']->getDisks())->get($novel->getPath()['cover']->getRelativeFilePath());
            $book->setCoverImage('Images/cover.jpg', $cover, 'image/jpeg');
        } catch (Exception $e) {
            dd($e->getCode(), $e->getMessage(), $e);
        }

        // Add description
        try {
            $description = Storage::disk($novel->getPath()['description']->getDisks())->get($novel->getPath()['description']->getRelativeFilePath());
            $book->addChapter($novel->getTitle(), 'Text/description.xhtml', $description, true, EPub::EXTERNAL_REF_ADD);
        } catch (Exception $e) {
            dd($e->getCode(), $e->getMessage(), $e);
        }

        foreach ($novel->getChapters() as $chapter) {
            if (strlen($chapter->getName()) <= 0) {
                throw new Exception('Error, chapter\'s name is ['.$chapter->getName().']', 7000);
            }
            $chapterId = $chapter->getId();
            $chapterTitle = $chapter->getTitle() ?? '';
            $chapterName = $chapter->getName();
            $chapterContents = $chapter->getContents();
            $chapterType = $chapter->getTarget()->getExtensions();
            $fileName = 'Text/'.$chapterId.'.xhtml';

            $chapterData = null;
            if ($chapterType === 'txt' || true) {
                $chapterData = '';
                $chapterData .= $htmlContentHeader;
                if ($chapterTitle && strlen($chapterTitle) > 0) {
                    $chapterData .= '<h1 class="chapter_hidden">'.$chapterTitle.'</h1>';
                }
                $chapterData .= '<h3 class="chapter_heading">'.$chapterName.'</h3>';
                foreach ($chapterContents as $index => $content) {
                    $chapterData .= '<p class="p_normal">'.$content.'</p>';
                }
                $chapterData .= $htmlContentFooter;
            }
            elseif (in_array($chapterType, ['html', 'xhtml'])) {
                $chapterData = $description = Storage::disk($chapter->getTarget()->getDisks())->get($chapter->getTarget()->getRelativeFilePath());
            }

            // Add Part -> Chapter
            $isChapterTitle = true;
            if ($chapterTitle && strlen($chapterTitle) > 0 && $isChapterTitle) {
                $book->rootLevel();
                $book->addChapter($chapterTitle, $fileName."#sub01");
                $book->subLevel();
            }
            $book->addChapter($chapterName, $fileName, $chapterData, true, EPub::EXTERNAL_REF_ADD);
        }
        // Finalize the book, and build the archive.
        $book->finalize();

        // Send the book to the client. ".epub" will be appended if missing.
        $bookName = $novel->getTitle();
        if ($novel->isEnd()) {
            $bookName = $bookName.' [End]';
        }
        else {
            $chapters = array_values($novel->getChapters());
            $cnt = sizeof($chapters);
            $start = (intval($chapters[0]->getId()) != 0) ? intval($chapters[0]->getId()) : 1;
            $end = $chapters[$cnt - 1]->getId();
            $bookName = $bookName.' '.intval($start).'-'.intval($end);
        }
        // $zipData = $book->sendBook(trim($bookName));
        $book->sendBook(trim($bookName));
    }

    private function getHtmlContentHeader()
    {
        $htmlContentHeader = null;
        $htmlContentHeader[] = "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
        $htmlContentHeader[] = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\" \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">";
        $htmlContentHeader[] = "<html xmlns=\"http://www.w3.org/1999/xhtml\">";
        $htmlContentHeader[] = "<head>";
        $htmlContentHeader[] = "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
        $htmlContentHeader[] = "<link href=\"../Styles/style.css\" rel=\"stylesheet\" type=\"text/css\"/>";
        $htmlContentHeader[] = "<title></title>";
        $htmlContentHeader[] = "</head>";
        $htmlContentHeader[] = "<body>";
        $htmlContentHeader[] = "\n";
        $htmlContentHeader = implode("\n", $htmlContentHeader);

        return $htmlContentHeader;
    }

    private function getHtmlContentFooter()
    {
        $htmlContentFooter = null;
        $htmlContentFooter[] = "\n";
        $htmlContentFooter[] = "</body>";
        $htmlContentFooter[] = "</html>";
        $htmlContentFooter[] = "\n";

        return $htmlContentFooter;
    }
}
