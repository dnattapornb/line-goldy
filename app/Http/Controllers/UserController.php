<?php

namespace App\Http\Controllers;

use App\Models\LineUser;
use App\Models\RomCharacter;
use App\Models\RomJob;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Yaml\Yaml;
use Ramsey\Uuid\Uuid;
use App\Services\Message;

class UserController extends Controller
{
    private $path, $users;

    /**
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function __construct()
    {
        $this->path = 'models\line-users.yaml';
        $this->users = [];
        $exists = Storage::disk('local')->exists($this->path);
        if ($exists) {
            $this->users = Yaml::parse(Storage::disk('local')->get($this->path));
        }
        else {
            Storage::disk('local')->put($this->path, Yaml::dump($this->users));
        }
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $users = LineUser::all();

        return Response()->json($users->toArray());
    }

    /**
     * @param  string  $key
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $key)
    {
        /** @var LineUser $user */
        $user = LineUser::where('key', $key)->first();

        return Response()->json($user->toArray());
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $key
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $key)
    {
        /** @var LineUser $user */
        $user = LineUser::where('key', $key)->first();
        if ($user) {
            $user->setPublished($request->toArray()['published'] ?? false);
            $user->setDisplayName(trim($request->toArray()['display_name']) ?? '');
            $user->setName(trim($request->toArray()['name']) ?? '');
            $user->save();
        }

        return Response()->json($user);
    }

    /**
     * @param  string  $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        return Response()->json(['id' => $id]);
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $ukey
     * @param  string                    $ckey
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCharacter(Request $request, string $ukey, string $ckey)
    {
        /** @var LineUser $user */
        $user = LineUser::where('key', $ukey)->first();
        if ($user) {
            try {
                /** @var RomJob $guildWarsJob */
                $guildWarsJob = RomJob::find($request->toArray()['guild_wars_job']['id'] ?? 1);
                /** @var RomJob $activitiesJob */
                $activitiesJob = RomJob::find($request->toArray()['activities_job']['id'] ?? 1);
            } catch (\Exception $e) {
                return Response()->json([
                    'status'  => 'error',
                    'message' => 'Error',
                    'errors'  => $e->getMessage(),
                ], 422);
            }

            /** @var RomCharacter $character */
            $character = RomCharacter::where('key', $ckey)->first();
            if ($character) {
                $character->setName(trim($request->toArray()['name']) ?? '');
                $character->guildWarsJob()->associate($guildWarsJob);
                $character->activitiesJob()->associate($activitiesJob);
                $character->user()->associate($user);
                $character->save();
            }
        }

        return Response()->json($user);
    }

    public function test()
    {
        // $message = 'ไป ไป ไป ไป';
        // $message = 'ไอเต่า';
        $message = 'user -l -k=1234567890 -n=บอย BoY_i-i~i -u=';
        // $pattern = $this->getMessagePattern($message);
        // dd($pattern);


        $userId = 'Ua4aca288a98cbaa22161f7ff4aaba88f';
        $groupId = 'C6732c6399f55dcaf00f4a86cc4bb97b5';
        $mentionIds = ['U915bb59bf9a4a7116b524852b6b46008'] ;
        $message = new Message($userId, $groupId, $mentionIds, $message);
        dd($message);
    }

    private function getMessagePattern($message)
    {
        $data = [
            'success' => false,
            'pattern' => [
                'category'   => '',
                'command'    => '',
                'action'     => '',
                'parameters' => [],
            ],
            'raw'     => [],
        ];

        /**
         * dj-วิว
         */
        if (!$data['success']) {
            $pattern = '/(ไ+ป+\s*){3,6}/i';
            preg_match($pattern, $message, $matches);
            if (!empty($matches)) {
                $data['success'] = true;
                $data['pattern']['category'] = 'dj';
                $data['pattern']['command'] = 'go';

                // raw
                $raw = [
                    'message' => $message,
                    'pattern' => $pattern,
                    'matches' => $matches,
                ];
                array_push($data['raw'], $raw);
            }
        }

        /**
         * bull-วัวชน
         */
        if (!$data['success']) {
            $groupName = [
                'boy'  => ['บอย'],
                'bas'  => ['บาส'],
                'poon' => ['ปูน'],
                'tong' => ['โต้ง'],
                'tee'  => ['ตี๋'],
                'pae'  => [
                    'เป้',
                    'เพ้',
                ],
                'krit' => [
                    'กิต',
                    'กิด',
                    'กฤติน์',
                    'กฤ',
                    'กฤต',
                ],
                'att'  => [
                    'อัต',
                    'เต่า',
                ],
            ];
            $names = [];
            foreach (array_values($groupName) as $value) {
                $names = array_merge($names, $value);
            }

            $pattern = '/';
            $pattern .= '(ไอ|อัย|พี่|น้อง)';
            $pattern .= '(';
            $pattern .= implode('|', $names);
            $pattern .= ')';
            $pattern .= '/i';
            preg_match($pattern, $message, $matches);
            if (!empty($matches)) {
                $command = '';
                $name = $matches[2] ?? '';
                foreach ($groupName as $key => $values) {
                    if (in_array($name, $values)) {
                        $command = $key;
                        break;
                    }
                }
                $data['success'] = true;
                $data['pattern']['category'] = 'bull';
                $data['pattern']['command'] = $command;

                // raw
                $raw = [
                    'message' => $message,
                    'pattern' => $pattern,
                    'matches' => $matches,
                ];
                array_push($data['raw'], $raw);
            }
        }

        /**
         * line-bot-command
         */
        if (!$data['success']) {
            $commands = ['user', 'char'];
            $groupAction = [
                'add'    => ['-a', '--add'],
                'modify' => ['-m', '--modify'],
                'list'   => ['-l', '--list'],
                // 'delete' => ['-d', '--delete'],
            ];

            $actions = [];
            foreach (array_values($groupAction) as $value) {
                $actions = array_merge($actions, $value);
            }

            $pattern = '/';
            $pattern .= '^(';
            $pattern .= implode('|', $commands);
            $pattern .= ')';
            $pattern .= '.*?';
            $pattern .= '(';
            $pattern .= implode('|', $actions);
            $pattern .= ')';
            $pattern .= '/';
            preg_match($pattern, $message, $matches);
            if (!empty($matches)) {
                $command = $matches[1];
                $action = '';
                $name = $matches[2];
                foreach ($groupAction as $key => $values) {
                    if (in_array($name, $values)) {
                        $action = $key;
                        break;
                    }
                }
                $data['success'] = true;
                $data['pattern']['category'] = 'command';
                $data['pattern']['command'] = $command;
                $data['pattern']['action'] = $action;

                // raw
                $raw = [
                    'message' => $message,
                    'pattern' => $pattern,
                    'matches' => $matches,
                ];
                array_push($data['raw'], $raw);

                // parameters
                if (isset($command) && !empty($command) && isset($action) && !empty($action)) {
                    $groupParameters = [
                        'key'  => ['-k', '--key'],
                        'name' => ['-n', '--name'],
                        'user' => ['-u', '--user'],
                    ];

                    foreach ($groupParameters as $key => $parameters) {
                        if ($key === 'key') {
                            $pattern = '/';
                            $pattern .= '(';
                            $pattern .= implode('|', $parameters);
                            $pattern .= ')';
                            $pattern .= '=(\d+)';
                            $pattern .= '/';
                            preg_match($pattern, $message, $matches);
                            if (!empty($matches)) {
                                /**
                                 * array:3 [
                                 * 0 => "-k=1234567890"
                                 * 1 => "-k"
                                 * 2 => "1234567890"
                                 * ]
                                 */
                                $param = [
                                    'key'   => $matches[1],
                                    'value' => trim($matches[2]),
                                ];
                                $data['pattern']['parameters'][$key] = $param;

                                // raw
                                $raw = [
                                    'message' => $message,
                                    'pattern' => $pattern,
                                    'matches' => $matches,
                                ];
                                array_push($data['raw'], $raw);
                            }
                        }

                        if ($key === 'name') {
                            $pattern = '/';
                            $pattern .= '(';
                            $pattern .= implode('|', $parameters);
                            $pattern .= ')';
                            $pattern .= '=([a-zA-Z0-9ก-๙\s_-]+)';
                            $pattern .= '/';
                            preg_match($pattern, $message, $matches);
                            if (!empty($matches)) {
                                /**
                                 * array:3 [
                                 * 0 => "-n=บอย BoY_i-i"
                                 * 1 => "-n"
                                 * 2 => "บอย BoY_i-i"
                                 * ]
                                 */
                                $param = [
                                    'key'   => $matches[1],
                                    'value' => trim($matches[2]),
                                ];
                                $data['pattern']['parameters'][$key] = $param;

                                // raw
                                $raw = [
                                    'message' => $message,
                                    'pattern' => $pattern,
                                    'matches' => $matches,
                                ];
                                array_push($data['raw'], $raw);
                            }
                        }

                        if ($key === 'user') {
                            $pattern = '/';
                            $pattern .= '(';
                            $pattern .= implode('|', $parameters);
                            $pattern .= ')';
                            $pattern .= '=([\@a-zA-Z0-9ก-๙\s_-]+)';
                            $pattern .= '/';
                            preg_match($pattern, $message, $matches);
                            if (!empty($matches)) {
                                /**
                                 * array:3 [
                                 * 0 => "-u=@вιя"
                                 * 1 => "-u"
                                 * 2 => "@вιя"
                                 * ]
                                 */
                                $param = [
                                    'key'   => $matches[1],
                                    'value' => trim($matches[2]),
                                ];
                                $data['pattern']['parameters'][$key] = $param;

                                // raw
                                $raw = [
                                    'message' => $message,
                                    'pattern' => $pattern,
                                    'matches' => $matches,
                                ];
                                array_push($data['raw'], $raw);
                            }
                        }
                    }
                }
            }
        }

        return $data;
    }
}
