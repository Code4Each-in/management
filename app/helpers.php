<?php

if (!function_exists('test_helper')) {
    function test_helper()
    {
        return 'Helper working';
    }
}



if (!function_exists('getPlaceholders')) {
    function getPlaceholders(string $text): array
    {
        preg_match_all('/\{\{(\w+)\}\}/', $text, $matches);
        return array_unique($matches[0]);
    }
}
