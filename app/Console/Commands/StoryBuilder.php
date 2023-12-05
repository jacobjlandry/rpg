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
    private $line;
    private $history;
    private $storyOptions = ["New Story", "Quit"];
    private $lineOptions = ["New Line", "Edit Line", "Back", "Quit"];
    
    public function handle2() 
    {
        // story select
        $choice = $this->choice(
            "Welcome, traveler!"
            array_merge(
                array_splice($this->storyOptions,0, 1),
                Story::all()->pluck("title")->toArray(),
                array_splice($this->storyOptions, 1, 2),
            ),
            0
        );
        
        $this->handleChoice($choice);
        
        // line select 
        $choice = $this->choice(
            "Welcome, traveler!"
            array_merge(
                array_splice($this->lineOptions,0, 1)
                $this->story->startingLines()->pluck("text")->toArray(),
                array_splice($this->lineOptions,2, 2),
            ),
            0
        );
        
        $this-handleChoice($choice);
        
        // line edit
        $choice = $this->choice(
            "Welcome, traveler!"
            array_merge(
                array_splice($this->lineOptions,0, 2)
                $this->story->startingLines()->pluck("text")->toArray(),
                array_splice($this->lineOptions,2, 2),
            ),
            0
        );
        
        $this->handleChoice($choice);
        
        // todo: manage state so we can continue editing until quit
        // todo: print story so far at each level
    }
    
    private function handleChoice($choice) {
        switch($choice) {
            case "New Story":
                $this->story = new Story();
                $this->title = $this->ask("What is the title of your story?");
                $this->story->save();
                break;
                
            case "New Line":
                $this->line = new StoryLine();
                $this->line->text = $this->ask("What is the text for this line?")
                $this->line->isEnd = $this->confirm("Is this the end of the story?");
                $this->line->save();
                break;
                
            case "Edit Line":
                $this->line->text = $this->ask("What is the text for this line?")
                $this->line->isEnd = $this->confirm("Is this the end of the story?");
                $this->line->save();
                break;
            
            case "Back":
                $this->line = $this->history->pop();
                break;
                
            case "Quit":
                $this->info("Goodbye");
                return;
                break;
                
            default:
                if(!$this->story) {
                    $this->story = Story::all()->where("title", $choice)->first();
                } else {
                    if($this->line) {
                        $this->history->push($this->line);
                    }
                    $this->line = $this->story->startingLines()->where("text", $choice)->first();
                }
                break;
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
