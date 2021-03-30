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

class LineBotController extends Controller
{
    public function index()
    {
        Log::info('LINE_BOT.Start => ');

        $httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(env('LINE_MESSAGING_API_CHANNEL_TOKEN', null));
        $bot = new \LINE\LINEBot($httpClient, ['channelSecret' => env('LINE_MESSAGING_API_CHANNEL_SECRET', null)]);
        $signature = $_SERVER['HTTP_'.\LINE\LINEBot\Constant\HTTPHeader::LINE_SIGNATURE];

        try {
            $request = file_get_contents('php://input');
            Log::info('LINE_BOT.Source => ', json_decode($request, true));
            $events = $bot->parseEventRequest($request, $signature);
        } catch (\LINE\LINEBot\Exception\InvalidSignatureException $e) {
            Log::error('LINE_BOT : parseEventRequest failed. InvalidSignatureException => ', var_export($e, true));
        } catch (\LINE\LINEBot\Exception\InvalidEventRequestException $e) {
            Log::error('LINE_BOT : parseEventRequest failed. InvalidEventRequestException => ', var_export($e, true));
        } catch (\LINE\LINEBot\Exception\InvalidEventSourceException $e) {
            Log::error('LINE_BOT : parseEventRequest failed. InvalidEventSourceException => ', var_export($e, true));
        } catch (\LINE\LINEBot\Exception\CurlExecutionException $e) {
            Log::error('LINE_BOT : parseEventRequest failed. CurlExecutionException => ', var_export($e, true));
        }

        /** @var \LINE\LINEBot\Event\MessageEvent\TextMessage[] $events */
        if (isset($events) && !empty($events)) {
            foreach ($events as $event) {
                $outputText = null;
                $userId = $event->getUserId();
                $user = [
                    'id'          => $userId,
                    'name'        => null,
                    'displayName' => null,
                    'pictureUrl'  => null,
                    'active'      => true,
                    'friend'      => false,
                ];

                // User
                $store = true;
                if (isset($userId) && !empty($userId) && $store) {
                    $userProfile = $bot->getProfile($userId);
                    Log::info('LINE_BOT.User => ', $userProfile->getJSONDecodedBody());
                    if ($userProfile->isSucceeded()) {
                        $data = $userProfile->getJSONDecodedBody();
                        $user['displayName'] = $data['displayName'] ?? null;
                        $user['pictureUrl'] = $data['pictureUrl'] ?? null;
                        $user['friend'] = true;
                    }

                    $path = 'models\line-users.yaml';
                    $exists = Storage::disk('local')->exists($path);
                    $users = [];
                    if ($exists) {
                        $users = Yaml::parse(Storage::disk('local')->get($path));
                    }

                    if (isset($users) && !empty($users) && $this->hasUser($users, $userId)) {
                        foreach ($users as &$_user) {
                            if ($_user['id'] === $userId) {
                                $rs = array_diff($user, $_user);
                                if (!empty($rs)) {
                                    if (isset($rs['displayName'])) {
                                        if (!empty($rs['displayName'])) {
                                            $_user['displayName'] = $user['displayName'];
                                        }
                                    }

                                    if (isset($rs['pictureUrl'])) {
                                        if (!empty($rs['pictureUrl'])) {
                                            $_user['pictureUrl'] = $user['pictureUrl'];
                                        }
                                    }

                                    if (isset($rs['friend'])) {
                                        if (!empty($rs['friend'])) {
                                            $_user['friend'] = $user['friend'];
                                        }
                                    }
                                }
                                break;
                            }
                        }
                    }
                    else {
                        array_push($users, $user);
                    }

                    Storage::disk('local')->put($path, Yaml::dump($users));
                }

                // Group
                if ($event->isGroupEvent()) {
                    $groupId = $event->getGroupId();
                    $group = [
                        'id'    => $groupId,
                        'name'  => '',
                        'users' => [],
                    ];
                    // $groupMemberIds = $bot->getGroupMemberIds($groupId);
                    // Log::info('LINE_BOT.Group => ', $groupMemberIds->getJSONDecodedBody());
                }

                $active = true;
                if ($active && $this->permitUser($userId)) {
                    // Text message
                    if (($event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage)) {
                        $messageText = strtolower(trim($event->getText()));
                        $validator = $this->msgValidator($messageText);
                        Log::info('LINE_BOT.TextMessage(validator) => ', $validator);
                        if ($validator['success']) {
                            if ($validator['text'] === 'ไป') {
                                $messages = $this->getMsgRandom();
                                if ($messages !== 'img') {
                                    $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($messages);
                                }
                                else {
                                    $img_url = env('PUBLIC_URL', null)."/storage_dummy/images/go-0.jpg";
                                    $outputText = new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder($img_url, $img_url);
                                }
                            }
                            elseif ($validator['text'] === 'วัว') {
                                $messages = "ไอควายยย ยย ย ยยยย";
                                $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($messages);
                            }
                            else {
                                $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("อิ");
                            }
                        }
                        else {
                            switch ($messageText) {
                                case "ตาย" :
                                {
                                    $i = rand(2, 10);
                                    $sound_url = env('PUBLIC_URL', null)."/storage_dummy/sounds/na_kom/".$i."_kills.ogg";
                                    $outputText = new \LINE\LINEBot\MessageBuilder\AudioMessageBuilder($sound_url, 6000);
                                    break;
                                }
                                case "ไอบาส" :
                                {
                                    $sound_url = env('PUBLIC_URL', null)."/storage_dummy/sounds/na_kom/get_it_on.ogg";
                                    $outputText = new \LINE\LINEBot\MessageBuilder\AudioMessageBuilder($sound_url, 6000);
                                    break;
                                }
                                case "party" :
                                    $messages = $this->getParty();
                                    $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($messages);
                                    break;
                                case "text" :
                                    $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("text message");
                                    break;
                                case "location" :
                                    $outputText = new \LINE\LINEBot\MessageBuilder\LocationMessageBuilder("Eiffel Tower", "Champ de Mars, 5 Avenue Anatole France, 75007 Paris, France", 48.858328, 2.294750);
                                    break;
                                case "button" :
                                    $actions = [
                                        // general message action
                                        new \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder("button 1", "text 1"),
                                        // URL type action
                                        new \LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder("Google", "http://www.google.com"),
                                        // The following two are interactive actions
                                        new \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("next page", "page=3"),
                                        new \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("Previous", "page=1"),
                                    ];
                                    $img_url = "https://cdn.shopify.com/s/files/1/0379/7669/products/sampleset2_1024x1024.JPG?v=1458740363";
                                    $button = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder("button text", "description", $img_url, $actions);
                                    $outputText = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("this message to use the phone to look to the Oh", $button);
                                    break;
                                case "carousel" :
                                    $columns = [];
                                    $img_url = "https://cdn.shopify.com/s/files/1/0379/7669/products/sampleset2_1024x1024.JPG?v=1458740363";
                                    for ($i = 0; $i < 5; $i++) {
                                        $actions = [
                                            new \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("Add to Card", "action=carousel&button=".$i),
                                            new \LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder("View", "http://www.google.com"),
                                        ];
                                        $column = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder("Title", "description", $img_url, $actions);
                                        $columns[] = $column;
                                    }
                                    $carousel = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder($columns);
                                    $outputText = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("Carousel Demo", $carousel);
                                    break;
                                case "image" :
                                    $img_url = "https://cdn.imageupload.workers.dev/6VqhfzZC_S__70852755.jpg";
                                    $outputText = new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder($img_url, $img_url);
                                    break;
                                case "confirm" :
                                    $actions = [
                                        new \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("yes", "ans=y"),
                                        new \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder("no", "ans=N"),
                                    ];
                                    $button = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ConfirmTemplateBuilder("problem", $actions);
                                    $outputText = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("this message to use the phone to look to the Oh", $button);
                                    break;
                                default :
                                    // $outputText = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("demo command: text, location, button, confirm to test message template");
                                    break;
                            }
                        }
                    }
                    // Sticker message
                    elseif ($event instanceof \LINE\LINEBot\Event\MessageEvent\StickerMessage) {
                        Log::info('LINE_BOT.Sticker(StickerMessage) => ', [
                            'packageId'           => $event->getPackageId(),
                            'stickerId'           => $event->getStickerId(),
                            'stickerResourceType' => $event->getStickerResourceType(),
                        ]);
                        continue;
                    }
                    // Image message
                    elseif ($event instanceof \LINE\LINEBot\Event\MessageEvent\ImageMessage) {
                        Log::info('LINE_BOT.Image(ImageMessage) => ', [
                            'contentProvider'                    => $event->getContentProvider(),
                            'contentProvider.previewImageUrl'    => $event->getContentProvider()->getPreviewImageUrl(),
                            'contentProvider.originalContentUrl' => $event->getContentProvider()->getOriginalContentUrl(),
                        ]);
                        continue;
                    }
                    // File Event
                    elseif ($event instanceof \LINE\LINEBot\Event\MessageEvent\FileMessage) {
                        Log::info('LINE_BOT.File(FileMessage) => ', [
                            'fileName' => $event->getFileName(),
                            'fileSize' => $event->getFileSize(),
                        ]);
                        continue;
                    }
                    // Video message
                    elseif ($event instanceof \LINE\LINEBot\Event\MessageEvent\VideoMessage) {
                        Log::info('LINE_BOT.Video(VideoMessage) => ', [
                            'duration'        => $event->getDuration(),
                            'contentProvider' => $event->getContentProvider(),
                        ]);
                        continue;
                    }
                    // Audio message
                    elseif ($event instanceof \LINE\LINEBot\Event\MessageEvent\AudioMessage) {
                        Log::info('LINE_BOT.Audio(AudioMessage) => ', [
                            'duration'        => $event->getDuration(),
                            'contentProvider' => $event->getContentProvider(),
                        ]);
                        continue;
                    }
                    // Location Event
                    elseif ($event instanceof \LINE\LINEBot\Event\MessageEvent\LocationMessage) {
                        Log::info('LINE_BOT.Location(LocationMessage) => ', [
                            'title'     => $event->getTitle(),
                            'address'   => $event->getAddress(),
                            'latitude'  => $event->getLatitude(),
                            'longitude' => $event->getLongitude(),
                        ]);
                        continue;
                    }
                    // Postback Event
                    elseif (($event instanceof \LINE\LINEBot\Event\PostbackEvent)) {
                        Log::info('LINE_BOT.Postback(PostbackEvent) => ', [
                            'postbackData'   => $event->getPostbackData(),
                            'postbackParams' => $event->getPostbackParams(),
                        ]);
                        continue;
                    }

                    if (isset($outputText) && !empty($outputText)) {
                        $response = $bot->replyMessage($event->getReplyToken(), $outputText);
                        // $response = $bot->pushMessage('U378a83ff7b5b9229f1ec15abe7fab4a2', $outputText);

                        if ($response->isSucceeded()) {
                            Log::info('LINE_BOT.End => Ok!');

                            return response('Ok!');
                        }
                        else {
                            Log::info('LINE_BOT.End => Fails!');

                            return response($response->getRawBody(), $response->getHTTPStatus());
                        }
                    }
                }
            }
        }

        Log::info('LINE_BOT.End => It works!');

        return response('It works!');
    }

