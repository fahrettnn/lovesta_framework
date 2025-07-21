Lovesta Framework
Lovesta, modern PHP uygulamaları geliştirmek için tasarlanmış hafif, esnek ve modüler bir PHP Framework'üdür. Model-View-Controller (MVC) mimari deseni üzerine kurulu olan Lovesta, güçlü bir bağımlılık enjeksiyon (Dependency Injection) container'ı, sezgisel bir yönlendirme sistemi, modüler eklenti yapısı ve kapsamlı CLI araçları ile geliştiricilere hızlı ve düzenli bir geliştirme ortamı sunar. Lovesta'nın temel amacı, geliştirme sürecini kolaylaştırmak, kod tekrarını azaltmak ve projenin ölçeklenebilirliğini artırmaktır.

Özellikler
MVC Mimarisi: Temiz kod organizasyonu ve sorumluluk ayrımı.

Bağımlılık Enjeksiyonu (PHP-DI): Servislerin kolay yönetimi ve test edilebilir kod.

Sezgisel Routing: Kolayca rota tanımlama ve yönetimi.

Modüler Eklenti Sistemi: Uygulamanızı bağımsız, yeniden kullanılabilir bileşenlerle genişletme.

Action/Filter (Hook) Sistemi: Eklentiler arası iletişim ve dinamik içerik enjeksiyonu.

Veritabanı Katmanı: Basit Active Record benzeri Model yapısı ve PDO tabanlı bağlantı.

Migration Sistemi: Veritabanı şema değişikliklerini kolayca yönetme.

CLI Araçları: Bileşen oluşturma (make:), veritabanı migration'ları (migrate:) gibi yaygın görevleri otomatikleştiren güçlü komutlar.

Çıktı Tamponlama ile Görünüm Yönetimi: Performanslı ve esnek görünüm işleme.

Ortam Değişkeni Yönetimi (.env): Konfigürasyonun güvenli ve kolay yönetimi.

Hata Ayıklama (Tracy Debugger): Kapsamlı hata raporlama ve ayıklama araçları.

İçindekiler
Gereksinimler

Kurulum

Kullanım

3.1. Uygulama Yapısı

3.2. Rota Tanımlama

3.3. Controller Oluşturma

3.4. Görünüm (View) Kullanımı

3.5. Model ve Veritabanı Etkileşimi

3.6. Migration Kullanımı

Eklenti Geliştirme

4.1. Yeni Eklenti Oluşturma

4.2. Eklenti İçinde Bileşenler

4.3. Ortak Alanlara İçerik Ekleme (Action/Filter)

CLI Komutları

Katkıda Bulunma

Lisans

1. Gereksinimler
PHP 8.0 veya üzeri

Composer

MySQL veritabanı

Apache veya Nginx gibi bir web sunucusu (URL yeniden yazma/rewrite modülü etkin olmalı)

2. Kurulum
Projeyi Klonlayın veya İndirin:

Bash

git clone [repo_url] lovesta-app
cd lovesta-app
(Eğer Git kullanmıyorsanız, proje dosyalarını doğrudan web sunucunuzun belge köküne kopyalayın.)

Composer Bağımlılıklarını Yükleyin:

Bash

composer install
Ortam Ayarlarını Yapılandırın:

