<?php

namespace MyNewTestPlugin; // DÜZELTİLDİ

use App\Core\Http\Request;
use App\Core\Http\Response;

class NewController // DÜZELTİLDİ
{
    public function index(Request $request): Response
    {
        return new Response("Hello from New in MyNewTestPlugin!");
    }
}