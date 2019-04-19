<?php

namespace App\Services;

use phpseclib\Net\SSH2;
use Symfony\Component\Yaml\Yaml;

class TaskService
{
    const ERROR_CODE = 1;

    /**
     * @param array $task
     * @param array $stories
     */
    protected function storiesReplace(array &$task, array $stories)
    {
        foreach ($task['commands'] as $key => $command) {
            if (mb_strpos($command, '@story:') === 0) {
                $command = substr($command, 7);

                $storyArgs = explode(' ', $command);
                $storyName = array_shift($storyArgs);

                if (array_key_exists($storyName, $stories)) {
                    $storyCommands = $stories[$storyName]['commands'];

                    $this->replaceCommandsArgs($storyCommands, $storyArgs);

                    array_splice($task['commands'], $key, 1, $storyCommands);
                } else {
                    unset($task['commands'][$key]);
                }
            }
        }
    }

    /**
     * @param array $commands
     * @param array $args
     */
    protected function replaceCommandsArgs(array &$commands, array $args)
    {
        foreach ($commands as &$command) {
            foreach ($args as $key => $arg) {
                $command = str_replace('$' . ($key + 1) , $arg, $command);
            }
        }
    }

    /**
     * @param array $commands
     * @param string $branch
     * @param string $repo
     */
    protected function replaceCommandsVars(array &$commands, string $branch, string $repo)
    {
        $vars = [
            '${branch}' => $branch,
            '${repo}' => $repo,
        ];

        foreach ($commands as &$command) {
            foreach ($vars as $key => $var) {
                $command = str_replace($key, $var, $command);
            }
        }
    }

    /**
     * @param array $task
     * @throws \Exception
     */
    protected function validateTask(array $task): void
    {
        $errors = [];

        if (!isset($task['repo'])) {
            $errors[] = 'repo';
        }
        if (!isset($task['branch'])) {
            $errors[] = 'branch';
        }
        if (!isset($task['server'])) {
            $errors[] = 'server';
        }
        if (!isset($task['path'])) {
            $errors[] = 'path';
        } elseif ($task['server'] !== 'localhost' && !$this->pathExists($task)) {
            $errors[] = 'given path - ' . $task['path'] . ' doesnt exist';
        }
        if (!isset($task['commands'])) {
            $errors[] = 'commands';
        }

        if ($errors) {
            throw new \Exception('Task validate failed, fields: ' . implode(',', $errors), self::ERROR_CODE);
        }
    }

    /**
     * @param array $task
     * @return bool
     * @throws \Exception
     */
    protected function pathExists(array $task)
    {
        $server = $task['server'];
        $ssh = new SSH2($server['ip'], $server['port']);

        $key = new \phpseclib\Crypt\RSA();
        $key->loadKey(file_get_contents(base_path() . "/docker/.ssh/id_rsa"));

        if (!$ssh->login($server['user'], $key)) {
            throw new \Exception('Login Failed', self::ERROR_CODE);
        }

        $ssh->exec('cd ' . $task['path']);
        $status = $ssh->getExitStatus();

        if ($status != 0) {
            return false;
        }

        return true;
    }

    /**
     * @param array $task
     * @return array
     */
    protected function executeTaskLocal(array $task): array
    {
        $cd = 'cd ..';

        foreach ($task['commands'] as $command) {
            $response = [];
            exec($cd . ' && ' . $command, $response, $status);

            $output[] = [
                'command' => $command,
                'response' => implode('\r\n', $response),
                'status' => $status,
            ];

            if ($status != 0) {
                $output['error'] = true;
                if ($task['stop_on_error']) {
                    break;
                }
            }
        }

        return $output;
    }

