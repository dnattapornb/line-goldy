<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Yaml\Yaml;
use function response;

class UserController extends Controller
{
    private $path, $users;

    /**
     * UserController constructor.
     *
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
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return Response()->json($this->users);
    }

    /**
     * Display the specified resource.
     *
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string                    $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $id)
    {
        if ($request->exists('id')) {
            $user = $request->toArray();
            foreach ($this->users as &$item) {
                if ($item['id'] === $id) {
                    $item['name'] = $user['name'];
                    $item['displayName'] = $user['displayName'];
                    $item['pictureUrl'] = $user['pictureUrl'];
                    $item['active'] = $user['active'] ?? false;
                    $item['friend'] = $user['friend'] ?? false;
                    break;
                }
            }

            try {
                Storage::disk('local')->put($this->path, Yaml::dump($this->users));
            } catch (\Exception $e) {
            }
        }

        return Response()->json($this->users);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        return Response()->json(['id' => $id]);
    }
}
