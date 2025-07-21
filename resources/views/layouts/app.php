<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Lovesta Uygulaması'; ?></title>
    <!-- CSS dosyalarınız -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <?php
    // Head eklentileri için bir hook tanımlayabiliriz
    if (isset($actionFilterHelper)) {
        $actionFilterHelper->doAction('app_head_elements');
    }
    ?>
</head>
<body>
    <div id="wrapper">
        <header>
            <?php
            // Header içeriği için kanca
            // Controller'dan gelen $actionFilterHelper nesnesini kullanıyoruz.
            if (isset($actionFilterHelper)) {
                $actionFilterHelper->doAction('app_header_content');
            }
            ?>
        </header>

        <main>
            <?php echo $content ?? ''; // Sayfaya özel içerik buraya gelecek ?>
        </main>

        <footer>
            <?php
            // Footer içeriği için kanca
            if (isset($actionFilterHelper)) {
                $actionFilterHelper->doAction('app_footer_content');
            }
            ?>
        </footer>
    </div>

    <!-- JS dosyalarınız -->
    <script src="/assets/js/main.js"></script>
    <?php
    // Body sonu eklentileri için bir hook
    if (isset($actionFilterHelper)) {
        $actionFilterHelper->doAction('app_body_end_elements');
    }
    ?>
</body>
</html>