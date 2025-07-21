<?php

namespace Slider\Controllers; // DÜZELTİLDİ

use App\Core\Http\Request;
use App\Core\Http\Response;

class SliderController // DÜZELTİLDİ
{
    public function index(Request $request): Response
    {
        return new Response("Hello from SliderController in Slider\Controllers!");
    }
}