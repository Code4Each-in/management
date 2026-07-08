<?php

namespace App\Http\Controllers;

use App\Models\TodoList;
use App\Models\Users;
use Illuminate\Http\Request;
use Auth;

class TodoListController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user()->load('role');
        $selectedUserId = $request->input('team_member_filter');

        // ✅ Fetch Super Admin's or Manager's own personal tasks (ordered by latest)
        $personalTasks = TodoList::where('user_id', $user->id)
            ->whereNull('ticket_id')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($user->role->name === 'Super Admin') {
            // ✅ Super Admin sees all employee tasks (excluding their own) ordered by latest
            $teamTasks = TodoList::where('user_id', '!=', $user->id)
                ->whereNull('ticket_id')
                ->orderBy('created_at', 'desc')
                ->get();

            // ✅ Get all users for dropdown (except the Super Admin)
            $users = Users::where('status', 1)
                ->where('id', '!=', $user->id)
                ->get(['id', 'first_name']);
        } elseif ($user->role->name === 'Manager') {
            // ✅ Get team members under the Manager
            $users = Users::join('managers', 'users.id', '=', 'managers.user_id')
                ->where('managers.parent_user_id', $user->id)
                ->where('users.status', 1)
                ->get(['users.id', 'users.first_name']);

            // ✅ Fetch team tasks ordered by latest
            $teamTasksQuery = TodoList::whereIn('user_id', $users->pluck('id')->toArray())
                ->whereNull('ticket_id')
                ->orderBy('created_at', 'desc');

            if ($selectedUserId && $users->contains('id', $selectedUserId)) {
                $teamTasksQuery->where('user_id', $selectedUserId);
            }

            $teamTasks = $teamTasksQuery->get();
        } else {
            // Default for other roles (they only see their own tasks)
            $teamTasks = collect(); // Empty collection
            $users = collect(); // Empty collection
        }

        return view('todo_list.index', compact('personalTasks', 'teamTasks', 'users', 'selectedUserId'));
    }

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'title' => 'required|string|max:255',
    //         'assigned_user_id' => 'nullable|exists:users,id' // Allow it to be null for personal tasks
    //     ]);

    //     $task = new TodoList();
    //     $task->title = $request->input('title');

    //     // ✅ If 'assigned_user_id' is provided, assign to team member; otherwise, assign to the manager
    //     $task->user_id = $request->input('assigned_user_id') ?? Auth::id();

    //     $task->status = 'open'; // Default status
    //     $task->save();

    //     return redirect()->back()->with('success', 'Task added successfully!');
    // }
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'assigned_user_id' => 'nullable|exists:users,id',
            'ticket_id' => 'nullable|exists:tickets,id',
        ]);

        $task = new TodoList();

        $task->title = $request->title;

        // Assigned user
        $task->user_id = $request->assigned_user_id ?? Auth::id();

        // Ticket relation
        $task->ticket_id = $request->ticket_id ?? null;

        // Creator
        $task->created_by = Auth::id();

        $task->status = 'open';

        $task->save();

        return redirect()->back()->with('success', 'Task added successfully!');
    }
    public function update(Request $request, TodoList $todoList)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'assigned_user_id' => 'nullable|exists:users,id',
        ]);

        $updateData = [
            'title' => $request->title,
        ];

        // optional assigned user update
        if ($request->filled('assigned_user_id')) {

            $updateData['user_id'] = $request->assigned_user_id;
        }

        $todoList->update($updateData);

        return response()->json(['success' => 'Task updated successfully']);
    }

    // public function updateStatus(Request $request, TodoList $todoList)
    // {
    //     $request->validate([
    //         'status' => 'required|string|in:open,completed,hold',
    //     ]);

    //     // If marked completed, set completed_at timestamp
    //     $todoList->update([
    //         'status' => $request->status,
    //         'completed_at' => $request->status === 'completed' ? now() : null,
    //     ]);

    //     return response()->json(['success' => 'Task status updated successfully']);
    // }
    public function updateStatus(Request $request, TodoList $todoList)
    {
        $request->validate([
            'status' => 'required|string|in:open,completed,hold',
        ]);

        $todoList->status = $request->status;

        if ($request->status === 'completed') {

            $todoList->completed_at = now();

            $todoList->completed_by = Auth::id();

        } else {

            $todoList->completed_at = null;

            $todoList->completed_by = null;
        }

        $todoList->save();

        return response()->json([
            'success' => 'Task status updated successfully'
        ]);
    }
    public function destroy(TodoList $todoList)
    {
        $todoList->delete();
        return response()->json(['success' => 'Task deleted successfully']);
    }

    public function holdTask($id)
    {
        $task = TodoList::findOrFail($id);
        $task->status = 'hold';
        $task->save();

        return response()->json(['message' => 'Task put on hold successfully!', 'task' => $task]);
    }
    public function updateInline(Request $request, $id)
    {
        $todo = TodoList::findOrFail($id);

        $todo->title = $request->title;

        $todo->user_id = $request->user_id;

        $todo->save();

        return response()->json([
            'success' => true
        ]);
    }
}

