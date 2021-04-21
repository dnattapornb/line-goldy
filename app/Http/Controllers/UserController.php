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
        $message = 'ไอเต่า';
        $pattern = $this->getMessagePattern($message);
        dd($pattern);
    }

    private function getMessagePattern($message)
    {
        $data = [
            'success' => false,
            'pattern' => [
                'command' => '',
                'params'  => [],
            ],
            'raw'     => [
                'message' => $message,
                'pattern' => $pattern ?? '',
                'matches' => $matches ?? '',
            ],
        ];

        /* dj */
        $pattern = '/(ไป\s*){3,6}/i';
        preg_match($pattern, $message, $matches);
        if (!empty($matches)) {
            $data['success'] = true;
            $data['pattern']['command'] = 'ไป';

            return $data;
        }

        /* ตัวเหีี๊ย */
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
            $command = $name = $matches[2];
            foreach ($groupName as $key => $values) {
                if (in_array($name, $values)) {
                    $command = $key;
                    break;
                }
            }
            $data['success'] = true;
            $data['pattern']['command'] = $command;

            return $data;
        }

        return $data;
    }
}
