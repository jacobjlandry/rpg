<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class Story extends Model
{
    use HasFactory;

    protected $guarded = ['_id', 'created_at', 'updated_at'];

    public function startingLines()
    {
        return StoryLine::where('story_id', $this->_id)
            ->whereNull('parent_story_line')
            ->get();
    }
}