    private function hasUser($users, $userId)
    {
        $has = false;
        foreach ($users as $user) {
            if ($user['id'] == $userId) {
                $has = true;
                break;
            }
        }

        return $has;
    }

    private function permitUser($userId)
    {
        $userIds = [
            'Uf327dc13da3f951e3a0ef8176d0bf7ba',
            'Udec9682fee45c7021c041ad8096853c4',
            'U13cf37536ff4889e8a36cfb0b5ba5423',
            'U4b60a459752c5d1cb6a3a8f3f68869df',
            'U915bb59bf9a4a7116b524852b6b46008',
            'Ua9d026c30844d82218631efcb70b88c8',
            'U378a83ff7b5b9229f1ec15abe7fab4a2',
        ];
        if (in_array($userId, $userIds)) {
            return true;
        }

        return false;
    }

    private function msgValidator($subject)
    {
        $data = [
            'success' => false,
            'text'    => '',
        ];

        /* dj */
        $pattern = '/(ไป\s*){3,6}/i';
        preg_match($pattern, $subject, $matches);
        if (!empty($matches)) {
            $data['success'] = true;
            $data['text'] = 'ไป';

            return $data;
        }

        /* ตัวเหีี๊ย */
        $pattern = '/(ไอ|พี่|น้อง)(กิต|ตี๋|เพ้|เป้|อัต)/i';
        preg_match($pattern, $subject, $matches);
        if (!empty($matches)) {
            $data['success'] = true;
            $data['text'] = 'วัว';

            return $data;
        }

        return $data;
    }

