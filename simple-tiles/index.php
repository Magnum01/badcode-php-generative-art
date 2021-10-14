<style>
    body {
        margin: 0;
        padding: 0;
    }

    img {
        border: 1em solid white;
        max-width: 100%;
    }

    .images {
        display: flex;
        flex-wrap: wrap;
    }

    .image {
        flex-grow: 1;
        position: relative;
    }

    span {
        position: absolute;
        top: 1em;
        left: 1em;
        padding: 2px 10px;
        background: white;
        font-size: 12px;
        font-weight: bold;
    }
</style>
<?php

// Размеры сетки.
define('GRID_WIDTH', 20);
define('GRID_HEIGHT', 20);
// Размер каждого отдельной тайла в наборе тайлов.
define('TILE_SIZE', 16);
// Насколько мы хотим увеличить размер изображения. Используйте целые числа, чтобы все выглядело четким.
define('MULT', 2);
// Ширина и высота выходного изображения.
// Для достижения наилучших результатов это значение должно быть в несколько раз больше изображения исходного слоя.
define('WIDTH', GRID_WIDTH * TILE_SIZE * MULT);
define('HEIGHT', GRID_HEIGHT * TILE_SIZE * MULT);
// Количество изображений для создания.
define('ITEM_COUNT', 3);
// Типы тайлов.
define('TILE_DEFAULT', 0);
define('TILE_ROAD', 1);
define('TILE_PARK', 2);

$isGDEnabled = get_extension_funcs('gd');
if (!$isGDEnabled) {
    echo 'GD не установлен.';
    die();
}

// изображение с набором тайлов (читай спрайт)
$tilesSourceImage = imagecreatefrompng('./tiles.png');

generate_images(ITEM_COUNT, $tilesSourceImage);
display_images();

/**
 * Генерация изображений
 * Это не обязательно должно быть в виде функции, это просто позволяет немного упорядочить вещи.
 */
function generate_images(int $count, $tilesSourceImage)
{
    // очищаем всю папку с изображениями перед генерацией изображений
    foreach (glob('./generated/*.png') as $image) {
        unlink($image);
    }

    for ($i = 1; $i <= $count; $i++) {
        $filename = "./generated/{$i}.png";

        // генерируем базовое изображение на котором будет рисовать остальные объекты
        $img = imagecreatetruecolor(WIDTH, HEIGHT);

        $world = generate_world();
        draw_world($world, $tilesSourceImage, $img);

        imagepng($img, $filename);
        imagedestroy($img);
    }
}

/**
 * Генерируем массив мира, который потом будет отрендерен на изображении
 */
