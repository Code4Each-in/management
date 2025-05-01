<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UpdateQuotesTableData extends Migration
{
    public function up()
    {

        DB::table('quotes')->truncate();
        $quotes = [
            'Success is not final, failure is not fatal: It is the courage to continue that counts.',
            'Don’t watch the clock; do what it does. Keep going.',
            'Opportunities don’t happen. You create them.',
            'Success usually comes to those who are too busy to be looking for it.',
            'Don’t be afraid to give up the good to go for the great.',
            'I find that the harder I work, the more luck I seem to have.',
            'If you are not willing to risk the usual, you will have to settle for the ordinary.',
            'Believe you can and you’re halfway there.',
            'What you lack in talent can be made up with desire and hustle.',
            'The way to get started is to quit talking and begin doing.',
            'Don’t let yesterday take up too much of today.',
            'Work hard in silence, let success make the noise.',
            'Whether you think you can or think you can’t, you’re right.',
            'Success is walking from failure to failure with no loss of enthusiasm.',
            'Hardships often prepare ordinary people for an extraordinary destiny.',
            'The future depends on what you do today.',
            'Small deeds done are better than great deeds planned.',
            'Well done is better than well said.',
            'Act as if what you do makes a difference. It does.',
            'Quality means doing it right when no one is looking.',
            'The only limit to our realization of tomorrow is our doubts of today.',
            'Start where you are. Use what you have. Do what you can.',
            'Your time is limited, so don’t waste it living someone else’s life.',
            'Dream bigger. Do bigger.',
            'Push yourself, because no one else is going to do it for you.',
            'Great things never come from comfort zones.',
            'Success doesn’t just find you. You have to go out and get it.',
            'It’s going to be hard, but hard does not mean impossible.',
            'Don’t wait for opportunity. Create it.',
            'Sometimes later becomes never. Do it now.'
        ];

        $insertData = [];
        $startDate = Carbon::create(2025, 5, 1);  

        foreach ($quotes as $index => $quote) {
            $date = $startDate->copy()->addDays($index)->format('Y-m-d');
            $insertData[] = [
                'quote_text' => $quote,
                'start_date' => $date,
                'end_date' => $date,  
            ];
        }

        DB::table('quotes')->insert($insertData);
    }

    public function down()
    {
        DB::table('quotes')->whereBetween('start_date', ['2025-05-01', '2025-05-30'])->delete();
    }
}

