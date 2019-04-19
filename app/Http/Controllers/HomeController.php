<?php

namespace App\Http\Controllers;

use Laravel\Telescope\Telescope;
use Illuminate\Routing\Controller;

class HomeController extends Controller
{
    /**
     * Display the Telescope view.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('layout', [
            'cssFile' => Telescope::$useDarkTheme ? 'app-dark.css' : 'app.css',
            'telescopeScriptVariables' => Telescope::scriptVariables(),
        ]);
    }
}
