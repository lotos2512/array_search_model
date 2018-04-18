# ArraySearchModel

This library by search data from array

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run
```
php composer.phar require lotos2512/array_search_model dev-master

```
## Base usage

```php 
    $data = ChildArraySearchModel::find()
      ->where(['in', 'array_field', array])
      ->where(['===', 'array_field_or_callback_with_result_can_be_array_or_array_value', equal_value])
      ->where(['regex', array_field_or_callback_with_result_can_be_array_or_array_value, equal_value|equal_value|equal_value])
      ->orderBy(callback_with_sort_function_result)
      ->limit(int_value)
      ->offset(int_value)
      ->indexBy('array_field')
      ->all();
  
  ```
