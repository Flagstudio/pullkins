<?php

namespace App\Jobs;

use App\Services\Interfaces\PayloadInterface;
use App\Services\SlackService;
use App\Services\TaskService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ExecuteWebhooksTasksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var array
     */
    private $tasks;
    /**
     * @var array
     */
    private $data;
    /**
     * @var PayloadInterface
     */
    private $payload;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $data, array $tasks, PayloadInterface $payload)
    {
        $this->data = $data;
        $this->tasks = $tasks;
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        $taskService = new TaskService;

        if ($this->tasks) {
            $tasksOutput = $taskService->executeTasks($this->tasks, $this->payload->getBranch(), $this->payload->getRepo());
        } else {
            $tasksOutput[] = [
                'output' => [
                    'command' => '',
                    'response' => 'There are no matching tasks',
                    'code' => 1,
                ],
                'executionTime' => 0,
                'domain' => '',
                'server' => '',
                'success' => false,
            ];
        }

        $slackService = new SlackService;
        foreach ($tasksOutput as $task) {
            $slackService->sendTaskDoneToSlack($task, $this->payload);
        }
    }
}