    private function getParty()
    {
        $spreadsheetId = '1ygx4ykVQkCtV-P-ksPYbekCJizNSRBqDoB4vLndynzY';
        $sheetId = 'rom-party';

        /** @var Collection $sheets */
        $sheets = Sheets::spreadsheet($spreadsheetId)->sheet($sheetId)->get();

        $header = $sheets->pull(0);

        /** @var Collection $party */
        $party = Sheets::collection($header, $sheets);

        $outputArray = [
            'header' => 'ประกาศตี้สำหรับ WOE|WOC ประจำวันที่ ',
            'body'   => [],

        ];
        if ($party->count() > 0) {
            $now = Carbon::now();
            $outputArray['header'] .= $now->format('d D, Y');
            foreach ($party as $p) {
                $text = [];
                $text[] = "ทีมที่ : ".$p->get('id');

                for ($i = 1; $i <= 8; $i++) {
                    if (!empty($p->get('member'.$i))) {
                        $text[] = $i.": ".$p->get('member'.$i);
                    }
                }

                array_push($outputArray['body'], $text);
            }
        }
        // dd($outputArray);

        $outputText = "";
        $outputText .= $outputArray['header'];
        $outputText .= "\n";
        foreach ($outputArray['body'] as $body) {
            $outputText .= "\n";
            $outputText .= implode("\n", $body);
            $outputText .= "\n";
        }

        // $outputText = [];
        // $header = '';
        // $header .= $outputArray['header'];
        // $header .= "\n";
        // foreach ($outputArray['body'] as $index => $body) {
        //     $content = $header;
        //     $content .= "\n";
        //     $content .= implode("\n", $body);
        //     $content .= "\n";
        //
        //     $outputText[] = $content;
        // }

        return $outputText;
    }

