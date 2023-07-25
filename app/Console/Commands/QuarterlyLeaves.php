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
        $usersData = Users::whereHas('role', function ($q) {
            $q->where('name', '!=', 'Super Admin');
        })
        ->where('status', 1) // Filter where status is 1
        ->where('joining_date', '<', Carbon::now()->subMonth(3)) // Filter where joining_date is older than 3 months ago
        ->whereNotNull('employee_id') // Filter where employee_id is not NULL
        ->get();
        foreach ($usersData as $data) {
                $company_leaves = CompanyLeaves::Create([
                    'employee_id' => $data->employee_id,
                    'leaves_count' => 4.5,
                    'created_at' =>  Carbon::now(),
                    'updated_at' =>  Carbon::now(),
                ]);
            }
            if($company_leaves){
                info("leaves added Successfully.");
            }
        return 0;
    }
}
