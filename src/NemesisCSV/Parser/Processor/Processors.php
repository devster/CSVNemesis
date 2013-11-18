<?php

namespace NemesisCSV\Parser\Processor;

class Processors
{
    /**
     * Trim all values of an array
     *
     * @param  array  $row
     * @return array
     */
    public static function trim(array $row)
    {
        return array_map(function($cell) {
            return trim($cell, " \t\n\r\0\x0B");
        }, $row);
    }

    /**
     * Exclude the first row of the CSV
     *
     * @param  array $row
     * @param  integer $rowNumber
     * @return array
     */
    public static function noHeadline(array $row, $rowNumber)
    {
        if (0 === $rowNumber) {
            return null;
        }

        return $row;
    }
}
