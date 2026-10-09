<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => ['sometimes', 'required',
                Rule::in(['todo', 'progress', 'done']);
            ],
            'priority' => [
                'sometimes', 'required',
                Rule::in(['low', 'medium', 'high']);
            ]
        ]);

        $user = $request->user();

        $query = Task::query()->where(function ($query) use ($user){
            $query->whereHas('project', function ($projectQuery) use ($user){
                $projectQuery->where('user_id', $user->id);
            })->orWhere('assigned_to', $user->id);
        })->with([
            'project::id,name,user_id',
            'assignee:id,name,user_id'
        ]);

        if (isset($filters['status'])){
            $query->where('status', $filters['status']);
        }
        if (isset($filters['priority'])){
            $query->where('priority', $filters['priority']);
        }

        return response()->json([
            'success'=>true,
            'message'=>'Get tasks success',
            'data'=>$query->latest()->paginate(10);
        ])
    }

    public function projectTasks(Request $request, Project $project) {
        $this->ensureProjectOwner($request, $project);

        $tasks = $project->tasks()
            ->with('assignee:id,name,email')
            ->latest()
            ->paginate(10);

        return response()->json([
            'success'=>true,
            'data'->$tasks
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Project $project)
    {
        $this->ensureProjectOwner($request, $project);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'status' => [
                'sometimes',
                'required',
                Rule::in(['todo', 'progress', 'done']),
            ],
            'priority' => [
                'sometimes',
                'required',
                Rule::in(['low', 'medium', 'high']),
            ],
        ]);

        $assignedTo = $data['assigned_to'] ?? null;
        unset($data['assigned_to']);

        $task = $project->tasks()->create($data);
        $task->assigned_to = $assignedTo;
        $task->save();
        $task->load('assignee:id,name,email');

        return response()->json([
            'success'=>true,
            'message' => 'Task added success',
            'data' => $task,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Task $task)
    {

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Task $task)
    {
        $isOwner = (int) $task->project->user_id
            === (int) $request->user()->id;

        $isAssignee = (int) $task->assigned_to
            === (int) $request->user()->id;

        abort_unless(
            $isOwner || $isAssignee,
            403,
            'You do not have access'
        );

        $task->load([
            'project:id,name,user_id',
            'assignee:id,name,email',
        ]);

        return response()->json([
            'success'=>true,
            'data' => $task,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task)
    {
        $this->ensureTaskOwner($request, $task);

        $data = $request->validate([
            'title' => [
                'sometimes', 'required', 'string', 'max:255',
            ],
            'description' => [
                'sometimes', 'nullable', 'string',
            ],
            'assigned_to' => [
                'sometimes', 'nullable', 'integer', 'exists:users,id',
            ],
            'status' => [
                'sometimes', 'required',
                Rule::in(['todo', 'progress', 'done']),
            ],
            'priority' => [
                'sometimes', 'required',
                Rule::in(['low', 'medium', 'high']),
            ],
        ]);

        $assignmentProvided = array_key_exists('assigned_to', $data);
        $assignedTo = $data['assigned_to'] ?? null;

        unset($data['assigned_to']);

        $task->fill($data);

        if ($assignmentProvided) {
            $task->assigned_to = $assignedTo;
        }

        $task->save();

        $task->load('assignee:id,name,email');

        return response()->json([
            'success'=>true,
            'message' => 'Task updated',
            'data' => $task,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Task $task)
    {
        $this->ensureTaskOwner($request, $task);
        $task->delete();

        return response()->json([
            'success'=>true,
            'message' => 'Task deleted',
        ]);
    }

    public function myTasks(Request $request)
    {
        $tasks = Task::query()
            ->where('assigned_to', $request->user()->id)
            ->with([
                'project:id,name,user_id',
                'assignee:id,name,email',
            ])
            ->latest()
            ->paginate(10);

        return response()->json([
            'success'=>true,
            'data'=>$tasks,
        ]);
    }

    public function updateStatus(
        Request $request,
        Task $task
    ) {
        $isOwner = (int) $task->project->user_id
            === (int) $request->user()->id;

        $isAssignee = (int) $task->assigned_to
            === (int) $request->user()->id;

        abort_unless(
            $isOwner || $isAssignee,
            403,
            'You do not have access to edit'
        );
        $data = $request->validate([
            'status' => [
                'required',
                Rule::in(['todo', 'progress', 'done']),
            ],
        ]);
        $task->update([
            'status' => $data['status'],
        ]);

        return response()->json([
            'success'=>true,
            'message' => 'Task status updated',
            'data' => $task->fresh()->load('assignee:id,name,email'),
        ]);
    }

    private function ensureProjectOwner(Request $request, Project $project): void {
        abort_unless(
            (int) $project->user_id ===
            (int) $request->user()->id,
            403,
            'Only owner can do this'
        );
    }
    private function ensureTaskOwner(
        Request $request,
        Task $task
    ): void {
        abort_unless(
            (int) $task->project->user_id ===
            (int) $request->user()->id,
            403,
            'Only owner can manage this task'
        );
    }
}
