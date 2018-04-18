# array_search_model
search model by array

Base usege
```php 
$data = ChildArraySearchModel::find()
  ->where(['in', 'array_field', array])
  ->where(['===', 'array_field_or_callback_with_result_can_be_array_or_array_value', equal_value])
  ->where(['regex', callback_with_array_return, equal_value|equal_value|equal_value])
  ->orderBy(callback_with_sort_function_result)
  ->limit(int_value)
  ->offset(int_value)
  ->indexBy('array_field')
  ->all();
  ```
