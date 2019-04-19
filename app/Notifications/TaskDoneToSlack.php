<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\SlackMessage;

class TaskDoneToSlack extends Notification
{
    use Queueable;

    /**
     * @var array
     */
    private $data;
    /**
     * @var array
     */
    private $fields;
    /**
     * @var string
     */
    private $channel;


    /**
     * Create a new notification instance.
     *
     * @param array $data
     * @param array $fields
     * @param string $channel
     */
    public function __construct(array $data, array $fields, string $channel = '#pullkins')
    {
        $this->data = $data;
        $this->fields = $fields;
        $this->channel = $channel;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['slack'];
    }
 
    public function toSlack($notifiable)
    {
        $success = $this->data['success'] ? 'success' : 'error';

        return (new SlackMessage)
            ->from('Pullkins', ':building_construction:')
            ->to($this->channel)
            ->$success() # вызываем функцию по имени success() или error()
            ->content($this->data['content'])
            ->attachment(function ($attachment) {
                $attachment->fields($this->fields);
            });
    }
}
