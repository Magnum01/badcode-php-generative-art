<style>
    body { margin: 0; padding: 0; }
    img { border: 1em solid white; max-width: 100%; }
    .images { display: flex; flex-wrap: wrap; }
    .image { flex-grow: 1; position: relative; }
    span { position: absolute; top: 1em; left: 1em; padding: 2px 10px; background: white; font-size: 12px; font-weight: bold; }
</style>
<?php

/**
 * Ширина и высота результирующего изображения.
 * Для достижения наилучших результатов это должно быть несколько изображений исходного слоя.
 */
define('WIDTH', 32 * 5);
define('HEIGHT', 32 * 5);
/**
 * К-во картинок, сколько генерируем
 */
define('ITEM_COUNT', 26);
/**
 * Флаг, что нужно оставлять уже созданные картинки при следующей генерации
 */
define('KEEP_EXISTING', false);

$isGDEnabled = get_extension_funcs('gd');
if (!$isGDEnabled) {
    echo 'GD не установлен.';
    die();
}

generate_images(ITEM_COUNT);
display_images();

/**
 * Функция генерации картинок
 */
function generate_images(int $count) {
    // очищаем всю папку с изображениями перед генерацией изображений
    foreach (glob('./generated/*.png') as $image) {
        unlink($image);
    }

    for ($i = 1; $i <= $count; $i++) {
        $img = imagecreatetruecolor(WIDTH, HEIGHT);
        $imagePath = "./generated/{$i}.png";

        /**
         * Добавляем разные слои на результирующее изображение.
         * Важно соблюдать порядок добавления слоёв. Нам не нужно чтобы слой с цветом наложился сверху остальных слоёв (он должен быть самым первым)
         */
        //draw_part($img, 'skin');

        // или же, цвет первого слоя можно генерировать автоматически, генерируя случайный цвет
        drawRandomColor($img);

        draw_part($img, 'eyes');
        draw_part($img, 'mouths');

        // сохраняем картину в формате png.
        imagepng($img, $imagePath);
        imagedestroy($img);
    }
}

function drawRandomColor($img)
{
    $color = imagecolorallocate($img, random_int(0, 255), random_int(0, 255), random_int(0, 255));
    imagefill($img, 0, 0, $color);
}

/**
 * Выводим на экран все сгенерированные картинки
 */
function display_images() {
    $images = glob( './generated/*.png' );

    echo '<div class="images">';

    foreach ($images as $image) {
        $name = str_replace('./generated/', '', $image);
        echo '<div class="image">';
        printf('<img src="%s" />', $image);
        printf('<span class="">%s</span>', $name);
        echo '</div>';
    }

    echo '</div>';
}

/**
 *  Функция по добавлению на картинку соответствующей части (глаза, фон, рот)
 */
function draw_part($resultImage, string $group) {
    // Находим все картинки из папки images
    $parts = glob("./images/{$group}/*.png");

    // перемешиваем массив с доступными шаблонами текущей группы
    shuffle($parts);

    // и берем из массива картинок первую
    // сейчас используется только .png формат и соответствующая функция для этого, но можно доработать этот скрипт и определять формат, вызывая соответствующую функцию динамически
    $imagePart = imagecreatefrompng($parts[0]);

    /**
     * Копируем слой полученной картинки в результирующую картинку, которую мы создаём.
     * Я использую imagecopyresized, чтобы пиксельная графика оставалась резкой.
     * Вы также можете использовать imagecopyresampled для более плавного масштабирования.
     *
     * функции imagesx() и imagesy() получают ширину и высоту картинки, которую мы копируем в результирующую картинку.
     */
    imagecopyresized($resultImage, $imagePart, 0, 0, 0, 0, WIDTH, HEIGHT, imagesx($imagePart), imagesy($imagePart));
}
