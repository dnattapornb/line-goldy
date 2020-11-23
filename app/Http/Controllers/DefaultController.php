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
        $path = 'line-members.yaml';
        $exists = Storage::disk('local')->exists($path);
        $lineMembersArray = [
            'groups' => [],
        ];
        if ($exists) {
            $lineMembersArray = Yaml::parse(Storage::disk('local')->get($path));
        }
        dump($lineMembersArray);

        $users = new Collection();
        foreach ($lineMembersArray['groups'] as $group) {
            foreach ($group['users'] as $user) {
                $user = [
                    'id'          => $user['id'],
                    'name'        => null,
                    'displayName' => $user['displayName'],
                    'pictureUrl'  => $user['pictureUrl'],
                ];
                $users->add($user);
            }
        }
        dump($users, $users->all(), $users->unique()->values()->toArray());

        $path = 'configs\line-users.yaml';
        Storage::disk('local')->put($path, Yaml::dump($users->unique()->values()->toArray()));
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
        $tests = new Collection();
        for ($i = 1; $i <= 10; $i++) {
            $test = [
                'id'   => $i,
                'name' => 'name'.$i,
            ];
            $tests->add($test);
        }

        dd($tests);
        exit();
    }
}
