<?php

namespace Aloware\FairQueue\Http\Controllers;

class HomeController extends Controller
{
    /**
     * Get the key performance stats for the dashboard.
     *
     * @return array
     */
    public function index()
    {
        return view('fairqueue::layout', [
            'cssFile' => 'app.css',
            'fairqueueScriptVariables' => ['path' => 'fairqueue']
        ]);
    }

}
