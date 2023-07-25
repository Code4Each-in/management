<?php

namespace App\Console\Commands;

use App\Models\CompanyLeaves;
use App\Models\Users;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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
        ->get();

        foreach ($usersData as $data) {
                $company_leaves = CompanyLeaves::Create([
                    'user_id' => $data->id,
                    'leaves_count' => 4.5,
                    'created_at' =>  Carbon::now(),
                    'updated_at' =>  Carbon::now(),
                ]);
            }
            if($company_leaves){
                info("leaves added Successfully.");
            }

             // Get the current date
            $currentDate = Carbon::now();

        // Fetch users whose probation period end date falls within the next three months
        $probationEndUsers = Users::whereHas('role', function ($q) {
                $q->where('name', '!=', 'Super Admin');
            })
            ->where('status', 1)
            ->select('*', DB::raw('DATE_ADD(joining_date, INTERVAL 3 MONTH) AS probation_end_date'))
            ->whereRaw('DATE_ADD(joining_date, INTERVAL 3 MONTH) BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 MONTH)')
            ->get();

        // Calculate the difference in months between probation_end_date and next three months
        foreach ($probationEndUsers as $user) {
            $probationEndDate = Carbon::parse($user->probation_end_date);
            $differenceInMonths = $probationEndDate->diffInMonths($currentDate->copy()->addMonths(3), false);
            // Check the day of the probation_end_date
            $dayOfMonth = $probationEndDate->day;
            // Calculate the number of additional 1.5 days based on the number of full months
            $newProbationEndleaeves = ($dayOfMonth < 15) ? $differenceInMonths * 1.5 : 0.5 + ($differenceInMonths- 1) * 1.5;
            $user->new_prabation_end_leaves = $newProbationEndleaeves;
            // Create a new CompanyLeaves record for the user
            CompanyLeaves::create([
                'user_id' => $user->id,
                'leaves_count' => $newProbationEndleaeves,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
