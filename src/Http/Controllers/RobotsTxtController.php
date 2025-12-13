<?php

declare(strict_types=1);

namespace DissNik\RobotsTxt\Http\Controllers;

use DissNik\RobotsTxt\Facades\RobotsTxt;
use Illuminate\Http\Response;

class RobotsTxtController
{
    public function __invoke(): Response
    {
        $content = RobotsTxt::generate();

        return response($content, 200, ['Content-Type' => 'text/plain']);
    }
}
