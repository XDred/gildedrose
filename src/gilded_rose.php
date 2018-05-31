<?php

/*  Возможно я неверно понял допустимые пределы изменения кода
*   Возможно можно было сделать лучше    
*   Но я подумал, что нужно сделать так, чтобы даже файл texttest_fixture.php не нужно было менять
*   Получилась полная совместимость со старыми версиями
*   
*   Новые варианты добавляются путём добавления классов
*   Фабрика автоматически находит все нужные классы и применяет их к соответствующим предметам
*/


class GildedRose
{
    private $items;

    public function __construct(&$items) // я не понял как это работало изначально без ссылки
    {
        foreach($items as &$item){              // для каждого предмета
            $item = TypesFactory::build($item); // строим подходящий класс
        } // возможно я не в курсе какой то важной фичи, как унаследовать класси оперировать уже новым в остальном коде
        $this->items = $items;                  // и записываем их всех в массив
    }

    public function update_quality()    // стандартная функция, название не трогал чтоб сохранить совместимость
    {
        foreach ($this->items as $item) {   // тупо для всех предметов 
            $item->updateQuality();         // вызываем метод
        }
    }
}

class Item // стандартный класс предмета, так же не трогал
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

// а вот здесь начинаются главные изменения
class ItemTypeDefault extends Item // дефолтный класс, расширяет функционал стандартного
{
    protected function addQuality($add_quality) // функция добавления качества
    {
        if ($add_quality != 0) { // если изменение качества не равно нулю
            $this->quality = $this->quality + $add_quality; // добавляем качество
            if ($this->quality < 0) {
                $this->quality = 0; // не выходим за пределы
            }
            if ($this->quality > 50) {
                $this->quality = 50;
            }
        }
    }

    public function updateQuality() // функция апдейта качества
    {
        $this->sell_in = $this->sell_in - 1; // уменьшаем количество оставшихся дней

        if ($this->sell_in < 0) { // если просрочка
            $this->addQuality(-2); // уменьшаем качество в 2 раза больше
        } else {
            $this->addQuality(-1); // просто уменьшаем качество
        }

    }
}

class ItemTypeSulfuras extends ItemTypeDefault // класс для сульфураса
{
    public static $type_name = "Sulfuras, Hand of Ragnaros"; // строка, по которой ищется подходящий класс

    public function updateQuality() // переопределяем дефолтный метод
    {
        // nothing to do here ...
    }
}

class ItemTypeAgedBrie extends ItemTypeDefault
{

    public static $type_name = "Aged Brie";

    public function updateQuality() // тут всё уже как обычно
    {
        $this->sell_in = $this->sell_in - 1;

        if ($this->sell_in < 0) {
            $this->addQuality(2); // так как это aged brie, увеличиваем качество
        } else {
            $this->addQuality(1);
        }
    }

}

class ItemTypeConjured extends ItemTypeDefault
{

    public static $type_name = "Conjured";

    public function updateQuality()
    {
        $this->sell_in = $this->sell_in - 1;

        if ($this->sell_in < 0) {
            $this->addQuality(-4); // призванные предметы ломаются в 2 раза быстрее
        } else {
            $this->addQuality(-2);
        }
    }

}

class ItemTypeBackstagePasses extends ItemTypeDefault
{

    public static $type_name = "Backstage passes";

    public function updateQuality()
    {
        $this->sell_in = $this->sell_in - 1;

        if ($this->sell_in < 0) { // а вот здесь не как обычно, потому что у билетов своя логика
            $this->quality = 0;
        } elseif ($this->sell_in < 5) {
            $this->addQuality(3);
        } elseif ($this->sell_in < 10) {
            $this->addQuality(2);
        } else {
            $this->addQuality(1);
        }
    }
}


class TypesFactory{ // ну и собственно фабричный метод
    public static function build(Item $item){
        $classes = get_declared_classes();
        foreach($classes as $class){            // возможно можно было и лучше организовать поиск классов среди всей кучи
            if((strpos($class,"ItemType")!== false) && isset($class::$type_name)){ // но, как есть так есть
                if(strpos($item->name,$class::$type_name) !== false){              // вобщем, тут ищется класс, проверяется
                    return new $class($item->name, $item->sell_in, $item->quality); // и возвращается если всё хорошо
                }
            }
        }
        return new ItemTypeDefault($item->name, $item->sell_in, $item->quality); // если других вариантов нет то возвращается дефолтный
    }
}