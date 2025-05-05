<?php
namespace App\Http\Controllers;

use App\Models\Reminder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ReminderController extends Controller
{
    public function create()
    {
        $reminders = Reminder::all();
        return view('reminder.create', compact('reminders'));
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:daily,weekly,monthly',
            'description' => 'required|string',
            'weekly_day' => 'nullable|string',
            'monthly_date' => 'nullable|integer|min:1|max:31',
        ]);
        $reminderDate = null;
        $now = Carbon::now();

        if ($data['type'] === 'daily') {
            $reminderDate = $now->startOfDay();
            if ($now->greaterThan($reminderDate)) {
                $reminderDate = $reminderDate->addDay();
            }
        } elseif ($data['type'] === 'weekly') {
            $reminderDate = $now->next($data['weekly_day'])->startOfDay();
        } elseif ($data['type'] === 'monthly') {
            $reminderDate = $now->day($data['monthly_date'])->startOfDay();
            if ($now->greaterThan($reminderDate)) {
                $reminderDate = $reminderDate->addMonth();
            }
        }
        $data['reminder_date'] = $reminderDate;
        $data['is_active'] = 1;

        // Create reminder
        $reminder = Reminder::create($data);

        if ($reminder) {
            return redirect()->route('dashboard.index')->with('reminder_notice', 'Reminder set successfully!');
        } else {
            return redirect()->route('dashboard.index')->with('error', 'Failed to set reminder.');
        }
    }

    public function saveClickTime(Request $request, Reminder $reminder)
    {
        $request->validate([
            'clicked_at' => 'required|date',
        ]);

        $reminder->clicked_at = Carbon::parse($request->clicked_at);
        $reminder->save();

        return response()->json(['success' => true]);
    }

    public function markAsRead(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:reminders,id',
            'clicked_at' => 'required|date',
        ]);

        DB::beginTransaction();

        try {
            $reminder = Reminder::find($request->id);

            if (!$reminder) {
                return response()->json(['message' => 'Reminder not found'], 404);
            }

            $reminder->clicked_at = Carbon::parse($request->clicked_at);
            $reminder->save();

            DB::commit();

            return response()->json(['message' => 'Reminder marked as read']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving click time:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to save the click time', 'error' => $e->getMessage()], 500);
        }
    }
    public function indexing()
    {
        $reminders = Reminder::all();
        return view('reminder.indexing', compact('reminders'));
    }

    public function edit($id)
{
    $reminder = Reminder::findOrFail($id);
    return view('reminder.edit', compact('reminder'));
}
public function destroy(Reminder $reminder)
{
    try {
        $reminder->delete();
        return response()->json(['success' => true]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()]);
    }
}

public function update(Request $request, $id)
{
    $data = $request->validate([
        'type' => 'required|in:daily,weekly,monthly',
        'description' => 'required|string',
        'weekly_day' => 'nullable|string',
        'monthly_date' => 'nullable|integer|min:1|max:31',
    ]);

    $reminder = Reminder::findOrFail($id);

    $reminderDate = null;
    $now = Carbon::now();

    if ($data['type'] === 'daily') {
        $reminderDate = $now->startOfDay();
        if ($now->greaterThan($reminderDate)) {
            $reminderDate = $reminderDate->addDay();
        }
    } elseif ($data['type'] === 'weekly') {
        $reminderDate = $now->next($data['weekly_day'])->startOfDay();
    } elseif ($data['type'] === 'monthly') {
        $reminderDate = $now->day($data['monthly_date'])->startOfDay();
        if ($now->greaterThan($reminderDate)) {
            $reminderDate = $reminderDate->addMonth();
        }
    }
    $data['reminder_date'] = $reminderDate;
    $data['is_active'] = 1;

    $reminder->update($data);

    return redirect()->route('Reminders.create')->with('success', 'Reminder updated successfully');
}



}
