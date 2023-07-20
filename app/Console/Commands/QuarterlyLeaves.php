<?php

namespace App\Console\Commands;

use App\Models\CompanyLeaves;
use App\Models\Users;
use Carbon\Carbon;
use Illuminate\Console\Command;

class QuarterlyLeaves extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leaves:quarterly';

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
        $usersData = Users::where('status', 1)->Where('joining_date','>',Carbon::now()->subMonth(3))->get();
        // print_r($usersData);
        // echo "\n"; 
        foreach ($usersData as $data) {
                $company_leaves = CompanyLeaves::Create([
                    'employee_id' => $data->employee_id,
                    'leaves_count' => 4.5,
                    'created_at' =>  Carbon::now(),
                    'updated_at' =>  Carbon::now(),
                ]);
            }
            if($company_leaves){
                echo "leaves added Successfully.";
                echo "\n"; 
            }
        
            // echo "out";
            // echo "\n";


        return 0;
    }
}
