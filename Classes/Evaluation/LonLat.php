<?php

namespace Bobosch\OdsOsm\Evaluation;

class LonLat
{
    public function returnFieldJS(): string
    {
        return "return value;";
    }

    public function evaluateFieldValue($value, $is_in, &$set): string
    {
        return sprintf('%01.6f', $value);
    }
}
