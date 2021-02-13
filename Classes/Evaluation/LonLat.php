<?php

namespace Bobosch\OdsOsm\Evaluation;

class LonLat
{
    function returnFieldJS()
    {
        return ("return value;");
    }

    function evaluateFieldValue($value, $is_in, &$set)
    {
        return (sprintf('%01.6f', $value));
    }
}
