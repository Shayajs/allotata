<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LegalController extends Controller
{
    public function mentionsLegales()
    {
        return view('pages.legal.mentions-legales');
    }

    public function politiqueConfidentialite()
    {
        return view('pages.legal.confidentialite');
    }

    public function cgu()
    {
        return view('pages.legal.cgu');
    }

    public function cgv()
    {
        return view('pages.legal.cgv');
    }

    public function cookies()
    {
        return view('pages.legal.cookies');
    }
}
