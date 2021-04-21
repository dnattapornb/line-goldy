## Laravel IDE Helper Generator
[https://github.com/barryvdh/laravel-ide-helper](https://github.com/barryvdh/laravel-ide-helper)
```bash
composer require --dev barryvdh/laravel-ide-helper

php artisan ide-helper:generate
php artisan ide-helper:meta
```

## Intervention Image
[http://image.intervention.io/getting_started/installation](http://image.intervention.io/getting_started/installation)
[https://github.com/Intervention/image](https://github.com/Intervention/image)
> supports currently the two most common image processing libraries `GD` Library and `Imagick`.
```bash
composer require intervention/image

php artisan vendor:publish --provider="Intervention\Image\ImageServiceProviderLaravelRecent"
```

## Laravel Roles
[https://github.com/jeremykenedy/laravel-roles](https://github.com/jeremykenedy/laravel-roles)
```bash
composer require jeremykenedy/laravel-roles

php artisan vendor:publish --tag=laravelroles
```
#### Service Provider
```php
'providers' => [

    //...

    /**
     * Third Party Service Providers...
     */
    jeremykenedy\LaravelRoles\RolesServiceProvider::class,

],
```

## Create directory `storage` 
> "storage/app/public" link from "public/storage" to "/Users/nattaporn.d/Desktop/\_\_NOVEL\_\_"
```bash
mkdir storage/app/public

ln -s "target_path" "link_path"
ln -s /Users/nattaporn.d/Desktop/__NOVEL__/novel storage/app/public/novel
ln -s /Users/nattaporn.d/Desktop/__NOVEL__/tmp storage/app/public/tmp
```

### Symbolic link directory `public`
> "public/storage" to "storage/app/public"
```bash
php artisan storage:link 
```

## Database

#### Create database "unique"
```mysql
CREATE SCHEMA `unique` DEFAULT CHARACTER SET utf8mb4 ;
```

#### Create migration table
```bash
php artisan make:migration create_line_users_table --create=line_users
php artisan make:model Models/LineUser

php artisan make:migration create_rom_characters_table --create=rom_characters
php artisan make:migration add_foreign_key_to_rom_characters_table --table=rom_characters
php artisan make:migration remove_main_column_from_rom_characters_table --table=rom_characters
php artisan make:model Models/RomCharacter

php artisan make:migration create_rom_jobs_table --create=rom_jobs
php artisan make:migration add_foreign_key_rom_job_id_to_rom_characters_table --table=rom_characters
php artisan make:migration convert_foreign_key_rom_job_id_to_guild_wars_rom_job_id_on_rom_characters_table --table=rom_characters
php artisan make:migration add_foreign_key_activities_rom_job_id_to_rom_characters_table --table=rom_characters
php artisan make:model Models/RomJob
```

#### Run migration and Seed
```bash
php artisan migrate
php artisan db:seed
```

## Docs
[https://docs.github.com/en/github/writing-on-github/basic-writing-and-formatting-syntax](https://docs.github.com/en/github/writing-on-github/basic-writing-and-formatting-syntax)