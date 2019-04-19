<?php

namespace App\Http\Controllers;

use App\Jobs\ExecuteWebhooksTasksJob;
use App\Services\BitbucketWebhookService;
use Illuminate\Http\Request;

class BitbucketWebhookController extends Controller
{
    /**
     * @var BitbucketWebhookService
     */
    private $bitbucketWebhookService;

    /**
     * BitbucketWebhookController constructor.
     * @param BitbucketWebhookService $bitbucketWebhookService
     */
    public function __construct(BitbucketWebhookService $bitbucketWebhookService)
    {
        $this->bitbucketWebhookService = $bitbucketWebhookService;
    }


    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        return view('welcome');
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function receive(Request $request)
    {
        $this->bitbucketWebhookService->receive($request->all());

        return response('', 200);
    }
}
