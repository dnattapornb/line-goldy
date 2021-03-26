<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LineUser extends Model
{
    use SoftDeletes;

    protected $table = 'line_users';
    protected $visible = [
        'id',
        'key',
        'name',
        'display_name',
        'picture_url',
        'published',
        'is_friend',
    ];
    protected $casts = [
        'published' => 'boolean',
        'is_friend' => 'boolean',
    ];

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

    public function getDisplayName()
    {
        return $this->attributes['display_name'];
    }

    public function setDisplayName($data)
    {
        $this->attributes['display_name'] = $data;
    }

    public function getPictureUrl()
    {
        return $this->attributes['picture_url'];
    }

    public function setPictureUrl($data)
    {
        $this->attributes['picture_url'] = $data;
    }

    public function getPublished()
    {
        return $this->attributes['published'];
    }

    public function setPublished($data)
    {
        $this->attributes['published'] = $data;
    }

    public function getIsFriend()
    {
        return $this->attributes['is_friend'];
    }

    public function setIsFriend($data)
    {
        $this->attributes['is_friend'] = $data;
    }

    /**
     * Get the "Rom Characters" for the "Line User".
     */
    public function characters()
    {
        return $this->hasMany(RomCharacter::class, 'line_user_id');
    }

    public function toArray()
    {
        $column = null;
        foreach ($this->getVisible() as $attribute) {
            $column[$attribute] = $this->getAttribute($attribute);
        }
        $column['characters'] = $this->characters()->get()->toArray();

        return $column;
    }
}
