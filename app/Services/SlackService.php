<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 12.04.2019
 * Time: 14:35
 */

namespace App\Services;

use App\Notifications\TaskDoneToSlack;
use App\Services\Interfaces\PayloadInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class SlackService
{
    /**
     * @param array $task
     * @param PayloadInterface $payload
     */
    public function sendTaskDoneToSlack(array $task, PayloadInterface $payload = null): void {
        $fields = $this->getFields($task, $payload);

        $task['content'] = $this->getSlackContent($task['output'], $payload, $task['jira_release']);

        $slackHook = config('pullkins.slack_webhook');

        // Notification to #pullkins
        Notification::route('slack', $slackHook)
            ->notify(new TaskDoneToSlack($task, $fields));


        // Personal notification
        $channels = config('pullkins.channels');
        if ($payload && array_key_exists($payload->getAuthor(), $channels)) {
            $channel = config('pullkins.channels.' . $payload->getAuthor());
            Notification::route('slack', $slackHook)
                ->notify(new TaskDoneToSlack($task, $fields, $channel));
        }

        //Error notification if any command failed
        if (!$task['success']) {
            $task['output'] = array_filter($task['output'], function($command) {
                return !$command['command'] || $command['status'] != 0;
            });

            $content = $this->getSlackContent($task['output'], $payload, false);
            $task['content'] = '<!channel>' . PHP_EOL . $content;
            Notification::route('slack', $slackHook)
                ->notify(new TaskDoneToSlack($task, $fields, '#pullkins-errors'));
        }
    }

    /**
     * Generate message content for slack notification
     *
     * @param PayloadInterface $payload
     * @param array $output
     * @return string
     */
    protected function getSlackContent(array $output, PayloadInterface $payload = null, bool $jiraRelease): string
    {
        $date = new Carbon;
        $date = $date->now()->toFormattedDateString();
        $content = 'Дата: ' . PHP_EOL;
        $content .= '*' . $date . '*' . PHP_EOL;

        if ($payload) {
            $commits = '*Коммиты:* ' . PHP_EOL;

            $issueKeys = [];
            foreach ($payload->getCommits() as $commit) {
                $commits .= ':commit: <' .
                    $commit['links']['html']['href'] .
                    '|' .
                    substr($commit['hash'], 0, 7) .
                    '>      ' .
                    $commit['message'] .
                    PHP_EOL;

                //Jira issue keys
                preg_match('/\w+-\d+/', $commit['message'], $matches);
                if ($matches) {
                    $issueKeys[] = $matches[0];
                }
            }

            if (isset($issueKeys)) {
                $jiraService = new JiraService;
                $issues = $jiraService->getIssuesByKeys($issueKeys);

                if ($issues) {
                    $issuesOutput = $this->getIssuesOutput($issues, $payload);

                    $content .= '*Задачи:* ' . PHP_EOL;
                    $content .= $issuesOutput;
                }

                if ($jiraRelease) {
                    $version = $jiraService->createVersion($issueKeys);

                    $content .= '*Версия:*' . PHP_EOL;
                    if ($version['status'] == 'success') {
                        $content .= '<' . $version['data']['url'] . ' |' . $version['data']['name'] . '> . Дата релиза: '
                            . $version['data']['date'] . PHP_EOL;
                    } else {
                        $content .= '``` Failed: ' . $version['message'] . '```';
                    }
                }
            }

            $content .= $commits;
        }

        $content .= '*Вывод:* ' . PHP_EOL;
        foreach ($output as $item) {
            if ($item['command']) {
                $content .= $item['status'] == 0 ? ':heavy_check_mark: ' : ':x: ';
                $content .= $item['command'] . PHP_EOL;
            }
            if ($item['response']) {
                $content .= $item['response'] . PHP_EOL;
            }
        }

        return $content;
    }

    /**
     * @param $issues
     * @param PayloadInterface $payload
     * @return string
     */
    protected function getIssuesOutput($issues, PayloadInterface $payload): string
    {
        $content = '';

        if ($issues) {
            $content .= ":{$payload->getAuthor()}:" . ' ' . $issues[0]->fields->assignee->displayName . PHP_EOL;
            foreach ($issues as $issue) {
                $fields = $issue->fields;

                switch ($fields->issuetype->name) {
                    case 'Задача':
                        $emoji = ':tasktask:';
                        break;
                    case 'Фича':
                        $emoji = ':storystory:';
                        break;
                    default:
                        $emoji = ':bugbug:';
                        break;
                }

                $link = config('pullkins.jira_host') . '/browse/' . $issue->key;
                $storyPointsField = config('pullkins.jira_story_points_field');
                $creator = $issue->fields->creator;

                $content .= $emoji . "<$link|$issue->key>";
                $content .= " `SP: {$issue->fields->customFields[$storyPointsField]}`";
                $content .= " {$issue->fields->summary}";
                $content .= " (Автор: {$creator->displayName} :{$creator->name}:)" . PHP_EOL;
            }
        }

        return $content;
    }

    /**
     * @param array $task
     * @param PayloadInterface $payload
     * @return array
     */
    private function getFields(array $task, PayloadInterface $payload = null): array
    {
        if ($payload) {
            $fields = [
                'Репозиторий' => '<' . $payload->getRepoUrl() . ' |' . explode('/', $payload->getRepo())[1] . '>',
                'Ветка' => ($payload->getBranch() == 'master' ? ':pray:' : ':ok_hand:') . ' ' . $payload->getBranch(),
                'Время выполнения скрипта' => ':stopwatch: ' . $task['executionTime'] . ' секунд',
                'Домен' => '<http://' . $task['domain'] . ' |' . $task['domain'] . '>',
                'Автор' => ":{$payload->getAuthor()}:" . ' ' . explode(' ', $payload->getAuthor())[0],
                'Сервер' => $task['server'],
            ];
        } else {
            $fields = [
                'Время выполнения скрипта' => ':stopwatch: ' . $task['executionTime'] . ' секунд',
                'Автор' => ':desktop_computer: Запущено из UI',
                'Домен' => '<http://' . $task['domain'] . ' |' . $task['domain'] . '>',
                'Сервер' => $task['server'],
            ];
        }
        return $fields;
    }
}