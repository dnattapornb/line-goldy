<?php

namespace App\Http\Controllers;

use App\Models\LineUser;
use App\Models\RomCharacter;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Yaml\Yaml;
use function response;

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
        $_users = null;
        if ($users->count()) {
            $_users = [];
            foreach ($users as $user) {
                $_user = $user->toArray();
                if (!isset($_user['characters']) || empty($_user['characters'])) {
                    $_user['characters'] = [];
                }
                $characters = $user->characters()->get();
                if ($characters->count()) {
                    $_user['characters'] = $characters->toArray();
                }

                array_push($_users, $_user);
            }
        }

        return Response()->json($_users);
    }

    /**
     * @param  string  $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $user = [];
        foreach ($this->users as $item) {
            if ($item['id'] === $id) {
                $user = $item;
                break;
            }
        }

        return Response()->json($user);
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $id)
    {
        /** @var \App\Models\LineUser $user */
        $user = LineUser::find($id);

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

    public function test()
    {
        /** @var LineUser $user */
        $user = LineUser::find(1);
        dump($user);

        $characters = $user->characters()->get();
        dump($characters, $characters->count(), $characters->toArray());

        // $this->save_character($user);
        // $this->associate_character($user);
        $this->dissociate_character($user);
    }

    private function save_character(LineUser $user)
    {
        $character = new RomCharacter();
        $character->setKey(1238);
        $character->setName('test1238');
        $user->characters()->save($character);

        $characters = $user->characters()->get();
        dump($characters, $characters->count(), $characters->toArray());

        exit();
    }

    private function associate_character(LineUser $user)
    {
        /** @var RomCharacter $character */
        $character = RomCharacter::find(2);
        // dd($character->toArray());

        $character->user()->associate($user);
        $character->save();

        $characters = $user->characters()->get();
        dump($characters, $characters->count(), $characters->toArray());

        exit();
    }

    private function dissociate_character(LineUser $user)
    {
        /** @var RomCharacter $character */
        $character = RomCharacter::find(2);
        // dd($character->toArray());

        $character->user()->dissociate();
        $character->save();

        $characters = $user->characters()->get();
        dump($characters, $characters->count(), $characters->toArray());

        exit();
    }
}
