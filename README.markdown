# Kohana Presentation Layer

Данный модуль реализует основные идеи Presentation Model Мартина Фаулера (http://martinfowler.com/eaaDev/PresentationModel.html) для Kohana Framework с учетом особенностей PHP и стиля кодирования Kohana.

## Требования

Модуль работает под Kohana 3.3.x и требует PHP 5.3 и выше. Примеры внизу используют синтаксис PHP 5.4 для наглядности, но сам модуль этого не делает и вы можете использовать синтаксис PHP 5.3 в своих проектах. Классы модуля используют собственное просмотранство имен (Yup) и не конфликтуют с другими модулями.

## Существущая проблема 

Типичная задача -- отображение данных модели из БД в HTML-представлении.
Имеется таблица следующей структуры:

##### *users*
~~~
int id
varchar first_name
varchar last_name
enum ('m','f') gender
timestamp last_login
~~~

Модель:

##### *classes/Model/User.php*
~~~
class Model_User extends \Yup\ORM {

    // Some data access and domain logic
}
~~~

Когда нужно вывести данные о сущности на страницу, обычно используется такой подход:

##### *classes/Controller/User.php*
~~~
$this->template->user = new Model_User(1);
$this->template->gender_captions = ['m' => __('Male'), 'f' => __('Female')];
~~~

##### *views/user_display.php*
~~~
<h3>User <?= HTML::chars($user->first_name) > <?= HTML::chars($user->last_name) ?></h3>
<table>
<tr>
<td>Gender: <?= $gender_captions[$user->gender] ?></td>
<td>Last login: <?= $user->last_login === '0000-00-00 00:00:00' ? 'Never' : Date::formatted_time($user->last_login) ?></td>
</tr>
</table>
~~~

Недостатками такого подхода является сильное связывание HTML-разметки и данных предоставляемых моделью. В шаблон разметки встроена также логика преобразования самих данных из формата, хранящегося в БД, что ухудшает читаемость и усложняет поддержку и изменение самой разметки. Ситуация усугубляется необходимостью использования одних и тех же данных в разных шаблонах. При этом логика отображения на них накладывается та же самая, что приводится к дублированию и копипасту кода. Например, список пользователей:

##### *classes/Controller/Users.php*
~~~
$this->template->users = (new Model_User())->find_all();
$this->template->gender_captions = ['m' => __('Male'), 'f' => __('Female')];
~~~

##### *views/user_list.php*
~~~
<table>
<tr>
<th>Name</th>
<th>Gender</th>
<th>Last login</th>
</tr>
<? foreach ($this->users as $user): ?>
<tr>
<td><?= HTML::chars($user->first_name) > <?= HTML::chars($user->last_name) ?></td>
<td>Last login: <?= $user->last_login === '0000-00-00 00:00:00' ? 'Never' : Date::formatted_time($user->last_login) ?></td>
<td>Gender: <?= $gender_captions[$user->gender] ></td>
</tr>
<? endforeach; >
</table>
~~~

Также возможны и другие контексты использования. Повторяющиеся методы, нужные лишь представлению, переносятся в саму модель, либо слабоструктурированные хелперы, при этом повышая связанность кода и не давая достаточной гибкости. К примеру, если нужно вывод между именем и фамилией отчества пользователя в готовой системе, что не было предусмотрено изначально, приходить делать Find/Replace в куче шаблонов. Также растет вероятность человеческой ошибки: при большом количестве одинаковых вызовов легко забыть поставить htmlspecialchars, например.

## Предлагаемое решение 

Данный модуль предлагает следующее решение: введение дополнительного класса-декоратора (Presentation Model), привязанного к ORM-модели (или просто согласованному набору произвольных данных), который и содержит все презентационную логику данных.
Класс позволяет:
* Накладывать фильтры на значения полей модели перед выводом;
* Создавать виртуальные поля, нужные исключительно для целей отображения и отсутствующие в модели;
* Производить данную фильтрацию максимально прозрачно как для программиста модели, так и для верстальщика шаблона, применяя инкапсуляцию.

### Применение

Предлагаемая архитектура будет особенно полезна в следуюшщих случаях:
* Получение данных и разметку шаблонов выполняют разные люди;
* В проекте используется сложная или часто модифицируемая разметка;
* Одни и те же структуры данных используются для вывода в множестве шаблонов;
* В системе производятся регулярные изменения.

### Пример

В примере с пользователем добавляется класс, Presentation_Model_User:
##### *classes/Presentation/Model/User.php*
~~~
class Presentation_Model_User extends \Yup\Presentation_Model {
	
	public function rules()
    {
        return [
            'first_name' => 'HTML::chars',
            'last_name'  => 'HTML::chars',
            'last_login' => function($value){
                return ($value === '0000-00-00 00:00:00') ? 'Never' : Date::formatted_time($value);
            },
            'gender' => $this->replace([
                'm' => __('Male'),
                'f' => __('Female'),
            ]),
        ];
    }
    
    protected function field_full_name()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
~~~

Код модели не меняется, кроме наследования от \Yup\ORM:

##### *classes/Model/User.php*
~~~
class Model_User extends \Yup\ORM {

	// Some data access and domain logic
}
~~~

В контроллере добавляется создание класса-обертки:

##### *classes/Controller/User.php*
~~~
$user = new Model_User(1);
$this->template->user = Present::model($user);
~~~

Шаблон при этом лишается логики представление данных и содержит лишь разметку:

##### *views/user_display.php*
~~~
<h3>User <?= $user->full_name ?></h3>
<table>
<tr>
<td>Gender: <?= $user->gender ?></td>
<td>Last login: <?= $user->last_login ?></td>
</tr>
</table>
~~~

Использование в контексте вывода списка:

##### *classes/Controller/Users.php*
~~~
$users = (new Model_User())->find_all();
$this->template->users = Present::db_result($users);
~~~

##### *views/user_list.php*
~~~
<table>
<tr>
<th>Name</th>
<th>Gender</th>
<th>Last login</th>
</tr>
<? foreach ($this->users as $user): ?>
<tr>
<td><?= $user->full_name ?></td>
<td>Last login: <?= $user->last_login ?></td>
<td>Gender: <?= $user->gender ></td>
</tr>
<? endforeach; >
</table>
~~~

Получаемые значения кешируются, и не будут высчитываться при повторных вызовах.

## Классы и методы

### \Yup\Presentation

Базовый абстрактный класс слоя представления.

#### rules
-----

**Description**_: Метод предназначен для перекрытия в потомках. Содержит массив правил, применяемых к полям исходной сущности. Ключи массива -- названия полей, которым будут применять правила преобразования. Это могут быть как оригинальные поля модели, так и вычисилимые поля, добавленые классом презентации через методы field_*. Сами преобразования будут вызваны в момент запроса значения (см. [get](#get))
Преобразования могут быть следующших типов:
* Анонимная функция с одним входным аргументом, например `function($v){ return $v*2; }`;
* Строковое имя внешней функции либо статического метода любого класса (можно использовать алиас self для вызова текущего класса, например, `self::format_time`). Преобразуемое значение будет всегда передано первым аргументом. Можно использовать массив, где первым аргументом передать имя функции, а последующими -- список всех аргументов, кроме первого;
* Массив значений из предыдущих двух пунктов. В этом случае они будут применены последовательно. Например, если объявлено правило для `'note'    => ['HTML::chars', 'nl2br']`, то при запросе поля note к нему будет применен сначала метод `HTML::chars`, а потом к результату функция `nb2br`, значение которой и будет возвращено.

##### *Parameters*
*no parameters*

##### *Return value*
*array*

##### *Example*
~~~
class Presentation_Model_Order extends \Yup\Presentation_Model {
    
    public function rules()
    {
        return [
            'created'      => 'Date::formatted_time',
			'total_amount' => [['number_format', 2, '.', '']],
            'state'   => $this->replace([
                'new'        => __('New'),
                'processing' => __('In process'),
                'completed'  => __('Done'),
                'cancelled'  => __('Cancelled'),
            ]),
            'note'    => ['HTML::chars', 'nl2br'],            
        ];
    }
}
~~~

#### get
-----
**Description**_: Возвращает преобразованное и пригодное к отображению значение поля. После первого вызова значение кэшируется. Вызывается магическим методом __get(). Можно добавлять вычисляемые поля, создавая в классе методы с названием field_{имя поля}. Если поле к которому происходит обращение, является ORM-объектом (например, при использовании belongs to), то на него при вызове также будет наложена обертка Presentation Model.

##### *Parameters*
*string*: field Имя поля.

##### *Return value*
*mixed*

##### *Example*
~~~
echo $user->name; // same as $user->get('name');
~~~

#### raw
-----
**Description**_: Возвращает оригинальное значение поля, без преобразований.

##### *Parameters*
*string*: field Имя поля.

##### *Return value*
*mixed*

##### *Example*
~~~
echo $user->raw('name');
~~~

#### replace
-----
**Description**_: Вспомогательный метод для использования в правилах преобразования в потомках. Возвращает функцию, заменяющую исходное значение на значение элемента массива replacements при вызове.

##### *Parameters*
*array*: replacements Ассоциативный массив для заменяемых значений.

##### *Return value*
*string*: Результат замены, или оригинальная строка, если соотвествия не найдено.

#### as_array
-----
**Description**_: Возвращает обработанные данные в виде массива.

#### clear_cache
-----
**Description**_: очищает внутренний кэш для преобразованных и рассчитанных полей. 

#### $_context
-----
**Description**_: Статичное свойство, позволяет менять префикс для создаваемых классов, в случаа нескольких различных контекстов вывода (например, для XML или JSON). 

##### *Example*
~~~
\Yup\Presentation::$_context = 'XML';
~~~

### \Yup\Presentation_Model

Реализация Presentation для обработки ORM-моделей.

#### factory
-----
**Description**_: Статичный метод, создающий экземпляр конкретного класса.

##### *Parameters*
*mixed*: model
Не обязательный параметр. Если задан, класс определяется на основе класса модели. Можно передавать как имя класса, так и экземпляр модели. Если не задан, создается экземпляр класса, метод которого вызван.

##### *Return value*
*\Yup\Presentation_Model*

##### *Example*
~~~
$user_model = new Model_User(1);

$user = Presentation_Model_User::factory()->set_model($user_model);
равнозначно
$user = \Yup\Presentation_Model::factory($user_model);
~~~

#### set_model
-----
**Description**_: Сеттер, задающий значение модели.

##### *Parameters*
*\Yup\ORM*: model

##### *Return value*
*\Yup\Presentation_Model*: $this

#### make
-----
**Description**_: Создает экземпляр модели из набора данных.

##### *Parameters*
*array*: $fields

##### *Return value*
*\Yup\Presentation_Model*: $this

##### *Example*
~~~
$product = Presentation_Model_Product::factory()->make([
    'id'  =>  $some_id,            
    'name'=> $some_name,    
]);
~~~

### \Yup\Presentation_Database_Result

Явялется оберткой для класса Database_Result, использующегося в Kohana для обработки результатов списковых запросов и иметт одинаковый с ним интерфейс. При итерировании создает и возвращает Presentation Model для результатов запроса. 

### \Yup\Presentation_Data

Позволяет создавать Presentation Model для данных, не имеющих конкретной модели (например, составленных из выборок нескольких таблиц) и работать с ними как с объектами из шаблонов.

*описание предвидится*

### \Yup\Presentation_List

Реализует итератор, интерфейсно аналогичный Database_Result, для большей консистентности. Применяется при листинге наборов произвольных данных, оборачивая их в декораторы.

*описание предвидится*

### \Yup\NS

Хелпер, предоставляющий функции обработки имен классов с учетом пространств имен.

### \Yup\ORM

Наследует ORM, добавляя методы, нужные для презентации, а также упрощает работу с листингом значений enum-полей.
Например, если в таблице orders содержится поле state типа enum со значениями ('new','processing','completed'), в модели Model_Order после загрузки станет доступно дополнительное поле states возвращающие массив:
~~~
[
'new' => 'new',
'processing' => 'processing',
'ordering' => 'ordering',
]
~~~

Если в Presentation Model задано преобразование подписей для этого поля, то список возможных значений также будет обработан. Пример:

~~~
class Presentation_Model_Order extends \Yup\Presentation_Model {
	
	public function rules()
    {
        return [
            'state' => $this->replace([
                'new'        => __('New'),
                'processing' => __('In process'),
                'completed'  => __('Done'),
            ]),
        ];
    }
}
~~~

В шаблоне теперь можно удобно формировать, например, выпадающие списки в формах редактирования:
~~~
<?= Form::select('state', $order->states, $order->raw('state')) ?>
~~~

Выведет код (если значение order->state равно processing):

~~~
<select name="state">
<option name="new">New</option>
<option name="processing" selected="selected">In process</option>
<option name="completed">Done</option>
</select>
~~~

### \Present

Набор статичных методов, предоставляющих упрощенные вызовы для создания презентационных классов.

#### model
-----
**Description**_: Метод для создания Presentation Model на осове ORM-модели.

##### *Parameters*
*\Yup\ORM*: model

##### *Return value*
*\Yup\Presentation_Model*

##### *Example*
~~~
$user = new Model_User(1);
$this->template->user = Present::model($user);
~~~

#### data
-----
**Description**_: Метод для создания Presentation Model на осове произвольных именованных данных.

##### *Parameters*
*string*: name
*array*: data

##### *Return value*
*\Yup\Presentation_Data*

##### *Example*
~~~
$data = ['currency_id' => 1, 'amount' => 5.5];
$this->template->currency = Present::data('currency', $data);
~~~

#### db_result
-----
**Description**_: Метод для создания обертки для списковых данных, полученных из БД.

##### *Parameters*
*\Database_Result*: db_result

##### *Return value*
*\Yup\Presentation_Database_Result*

##### *Example*
~~~
$users = (new Model_User())->find_all();
$this->template->users = Present::db_result($users);
~~~

#### data_list
-----
**Description**_: Метод для создания обертки-итератора для произвольных списковых данных.

##### *Parameters*
*array*: list

##### *Return value*
*\Yup\Presentation_List*

##### *Example*
~~~
$products = array(
    Present::data('product', $this->get_product_data(1)),
    Present::data('product', $this->get_product_data(2)),
);
$this->template->products = Present::data_list($products);
~~~
