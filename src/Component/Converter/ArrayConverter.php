<?php

namespace App\Component\Converter;

use function Symfony\Component\String\u;

class ArrayConverter
{
    public function keysToCamel(array $array, bool $force = true): array
    {
        $output = [];

        foreach ($array as $key => $value) {
            if (is_string($key) && !preg_match('/[А-Яа-яЁё]/u', $key)) {
                if ($force) {
                    $key = u($key)->camel()->toString();
                } else {
                    $key = preg_replace_callback('/[_\s](\w)/', function ($mathces) {
                        return strtoupper($mathces[1]);
                    }, $key);
                }
            }

            if (is_array($value)) {
                $value = $this->keysToCamel($value, $force);
            }

            $output[$key] = $value;
        }

        return $output;
    }

    public function keysToSnake(array $array, bool $force = true): array
    {
        $output = [];

        foreach ($array as $key => $value) {
            if (is_string($key) && !preg_match('/[А-Яа-яЁё]/u', $key)) {
                $key = u($key)->snake()->toString();
            }

            if (is_array($value)) {
                $value = $this->keysToSnake($value, $force);
            }

            $output[$key] = $value;
        }

        return $output;
    }

    public function flatten(array $array, $delimiter = '.', $prepend = ''): array
    {
        $flatten = [];

        foreach ($array as $key => $value) {
            if (is_array($value) && !empty($value) && !is_int(array_keys($value)[0])) {
                $flatten = array_merge($flatten, $this->flatten($value, $delimiter, $prepend. $key. $delimiter) );
            } else {
                $flatten[$prepend . $key] = $value;
            }
        }

        return $flatten;
    }
}
