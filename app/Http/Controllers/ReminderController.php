<?php
namespace App\Http\Controllers;

use App\Models\Reminder;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Auth;

class ReminderController extends Controller
{
    public function create()
    {
        $user = auth()->user()->load('role');
        $reminders = $this->isAdmin($user)
            ? Reminder::with('user')->get()  // Admins/Managers can view all reminders
            : Reminder::where('user_id', $user->id)->get();  // Regular users can view only their own reminders
        $users = $this->isAdmin($user)
            ? Users::select('id', 'first_name')->get()  // Fetch users for assignment
            : [];

        return view('reminder.create', compact('reminders', 'users'));
    }


    public function store(Request $request)
{
    $data = $request->validate([
        'type' => 'required|in:daily,weekly,monthly,biweekly,custom',  // Add 'custom' to validation
        'description' => 'required',
        'weekly_day' => 'required_if:type,weekly|nullable|string|in:Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
        'monthly_date' => 'required_if:type,monthly|nullable|integer|min:1|max:31',
        'user_id' => 'nullable|exists:users,id', // Ensure this validation
        'custom_date' => 'required_if:type,custom|nullable|date|after:today',  // Custom date validation
    ], [
        'type.required' => 'The type field is required.',
        'type.in' => 'The type must be one of the following: daily, weekly, monthly, or custom.',
        'description.required' => 'The description field is required.',
        'weekly_day.required_if' => 'The weekly day field is required when the type is weekly.',
        'weekly_day.string' => 'The weekly day must be a valid string.',
        'weekly_day.in' => 'The weekly day must be a valid day of the week.',
        'monthly_date.required_if' => 'The monthly date field is required when the type is monthly.',
        'monthly_date.integer' => 'The monthly date must be a valid integer.',
        'monthly_date.min' => 'The monthly date must be at least 1.',
        'monthly_date.max' => 'The monthly date must not be greater than 31.',
        'user_id.exists' => 'The selected user does not exist.',
        'custom_date.required_if' => 'The custom date field is required when the type is custom.',
        'custom_date.date' => 'The custom date must be a valid date.',
        'custom_date.after' => 'The custom date must be a date after today.',
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
    } elseif ($data['type'] === 'biweekly') {
        // First find the next selected weekday (same as weekly)
        $reminderDate = $now->next($data['weekly_day'])->startOfDay();

        // Then add 1 extra week (so total = after 2 weeks)
        $reminderDate = $reminderDate->addWeek();
    } elseif ($data['type'] === 'monthly') {
        $reminderDate = $now->day($data['monthly_date'])->startOfDay();
        if ($now->greaterThan($reminderDate)) {
            $reminderDate = $reminderDate->addMonth();
        }
    } elseif ($data['type'] === 'custom') {
        $reminderDate = Carbon::parse($request->custom_date)->startOfDay();
        if ($now->greaterThan($reminderDate)) {
            return redirect()->back()->withErrors(['custom_date' => 'Custom date must be in the future.']);
        }
    }
    $data['reminder_date'] = $reminderDate;
    if (auth()->user()->role->name === 'Super Admin' || auth()->user()->role->name === 'Manager') {
        $data['user_id'] = $request->user_id;
    } else {
        $data['user_id'] = auth()->id();
    }

    $reminder = Reminder::create($data);

    if ($reminder) {
        return redirect()->route('reminder.create')->with('reminder_notice', 'Reminder set successfully!');
    } else {
        return redirect()->route('reminder.create')->with('error', 'Failed to set reminder.');
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
        $user = auth()->user()->load('role');
        $reminders = ($user->role->name === 'Super Admin' || $user->role->name === 'Manager')
            ? Reminder::all()  // Super Admins/Managers see all reminders
            : Reminder::where('user_id', auth()->id())->get();  // Regular users only see their own

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
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete reminder: ' . $e->getMessage()
            ]);
        }
    }

    public function update(Request $request, $id)
{
    $data = $request->validate([
        'type' => 'required|in:daily,weekly,monthly,biweekly,custom',  // Add 'custom' to validation
        'description' => 'required',
        'weekly_day' => 'required_if:type,weekly|nullable|string|in:Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
        'monthly_date' => 'required_if:type,monthly|nullable|integer|min:1|max:31',
        'user_id' => 'nullable|exists:users,id', // Ensure this validation
        'custom_date' => 'required_if:type,custom|nullable|date|after:today',  // Custom date validation
    ], [
        'type.required' => 'The type field is required.',
        'type.in' => 'The type must be one of the following: daily, weekly, monthly, or custom.',
        'description.required' => 'The description field is required.',
        'weekly_day.required_if' => 'The weekly day field is required when the type is weekly.',
        'weekly_day.string' => 'The weekly day must be a valid string.',
        'weekly_day.in' => 'The weekly day must be a valid day of the week.',
        'monthly_date.required_if' => 'The monthly date field is required when the type is monthly.',
        'monthly_date.integer' => 'The monthly date must be a valid integer.',
        'monthly_date.min' => 'The monthly date must be at least 1.',
        'monthly_date.max' => 'The monthly date must not be greater than 31.',
        'user_id.exists' => 'The selected user does not exist.',
        'custom_date.required_if' => 'The custom date field is required when the type is custom.',
        'custom_date.date' => 'The custom date must be a valid date.',
        'custom_date.after' => 'The custom date must be a date after today.',
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
    } elseif ($data['type'] === 'biweekly') {
        // First find the next selected weekday (same as weekly)
        $reminderDate = $now->next($data['weekly_day'])->startOfDay();

        // Then add 1 extra week (so total = after 2 weeks)
        $reminderDate = $reminderDate->addWeek();
    }elseif ($data['type'] === 'monthly') {
        $reminderDate = $now->day($data['monthly_date'])->startOfDay();
        if ($now->greaterThan($reminderDate)) {
            $reminderDate = $reminderDate->addMonth();
        }
    } elseif ($data['type'] === 'custom') {
        $reminderDate = Carbon::parse($request->custom_date)->startOfDay();
        if ($now->greaterThan($reminderDate)) {
            return redirect()->back()->withErrors(['custom_date' => 'Custom date must be in the future.']);
        }
    }
    $data['reminder_date'] = $reminderDate;

    $reminder->update($data);

    if ($reminder) {
        return redirect()->route('reminder.create')->with('reminder_notice', 'Reminder updated successfully!');
    } else {
        return redirect()->route('reminder.create')->with('error', 'Failed to update reminder.');
    }
}
    protected function isAdmin($user)
    {
        return $user->role_id == 1 || $user->role_id == 2; // Assuming 1 is Super Admin and 2 is Manager
    }
}
