<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;
use Illuminate\View\View;
use Symfony\Component\Yaml\Yaml;

class UserController extends Controller
{
    public function index()
    {
        $path = 'models\line-users.yaml';
        $exists = Storage::disk('local')->exists($path);
        $users = [];
        if ($exists) {
            $users = Yaml::parse(Storage::disk('local')->get($path));
        }

        return \response()->json($users);
    }
}
