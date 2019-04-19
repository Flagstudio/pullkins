<?php

namespace App\Services;

use App\Jobs\ExecuteWebhooksTasksJob;
use App\Services\Classes\BitbucketPayload;
use App\Services\Interfaces\PayloadInterface;

class BitbucketWebhookService
{
    const ERROR_CODE = 1;

    /**
     * @param array $data
     * @return PayloadInterface
     */
    public function getPayloadData(array $data): PayloadInterface
    {
        $payload = new BitbucketPayload;

        if (app()->environment() == 'local') {
            //Testing payload
            $payload->setRepo('flag_studio/fit')
                ->setRepoUrl('https://bitbucket.org/flag_studio/fit')
                ->setBranch('premaster')
                ->setAuthor('vasishakd')
                ->setCommits([
                    [
                        'links' => [
                            'html' => [
                                'href' => 'https://bitbucket.org/flag_studio/au/commits/c88b0e404ab721ba671129a94ebaaf116e543a40',
                            ],
                        ],
                        'hash' => 'c88b0e404ab721ba671129a94ebaaf116e543a40',
                        'date' => now()->toDateTimeString(),
                        'message' => 'test',
                    ],
                    [
                        'links' => [
                            'html' => [
                                'href' => 'https://bitbucket.org/flag_studio/au/commits/c88b0e404ab721ba671129a94ebaaf116e543a40',
                            ],
                        ],
                        'hash' => 'ba671129a94c88b0e404ab721ebaaf116e543a40',
                        'date' => now()->subMinutes(5)->toDateTimeString(),
                        'message' => 'FIT-1288',
                    ],
                ])
                ->setRepoUrl('https://bitbucket.org/flag_studio/fit');
        } else {
            $payload->setRepo($data['repository']['full_name'])
                ->setRepoUrl($data['repository']['links']['html']['href'])
                ->setBranch($data['push']['changes'][0]['new']['name'])
                ->setAuthor($data['actor']['username'])
                ->setCommits($data['push']['changes'][0]['commits']);
        }

        return $payload;
    }

    public function receive($data)
    {
        $configService = new ConfigService;

        $config = $configService->getConfig();

        $tasks = $config['tasks'];

        $payload = $this->getPayloadData($data);

        $tasks = array_values(array_filter($tasks, function ($task) use ($payload) {
            return $task['repo'] == $payload->getRepo() && ($task['branch'] == '*' || preg_match("/{$task['branch']}/", $payload->getBranch()));
        }));

        $data = [
            'domain' => $tasks[0]['domain'],
            'branch' => $payload->getBranch(),
            'author' => $payload->getAuthor(),
            'repo' => $payload->getRepo(),
            'webhook_data' => $data,
        ];

        ExecuteWebhooksTasksJob::dispatch($data, $tasks, $payload);
    }
}