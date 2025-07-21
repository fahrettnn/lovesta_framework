<?php

namespace Home\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Helpers\ActionFilterHelper;

class HomeController
{
    public function index(Request $request, ActionFilterHelper $actionFilterHelper): Response
    {
        // Ana sayfa içeriğini bir değişkene alın
        ob_start();
        // home.php içeriği artık buraya gelecek
        // home.php'nin içinde ActionFilterHelper'ı çağırmak yerine,
        // direkt burada doAction'ı çağırabiliriz, veya home.php içinde bırakabiliriz.
        // home.php'nin kendisi bir alt-görünüm gibi çalışacak.
        // Slider içeriğini buraya dahil edebiliriz:
        // $actionFilterHelper->doAction('top_page_slider'); // Eğer bu sadece home.php'ye özelse burada bırakın.
        // Aksi halde home.php içinde kalması daha iyidir.
        require APP_ROOT_PATH . '/plugins/home/views/home.php';
        $homeContent = ob_get_clean();

        // Layout'u render ederken, ana sayfa içeriğini ve ActionFilterHelper'ı layout'a gönderin
        return Response::view('resources/views/layouts/app.php', [
            'content' => $homeContent, // home.php'nin çıktısı
            'pageTitle' => 'Ana Sayfa - Lovesta',
            'actionFilterHelper' => $actionFilterHelper // ActionFilterHelper'ı layout'a geçirin
        ]);
    }
}