    private function getMsgRandom()
    {
        $msg = [
            'img',
            '“อะฮีอีอี้”',
            '“เลี้ยงวัวยังได้ขายที่🐄 แต่ถ้าเลี้ยงxีคงต้องขายวัวนะฮะ😂”',
            '“ความรักเกิดจากคน2คน❤️ แต่มักจบลงที่คน2ใจ🥲”',
            '“หลอกให้รักไม่พอ มาบอกให้รออีก อีห่า😤”',
            '“เจี๊ยวไม่ใหญ่ แต่ไข่น่ารัก”',
            '“ใบไม้ยังมีหลายแฉก🍀 ก็ไม่แปลกที่เธอจะหลายใจ อะฮี๊ๆๆ😂”',
            '“อยากชิมก็ต้องเอาลิ้นแตะ อยากแฉะก็ต้องเอาลิ้นเลีย👅 อ่ะฮิ๊ๆ😋”',
            '“ผับไม่เปิด ก็เกิดได้.. ก็มาดิคร้าบ อะฮิ อะฮิ”',
            '“ความรักก็เหมือนดัมมี่ มีทั้งปี้ มีทั้งอม🤪”',
            '“เป็นคนแค่ในจอ แต่ไม่ดีพอเท่าคนในใจ เฉียบ😎”',
            '“แสนดีทำไม เขาเลือกคนถูกใจไม่ใช่คนดี😔”',
            '“อะไรจะเกิดก็ต้องเกิด ขนาดคุมกำเนิดยังเกิดได้เลย🤣”',
            '“เราให้ทุกสิ่ง แต่เขาทิ้งทุกอย่าง เจ็บจัด😢”',
            '“สมัยนี้ผมลิขิต สู้บัตรเครดิตไม่ได้หรอก เฉียบ🤑”',
            '“แป้งอะเราใช้จอนสัน แต่ถ้ามึงหยากหน้าหันก็มาดิครับ🤬”',
            '“ในใจกลางเมืองมีแต่ตึก🏥 ในความรู้สึกมีแต่เธอ😋”',
            '“หยุดนะหอยโหนก กระโปกล้อมไว้หมดแล้ว😋”',
        ];

        $rand_keys = array_rand($msg, 1);

        return $msg[$rand_keys];
    }
}
