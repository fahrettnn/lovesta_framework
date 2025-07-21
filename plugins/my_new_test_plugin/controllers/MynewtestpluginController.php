<?php

namespace MyNewTestPlugin\Controllers; // DÜZELTİLDİ

use App\Core\Http\Request;
use App\Core\Http\Response;

class MynewtestpluginController // DÜZELTİLDİ
{
    public function index(Request $request): Response
    {
        return new Response("Hello from MynewtestpluginController in MyNewTestPlugin\Controllers!");
    }
}