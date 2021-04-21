<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RomJob extends Model
{
    protected $table = 'rom_jobs';
    protected $visible = [
        'id',
        'label',
        'name',
    ];
    protected $casts = [];

    public function getId()
    {
        return $this->attributes['id'];
    }

    public function getLabel()
    {
        return $this->attributes['label'];
    }

    public function setLabel($data)
    {
        $this->attributes['label'] = $data;
    }

    public function getName()
    {
        return $this->attributes['name'];
    }

    public function setName($data)
    {
        $this->attributes['name'] = $data;
    }

    /**
     * Get the "Rom Characters" for the "Guild Wars Rom Job".
     */
    public function characters()
    {
        return $this->hasMany(RomCharacter::class, 'guild_wars_rom_job_id');
    }

    public function toArray()
    {
        $column = null;
        foreach ($this->getVisible() as $attribute) {
            $column[$attribute] = $this->getAttribute($attribute);
        }

        return $column;
    }
}
