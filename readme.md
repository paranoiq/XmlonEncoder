
XMLON encoder
=============

Serialize variables to simple XML format used by old 37signals APIs

 - element names are translated from camelCase to dash-case (optional)
 - objects and strings have no type attribute
 - datetime with zero time is exported as 'date' type
 - if no name of root element is given "data" is used
 - supports only UTF-8 encoding
 - does not support namespaces or any other fancy XML features

PHP:
```php
(object) [
  'intVal' => 123,
  'floatVal' => 456.789,
  'boolVal' => TRUE,
  'dateVal' => new DateTime('2013-10-10'),
  'timeVal' => new DateTime(),
  'arrayVals' => ['Hello', 'World']
]
```

XML:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<data>
  <int-val type="integer">123</int-val>
  <float-val type="float">456.786</float-val>
  <bool-val type="boolean">true</bool-val>
  <date-val type="date">2013-10-10</date-val>
  <time-val type="datetime">2013-10-10T10:10:10+0100</time-val>
  <array-vals type="array">
    <array-val>Hello</array-val>
    <array-val>World</array-val>
  </array-val>
</data>
```
