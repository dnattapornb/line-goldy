#### Create a "storage/app/public" link from "public/storage" to "/Users/nattaporn.d/Desktop/\_\_NOVEL\_\_"
```
ln -s target_path link_path
ln -s /Users/nattaporn.d/Desktop/__NOVEL__ storage/app/public
```

#### Create a symbolic link from "public/storage" to "storage/app/public"
```
php artisan storage:link 
```

#### Create database "unique"
```
CREATE SCHEMA \`unique\` DEFAULT CHARACTER SET utf8mb4 ;
```

#### Migration and Seed table
```
php artisan make:migration create_line_users_table --create=line_users
php artisan make:model Models/LineUser

php artisan make:migration create_rom_characters_table --create=rom_characters
php artisan make:migration add_foreign_key_to_rom_characters_table --table=rom_characters
php artisan make:migration remove_main_column_from_rom_characters_table --table=rom_characters
php artisan make:model Models/RomCharacter

php artisan migrate
php artisan db:seed
```
