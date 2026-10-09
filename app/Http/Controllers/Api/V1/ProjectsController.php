<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\projects;
use GuzzleHttp\Handler\ProxyEnv;
use Illuminate\Http\Request;

class ProjectsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $projects = $request->user()->project()->withCount('tasks')->latest()->paginate(10);
        return response()->json([
            'success' => true,
            'message' => 'Get all projects success',
            'data' => $projects
        ], 201);
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
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'desription' => 'nullable|string',
            'deadline' => 'nullable|date'
        ]);
        $project = $request->user()->project()->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Project added',
            'data' => $project
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Project $project)
    {
        $this->ensureOwner($request, $project);
        $project->load(['task.assignee:id,name,email']);
        return response()->json([
            'success' => true,
            'message' => 'Get project data success',
            'data' => $project
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(projects $projects)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project)
    {
        $this->ensureOwner($request, $project);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'desription' => 'nullable|string',
            'deadline' => 'nullable|date'
        ]);
        $project->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Project update success',
            'data' => $project->fresh(),
        ]);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Project $project)
    {
        $this->ensureOwner($request, $project);
        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Project delete success',
        ]);
    }

    private function ensureOwner(Request $request, Project $project): void
    {
        abort_unless(
            (int) $project->user_id === (int) $request->user()->id,
            403,
            'You do not have access to this project'
        );
    }
}
