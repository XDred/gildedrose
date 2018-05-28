<?php

/*
 *   Нужно было добавить возможность легко добавлять новые категории товаров со своими свойствами
 *
 *   для этого я добавил 2 массива - по умолчанию и настройки
 */

class GildedRose
{

    private $items;

    private $default = array( // массив по умолчанию - применяется если не предмет не найден в массиве настроек
        'sellin'  => -1, // увеличение параметра sell_in за шаг
        'quality' => -1, // увеличение параметра quality за шаг
        'expired' => -2, // увеличение параметра quality за шаг, если продукт просрочен
    );

    private $settings = array( // массив настроек - описывает параметры предметов
        'Sulfuras, Hand of Ragnaros' => array( // сульфурас легендарный - у него ничего не меняется
            'sellin'  => 0,
            'quality' => 0,
            'expired' => 0,
        ),
        'Aged Brie'                  => array( // бри прибавляет качества со временем
            'sellin'  => -1,
            'quality' => 1,
            'expired' => 2,
        ),
        'Conjured'                   => array( // призванные товары теряют качество в 2 раза сильнее обычных
            'sellin'  => -1,
            'quality' => -2,
            'expired' => -4,
        ),
        'Backstage passes'           => array(
            'sellin'  => -1,
            'quality' => 1,
            'expired' => '=0', // установить quality сразу в 0 если продукт просрочен
            'days'    => array( // особый параметр, отвечает за изменение качества в зависимости от оставшихся дней
                10 => 2, // если меньше 10 то +2
                5  => 3, // если меньше 5 то +3
            ), // впринципе можно было объеденить с expired но я подумал что так будет лучше
        ),
    );

    public function __construct($items)
    {
        $this->items = $items;
    }

    private function add_quality(&$cur_quality, $add_quality){
        if ($add_quality != 0) { // если изменение качества не равно нулю
            $cur_quality = $cur_quality + $add_quality; // добавляем качество
            if ($cur_quality < 0) {
                $cur_quality = 0;
            }
            if ($cur_quality > 50) {
                $cur_quality = 50;
            }
        }
    }

    public function update_quality()
    {
        foreach ($this->items as $item) {
            $settings    = array_keys($this->settings); // берём ключи массива настроек
            $cur_setting = $this->default; // записываем настройку по умолчанию в качестве текущей
            foreach ($settings as $setting) {
                if (strpos($item->name, $setting) !== false) { // если название предмета содержит ключ из массива
                    $cur_setting = $this->settings[$setting]; // то запоминаем эту настройку в качестве текущей
                    break; // и выходим из цикла
                }
            }

            $item->sell_in = $item->sell_in + $cur_setting['sellin']; // уменьшаем количество оставшихся дней на столько сколько указано в настройках

            if ($item->sell_in < 0) { // если продукт просрочен
                if ($cur_setting['expired'] === '=0') { // если в настройках указано что просрочку сразу устанавливаем в 0
                    $item->quality = 0; // ставим 0
                } else {
                    $this->add_quality($item->quality, $cur_setting['expired']);
                }
            } else { // соответственно, если продукт не просрочен
                if (isset($cur_setting['days'])) { // проверяем, есть ли у него особый параметр
                    $days        = array_keys($cur_setting['days']); //берём дни
                    $cur_quality = $cur_setting['quality']; // запоминаем текущую настройку без учёта дней как настройку по умолчанию
                    rsort($days); // сортируем, чтобы всегда было правильно
                    foreach ($days as $day) {
                        if ($item->sell_in < $day) {
                            $cur_quality = $cur_setting['days'][$day];// и ищем нужную настройку
                        }                        
                    }
                    $this->add_quality($item->quality, $cur_quality);    
                } else { // если нет особого параметра
                    $this->add_quality($item->quality, $cur_setting['quality']);
                }
            } // готово, мы великолепны
        }
    }
}

class Item
{

    public $name;
    public $sell_in;
    public $quality;

    public function __construct($name, $sell_in, $quality)
    {
        $this->name    = $name;
        $this->sell_in = $sell_in;
        $this->quality = $quality;
    }

    public function __toString()
    {
        return "{$this->name}, {$this->sell_in}, {$this->quality}";
    }

}
