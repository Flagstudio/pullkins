<?php

namespace App\Http\Controllers;

use App\Jobs\ExecuteTasksJob;
use App\Services\ConfigService;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Symfony\Component\Yaml\Yaml;

class TaskController extends Controller
{
    /**
     * @var TaskService
     */
    private $taskService;

    /**
     * TaskController constructor.
     * @param TaskService $taskService
     */
    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function get()
    {
        return $this->taskService->getTasks();
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function execute(Request $request)
    {
        $request->validate([
            'branch' => 'required',
            'repo' => 'required',
        ]);

        return $this->taskService->execute($request->branch, $request->repo);
    }
}
