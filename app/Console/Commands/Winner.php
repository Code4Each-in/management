<?php

namespace App\Console\Commands;

use App\Models\Votes;
use App\Models\Winners;
use Illuminate\Console\Command;

class Winner extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'votes:winner';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
{
    $currentMonth = date('n');
    $currentYear = date('Y');

    // Calculate the previous month and year
    $previousMonth = $currentMonth - 1;
    $previousYear = $currentYear;

    // Adjust for January (1) being the current month
    if ($currentMonth == 1) {
        $previousMonth = 12;
        $previousYear--;
    }

    // Log the current month and year for reference
    $this->info("Current Month: $currentMonth");
    $this->info("Current Year: $currentYear");

    // Log the previous month and year for reference
    $this->info("Previous Month: $previousMonth");
    $this->info("Previous Year: $previousYear");

    // Check if a winner already exists for the previous month
    $existingWinner = winners::where('month', '=', $previousMonth)
        ->where('year', '=', $previousYear)
        ->exists();

    if ($existingWinner) {
        $this->info('Winner already exists for the previous month and year.');
        return;
    }

    // Fetch users and their vote counts for the previous month and year
    $voteCounts = votes::select('to')
        ->selectRaw('COUNT(*) as total_votes')
        ->where('month', '=', $previousMonth)
        ->where('year', '=', $previousYear)
        ->groupBy('to')
        ->orderByDesc('total_votes')
        ->get();

    if ($voteCounts->isEmpty()) {
        $this->info('No votes found for the previous month.');
        return;
    }

    // Check if there is a tie between multiple users
    $maxVotes = $voteCounts->max('total_votes');
    $potentialWinners = $voteCounts->where('total_votes', $maxVotes);

    if ($potentialWinners->count() == 1) {
        // If there's only one potential winner, select them
        $winner = $potentialWinners->first();
    } elseif ($potentialWinners->count() == 2) {
        // If there's a tie between two users, store both as winners
        foreach ($potentialWinners as $potentialWinner) {
            winners::create([
                'user_id' => $potentialWinner->to,
                'month' => $previousMonth,
                'year' => $previousYear
            ]);
        }
        $this->info('Tie between two users. Both stored as winners.');
        return;
    } else {
        // If there's a tie between three or more users, randomly select one
        $winner = $potentialWinners->random();
    }

    // Insert the winner into the winners table
    winners::create([
        'user_id' => $winner->to,
        'month' => $previousMonth,
        'year' => $previousYear
    ]);

    $this->info('Winner for the previous month saved successfully!');
}

}
