<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Story;
use App\Models\StoryLine;

class StoryBuilder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'story:builder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build a story in parts';

    private $story;
    private $previousLine = 0; // delete 
    private $line:
    private $history;
    private $options = ["New", "Back", "Quit"];
    
    public function handle2() 
    {
        $choice = $this->choice(
            "Welcome, traveler!"
            array_merge(
                array_splice($this->options, 0, 1),
                Story::all()->pluck("title")->toArray(),
                areay_splice($this->options, 2, 1),
            ),
            0
        );
        
        switch($choice) {
            case "New":
                $this->story = new Story();
                break;
            
            case "Back":
                
                break;
                
            case "Quit":
                $this->info("Goodbye");
                return;
                break;
                
            default:
                $this->story = Story::all()->where("title", $choice)->first();
                break;
        }
        
        if(!$this->story->exists) {
            $this->story->title = $this->ask("What is this storu called?");
            $this->story->save();
        }
        
         $choice = $this->choice(
            "Welcome, traveler!"
            array_merge(
                array_splice($this->options, 0, 1),
                $this->story->startingLines()->pluck("text")->toArray(),
                areay_splice($this->options, 1, 2),
            ),
            0
        );
        
        // make handlers above func
        // but need to recognize lines vs story
        
        if(true) {
            $this->line = $this->story->startingLines()->where("text", $choice)->first();
        }
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Welcome to the Story Builder!");
        $this->info("Build your world today");

        if($this->confirm("Is this a new story?")) {
            $this->story = new Story();
            $this->story->title = $this->ask("Title");
            $this->story->save();
        } else {
            $stories = Story::all();
            $choice = $this->choice("Which story would you like to edit?", $stories->pluck('title')->toArray(), 0);
            $this->story = $stories->where('title', $choice)->first();
        }

        while($this->confirm("Would you like update this story?")) {
            if($this->confirm("Do you want to add a new line at this level?")) {
                $this->addLine();
            } else if($this->confirm("Do you want to edit a line at this level?")) {
                $lines = ($this->previousLine !== 0) ? $this->previousLine->choices() : $this->story->startingLines();
                $line = $this->choice("Which Line?", $lines->pluck("text")->toArray());
                $path = $this->choice("Edit Text or Add Options?", ["Edit Text", "Add Options"]);

                if($path == "Edit Text") {
                    $this->editLine($lines->where('text', $line)->first());
                } else {
                    $this->previousLine = $lines->where('text', $line)->first();
                    $this->addLine();
                }
            }
        };

        $this->info("Your story is complete!");
        return;
    }

    private function addLine()
    {
        if($this->previousLine !== 0 && $this->previousLine->choices()->count() > 0) {
            $this->info("Existing story lines");
            $this->previousLine->choices()->each(function($line) {
                $this->info($line->text);
            });
        }
        
        $this->info("New Line");
        $text = $this->ask("Text:");
        $end = $this->confirm("Is this the end of the story?");

        $this->info("Review");
        $this->info($text);
        $this->info($end ? "This is the end." : "This is not the end");

        if($this->confirm("OK?")) {
            $storyLine = new StoryLine();
            $storyLine->story_id = $this->story->_id;
            if($this->previousLine !== 0) {
                $storyLine->parent_story_line = $this->previousLine->_id;
            }
            $storyLine->text = $text;
            $storyLine->end = $end;
            $storyLine->save();

            $this->previousLine = $storyLine;
        } else {
            $this->info("Starting over");
            $this->addLine();
        }

        return;
    }

    private function editLine($line) {
        //
    }
}
