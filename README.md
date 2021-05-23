# var-dumper

Набор функций, заменяющих Дебаггер, когда его подключить не выходит или нет возможности

### Базовые функции

```php
<?php

$arguments = [ 'hello' ];

// вывести на экран
g(...$arguments);

// вывести и завершить приложение
gg(...$arguments);

// вывести в переменную
$buffer = gb(...$arguments);
```

### Ручное управление

```php
<?php

$arguments = [ 'hello' ];

gpause(...$arguments); // вывести в консоль и ожидать нажатия любой клавиши от программиста
gdump(...$arguments); // если консоль - использовать gpause(), иначе - dump()
```

### Итеративное управление

```php
<?php

$arguments = [ 'hello' ];

ggn(2, ...$arguments); // вывести вторую итерацию и завершить программу

ggt(2,...$arguments); // вывести первую и вторую итерацию и завершить программу
ggt([2,2], ...$arguments); // пропустить первую и вторую, вывести третью и четвертую и завершить программу
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

// добавить новый кастер
gcast([
    BService::class => 'is_null'
]);

// воспользоваться выводом
g($a = AService());

// вернуться к предыдущей настройке кастеров
$casters = gpop();

// воспользоваться выводом
g($a);
```