function generate_world(): array
{
    $world = [];

    /**
     * Вы можете заметить, что мир сохраняется как массив строк, который содержит столбцы.
     * Это распространённая логика, что встречается в играх, но на первый взгляд может показаться нелогичной.
     *
     * Это означает, что массив хранится как $world[$y][$x], а не x,y,
     * что на первый взгляд кажется более логичным.
     */
    for ($y = 0; $y < GRID_HEIGHT; $y++) {
        $world[$y] = array_fill(0, GRID_WIDTH, TILE_DEFAULT);
    }

    /**
     * Добавляем дороги
     * Сначала определим, сколько дорог нужно добавить, глядя на количество тайлов. Затем умножаем на значение, чтобы вычислить долю мира.
     * Мы используем множитель сетки, чтобы между дорогами всегда оставалось пространство для добавления здания
     */
    $directions = ['ns', 'ew'];
    $grid = 4; // Может быть, это может быть случайный размер для каждого изображения?

    /**
     * Корректируем число множителя (0,03), пока дороги не будут соответствовать желаемой плотности.
     * Если вы добавите слишком много дорог, это будет полная сетка в дорогах.
     */
    $road_count = (GRID_WIDTH * GRID_HEIGHT) * 0.03;

    /**
     * Обратите внимание, что здесь нет проверки ошибок. Если c < 1, этот цикл ничего не сделает, но поскольку это код для демонстрации алгоритма, это не имеет значения.
     */
    for ($c = 0; $c < $road_count; $c++) {

        /**
         * Мы могли бы просто использовать здесь rand, но я возможно захочу добавить дополнительные направления для диагональных дорог или какой-либо другой формы,
         * поэтому использование массива значений делает эту часть более аккуратной
         */
        shuffle($directions);

        // длина дороги
        $length = random_int(6, 12);

        /**
         * Начальное и конечное положение дороги.
         * Обратите внимание, что деление возможных значений на сетку и последующее умножение сохраняет дороги на определённом интервале сетки
         */
        $startX = random_int(0, round(GRID_WIDTH / $grid)) * $grid;
        $startY = random_int(0, round(GRID_HEIGHT / $grid)) * $grid;
        $endX = $startX;
        $endY = $startY;

        // Меняем конечные положения отрезков дороги.
        if ('ns' === $directions[0]) {
            $endY = min($endY + $length, GRID_HEIGHT);
        }
        if ('ew' === $directions[0]) {
            $endX = min($endX + $length, GRID_WIDTH);
        }

        // Добавляем тайлы дороги
        for ($y = $startY; $y <= $endY; $y++) {
            for ($x = $startX; $x <= $endX; $x++) {
                $world[$y][$x] = TILE_ROAD;
            }
        }

    }

    // Добавим несколько парков несколько парков.
    $park_count = (GRID_WIDTH * GRID_HEIGHT) * 0.03; // Корректировка цифр, пока они не станут оптимальными.
    $grid = random_int(2, 4);

    for ($c = 0; $c < $park_count; $c++) {

        /**
         * Вычисляем координаты некоторых прямоугольных парков.
         * Не проверяется, расположены ли парки (или дороги, если на то пошло) друг над другом.
         * Но это не имеет значения, это часть случайности/органической природы генеративного процесса.
         */
        $startX = random_int(0, round(GRID_WIDTH / $grid)) * $grid;
        $startY = random_int(0, round(GRID_HEIGHT / $grid)) * $grid;
        $endX = $startX + random_int(2, 4);
        $endY = $startY + random_int(2, 4);

        // Добавляем тайлы парков
        for ($y = $startY; $y <= $endY; $y++) {
            for ($x = $startX; $x <= $endX; $x++) {
                $world[$y][$x] = TILE_PARK;
            }
        }

    }
    return $world;
}


/**
 * Рисуем мир на изображение
 */
function draw_world(array $world, $tilesSourceImage, $img)
{
    // Пройдёмся по координате y, сверху вниз
    for ($y = 0; $y < GRID_HEIGHT; $y++) {
        // теперь пройдёмся по колонках, координате x
        for ($x = 0; $x < GRID_WIDTH; $x++) {
            $tile_x_position = $x * TILE_SIZE * MULT;
            $tile_y_position = $y * TILE_SIZE * MULT;

            draw_tile($tilesSourceImage, $img, $world[$y][$x], $tile_x_position, $tile_y_position);
        }
    }
}


/**
 * Отрисовка одного тайла
 */
function draw_tile($tilesSourceImage, $img, $tile_type, $x, $y)
{
    /**
     * Бетонная плитка по умолчанию.
     * В массиве хранится позиция "x, y" изображения плитки, которую мы будем рисовать.
     */
    $tile = [0, 0];

    if (TILE_DEFAULT === $tile_type) {
        // Используем это, чтобы решить, рисовать здание или нет.
        if (random_int(0, 100) > 50) {
            /**
             * Параметры x (индекс 0) - это случайное число в диапазоне от 0 до 1.
             * Это число могло бы быть больше, если бы было больше плиток для выбора.
             */
            $tile = [random_int(0, 1), 2];
        }
    }

    if (TILE_ROAD === $tile_type) {
        $tile = [1, 0];
    }

    if (TILE_PARK === $tile_type) {
        $tile = [random_int(0, 2), 1];
    }

    imagecopyresized(
        $img, $tilesSourceImage, // изображения
        $x, $y, // положение на рисуемом изображении.
        $tile[0] * TILE_SIZE, // X позиция плитки для рисования.
        $tile[1] * TILE_SIZE, // Y позиция плитки для рисования.
        TILE_SIZE * MULT, TILE_SIZE * MULT, // Размеры, в которые нужно скопировать плитку.
        TILE_SIZE, TILE_SIZE // Размер плитки на исходном изображении.
    );

}


/**
 * Отображение списка сгенерированных изображений.
 */
function display_images()
{
    $images = glob('./generated/*.png');

    echo '<div class="images">';

    foreach ($images as $image) {
        $name = str_replace(array('./generated/', '.png'), '', $image);
        echo '<div class="image">';
        printf('<img src="%s" />', $image);
        printf('<span class="">%s</span>', $name);
        echo '</div>';
    }

    echo '</div>';

}
