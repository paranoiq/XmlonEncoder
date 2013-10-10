<?php

require __DIR__ . '/boot.php';

use Tester\Assert;
use Paranoiq\Xmlon\XmlonEncoder;

$p = new XmlonEncoder;
$p->addXmlHeader = FALSE;
$p->translateKeys = FALSE;

// empty array
Assert::same('<data type="array"></data>', $p->encode(array(), 'data'));

// array
Assert::same('<values type="array"><value>a</value><value>b</value></values>', $p->encode(array('a', 'b'), 'values'));

// empty object
Assert::same('<data></data>', $p->encode((object) array(), 'data'));

// object
Assert::same('<data><a>b</a></data>', $p->encode(array('a' => 'b'), 'data'));

// empty string
Assert::same('<data></data>', $p->encode("", 'data'));

// string
Assert::same('<data>hello</data>', $p->encode("hello", 'data'));

// integer
Assert::same('<data type="integer">123</data>', $p->encode(123, 'data'));

// float
Assert::same('<data type="float">456.789</data>', $p->encode(456.789, 'data'));

// boolean
Assert::same('<data type="boolean">true</data>', $p->encode(TRUE, 'data'));

// date
Assert::same('<data type="date">2013-10-10</data>', $p->encode(new DateTime('2013-10-10'), 'data'));

// time
Assert::same('<data type="datetime">2013-10-10T10:10:10+00:00</data>', $p->encode(new DateTime('2013-10-10T10:10:10Z'), 'data'));

$p->addXmlHeader = TRUE;

$struct = (object) array(
    'intVal' => 123,
    'floatVal' => 456.789,
    'boolVal' => TRUE,
    'dateVal' => new DateTime('2013-10-10'),
    'timeVal' => new DateTime('2013-10-10T10:10:10+01:00'),
    'arrayVals' => ['Hello', 'World']
);
$doc1 = '<?xml version="1.0" encoding="UTF-8"?><data>'
    . '<intVal type="integer">123</intVal>'
    . '<floatVal type="float">456.789</floatVal>'
    . '<boolVal type="boolean">true</boolVal>'
    . '<dateVal type="date">2013-10-10</dateVal>'
    . '<timeVal type="datetime">2013-10-10T10:10:10+01:00</timeVal>'
    . '<arrayVals type="array"><arrayVal>Hello</arrayVal><arrayVal>World</arrayVal></arrayVals>'
    . '</data>';
$doc2 = '<?xml version="1.0" encoding="UTF-8"?><data>'
    . '<int-val type="integer">123</int-val>'
    . '<float-val type="float">456.789</float-val>'
    . '<bool-val type="boolean">true</bool-val>'
    . '<date-val type="date">2013-10-10</date-val>'
    . '<time-val type="datetime">2013-10-10T10:10:10+01:00</time-val>'
    . '<array-vals type="array"><array-val>Hello</array-val><array-val>World</array-val></array-vals>'
    . '</data>';

// all together
Assert::same($doc1, $p->encode($struct));

// decode
Assert::equal($struct, $p->decode($doc1));

$p->translateKeys = TRUE;

// translated keys
Assert::same($doc2, $p->encode($struct));

// decode
Assert::equal($struct, $p->decode($doc2));

