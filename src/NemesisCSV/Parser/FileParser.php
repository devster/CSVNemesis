<?php

namespace NemesisCSV\Parser;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use NemesisCSV\Parser\Exception\StopProcessingException;

/**
 * @author Jeremy Perret <jeremy@devster.org>
 */
class FileParser extends AbstractParser
{
    protected function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(['file']);
    }

    /**
     * Create a SplFileObject ready to go from a mixed resource
     *
     * @param  mixed $resource
     * @return \SplFileObject
     */
    protected function initializeResource($resource)
    {
        if (! $resource && isset($this->options['file'])) {
            $resource = $this->options['file'];
        }

        if (! $resource) {
            throw new \InvalidArgumentException('A file to parse is required');
        }

        $resource = $resource instanceof \SplFileObject ? $resource : new \SplFileObject($resource);
        $resource->setCsvControl($this->options['delimiter'], $this->options['enclosure'], $this->options['escape']);
        $resource->setFlags(\SplFileObject::READ_CSV);

        return $resource;
    }

    /**
     * Parse a CSV file, No row will be buff, just the processor stack will be executed on each row
     *
     * @param  mixed $resource Can be s filename string or a SplFile* Object
     * @return void
     */
    public function parse($resource = null)
    {
        $resource = $this->initializeResource($resource);

        foreach ($resource as $rowNumber => $row) {
            $this->processRow($row, $rowNumber);
        }
    }

    /**
     * Parse a CSV file, buff all the parsed rows and return the results, can be memory consuming
     *
     * @param  mixed $resource Can be s filename string or a SplFile* Object
     * @return array
     */
    public function dump($resource = null)
    {
        $dump = array();
        $resource = $this->initializeResource($resource);

        foreach ($resource as $rowNumber => $row) {
            $row = $this->processRow($row, $rowNumber);

            if (is_array($row)) {
                $dump[] = $row;
            }
        }

        return $dump;
    }
}
