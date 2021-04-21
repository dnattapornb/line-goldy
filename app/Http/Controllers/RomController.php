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

class RomController extends Controller
{
    public function __construct()
    {
        // ...
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexCharacters()
    {
        $jobs = RomCharacter::all();

        return Response()->json($jobs->toArray());
    }

    /**
     * @param  string  $key
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function showCharacters($key)
    {
        $job = RomCharacter::where('key', $key)->first();

        return Response()->json($job->toArray());
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexJobs()
    {
        $jobs = RomJob::all();

        return Response()->json($jobs->toArray());
    }

    /**
     * @param  int  $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function showJobs($id)
    {
        $job = RomJob::find($id);

        return Response()->json($job->toArray());
    }
}
