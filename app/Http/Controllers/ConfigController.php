<?php

namespace App\Http\Controllers;

use App\Services\ConfigService;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    /**
     * @var ConfigService
     */
    private $configService;

    public function __construct(ConfigService $configService)
    {
        $this->configService = $configService;
    }

    public function parseServers()
    {
        $this->configService->parseServers();
    }

    public function parseTasks()
    {
        $this->configService->parseTasks();
    }
}
