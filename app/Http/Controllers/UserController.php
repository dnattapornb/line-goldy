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

        return Response()->json($users->toArray());
    }

    /**
     * @param  string  $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $user = LineUser::where('key', $id)->first();

        return Response()->json($user->toArray());
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

        /** @var \App\Models\RomCharacter[] $characters */
        foreach ($characters as $character) {
            /** @var \Illuminate\Support\Collection $user */
            $user = $character->user()->get();
            // $userName = $character->user->name;
            $job = $character->job()->get();
            // $jobName = $character->job->name;
            dd($user->isEmpty(), $job->isEmpty());
        }
        exit();

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
