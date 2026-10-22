<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $body = implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin/',
            'Disallow: /account/',
            'Disallow: /candidate/',
            'Disallow: /employer/',
            'Disallow: /freelancer/profile',
            'Disallow: /recruiter/',
            'Disallow: /contracts/',
            'Disallow: /messages/',
            'Disallow: /profile',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /reset-password',
            'Sitemap: '.route('sitemap'),
            '',
        ]);

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
