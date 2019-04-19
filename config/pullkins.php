<?php

return [
    # Slack channels and webhooks
    'channels' => [
        'username_in_github' => '@username_in_slack', # Edit here
    ],
    'slack_webhook' => env('SLACK_NOTIFICATION_WEBHOOK'),
    'error_to_slack' => env('LOG_SLACK_WEBHOOK_URL'),


    # Jira credentials
    'jira_host' => env('JIRA_HOST'),
    'jira_pass' => env('JIRA_PASS'),
    'jira_user' => env('JIRA_USER'),
    'jira_story_points_field' => env('JIRA_STORY_POINTS_FIELD'),
    'jira_done_status_id' => env('JIRA_DONE_STATUS'),

    # Telescope credentials
    'UI_email' => env('UI_EMAIL'),
    'UI_password' => env('UI_PASSWORD'),

];