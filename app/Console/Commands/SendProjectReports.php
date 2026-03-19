<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendProjectReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:project-reports';
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
    $statusLabels = [
        'to_do' => 'To Do',
        'in_progress' => 'In Progress',
        'ready' => 'Ready',
        'deployed' => 'Deployed',
        'complete' => 'Complete',
        'invoice_done' => 'Invoice Done'
    ];

    $projects = DB::table('projects')
        ->where('status', 'active')
        ->get();

    foreach ($projects as $project) {

        $client = DB::table('clients')->where('id', $project->client_id)->first();
        if (!$client) continue;

        // 🔹 Status-wise count
        $ticketCounts = DB::table('tickets')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->where('project_id', 3)
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $finalCounts = [];
        foreach ($statusLabels as $key => $label) {
            $finalCounts[$label] = $ticketCounts[$key] ?? 0;
        }

        $data = [
            'client_name' => $client->name,
            'project_name' => $project->project_name,
            'status_counts' => $finalCounts
        ];

        Mail::send('email.monthly_report', $data, function ($message) use ($client, $project) {
            $message->to($client->email);
            $message->subject('Project Status Report - ' . $project->project_name);
        });
    }
}
}
