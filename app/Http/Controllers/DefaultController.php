<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;
use Symfony\Component\Yaml\Yaml;

use Revolution\Google\Sheets\Facades\Sheets;

class DefaultController extends Controller
{
    public function index()
    {
        $s1 = storage_path();
        $s2 = storage_path('app');
        $s3 = storage_path('app/public');
        dump($s1, $s2, $s3);
        $a = Storage::disk('local');
        $b = Storage::disk('public');
        dump($a->files(), $b);

        $now = Carbon::now();
        $url = null;
        $file_name = strtolower($now->shortEnglishDayOfWeek);
        $file_name = $file_name.'.jpg';
        $directory_path = 'public/images/days/';
        $file_path = $directory_path.$file_name;
        $exists = Storage::disk('local')->exists($file_path);
        if ($exists) {
            $file_url = Storage::disk('local')->url($file_path);
            $url = URL::to($file_url);
        }

        dump($file_path, $exists, $url);

        $path = 'line-members.yaml';
        $exists = Storage::disk('local')->exists($path);
        $lineMembersArray = [
            'groups' => [],
        ];
        if ($exists) {
            $lineMembersArray = Yaml::parse(Storage::disk('local')->get($path));
        }
        dd($lineMembersArray);

        $a = [
            'groups' => [
                [
                    'id'    => '1',
                    'name'  => 'name1',
                    'users' => [
                        [
                            'id'          => '1',
                            'displayName' => '',
                            'pictureUrl'  => '',
                        ],
                        [
                            'id'          => '2',
                            'displayName' => '',
                            'pictureUrl'  => '',
                        ],
                    ],
                ],
                [
                    'id'    => '2',
                    'name'  => 'name2',
                    'users' => [],
                ],
            ],
        ];
        $value = Yaml::dump($a, 2, 8);
        dump($value);

        file_put_contents($lineMemberPath, $value);
    }

    public function sheet()
    {
        $spreadsheetId = '1ygx4ykVQkCtV-P-ksPYbekCJizNSRBqDoB4vLndynzY';
        $sheetId = 'line-member';
        $sheetId = 'rom-member';
        $sheetId = 'rom-party';

        /** @var Collection $sheets */
        $sheets = Sheets::spreadsheet($spreadsheetId)->sheet($sheetId)->get();
        dump($sheets);

        $header = $sheets->pull(0);
        dump($header);

        /** @var Collection $posts */
        $posts = Sheets::collection($header, $sheets);
        dump($posts);
        exit();
    }

    public function collection()
    {
        $spreadsheetId = '1ygx4ykVQkCtV-P-ksPYbekCJizNSRBqDoB4vLndynzY';
        $sheetId = 'rom-party';

        /** @var Collection $sheets */
        $sheets = Sheets::spreadsheet($spreadsheetId)->sheet($sheetId)->get();

        $header = $sheets->pull(0);

        /** @var Collection $party */
        $party = Sheets::collection($header, $sheets);

        $texts = [
            'header' => 'ประกาศตี้สำหรับ WOE|WOC ประจำวันที่ ',
            'body'   => [],

        ];
        if ($party->count() > 0) {
            $now = Carbon::now();
            $texts['header'] .= $now->format('d D, Y')."\n";
            foreach ($party as $p) {
                $text = [];
                $text[] = "ทีมที่ : ".$p->get('id');

                for ($i=1;$i<=8;$i++) {
                    if (!empty($p->get('member'.$i))) {
                        $text[] = $i.": ".$p->get('member'.$i);
                    }
                }

                array_push($texts['body'], $text);
            }
        }

        dump($texts);

        $textss = "";
        $textss .= $texts['header'];

        foreach ($texts['body'] as $b) {
            $textss .= "\n";
            $textss .= implode("\n", $b);
            $textss .= "\n";
        }

        dd($textss);

        exit();
    }
}
