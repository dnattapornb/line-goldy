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
     * Get the "Rom Job" that owns the "Rom Character".
     */
    public function job()
    {
        return $this->belongsTo(RomJob::class, 'rom_job_id', 'id');
    }

    public function toArray()
    {
        $column = null;
        foreach ($this->getVisible() as $attribute) {
            $column[$attribute] = $this->getAttribute($attribute);
        }
        $column['job'] = null;
        if (!$this->job()->get()->isEmpty() && $this->job()->get()->count() > 0) {
            $column['job'] = $this->job()->get()->toArray()[0];
        }

        return $column;
    }
}
