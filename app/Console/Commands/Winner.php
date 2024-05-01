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

    $previousMonth = $currentMonth - 1;
    $previousYear = $currentYear;

    if ($currentMonth == 1) {
        $previousMonth = 12;
        $previousYear--;
    }

    $this->info("Current Month: $currentMonth");
    $this->info("Current Year: $currentYear");

    $this->info("Previous Month: $previousMonth");
    $this->info("Previous Year: $previousYear");

    $existingWinner = Winners::where('month', '=', $previousMonth)
        ->where('year', '=', $previousYear)
        ->exists();

    if ($existingWinner) {
        $this->info('Winner already exists for the previous month and year.');
        return;
    }
    $totalVotes = Votes::where('month', '=', $previousMonth)
    ->where('year', '=', $previousYear)
    ->distinct('from')
    ->count('from');

    $voteCounts = Votes::select('to')
        ->selectRaw('COUNT(*) as winner_votes')
        ->where('month', '=', $previousMonth)
        ->where('year', '=', $previousYear)
        ->groupBy('to')
        ->orderByDesc('winner_votes')
        ->get();

    if ($voteCounts->isEmpty()) {
        $this->info('No votes found for the previous month.');
        return;
    }

    $maxVotes = $voteCounts->max('winner_votes');
    $this->info($maxVotes);
    $tieWinners = $voteCounts->where('winner_votes', $maxVotes);
    $this->info($tieWinners);
    // dump($tieWinners);
    // $this->info('asdsada');

    if ($tieWinners->count() == 1) {
        // $this->info($tieWinners->first());
        $winner = $tieWinners->first();
    } elseif ($tieWinners->count() > 1) {
        if ($tieWinners->count() == 3) {
            $winner = $tieWinners->random();
        } else {
            foreach ($tieWinners as $tieWinner) {
                winners::create([
                    'user_id' => $tieWinner->to,
                    'month' => $previousMonth,
                    'year' => $previousYear,
                    'winner_votes' => $tieWinner->winner_votes,
                    'totalvotes'=> $totalVotes
                ]);
            }
            $this->info('Tie between multiple users. All tied users stored as winners.');
            return;
        }
    }

    Winners::create([
        'user_id' => $winner->to,
        'month' => $previousMonth,
        'year' => $previousYear,
        'winner_votes' => $winner->winner_votes,
        'totalvotes' => $totalVotes
    ]);

}


}
