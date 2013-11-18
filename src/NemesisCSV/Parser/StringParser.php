<?php

namespace NemesisCSV\Parser;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use NemesisCSV\Parser\Exception\StopProcessingException;

/**
 * @author Jeremy Perret <jeremy@devster.org>
 */
class StringParser extends AbstractParser
{
    protected function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(['string']);
    }

    /**
     * Return the string to parse
     *
     * @param  string $resource
     * @return string
     */
    protected function initializeResource($resource)
    {
        if (! $resource && isset($this->options['string'])) {
            $resource = $this->options['string'];
        }

        if (! $resource) {
            throw new \InvalidArgumentException('A string to parse is required');
        }

        return $resource;
    }

    /**
     * Parse a CSV string, No row will be buff, just the processor stack will be executed on each row
     *
     * @param  string $string
     * @return void
     */
    public function parse($string = null)
    {
        $string = $this->initializeResource($string);

        $rows = str_getcsv($string, "\n");

        foreach($rows as $rowNumber => $row) {
            try {
                $row = $this->parseRow($row);
                $this->processRow($row, $rowNumber);
            } catch (StopProcessingException $e) {
                continue;
            }
        }
    }

    /**
     * Parse a CSV string, buff all the parsed rows and return the results, can be memory consuming
     *
     * @param  string $string
     * @return array
     */
    public function dump($string = null)
    {
        $string = $this->initializeResource($string);

        $dump = [];
        $rows = str_getcsv($string, "\n");

        foreach($rows as $rowNumber => $row) {
            try {
                $row = $this->parseRow($row);
                $dump[] = $this->processRow($row, $rowNumber);
            } catch (StopProcessingException $e) {
                continue;
            }
        }

        return $dump;
    }




    /**
     * Parse a CSV string
     *
     * @param  string $string
     * @return array
     */
    protected function parseRow($string)
    {
        return str_getcsv(
            $string,
            $this->options['delimiter'],
            $this->options['enclosure'],
            $this->options['escape']
        );
    }
}
