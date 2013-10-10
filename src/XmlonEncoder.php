<?php

namespace Paranoiq\Xmlon;


/**
 * Works just as JSON parser, but creates simple XML with types instead.
 */
class XmlonEncoder
{

    /** @var array */
    private $objects = array();

    /** @var bool translate keys from camelCase to dash-case */
    public $translateKeys = TRUE;

    /** @var bool */
    public $addXmlHeader = TRUE;

    /**
     * Encode PHP structure to XML
     *
     * @param object|array - data to encode
     * @param string
     * @param string (for plain arrays)
     * @return string
     */
    public function encode($value, $rootElement = 'data')
    {
        $this->objects = array();

        $xml = ($this->addXmlHeader ? '<?xml version="1.0" encoding="UTF-8"?>' : '');
        $xml .= $this->encodeValue($value, $rootElement);

        return $xml;
    }

    private function encodeValue($value, $element)
    {
        if ($this->translateKeys)
        {
            $element = $this->xmlizeKey($element);
        }

        if ($value instanceof \DateTime || $value instanceof \DateTimeInterface)
        {
            /** @var \DateTime $value */
            if ($value->format('His') === '000000')
            {
                return "<$element type=\"date\">" . $value->format('Y-m-d') . "</$element>";
            }
            else
            {
                return "<$element type=\"datetime\">" . $value->format('c') . "</$element>";
            }
        }
        elseif (is_object($value))
        {
            // watch for recursion
            $id = spl_object_hash($value);
            if (isset($this->objects[$id]))
            {
                throw new XmlonParserException("Recursion detected on element '$element'. Cannot serialize.");
            }
            $this->objects[$id] = TRUE;

            if ($value instanceof \Traversable)
            {
                $value = iterator_to_array($value, TRUE);
            }
            else
            {
                $value = $this->filterKeys((array) $value);
            }

            return $this->encodeObject($value, $element);
        }
        elseif (is_array($value))
        {
            if ($this->isPlainArray($value))
            {
                $array = "<$element type=\"array\">";
                foreach ($value as $val)
                {
                    $array .= $this->encodeValue($val, substr($element, 0, -1));
                }
                $array .= "</$element>";

                return $array;
            }
            else
            {
                return $this->encodeObject($value, $element);
            }
        }
        elseif (is_bool($value))
        {
            return "<$element type=\"boolean\">" . ($value ? 'true' : 'false') . "</$element>";
        }
        elseif (is_int($value))
        {
            return "<$element type=\"integer\">$value</$element>";
        }
        elseif (is_float($value))
        {
            return "<$element type=\"float\">$value</$element>";
        }
        else
        {
            return "<$element>" . htmlspecialchars($value) . "</$element>";
        }
    }

    private function isPlainArray($value)
    {
        return !count($value) || array_keys($value) === range(0, count($value) - 1);
    }

    private function encodeObject(array $data, $element)
    {
        $object = "<$element>";
        foreach ($data as $key => $val)
        {
            $object .= $this->encodeValue($val, $key);
        }
        $object .= "</$element>";

        return $object;
    }

    private function filterKeys(array $array)
    {
        foreach ($array as $key => $value)
        {
            if ($pos = strrpos("\\x0", $key))
            {
                unset($array[$key]);
                $key = substr($key, $pos);
                $array[$key] = $value;
            }
        }
        return $array;
    }

    private function xmlizeKey($key)
    {
        return trim(preg_replace_callback('/[A-Z]/', function ($k) { return '-' . strtolower($k[0]); }, $key), '-');
    }

    /**
     * Decode XML string to PHP structure
     *
     * @param string
     * @param null
     * @return \StdClass|array
     */
    public function decode($data, $rootElement = NULL)
    {
        /** @var \SimpleXmlElement $element */
        $element = simplexml_load_string($data);
        if (!$element)
        {
            throw new XmlonParserException('Invalid XML input.');
        }
        if ($rootElement && $element->getName() !== $rootElement)
        {
            throw new XmlonParserException('Given root element not found.');
        }

        return $this->decodeElement($element);
    }

    private function decodeElement(\SimpleXMLElement $element)
    {
        $attr = $element->attributes();
        $type = $attr['type'] ?: ($element->children() ? 'object' : 'string');
        switch ($type) {
            case 'object':
                $object = array();
                /** @var \SimpleXmlElement $item */
                foreach ($element->children() as $item)
                {
                    $key = $item->getName();
                    if ($this->translateKeys)
                    {
                        $key = $this->phpizeKey($key);
                    }
                    $object[$key] = $this->decodeElement($item);
                }
                return (object) $object;
                break;
            case 'array':
                $array = array();
                foreach ($element->children() as $item)
                {
                    $array[] = $this->decodeElement($item);
                }
                return $array;
                break;
            case 'integer':
                return (int) (string) $element;
                break;
            case 'float':
                return (float) (string) $element;
                break;
            case 'boolean':
                $v = (string) $element;
                if ($v === 'true') return TRUE;
                if ($v === 'false') return FALSE;
                throw new XmlonParserException('Invalid boolean value found: ' . $v);
                break;
            case 'date':
            case 'datetime':
                $v = (string) $element;
                try
                {
                    $date = new \DateTime($v);
                }
                catch (\Exception $e)
                {
                    throw new XmlonParserException('Invalid DateTime value found: ' . $v);
                }
                return $date;
                break;
            default:
                return (string) $element;
        }
    }

    private function phpizeKey($key)
    {
        return preg_replace_callback('/-[a-z]/', function ($k) { return strtoupper($k[0][1]); }, $key);
    }

}


class XmlonParserException extends \RuntimeException
{
    //
}
