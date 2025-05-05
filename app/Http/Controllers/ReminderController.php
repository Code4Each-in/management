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
            'type' => 'required|in:daily,weekly,monthly',
            'description' => 'required',
            'weekly_day' => 'nullable|string',
            'monthly_date' => 'nullable|integer|min:1|max:31',
            'user_id' => 'nullable|exists:users,id', // Ensure this validation
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
        if (auth()->user()->role->name === 'Super Admin' || auth()->user()->role->name === 'Manager') {
            // Use the selected user_id
            $data['user_id'] = $request->user_id;
        } else {
            // Default to the current authenticated user's ID
            $data['user_id'] = auth()->id();
        }

        // Create the reminder
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
