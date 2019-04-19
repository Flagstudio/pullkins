<?php

namespace App\Services;

use Symfony\Component\Yaml\Yaml;

class ConfigService
{
    public function parseServers(): void
    {
        $servers = [
            '60' => 'root@62.109.14.60',
            '47' => 'root@188.93.211.47',
            'local' => '127.0.0.1',
            'sek' => '185.93.111.244',
            'gkers' => 'u22884@u22884.netangels.ru',
            'gagarin' => 'u0354974@31.31.196.163',
            'cin' => 'artinstall@92.53.96.105',
            'bergauf' => 'u19653@u19653.netangels.ru',
            '35' => 'root@95.216.170.35',
            'pw' => 'u0507965@server150.hosting.reg.ru',
            'iq' => 'root@95.216.188.245',
            'elpas' => 'root@94.154.13.199',
            'uns' => 'root@91.226.83.130',
            'riv' => 'u15498@u15498.netangels.ru',
            'dm' => 'root@159.69.37.50',
            'artvds' => 'root@5.23.52.229',
            'fto' => 'root@62.109.18.1',
            'ruriv' => 'root@94.154.11.91',
            'au' => 'root@94.154.11.143',
            'gg_prod' => 'root@95.216.166.63'
        ];


        foreach ($servers as $key => $server) {
            $server = explode('@', $server);
            $config[$key] = [
                'user' => count($server) > 1 ? $server[0] : 'root',
                'ip' => $server[1] ?? $server[0],
                'port' => 22,
            ];
        }

        $config = \Symfony\Component\Yaml\Yaml::dump($config, 4, 2);

        file_put_contents(base_path() . '/pullkins/servers.yml', $config);
    }

    public function parseTasks(): void
    {
        preg_match_all('/@task((.|\n)*)@endtask/U', file_get_contents(base_path() . '/Envoy.blade.php'), $matches);

        foreach ($matches[1] as $task) {
            $task = explode(PHP_EOL, $task);

            $task = array_map('trim', $task);

            $task = array_values(array_filter($task, function($item) {
                return !empty($item);
            }));

            $explode = explode(',', trim($task[0], '()'));
            $params = explode(':', $explode[0]);
            $repo = trim($params[0], "'");
            $branch = explode('--', $params[1])[0];
            $domain = trim(explode('--', $params[1])[1], "'");
            $server = trim(explode('=>', $explode[1])[1], "'[] ");
            $path = explode(' ', trim($task[1]))[1];
            $commands = array_slice($task, 2);

            $tasks[] = [
                'repo' => $repo,
                'branch' => $branch,
                'domain' => $domain,
                'server' => $server,
                'path' => $path,
                'commands' => $commands,
                'stop_on_error' => false,
                'jira_release' => false,
            ];
        }

        foreach ($tasks as $task) {
            $config = \Symfony\Component\Yaml\Yaml::dump([$task], 4 ,2);

            file_put_contents(base_path() . "/pullkins/tasks/{$task['domain']}.yml", $config);
        }
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        $config['servers'] = Yaml::parseFile(base_path() . '/pullkins/servers.yml');

        $config['stories'] = Yaml::parseFile(base_path() . '/pullkins/stories.yml');

        $tasksFiles = array_diff(scandir(base_path() . '/pullkins/tasks'), array('.', '..'));
        $config['tasks'] = [];
        foreach ($tasksFiles as $file) {
            $config['tasks'] = array_merge($config['tasks'], Yaml::parseFile(base_path() . '/pullkins/tasks/' . $file));
        }

        return $config;
    }
}