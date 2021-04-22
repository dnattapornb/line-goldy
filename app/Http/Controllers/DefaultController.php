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
        $path = 'models\line-users.yaml';
        $exists = Storage::disk('local')->exists($path);
        $users = [];
        if ($exists) {
            $users = Yaml::parse(Storage::disk('local')->get($path));
        }
        dump($users);
    }

    public function sheet()
    {
        $spreadsheetId = config('sheets.post_spreadsheet_id');
        // $sheetId = 'line-member';
        // $sheetId = 'rom-member';
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
        $tests = new Collection();
        for ($i = 1; $i <= 20; $i++) {
            $test = [
                'id'   => $i,
                'name' => 'name'.$i,
            ];
            $tests->add($test);
        }

        dd($tests->toJson(), $tests->toArray());

        // return response($tests->toJson());
        // return response()->json($tests->toArray());
    }
}
