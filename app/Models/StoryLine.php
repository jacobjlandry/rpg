<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class StoryLine extends Model
{
    use HasFactory;

    protected $guarded = ["_id", "created_at", "updated_at"];

    public function choices()
    {
        return StoryLine::where('parent_story_line_id', $this->_id)->get();
    }
}
