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

use Sunra\PhpSimple\HtmlDomParser;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Exception;

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

    public function reportGroupHotel()
    {
        $demoHotels = [
            '000001',
            '000002',
            '000078',
            '000091',
            '000500',
            '000501',
            '000502',
            '001875',
            '002837',
        ];
        $H = [];
        $st = 2016;
        $en = 2022;
        for ($i = $st; $i <= $en; $i++) {
            $path = 'report/group-hotel-'.$i.'.json';
            $exists = Storage::disk('download')->exists($path);
            if ($exists) {
                $jsonString = Storage::disk('download')->get($path);
                $jsons = json_decode($jsonString, true);
                foreach ($jsons as $data) {
                    $hotelId = $data['hotelId'];
                    $hotelName = $data['HotelName'];
                    $hotelLocation = $data['Location'];
                    $total = floatval($data['Total']);

                    if (!in_array($hotelId, $demoHotels)) {
                        if (!isset($H[$hotelId]) || empty($H[$hotelId])) {
                            $H[$hotelId] = [
                                'hotelId'       => $hotelId,
                                'hotelName'     => $hotelName,
                                'hotelLocation' => $hotelLocation,
                                'total'         => [
                                    '2016' => 0.00,
                                    '2017' => 0.00,
                                    '2018' => 0.00,
                                    '2019' => 0.00,
                                    '2020' => 0.00,
                                    '2021' => 0.00,
                                    '2022' => 0.00,
                                ],
                            ];
                        }
                        $H[$hotelId]['total'][$i] = $total;
                    }
                }
            }
        }
        // dd($H['003754']);
        $path = 'report/group-hotel.csv';
        $header = 'HotelId,HotelName,Location,2016,2017,2018,2019,2020,2021,2022';
        Storage::disk('download')->put($path, $header);
        foreach ($H as $data) {
            $content = null;
            $hotelId = $data['hotelId'];
            $hotelName = $data['hotelName'];
            $hotelName = preg_replace('/[ ,]+/', '-', trim($hotelName));
            $hotelName = trim($hotelName);
            $hotelLocation = $data['hotelLocation'];
            $hotelLocation = preg_replace('/[ ,]+/', '-', trim($hotelLocation));
            $hotelLocation = trim($hotelLocation);

            $content = $hotelId.','.$hotelName.','.$hotelLocation;
            foreach ($data['total'] as $datum) {
                $content .= ','.$datum;
            }
            Storage::disk('download')->append($path, $content);
        }
        exit();
    }

    public function reportGroupHotelChannel()
    {
        $hotels = [
            "004334",
            "002196",
            "003679",
            "003233",
            "002270",
            "001443",
            "001452",
            "003068",
            "004500",
            "002572",
            "000099",
            "002611",
            "004785",
            "002839",
            "001366",
            "001240",
            "004890",
            "003342",
            "002064",
            "002215",
            "004268",
            "002913",
            "003693",
            "004800",
            "002131",
            "003679",
            "002649",
            "003669",
            "000089",
            "001359",
            "004889",
        ];
        $demoHotels = [
            '000001',
            '000002',
            '000078',
            '000091',
            '000500',
            '000501',
            '000502',
            '001875',
            '002837',
        ];
        $chs = [
            '000001' => 'Agoda',
            '000002' => 'Expedia',
            // '000004' => 'Hostel World',
            '000005' => 'Booking.com',
            '000008' => 'Hotelbeds',
            '000011' => 'Trip.com',
        ];
        $_TOTAL = [];
        $HCH = [];
        $st = 2016;
        $en = 2021;
        for ($i = $st; $i <= $en; $i++) {
            $path = 'report/group-hotel-channel-'.$i.'.json';
            $exists = Storage::disk('download')->exists($path);
            if ($exists) {
                $jsonString = Storage::disk('download')->get($path);
                $jsons = json_decode($jsonString, true);
                foreach ($jsons as $data) {
                    $hotelId = $data['hotelId'];
                    $hotelName = $data['HotelName'];
                    $hotelLocation = $data['Location'];
                    $channelId = $data['channelId'];
                    $total = floatval($data['Total']);
                    $numberOfBooking = intval($data['NumberOfBooking']);

                    if (!in_array($hotelId, $demoHotels)) {
                        // if (!in_array($hotelId, $demoHotels) && in_array($channelId, array_keys($chs)) ) {
                        // if (in_array($hotelId, $hotels) && in_array($channelId, array_keys($chs))) {
                        if (!isset($HCH[$channelId]) || empty($HCH[$channelId])) {
                            $HCH[$channelId] = [
                                'channelId'   => $channelId,
                                'channelName' => $chs[$channelId] ?? null,
                                'hotel'       => [],
                            ];
                        }

                        if (!isset($HCH[$channelId]['hotel'][$hotelId]) || empty($HCH[$channelId]['hotel'][$hotelId])) {
                            $HCH[$channelId]['hotel'][$hotelId] = [
                                'hotelId'         => $hotelId,
                                'hotelName'       => $hotelName,
                                'hotelLocation'   => $hotelLocation,
                                'total'           => [
                                    '2016' => 0.00,
                                    '2017' => 0.00,
                                    '2018' => 0.00,
                                    '2019' => 0.00,
                                    '2020' => 0.00,
                                    '2021' => 0.00,
                                    '2022' => 0.00,
                                ],
                                'numberOfBooking' => [
                                    '2016' => 0,
                                    '2017' => 0,
                                    '2018' => 0,
                                    '2019' => 0,
                                    '2020' => 0,
                                    '2021' => 0,
                                    '2022' => 0,
                                ],
                            ];
                        }
                        $HCH[$channelId]['hotel'][$hotelId]['total'][$i] = $total;
                        $HCH[$channelId]['hotel'][$hotelId]['numberOfBooking'][$i] = $numberOfBooking;

                        if (!isset($_TOTAL[$hotelId]) || empty($_TOTAL[$hotelId])) {
                            $_TOTAL[$hotelId] = [
                                'hotelId'       => $hotelId,
                                'hotelName'     => $hotelName,
                                'hotelLocation' => $hotelLocation,
                                'channel'       => [],
                                'total'         => [
                                    '2016' => 0.00,
                                    '2017' => 0.00,
                                    '2018' => 0.00,
                                    '2019' => 0.00,
                                    '2020' => 0.00,
                                    '2021' => 0.00,
                                    '2022' => 0.00,
                                ],
                            ];
                        }

                        if (!isset($_TOTAL[$hotelId]['channel'][$channelId]) || empty($_TOTAL[$hotelId]['channel'][$channelId])) {
                            $_TOTAL[$hotelId]['channel'][$channelId] = [
                                '2016' => 0.00,
                                '2017' => 0.00,
                                '2018' => 0.00,
                                '2019' => 0.00,
                                '2020' => 0.00,
                                '2021' => 0.00,
                                '2022' => 0.00,
                            ];
                        }
                        $_TOTAL[$hotelId]['channel'][$channelId][$i] = $total;
                        $_TOTAL[$hotelId]['total'][$i] += $total;
                    }
                }
            }
        }

        // dd($HCH['000002']);
        // dd($HCH['000006']['hotel']);
        // dd($HCH['000001']['hotel']['001749']);
        foreach ($HCH as $CH) {
            $channelId = $CH['channelId'];
            $channelName = $CH['channelName'];
            if (in_array($channelId, array_keys($chs))) {
                $path = 'report/total-revenue-ota-'.strtolower($channelName).'.csv';
                $header = 'HotelId,HotelName,Location,NumberOfBookings,Revenue,NumberOfBookings,Revenue,NumberOfBookings,Revenue,NumberOfBookings,Revenue,NumberOfBookings,Revenue,NumberOfBookings,Revenue,NumberOfBookings,Revenue';
                Storage::disk('download')->put($path, $header);
                foreach ($CH['hotel'] as $data) {
                    $content = null;
                    $hotelId = $data['hotelId'];
                    $hotelName = $data['hotelName'];
                    $hotelName = preg_replace('/[ ,]+/', '-', trim($hotelName));
                    $hotelName = trim($hotelName);
                    $hotelLocation = $data['hotelLocation'];
                    $hotelLocation = preg_replace('/[ ,]+/', '-', trim($hotelLocation));
                    $hotelLocation = trim($hotelLocation);

                    $content = $hotelId.','.$hotelName.','.$hotelLocation;
                    // foreach ($data['total'] as $datum) {
                    //     $content .= ','.$datum;
                    // }
                    for ($i = $st; $i <= $en; $i++) {
                        $content .= ','.$data['numberOfBooking'][$i];
                        $content .= ','.$data['total'][$i];
                    }
                    Storage::disk('download')->append($path, $content);
                }
            }
        }
        exit();

        dd($_TOTAL['000006']);
        $path = 'report/group-hotel-channel-5OTA.csv';
        $header = 'HotelId,HotelName,Location,2016,2017,2018,2019,2020,2021,2022';
        Storage::disk('download')->put($path, $header);
        foreach ($_TOTAL as $H) {
            $content = null;
            $hotelId = $H['hotelId'];
            $hotelName = $H['hotelName'];
            $hotelName = preg_replace('/[ ,]+/', '-', trim($hotelName));
            $hotelName = trim($hotelName);
            $hotelLocation = $H['hotelLocation'];
            $hotelLocation = preg_replace('/[ ,]+/', '-', trim($hotelLocation));
            $hotelLocation = trim($hotelLocation);

            $content = $hotelId.','.$hotelName.','.$hotelLocation;
            foreach ($H['total'] as $datum) {
                $content .= ','.$datum;
            }
            Storage::disk('download')->append($path, $content);
        }
        exit();
    }

    public function myNovel()
    {
        $baseUri = 'https://asia-southeast2-mynovel01.cloudfunctions.net';
        $client = new Client([
            'base_uri' => $baseUri,
            'timeout'  => 60,
        ]);

        // $novelKey = 'DFot8XN5t4jZvHmbkMY9';
        $novelKey = '9HIIW0M43Xks68nIhEa5';
        $uri = '/product/'.$novelKey;
        try {
            $response = $client->request('GET', $uri, [
                'http_errors' => false,
                'headers'     => [
                    'Accept'       => 'application/json; charset=UTF-8',
                    'Content-Type' => 'application/json; charset=UTF-8',
                ],
            ]);
            $code = $response->getStatusCode(); // 200
            $contents = $response->getBody()->getContents();
            $contents = json_decode($contents, true);
            if (isset($contents['EpTopic']) && !empty($contents['EpTopic']) && is_array($contents['EpTopic'])) {
                $chapters = $contents['EpTopic'];
                foreach ($chapters as $index => $chapter) {
                    if($index >= 20) {
                        exit();
                    }
                    // $chapter['ProductId'];
                    // $chapter['ProductTypeSet'];
                    $id = $chapter['EpId'];
                    $chapterName = $chapter['EpName'];
                    $novelCode = 'dsd';
                    // dd($chapter);

                    $pattern = '/^(';
                    $pattern .= implode('|', ['บทที่', 'ตอนที่']);
                    $pattern .= ')';
                    $pattern .= '\s*(\d+)\.?\-?(\d+)*\s*\:?\s*(.*?)\(??(\d*)\)?$/i';
                    preg_match($pattern, $chapterName, $matches);
                    if (!isset($matches[2])) {
                        throw new Exception('Error, chapter ['.$chapterName.'], on ['.$novelCode.']', 7000);
                    }
                    $fileName = $novelCode.$matches[2].'.txt';

                    if (Storage::disk('download')->exists($fileName)) {
                        $delete = Storage::disk('download')->delete($fileName);
                        if (!$delete) {
                            throw new Exception('Error, delete ['.$chapter->getTarget()->getFullPath().'] is fails', 7000);
                        }
                    }
                    Storage::disk('download')->put($fileName, $chapterName."\n");

                    $data = [
                        'appKey' => 'xdde8cNN5k7AuVTMgz7b',
                        // 'id'     => '62a07a12ece0de2ad8237cf2',
                        'id'     => $id,
                    ];
                    $response = $client->request('POST', '/productEP/getProductEpById', [
                        'http_errors' => false,
                        'headers'     => [
                            'Accept'       => 'application/json; charset=UTF-8',
                            'Content-Type' => 'application/json; charset=UTF-8',
                        ],
                        'body'        => json_encode($data),
                    ]);
                    $code = $response->getStatusCode(); // 200
                    $contents = $response->getBody()->getContents();
                    $contents = json_decode($contents, true);
                    if (isset($contents['EpName']) && !empty($contents['EpName']) && is_string($contents['EpName']) && isset($contents['EpDetail']) && !empty($contents['EpDetail']) && is_string($contents['EpDetail'])) {
                        $chapterName = $contents['EpName'];
                        $chapterContents = explode('<br>', $contents['EpDetail']);
                        // dd($chapterName, $chapterContents);

                        $dom = HtmlDomParser::str_get_html($contents['EpDetail']);
                        dd($chapterName, $contents['EpDetail'], $dom);

                        foreach ($chapterContents as $content) {
                            if (isset($content) && !empty($content) && is_string($content)) {
                                Storage::disk('download')->append($fileName, trim($content)."\n");
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            dd($e);
        }
        exit();
    }
}
