<?php

namespace NemesisCSV\Parser\Processor;

use NemesisCSV\Parser\Exception\StrictViolationException;

class StrictProcessor
{
    protected $nbCells;

    /**
     * Throw an exception if the number of cells in a row doesn't fit
     *
     * @param  array  $row
     * @param  integer $rowNumber
     * @return array
     */
    public function strict(array $row, $rowNumber)
    {
        if (is_null($this->nbCells)) {
            $this->nbCells = count($row);
        } else if ($this->nbCells != count($row)) {
            throw new StrictViolationException(
                sprintf(
                    '%d cells expected on row %d, %d given',
                    $this->nbCells,
                    $rowNumber,
                    count($row)
                )
            );
        }

        return $row;
    }
}
