<?php

namespace App\Http\Controllers;
use App\Models\BidSprint;
use App\Models\Task;
use App\Models\Users;
use App\Models\BDEComment;
use Illuminate\Http\Request;

class BDEController extends Controller
{
   public function index()
{
    $bidSprint = BidSprint::all(); 
    return view('bde.index', compact('bidSprint'));
}

    public function add(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'status' => 'required|in:0,1,2',
            'description' => 'nullable|string',
        ]);
        $sprint = BidSprint::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Bid sprint added successfully.',
            'data' => $sprint
        ]);
    }

    public function edit($id)
{
    $sprint = BidSprint::findOrFail($id);
    return view('bde.edit-bid-sprint', compact('sprint'));
}

public function update(Request $request, $id)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'start_date' => 'required|date',
        'end_date' => 'nullable|date|after_or_equal:start_date',
        'status' => 'required|in:0,1,2',
        'description' => 'required|string',
    ]);

    $sprint = BidSprint::findOrFail($id);
    $sprint->name = $request->name;
    $sprint->start_date = $request->start_date;
    $sprint->end_date = $request->end_date;
    $sprint->status = $request->status;
    $sprint->description = $request->description;
    $sprint->save();

    return redirect()->route('bdeSprint.index')->with('success', 'Bid Sprint updated successfully.');
}

public function destroy($id)
{
    $sprint = BidSprint::findOrFail($id);
    $sprint->delete();

    return response()->json(['message' => 'Bid Sprint deleted successfully.']);
}

public function view($id)
{
   
    $tasks = Task::with('creator')->where('bdesprint_id', $id)->get();

    $bdeSprints = BidSprint::all();
    $bdeSprint = BidSprint::findOrFail($id);

    $applied = $tasks->where('status', 'applied')->count();
    $viewed = $tasks->where('status', 'viewed')->count();
    $replied = $tasks->where('status', 'replied')->count();
    $success = $tasks->where('status', 'success')->count();

    $total = $tasks->count();
    $creatorIds = Task::whereNotNull('created_by')
        ->distinct()
        ->pluck('created_by');

    $user = Users::whereIn('id', $creatorIds)->get();
   $tasksJson = $tasks->map(function ($task) {
        return [
            'status' => $task->status,
            'created_by' => $task->created_by,
            'created_at' => $task->created_at->toDateString(),
        ];
    });
    return view('bde.bid-sprint-view', compact(
        'tasks', 'bdeSprints', 'bdeSprint',
        'applied', 'viewed', 'replied', 'success',
        'total','user','tasksJson'
    ));
}

public function storeTask(Request $request)
{
    $userId = auth()->id();

    $request->validate([
        'job_title' => 'required|string|max:255',
        'bdesprint_id' => 'required',
        'job_link' => 'nullable|url',
        'source' => 'nullable|string|max:255',
        'profile' => 'nullable|string|max:255',
        'status' => 'required|in:applied,viewed,replied,success',
    ]);

    Task::create([
        'job_title'     => $request->job_title,
        'job_link'      => $request->job_link,
        'source'        => $request->source,
        'profile'       => $request->profile,
        'status'        => $request->status,
        'bdesprint_id'  => $request->bdesprint_id,
        'created_by'    => $userId,
    ]);

    return response()->json(['success' => true, 'message' => 'Task created successfully.']);
}

public function edittask($id)
{
    $bdeSprints = BidSprint::all(); 
    $task = Task::findOrFail($id);
    return view('bde.edit-task', compact('task','bdeSprints'));
}

public function updateTaskWithPost(Request $request, $id)
{
    $request->validate([
        'job_title' => 'required|string|max:255',
        'bdesprint_id' => 'required|exists:bid_sprints,id',
    ]);

    $task = Task::findOrFail($id);
    $task->update([
        'job_title' => $request->job_title,
        'job_link' => $request->job_link,
        'source' => $request->source,
        'profile' => $request->profile,
        'bdesprint_id' => $request->bdesprint_id,
        'status' => $request->status,
    ]);

    return redirect()->route('tasks.edit', $id)->with('success', 'Task updated successfully.');
}

public function destroytask($id)
{
    $task = Task::findOrFail($id);
    $task->delete();

    return response()->json(['message' => 'Task deleted successfully.']);
}

public function show($id)
{
    $task = Task::findOrFail($id);
    // $comments = BDEComment::with('user') 
    // ->where('task_id', $task->id)
    // ->oldest() 
    // ->get();
    return view('bde.task-detail', compact('task'));
}

public function addComment(Request $request)
{
    $request->validate([
        'task_id' => 'required|exists:tasks,id',
        'comment' => 'required_without:comment_file|string',
        'comment_file.*' => 'file|max:2048', 
    ]);

    $commentText = $request->input('comment', '');
    $taskId = $request->task_id;
    $userId = auth()->id();

    $documentPaths = [];

    if ($request->hasFile('comment_file')) {
        foreach ($request->file('comment_file') as $file) {
            $fileNameWithoutExt = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $dateString = date('YmdHis');
            $name = $dateString . '_' . $fileNameWithoutExt . '.' . $file->extension();
            $file->move(public_path('assets/img/ticketAssets'), $name);

            $path = 'ticketAssets/' . $name;
            $documentPaths[] = $path;
        }
    }

    $bdeComment = BDEComment::create([
        'task_id' => $taskId,
        'comment_by' => $userId,
        'comments' => $commentText,
        'document' => implode(',', $documentPaths), 
    ]);

    return response()->json([
        'status' => 200,
        'message' => 'Comment added successfully',
        'data' => $bdeComment,
    ]);
}

    public function getComments($taskId)
    {
        $comments = BDEComment::with('user')
            ->where('task_id', $taskId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($comments);
    }

            public function updateStatus(Request $request, $taskId)
        {
            $request->validate([
                'status' => 'required|in:applied,viewed,replied,success',
            ]);

            $task = Task::findOrFail($taskId);
            $task->status = $request->status;
            $task->save();

            return response()->json(['success' => true, 'status' => $task->status]);
        }



}

