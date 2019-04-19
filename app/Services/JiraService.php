<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use JiraRestApi\Configuration\ArrayConfiguration;
use JiraRestApi\Issue\JqlQuery;
use JiraRestApi\Issue\Transition;
use JiraRestApi\Issue\Version;
use JiraRestApi\JiraException;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\Project\ProjectService;
use JiraRestApi\Version\VersionService;

class JiraService
{
    private $config;

    public function __construct()
    {
        $this->config = new ArrayConfiguration([
            'jiraHost' => config('pullkins.jira_host'),
            'jiraUser' => config('pullkins.jira_user'),
            'jiraPassword' => config('pullkins.jira_pass'),
        ]);
    }

    /**
     * @param array $issueKeys
     * @return \JiraRestApi\Issue\Issue[]|null
     */
    public function getIssuesByKeys(array $issueKeys)
    {
        $jql = new JqlQuery();
        $jql->addInExpression('issuekey', $issueKeys);

        try {
            $issueService = new IssueService($this->config);
            $response = $issueService->search($jql->getQuery(), 0, 50);

            return $response->issues;
        } catch (JiraException | \Exception $e) {
            Log::channel('single')->error('Jira error: ' . $e->getMessage());
        }

        return null;
    }

    public function createVersion(array $issueKeys)
    {
        try {
            $projectService = new ProjectService($this->config);

            $projectKey = explode('-', $issueKeys[0])[0];
            $project = $projectService->get($projectKey);

            $versions = $projectService->getVersions($project->id);
            $lastVersion = $versions[$versions->count() - 1] ?? 'v1.0.0';

            $issues = $this->getIssuesByKeys($issueKeys);
            $storyPointsField = config('pullkins.jira_story_points_field');
            $storyPoints = 0;

            foreach ($issues as $issue) {
                $storyPoints += $issue->fields->customFields[$storyPointsField];
            }

            $versionName = $this->increaseVersion($storyPoints, $lastVersion->name);

            $versionService = new VersionService($this->config);
            $version = new Version;
            $version->setName($versionName)
                ->setDescription('Pullkins release')
                ->setReleased(true)
                ->setReleaseDate(now())
                ->setProjectId($project->id);
            $version = $versionService->create($version);

            $issueService = new IssueService($this->config);
            foreach ($issueKeys as $key) {
                $issueService->updateFixVersions($key, [$versionName], []);

                $transition = new Transition;
                $doneStatusId = config('pullkins.jira_done_status_id');
                $transition->setTransitionId($doneStatusId);
                $issueService->transition($key, $transition);
            }

        } catch (JiraException | \Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }

        return [
            'status' => 'success',
            'data' => [
                'name' => $version->name,
                'date' => $version->releaseDate,
                'url' => config('pullkins.jira_host') . '/projects/' . $projectKey . '/versions/' . $version->id,
            ]
        ];
    }


    /**
     * @param float $storyPoints
     * @param string $versionName
     * @return string
     */
    private function increaseVersion(float $storyPoints, string $versionName): string
    {
        $versionName = explode('.', $versionName);

        if ($storyPoints < 10) {
            $versionName[2] = ($versionName[2] ?? 0) + 1;
        } elseif ($storyPoints < 40) {
            $versionName[1] = ($versionName[1] ?? 0) + 1;
        } else {
            $versionName[0] = 'v' . ((int)$versionName[0] + 1);
        }

        return implode('.', $versionName);
    }
}