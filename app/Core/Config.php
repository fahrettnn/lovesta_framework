<?php

namespace App\Core;

class Config
{
    protected array $items = [];

    public function load(string $key, array $data): void
    {
        $this->items[$key] = $data;
    }

    public function get(string $key, $default = null)
    {
        $parts = explode('.', $key);
        $current = $this->items;

        foreach ($parts as $part) {
            if (isset($current[$part])) {
                $current = $current[$part];
            } else {
                return $default;
            }
        }

        return $current;
    }

    public function set(string $key, $value): void
    {
        $parts = explode('.', $key);
        $current = &$this->items;

        foreach ($parts as $part) {
            if (!isset($current[$part]) || !is_array($current[$part])) {
                $current[$part] = [];
            }
            $current = &$current[$part];
        }

        $current = $value;
    }
}