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
            ->orderBy('created_at', 'desc')
            ->get();

        if ($user->role->name === 'Super Admin') {
            // ✅ Super Admin sees all employee tasks (excluding their own) ordered by latest
            $teamTasks = TodoList::where('user_id', '!=', $user->id)
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







    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'assigned_user_id' => 'nullable|exists:users,id' // Allow it to be null for personal tasks
        ]);

        $task = new TodoList();
        $task->title = $request->input('title');

        // ✅ If 'assigned_user_id' is provided, assign to team member; otherwise, assign to the manager
        $task->user_id = $request->input('assigned_user_id') ?? Auth::id();

        $task->status = 'open'; // Default status
        $task->save();

        return redirect()->back()->with('success', 'Task added successfully!');
    }




    public function update(Request $request, TodoList $todoList)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $todoList->update([
            'title' => $request->title,
        ]);

        return response()->json(['success' => 'Task updated successfully']);
    }

    public function updateStatus(Request $request, TodoList $todoList)
    {
        $request->validate([
            'status' => 'required|string|in:open,completed,hold',
        ]);

        // If marked completed, set completed_at timestamp
        $todoList->update([
            'status' => $request->status,
            'completed_at' => $request->status === 'completed' ? now() : null,
        ]);

        return response()->json(['success' => 'Task status updated successfully']);
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




}

