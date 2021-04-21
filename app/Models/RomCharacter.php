<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RomCharacter extends Model
{
    protected $table = 'rom_characters';
    protected $visible = [
        'id',
        'key',
        'name',
    ];
    protected $casts = [];

    public function getId()
    {
        return $this->attributes['id'];
    }

    public function getKey()
    {
        return $this->attributes['key'];
    }

    public function setKey($data)
    {
        $this->attributes['key'] = $data;
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
     * Get the "Line User" that owns the "Rom Character".
     */
    public function user()
    {
        return $this->belongsTo(LineUser::class, 'line_user_id', 'id');
    }

    /**
     * Get the "Guild Wars Rom Job" that owns the "Rom Character".
     */
    public function guildWarsJob()
    {
        return $this->belongsTo(RomJob::class, 'guild_wars_rom_job_id', 'id');
    }

    /**
     * Get the "Activities Rom Job" that owns the "Rom Character".
     */
    public function activitiesJob()
    {
        return $this->belongsTo(RomJob::class, 'activities_rom_job_id', 'id');
    }

    public function toArray()
    {
        $column = null;
        foreach ($this->getVisible() as $attribute) {
            $column[$attribute] = $this->getAttribute($attribute);
        }
        $column['guild_wars_job'] = null;
        if (!$this->guildWarsJob()->get()->isEmpty() && $this->guildWarsJob()->get()->count() > 0) {
            $column['guild_wars_job'] = $this->guildWarsJob()->get()->toArray()[0];
        }
        $column['activities_job'] = null;
        if (!$this->activitiesJob()->get()->isEmpty() && $this->activitiesJob()->get()->count() > 0) {
            $column['activities_job'] = $this->activitiesJob()->get()->toArray()[0];
        }

        // double check job
        if (!isset($column['activities_job']) || empty($column['activities_job'])) {
            $column['activities_job'] = $column['guild_wars_job'];
        }

        return $column;
    }
}
