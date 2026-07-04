<?php

namespace RubikaBot\Models;

class Model
{
    /**
     * @param array|null $data
     * @return static|null
     */
    public static function fromArray($data)
    {
        if (empty($data) || !is_array($data)) {
            return null;
        }

        $instance = new static();
        $reflection = new \ReflectionClass($instance);

        foreach ($data as $key => $value) {
            if (!property_exists($instance, $key) || $value === null) {
                continue;
            }

            try {
                $property = $reflection->getProperty($key);
                $instance->{$key} = self::hydratePropertyValue($property, $value);
            } catch (\ReflectionException $e) {
                $instance->{$key} = $value;
            }
        }

        return $instance;
    }

    /**
     * @param \ReflectionProperty $property
     * @param mixed $value
     * @return mixed
     */
    private static function hydratePropertyValue(\ReflectionProperty $property, $value)
    {
        if (!is_array($value)) {
            return $value;
        }

        $type = self::getPropertyType($property);

        if ($type === 'array') {
            return self::parseArrayProperty($value);
        }

        if ($type && class_exists($type)) {
            return $type::fromArray($value);
        }

        return $value;
    }

    /**
     * @param \ReflectionProperty $property
     * @return string|null
     */
    private static function getPropertyType(\ReflectionProperty $property)
    {
        $doc = $property->getDocComment();

        if ($doc === false) {
            return null;
        }

        if (preg_match('/@var\s+([\\a-zA-Z0-9_]+)/', $doc, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * @param array $value
     * @return array
     */
    private static function parseArrayProperty(array $value)
    {
        $parsed = array();
        foreach ($value as $item) {
            $parsed[] = $item;
        }
        return $parsed;
    }

    /**
     * @param array $data
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected static function value(array $data, $key, $default = null)
    {
        return isset($data[$key]) ? $data[$key] : $default;
    }
}
