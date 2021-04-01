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

use LINE\LINEBot\Event\PostbackEvent;
use LINE\LINEBot\Event\MessageEvent\TextMessage;
use LINE\LINEBot\Event\MessageEvent\StickerMessage;
use LINE\LINEBot\Event\MessageEvent\ImageMessage;
use LINE\LINEBot\Event\MessageEvent\AudioMessage;
use LINE\LINEBot\Event\MessageEvent\VideoMessage;
use LINE\LINEBot\Event\MessageEvent\FileMessage;
use LINE\LINEBot\Event\MessageEvent\LocationMessage;

use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use LINE\LINEBot\MessageBuilder\ImageMessageBuilder;
use LINE\LINEBot\MessageBuilder\AudioMessageBuilder;
use LINE\LINEBot\MessageBuilder\VideoMessageBuilder;
use LINE\LINEBot\MessageBuilder\LocationMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;

use LINE\LINEBot\TemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder;

use LINE\LINEBot\MessageBuilder\TemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ConfirmTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder;

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

        /** @var \LINE\LINEBot\Event\BaseEvent[] $events */
        if (isset($events) && !empty($events)) {
            foreach ($events as $event) {
                $messageBuilder = null;
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
                if (isset($userId) && !empty($userId)) {
                    $userProfile = $bot->getProfile($userId);
                    Log::info('LINE_BOT.User => ', $userProfile->getJSONDecodedBody());
                    if ($userProfile->isSucceeded()) {
                        $data = $userProfile->getJSONDecodedBody();
                        $user['displayName'] = $data['displayName'] ?? null;
                        $user['pictureUrl'] = $data['pictureUrl'] ?? null;
                        $user['friend'] = true;
                    }

                    // Add user.
                    // $this->addUser($user);
                }

                // Group
                if ($event->isGroupEvent()) {
                    Log::info('LINE_BOT.Event.Group');
                    $groupId = $event->getGroupId();
                    $group = [
                        'id'    => $groupId,
                        'name'  => '',
                        'users' => [],
                    ];
                    // $groupMemberIds = $bot->getGroupMemberIds($groupId);
                    // Log::info('LINE_BOT.GroupMemberIds => ', [$groupMemberIds, $groupMemberIds->getJSONDecodedBody()]);

                    $groupMemberProfile = $bot->getGroupMemberProfile($groupId, $userId);
                    Log::info('LINE_BOT.GroupMemberProfile => ', $groupMemberProfile->getJSONDecodedBody());
                    if ($groupMemberProfile->isSucceeded()) {
                        $data = $groupMemberProfile->getJSONDecodedBody();
                        $user['displayName'] = $data['displayName'] ?? null;
                        $user['pictureUrl'] = $data['pictureUrl'] ?? null;
                    }

                    // Add user.
                    $this->addUser($user);
                }

                if ($event->isUserEvent()) {
                    Log::info('LINE_BOT.Event.User');
                    $updateInvalidUser = false;
                    if ($updateInvalidUser) {
                        $groupId = 'C6732c6399f55dcaf00f4a86cc4bb97b5';
                        // $groupId = 'C91711fd4b70c5ca9710f4be1481bddc2';
                        $users = $this->getInvalidUsers();
                        foreach ($users as $user) {
                            $userId = $user['id'];

                            $groupMemberProfile = $bot->getGroupMemberProfile($groupId, $userId);
                            if ($groupMemberProfile->isSucceeded()) {
                                $data = $groupMemberProfile->getJSONDecodedBody();
                                $user['displayName'] = $data['displayName'] ?? null;
                                $user['pictureUrl'] = $data['pictureUrl'] ?? null;
                            }
                            else {
                                $user['active'] = false;
                                $user['friend'] = true;
                            }

                            // Add user.
                            $this->addUser($user);
                        }
                    }
                }

                // Text message
                if (($event instanceof TextMessage)) {
                    $messageText = strtolower(trim($event->getText()));
                    if ($this->permitUser($userId)) {
                        $validator = $this->msgPattern($messageText);
                        Log::info('LINE_BOT.TextMessage(validator) => ', $validator);
                        if ($validator['success']) {
                            if ($validator['text'] === 'ไป') {
                                $messages = $this->getMsgRandom();
                                if ($messages !== 'img') {
                                    $messageBuilder = new TextMessageBuilder($messages);
                                }
                                else {
                                    $img_url = env('PUBLIC_URL', null).'/storage_dummy/images/go-0.jpg';
                                    $messageBuilder = new ImageMessageBuilder($img_url, $img_url);
                                }
                            }
                            elseif ($validator['text'] === 'วัว') {
                                $messages = 'ไอควายยย ยย ย ยยยย';
                                $messageBuilder = new TextMessageBuilder($messages);
                            }
                            else {
                                $messageBuilder = new TextMessageBuilder('อิ');
                            }
                        }

                        switch ($messageText) {
                            case 'ไอบอย' :
                                $messageBuilder = new TextMessageBuilder('คนดี ศรีภูเก็ต');
                                break;
                            case 'ตาย' :
                            {
                                $i = rand(2, 10);
                                $sound_url = env('PUBLIC_URL', null).'/storage_dummy/sounds/na_kom/'.$i.'_kills.ogg';
                                $messageBuilder = new AudioMessageBuilder($sound_url, 6000);
                                break;
                            }
                            case 'ไอบาส' :
                            {
                                $sound_url = env('PUBLIC_URL', null).'/storage_dummy/sounds/na_kom/get_it_on.ogg';
                                $messageBuilder = new AudioMessageBuilder($sound_url, 6000);
                                break;
                            }
                            case 'party' :
                                $messages = $this->getParty();
                                $messageBuilder = new TextMessageBuilder($messages);
                                break;
                            case 'location' :
                                $messageBuilder = new LocationMessageBuilder('Eiffel Tower', 'Champ de Mars, 5 Avenue Anatole France, 75007 Paris, France', 48.858328, 2.294750);
                                break;
                            case 'button' :
                                $actions = [
                                    // general message action
                                    new TemplateActionBuilder\MessageTemplateActionBuilder('button 1', 'text 1'),
                                    // URL type action
                                    new TemplateActionBuilder\UriTemplateActionBuilder('Google', 'http://www.google.com'),
                                    // The following two are interactive actions
                                    new TemplateActionBuilder\PostbackTemplateActionBuilder('next page', 'page=3'),
                                    new TemplateActionBuilder\PostbackTemplateActionBuilder('Previous', 'page=1'),
                                ];
                                $img_url = 'https://cdn.shopify.com/s/files/1/0379/7669/products/sampleset2_1024x1024.JPG?v=1458740363';
                                $button = new TemplateBuilder\ButtonTemplateBuilder('button text', 'description', $img_url, $actions);
                                $messageBuilder = new TemplateMessageBuilder('this message to use the phone to look to the Oh', $button);
                                break;
                            case 'carousel' :
                                $columns = [];
                                $img_url = 'https://cdn.shopify.com/s/files/1/0379/7669/products/sampleset2_1024x1024.JPG?v=1458740363';
                                for ($i = 0; $i < 5; $i++) {
                                    $actions = [
                                        new TemplateActionBuilder\PostbackTemplateActionBuilder('Add to Card', 'action=carousel&button='.$i),
                                        new TemplateActionBuilder\UriTemplateActionBuilder('View', 'http://www.google.com'),
                                    ];
                                    $column = new TemplateBuilder\CarouselColumnTemplateBuilder('Title', 'description', $img_url, $actions);
                                    $columns[] = $column;
                                }
                                $carousel = new TemplateBuilder\CarouselTemplateBuilder($columns);
                                $messageBuilder = new TemplateMessageBuilder('Carousel Demo', $carousel);
                                break;
                            case 'confirm' :
                                $actions = [
                                    new TemplateActionBuilder\PostbackTemplateActionBuilder('yes', 'ans=y'),
                                    new TemplateActionBuilder\PostbackTemplateActionBuilder('no', 'ans=N'),
                                ];
                                $button = new TemplateBuilder\ConfirmTemplateBuilder('problem', $actions);
                                $messageBuilder = new TemplateMessageBuilder('this message to use the phone to look to the Oh', $button);
                                break;
                            default :
                                // $messageBuilder = new TextMessageBuilder('demo command: text, location, button, confirm to test message template');
                                break;
                        }
                    }
                }
                // Sticker message
                elseif ($event instanceof StickerMessage) {
                    Log::info('LINE_BOT.Sticker(StickerMessage) => ', [
                        'packageId'           => $event->getPackageId(),
                        'stickerId'           => $event->getStickerId(),
                        'stickerResourceType' => $event->getStickerResourceType(),
                    ]);
                    continue;
                }
                // Image message
                elseif ($event instanceof ImageMessage) {
                    Log::info('LINE_BOT.Image(ImageMessage) => ', [
                        'contentProvider'                    => $event->getContentProvider(),
                        'contentProvider.previewImageUrl'    => $event->getContentProvider()->getPreviewImageUrl(),
                        'contentProvider.originalContentUrl' => $event->getContentProvider()->getOriginalContentUrl(),
                    ]);
                    continue;
                }
                // File Event
                elseif ($event instanceof FileMessage) {
                    Log::info('LINE_BOT.File(FileMessage) => ', [
                        'fileName' => $event->getFileName(),
                        'fileSize' => $event->getFileSize(),
                    ]);
                    continue;
                }
                // Video message
                elseif ($event instanceof VideoMessage) {
                    Log::info('LINE_BOT.Video(VideoMessage) => ', [
                        'duration'        => $event->getDuration(),
                        'contentProvider' => $event->getContentProvider(),
                    ]);
                    continue;
                }
                // Audio message
                elseif ($event instanceof AudioMessage) {
                    Log::info('LINE_BOT.Audio(AudioMessage) => ', [
                        'duration'        => $event->getDuration(),
                        'contentProvider' => $event->getContentProvider(),
                    ]);
                    continue;
                }
                // Location Event
                elseif ($event instanceof LocationMessage) {
                    Log::info('LINE_BOT.Location(LocationMessage) => ', [
                        'title'     => $event->getTitle(),
                        'address'   => $event->getAddress(),
                        'latitude'  => $event->getLatitude(),
                        'longitude' => $event->getLongitude(),
                    ]);
                    continue;
                }
                // Postback Event
                elseif (($event instanceof PostbackEvent)) {
                    Log::info('LINE_BOT.Postback(PostbackEvent) => ', [
                        'postbackData'   => $event->getPostbackData(),
                        'postbackParams' => $event->getPostbackParams(),
                    ]);
                    continue;
                }

                if (isset($messageBuilder) && !empty($messageBuilder)) {
                    $response = $bot->replyMessage($event->getReplyToken(), $messageBuilder);

                    if ($response->isSucceeded()) {
                        Log::info('LINE_BOT.End => Ok!');

                        return response('Ok!');
                    }
                    else {
                        Log::info('LINE_BOT.End => Fails!', [
                            $response->getRawBody(),
                            $response->getHTTPStatus(),
                        ]);

                        return response($response->getRawBody(), $response->getHTTPStatus());
                    }
                }
            }
        }

        Log::info('LINE_BOT.End => It works!');

        return response('It works!');
    }

    private function permitUser($userId)
    {
        $userIds = [
            'Uf327dc13da3f951e3a0ef8176d0bf7ba',// boy
            'Udec9682fee45c7021c041ad8096853c4',// poon
            'U13cf37536ff4889e8a36cfb0b5ba5423',// krit
            'U4b60a459752c5d1cb6a3a8f3f68869df',// tong
            'U915bb59bf9a4a7116b524852b6b46008',// tee
            'Ua9d026c30844d82218631efcb70b88c8',// bas
            'Uc750f0ec197429b3b38762a4b3cf4ba2',// att
            'U5aa838fd509e5ff054ca9c204ec3637f',// pe
            'U378a83ff7b5b9229f1ec15abe7fab4a2',// chom
        ];
        if (in_array($userId, $userIds)) {
            return true;
        }

        return false;
    }

    private function msgPattern($subject)
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
        $pattern = '/(ไอ|พี่|น้อง)?(กิต|ตี๋|เพ้|เป้|อัต|เต่า)$/i';
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

        $message = "";
        $message .= $outputArray['header'];
        $message .= "\n";
        foreach ($outputArray['body'] as $body) {
            $message .= "\n";
            $message .= implode("\n", $body);
            $message .= "\n";
        }

        // $message = [];
        // $header = '';
        // $header .= $outputArray['header'];
        // $header .= "\n";
        // foreach ($outputArray['body'] as $index => $body) {
        //     $content = $header;
        //     $content .= "\n";
        //     $content .= implode("\n", $body);
        //     $content .= "\n";
        //
        //     $message[] = $content;
        // }

        return $message;
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

    private function addUser($data)
    {
        $path = 'models\line-users.yaml';
        $exists = Storage::disk('local')->exists($path);
        $users = [];
        if ($exists) {
            $users = Yaml::parse(Storage::disk('local')->get($path));
        }

        if (isset($users) && !empty($users) && $this->hasUser($users, $data['id'])) {
            foreach ($users as &$user) {
                if ($user['id'] === $data['id']) {
                    $rs = array_diff($data, $user);
                    if (!empty($rs)) {
                        if (isset($rs['displayName'])) {
                            if (!empty($rs['displayName'])) {
                                $user['displayName'] = $data['displayName'];
                            }
                        }

                        if (isset($rs['pictureUrl'])) {
                            if (!empty($rs['pictureUrl'])) {
                                $user['pictureUrl'] = $data['pictureUrl'];
                            }
                        }

                        if (isset($rs['friend'])) {
                            if (!empty($rs['friend'])) {
                                $user['friend'] = $data['friend'];
                            }
                        }
                    }
                    break;
                }
            }
        }
        else {
            array_push($users, $data);
        }

        Storage::disk('local')->put($path, Yaml::dump($users));
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

    private function getInvalidUsers()
    {
        $path = 'models\line-users.yaml';
        $exists = Storage::disk('local')->exists($path);
        $users = [];
        $_users = [];
        if ($exists) {
            $users = Yaml::parse(Storage::disk('local')->get($path));
            foreach ($users as $user) {
                if (!isset($user['pictureUrl'])) {
                    array_push($_users, $user);
                }
            }
        }

        return $_users;
    }
}