.env.example dosyasını kopyalayarak .env` adında yeni bir dosya oluşturun:

Bash

cp .env.example .env
.env dosyasını açın ve veritabanı bağlantı bilgilerini, APP_DEBUG ayarını ve diğer gerekli ortam değişkenlerini kendi ortamınıza göre düzenleyin.

Kod snippet'i

APP_NAME=MyLovestaApp
APP_ENV=local
APP_DEBUG=true

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
Web Sunucusu Yapılandırması:

Web sunucunuzu (Apache veya Nginx), projenizin public dizinini belge kökü (document root) olarak işaretleyecek şekilde yapılandırın. .htaccess dosyası URL yeniden yazma için zaten public dizini içinde mevcuttur.

Veritabanı Migration'larını Çalıştırın:

Veritabanı tablolarınızı oluşturmak için CLI aracını kullanın:

Bash

php lovesta migrate
Tebrikler! Lovesta Framework uygulamanız artık çalışmaya hazır. Tarayıcınızda uygulamanızın URL'sini ziyaret edebilirsiniz (örn. http://localhost/lovesta-app/public).

3. Kullanım
3.1. Uygulama Yapısı
Lovesta'nın temel klasör yapısı, projenizi düzenli tutmanıza yardımcı olur:

lovesta-framework/
├── app/                  # Uygulamanın çekirdek ve özel sınıfları
│   ├── Console/          # CLI komutları
│   ├── Core/             # Framework'ün temel bileşenleri (Http, Database, Helpers vb.)
│   ├── Exceptions/       # Uygulamaya özgü hata sınıfları
│   ├── Http/             # HTTP ile ilgili sınıflar (Controllers, Middleware vb.)
│   ├── Models/           # Uygulamanızın veritabanı modelleri
│   └── Services/         # Uygulamanızın iş mantığı servisleri
├── bootstrap/            # Uygulama başlatma dosyaları
├── config/               # Uygulama konfigürasyon dosyaları
├── database/             # Veritabanı ile ilgili dosyalar (migrations, seeders)
├── public/               # Web sunucusunun belge kökü, public erişimli dosyalar
├── plugins/              # Eklenti dizinleri
├── resources/            # Uygulama kaynakları (views, assets, lang)
├── routes/               # Rota tanımlama dosyaları (web.php, api.php)
├── storage/              # Loglar, cache, kullanıcı yüklemeleri gibi geçici/depolama dosyaları
├── vendor/               # Composer bağımlılıkları
├── .env                  # Ortam değişkenleri
├── composer.json         # Composer konfigürasyonu
└── lovesta               # CLI betiği
3.2. Rota Tanımlama
Rota tanımları routes/web.php (web arayüzü) ve routes/api.php (API) dosyalarında yapılır.

Örnek (routes/web.php):

PHP

use App\Core\Http\Router;
use App\Http\Controllers\WelcomeController; // Varsayılan controller'ınız varsa

/** @var Router $router */

// Basit GET rotası
$router->get('/', [WelcomeController::class, 'index']);

// Parametreli rota
$router->get('/users/{id}', function(int $id) {
    return new App\Core\Http\Response("Kullanıcı ID: " . $id);
});

// POST rotası
$router->post('/contact', [App\Http\Controllers\ContactController::class, 'submit']);

// Birden fazla HTTP metodu için
$router->match(['GET', 'POST'], '/form', [App\Http\Controllers\FormController::class, 'handle']);
3.3. Controller Oluşturma
Controller'lar, istekleri işleyen sınıflardır. make:controller CLI komutunu kullanarak oluşturulurlar.

Bash

php lovesta make:controller WelcomeController
# Oluşturur: app/Http/Controllers/WelcomeController.php
app/Http/Controllers/WelcomeController.php:

PHP

<?php

namespace App\Http\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Helpers\ActionFilterHelper; // Eğer kullanacaksanız

class WelcomeController
{
    public function index(Request $request, ActionFilterHelper $actionFilterHelper): Response
    {
        // View'a gönderilecek veriler
        $data = [
            'pageTitle' => 'Hoş Geldiniz!',
            'welcomeMessage' => 'Lovesta Framework ile tanışın.',
            'actionFilterHelper' => $actionFilterHelper // Layout için helper'ı geçirin
        ];

        // İçerik view'ını yakala
        ob_start();
        require APP_ROOT_PATH . '/resources/views/welcome.php'; // Sayfaya özel içeriği render et
        $pageContent = ob_get_clean();

        // Layout'u kullanarak Response nesnesi döndür
        return Response::view('resources/views/layouts/app.php', [
            'content' => $pageContent,
            'pageTitle' => $data['pageTitle'],
            'actionFilterHelper' => $actionFilterHelper // Layout da ActionFilterHelper'ı kullanabilir
        ]);
    }
}
3.4. Görünüm (View) Kullanımı
Görünümler, HTML çıktısını içeren PHP dosyalarıdır.

Ana Uygulama Görünümleri: resources/views/

resources/views/layouts/app.php (ana layout)

resources/views/welcome.php (sayfaya özel içerik)

Eklenti Görünümleri: plugins/plugin_adi/views/

plugins/slider/views/main_slider.php

Görünüm Render Etme:

Response::view(string $viewPath, array $data = [], ...): Bir Response nesnesi döndürür. Genellikle Controller'lardan HTTP yanıtı olarak kullanılır. $viewPath için 'resources/views/welcome.php' veya 'plugins/home/views/home.php' gibi tam veya eklenti göreceli yol kullanılır.

Response::renderPartial(string $viewName, array $data = []): İçeriği doğrudan mevcut çıktı tamponuna (output buffer) basar (bir Response nesnesi döndürmez). Widget'lar, eklenti içerikleri veya layout içindeki parçalar için idealdir.

$viewName için şu formatlar desteklenir:

'plugin_adı::view_adı' (örn. 'slider::main_slider' -> plugins/slider/views/main_slider.php)

'resources/views/tam/yol/view_adı.php' (örn. 'resources/views/partials/header.php')

'plugin_adı/views/view_adı.php' (eski plugin formatı)

'view_adı' veya 'alt_klasör/view_adı' (varsayılan olarak resources/views/ altında arar)

Örnek (resources/views/welcome.php):

PHP

<h1><?php echo htmlspecialchars($welcomeMessage); ?></h1>
<p>Bu, WelcomeController tarafından işlenen ana içeriktir.</p>

<?php
// Eğer bu sayfaya özel bir hook varsa
if (isset($actionFilterHelper)) {
    $actionFilterHelper->doAction('homepage_specific_area');
}
?>
3.5. Model ve Veritabanı Etkileşimi
App\Core\Database\Model sınıfını genişleterek veritabanı tablolarınızla etkileşim kurun.

Bash

php lovesta make:model Product
# Oluşturur: app/Models/Product.php
app/Models/Product.php:

PHP

<?php

namespace App\Models;

use App\Core\Database\Model;

class Product extends Model
{
    protected string $table = 'products'; // Modelin bağlı olduğu tablo adı

    // İlişkiler veya özel sorgu metotları ekleyebilirsiniz
    public function getActiveProducts(): array
    {
        return $this->where('is_active', 1)->get();
    }
}
Kullanım Örneği:

PHP

use App\Models\Product;

// Tüm ürünleri al
$products = Product::table()->get();

// ID'ye göre ürün bul
$product = Product::table()->where('id', 1)->first();

// Yeni ürün ekle
Product::table()->insert([
    'name' => 'Yeni Ürün',
    'price' => 29.99,
    'is_active' => 1
]);

// Ürünü güncelle
Product::table()->where('id', 1)->update(['price' => 34.99]);

// Ürünü sil
Product::table()->where('id', 1)->delete();
3.6. Migration Kullanımı
Veritabanı şema değişikliklerini yönetmek için make:migration komutunu kullanın.

Bash

php lovesta make:migration create_users_table
# Oluşturur: database/migrations/YYYY_MM_DD_HHMMSS_create_users_table.php
database/migrations/YYYY_MM_DD_HHMMSS_create_users_table.php:

PHP

<?php

use App\Core\Database\AbstractMigration;

class CreateUsersTable extends AbstractMigration
{
    public function up(): void
    {
        $this->createTable('users', [
            'id INT AUTO_INCREMENT PRIMARY KEY',
            'name VARCHAR(255) NOT NULL',
            'email VARCHAR(255) NOT NULL UNIQUE',
            'password VARCHAR(255) NOT NULL',
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ]);
        // İsteğe bağlı olarak başlangıç verileri ekleyebilirsiniz
        $this->insert('users', [
            ['name' => 'Admin User', 'email' => 'admin@example.com', 'password' => password_hash('password', PASSWORD_DEFAULT)],
        ]);
    }

    public function down(): void
    {
        $this->dropTable('users');
    }
}
Migration'ları çalıştırmak için: php lovesta migrate

4. Eklenti Geliştirme
Lovesta'nın eklenti sistemi, uygulamanızı modüler ve genişletilebilir hale getirir.

4.1. Yeni Eklenti Oluşturma
Bash

php lovesta make:plugin MyBlog
# Oluşturur: plugins/my_blog/ dizinini ve temel dosyaları.
Oluşturulduktan sonra, eklentinizi config/plugins.php dosyasına ekleyerek etkinleştirmeniz gerekir:

PHP

// config/plugins.php
return [
    'enabled' => [
        'home',
        'slider',
        'footer',
        'my_blog', // Yeni eklentinizi buraya ekleyin
    ],
];
4.2. Eklenti İçinde Bileşenler
Eklentiler kendi Controller, Model, View, Service, Migration ve rota dosyalarına sahip olabilir. Bu dosyalar, eklentinizin kök dizini altındaki ilgili alt dizinlerde yer alır. Eklentinizdeki plugin.php dosyası, eklentinin başlatma noktasıdır ve rotalarınızı, servislerinizi ve aksiyon/filtrelerinizi burada kaydedersiniz.

4.3. Ortak Alanlara İçerik Ekleme (Action/Filter)
Uygulamanızın layout'ları veya diğer görünümleri içinde tanımlanmış "action hook"larına eklentilerden içerik enjekte edebilirsiniz.

Örnek: app_header_content kancasına içerik ekleme:

Layout'unuzda kancayı tanımlayın (resources/views/layouts/app.php):

PHP

<header>
    <?php if (isset($actionFilterHelper)) $actionFilterHelper->doAction('app_header_content'); ?>
</header>
Eklentinizin plugin.php dosyasında kancaya bağlanın (plugins/my_header_plugin/plugin.php):

PHP

<?php
namespace MyHeaderPlugin;

use App\Core\Http\Router;
use App\Core\Helpers\ActionFilterHelper;
use App\Core\Http\Response; // Response sınıfını kullanın
use Psr\Container\ContainerInterface;

return function (Router $router, ContainerInterface $container, ActionFilterHelper $actionFilter) {
    $actionFilter->addAction('app_header_content', function() {
        // Header eklentisinin view'ını doğrudan çıktıya render eder
        Response::renderPartial('my_header_plugin::main_header');
    });
};
Bu örnekte plugins/my_header_plugin/views/main_header.php dosyası header içeriğini barındıracaktır.

5. CLI Komutları
Projenizin kök dizininden php lovesta komutuyla tüm CLI araçlarına erişebilirsiniz.

php lovesta list : Tüm mevcut komutları listeler.

php lovesta help [command] : Belirli bir komut hakkında yardım gösterir.

Örnek Komutlar:

php lovesta make:controller MyNewController

php lovesta make:model Order --plugin=Ecommerce

php lovesta make:migration create_products_table

php lovesta migrate

php lovesta migrate:rollback

6. Katkıda Bulunma
Lovesta Framework'ü daha da iyi hale getirmek için katkılarınızı bekliyoruz! Lütfen aşağıdaki adımları izleyerek projenize katkıda bulunun:

Projenin kod stil kılavuzlarına uyun.

Yeni özellikler veya hata düzeltmeleri için testler yazın.

Değişikliklerinizi ayrı bir dalda (branch) geliştirin.

Geliştirdiğiniz özelliği veya düzeltmeyi anlatan net bir Pull Request (PR) gönderin.