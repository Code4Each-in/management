<?php

namespace App\Http\Controllers;

use App\Models\TodoList;
use Illuminate\Http\Request;
use Auth;

class TodoListController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $tasks = $user->todoLists;

        return view('todo_list.index', compact('tasks'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $task = TodoList::create([
            'title' => $request->title,
            'user_id' => auth()->id(),
            'status' => 'open',
        ]);

        return response()->json($task);
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
    
        $status = $request->status;
        if ($status === 'completed') {
            $todoList->update(['completed_at' => now()]);
        } else {
            $todoList->update(['completed_at' => null]); 
        }
    
        $todoList->update([
            'status' => $status,
        ]);
    
        return response()->json(['success' => 'Task status updated successfully']);
    }
    


    public function destroy(TodoList $todoList)
    {
        $todoList->delete();
        return response()->json(['success' => 'Task deleted successfully']);
    }

    public function holdTask(TodoList $todoList)
{
    $todoList->update([
        'status' => 'hold',
    ]);

    return response()->json(['success' => 'Task put on hold successfully', 'status' => 'hold']);
}

    
}
