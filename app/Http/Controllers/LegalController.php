<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class LegalController extends Controller
{
    public function terms()
    {
        return Inertia::render('legal/terms');
    }

    public function privacy()
    {
        return Inertia::render('legal/privacy');
    }

    public function refund()
    {
        return Inertia::render('legal/refund');
    }
}
