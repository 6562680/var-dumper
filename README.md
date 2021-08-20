# var-dumper

Набор функций, заменяющих Дебаггер, когда его подключить не выходит или нет возможности

### Базовые функции

```php
<?php

$arguments = [ 'hello' ];

// вывести на экран
gd(...$arguments);

// вывести и завершить приложение
gdd(...$arguments);

// вывести в переменную
$buffer = gbuff(...$arguments);
```

### Итеративное управление

```php
<?php

$arguments = [ 'hello' ];

gdr(2, ...$arguments); // вывести только вторую итерацию и завершить программу
gdr([2,4], ...$arguments); // вывести 2, 3 и 4 итерацию
````

### Аспектное управление

```php
<?php

$group = '1';

ggd(); // вывести на экран

ggroup($group); // задать группу

ggd($group); // вывести на экран
ggd(''); // ничего не выведено, группы не существует
ggd(); // ничего не выведено, группа не указана

ggroup(); // очистить группы
````

### Настройка кастеров для Symfony\VarDumper

```php
<?php

class BService
{}

class AService
{
    protected $b;
  
    public function __construct() {
        $this->b = new BService();
    }
}


$service = new AService();


// добавить новый кастер
gcast([
    BService::class => 'is_null'
]);

// воспользоваться выводом
gd($service);

// нет аргументов? возврат к предыдущей настройке кастеров
gcast();

// воспользоваться выводом
gd($service);
```
