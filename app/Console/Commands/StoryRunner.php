<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Story;
use App\Models\StoryLine;

class StoryRunner extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'story:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run a story';

    /**
     * Execute the console command.
     */
    private $story;
    private $line;
    private $history;
    
    public function handle() 
    {
        $this->history = collect([]);

        // story select
        if(!$this->story) {
            $choice = $this->choice(
                "Choose a story",
                Story::all()->pluck("title")->toArray(),
                0
            );
            $this->handleChoice($choice);
        }
        
        // line select 
        while($this->story) {
            // show history
            $this->info($this->story->title);
            $this->history->each(function($history) {
                $this->info("> " . $history->text);
            });
            if($this->line) {
                $this->info("> " . $this->line->text);
            }

            // get choices
            if($this->line) {
                $choices = $this->line->choices()->pluck("text")->toArray();
            } else {
                $choices = $this->story->choices()->pluck("text")->toArray();
            }

            $choice = $this->choice(
                "Choose a story line",
                $choices,
                0
            );
            $this->handleChoice($choice);
            
            // page break to easily discern between steps
            $this->info(" ");
            $this->info(" ========================================== ");
            $this->info(" ");
        }
    }
    
    private function handleChoice($choice) {
        switch($choice) {
            default:
                if(!$this->story) {
                    $this->story = Story::all()->where("title", $choice)->first();
                } else {
                    if($this->line) {
                        $this->history->push($this->line);
                        $this->line = $this->line->choices()->where("text", $choice)->first();
                    } else {
                        $this->line = $this->story->choices()->where("text", $choice)->first();
                    }
                    if($this->line->isEnd || $this->line->choices()->count() == 0) {
                        exit;
                    }
                }
                break;
        }
    }
}
