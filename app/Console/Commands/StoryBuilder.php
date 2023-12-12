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
    private $line;
    private $history;
    private $storyOptions = ["New Story", "Quit"];
    private $lineOptions = ["New Line", "Edit Line", "Find Dead Ends", "Back", "Quit"];
    
    public function handle() 
    {
        $this->history = collect([]);

        // story select
        if(!$this->story) {
            $choice = $this->choice(
                "Choose a story",
                array_merge(
                    array_slice($this->storyOptions, 0, 1),
                    Story::all()->pluck("title")->toArray(),
                    array_slice($this->storyOptions, 1, 2),
                ),
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
                array_merge(
                    array_slice($this->lineOptions, 0, 2),
                    $choices,
                    array_slice($this->lineOptions, 2, 2),
                ),
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
            case "New Story":
                $this->story = new Story();
                $this->story->title = $this->ask("What is the title of your story?");
                $this->story->save();
                break;
                
            case "New Line":
                $line = new StoryLine();
                $line->story_id = $this->story->_id;
                $line->parent_story_line_id = isset($this->line) ? $this->line->_id : null;
                $line->text = $this->ask("What is the text for this line?");
                $line->isEnd = $this->confirm("Is this the end of the story?");
                $line->save();
                break;
                
            case "Edit Line":
                $this->line->text = $this->ask("What is the text for this line?");
                $this->line->isEnd = $this->confirm("Is this the end of the story?");
                $this->line->save();
                break;
            
            case "Find Dead Ends":
                $newLineText = $this->choice("Choose a line", $this->story->deadEnds()->pluck('text')->toArray(), 0);
                $newLine = $this->story->deadEnds()->filter(function($line) use($newLineText) { return $line->text === $newLineText; })->first();
                if($this->line) {
                    $this->history->push($this->line);
                }
                $this->line = $newLine;
                break;
            
            case "Back":
                if($this->history->count()) {
                    $this->line = $this->history->pop();
                } else if($this->line) {
                    $this->line = null;
                } else {
                    $this->story = null;
                }
                
                break;
                
            case "Quit":
                $this->info("Goodbye");
                exit;
                break;
                
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
                    
                }
                break;
        }
    }
}
