<?php

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Yaml\Yaml;

class LineUsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $path = 'models\line-users.yaml';
        $users = [];
        $exists = Storage::disk('local')->exists($path);
        if ($exists) {
            $users = Yaml::parse(Storage::disk('local')->get($path));
        }

        foreach ($users as $user) {
            DB::table('line_users')->insert([
                'key'          => $user['id'],
                'name'         => $user['name'] ?? '',
                'display_name' => $user['displayName'] ?? '',
                'picture_url'  => $user['pictureUrl'] ?? '',
                'published'    => $user['active'] ?? false,
                'is_friend'    => $user['friend'] ?? false,
                'created_at'   => $user['created_at'] ?? Carbon::now()->format('Y-m-d H:i:s'),
            ]);
        }
    }
}