    /**
     * @param array $task
     * @return array
     * @throws \Exception
     */
    protected function executeTaskSSH(array $task): array
    {
        $server = $task['server'];
        $ssh = new SSH2($server['ip'], $server['port']);

        $key = new \phpseclib\Crypt\RSA();
        $key->loadKey(file_get_contents(base_path() . "/docker/.ssh/id_rsa"));

        if (!$ssh->login($server['user'], $key)) {
            throw new \Exception('Login Failed', self::ERROR_CODE);
        }

        $cd = 'cd ' . $task['path'];

        foreach ($task['commands'] as $command) {
            $response = $ssh->exec($cd . '&& ' . $command);
            $status = $ssh->getExitStatus();

            $output[] = [
                'command' => $command,
                'response' => $response,
                'status' => $status,
            ];

            if ($status != 0) {
                $output['error'] = true;
                if ($task['stop_on_error']) {
                    break;
                }
            }
        }

        return $output;
    }

    /**
     * @param array $task
     * @return array
     * @throws \Exception
     */
    protected function getServer(array $config, array $task): array
    {
        if (!$server = $config['servers'][$task['server']] ?? []) {
            throw new \Exception("Server {$task['server']} not found", self::ERROR_CODE);
        }

        return $server;
    }

    /**
     * @param array $tasks
     * @param string $repository
     * @param string $branch
     * @return array
     */
    public function executeTasks(array $tasks, string $branch, string $repository): array
    {
        $configService = new ConfigService;

        $config = $configService->getConfig();

        foreach ($tasks as $task) {
            try {
                $startTime = microtime(true);

                $task['server'] = $task['server'] == 'localhost' ? 'localhost' : $this->getServer($config, $task);

                $this->validateTask($task);

                if ($config['stories']) {
                    $this->storiesReplace($task, $config['stories']);
                }

                $this->replaceCommandsVars($task['commands'], $branch, $repository);

                $output =  $task['server'] == 'localhost' ? $this->executeTaskLocal($task) : $this->executeTaskSSH($task);

                if (isset($output['error'])) {
                    $success = false;
                } else {
                    $success = true;
                }
            } catch (\Exception $e) {
                $output[] = [
                    'command' => '',
                    'response' => $e->getMessage(),
                    'status' => self::ERROR_CODE,
                ];
                $success = false;
            } finally {
                $executionTime = round(microtime(true) - $startTime, 1);
                $server = is_array($task['server']) ? $task['server']['ip'] : $task['server'];

                $data[] = [
                    'output' => $output,
                    'executionTime' => $executionTime,
                    'domain' => $task['domain'],
                    'server' => $server,
                    'success' => $success,
                    'jira_release' => $task['jira_release'],
                ];
            }
        }

        return $data;
    }

    /**
     * @param string $branch
     * @param string $repo
     */
    public function execute(string $branch, string $repo)
    {
        $configService = new ConfigService;

        $config = $configService->getConfig();

        $tasks = $config['tasks'];

        $tasks = array_filter($tasks, function ($task) use ($branch, $repo) {
            return $task['repo'] == $repo && ($task['branch'] == '*' || preg_match("/{$task['branch']}/", $branch));
        });

        $outputTasks = $this->executeTasks($tasks, $branch, $repo);

        $error = false;

        $slackService = new SlackService;
        foreach ($outputTasks as $output) {
            if (!$output['success']) {
                $error = true;
                if (!$output['output'][0]['command']) {
                    $errors[] = $output['output'][0]['response'];
                }
            }

            $slackService->sendTaskDoneToSlack($output);
        }

        if ($error) {
            $response = [
                'status' => 'error',
                'message' => 'Task error',
                'data' => [
                    'output' => $errors ?? 'Command failed',
                ]
            ];
            $code = 400;
        } else {
            $response = [
                'status' => 'success',
            ];
            $code = 200;
        }

        return response()->json($response, $code);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTasks()
    {
        try {
            $tasksFiles = array_diff(scandir(base_path() . '/pullkins/tasks'), array('.', '..'));

            foreach ($tasksFiles as $file) {
                $tasks[] = Yaml::parseFile(base_path() . '/pullkins/tasks/' . $file);
            }

            $data = [
                'status' => 'success',
                'data' => [
                    'tasks' => $tasks,
                ],
            ];

            return response()->json($data, 200);
        } catch (\Exception $e) {
            $code = 500;

            $data = [
                'status' => 'error',
                'code' => $code,
                'message' => $e->getMessage(),
            ];

            return response()->json($data, $code);
        }
    }
}