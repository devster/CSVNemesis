<?php

namespace NemesisCSV\Parser;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

abstract class AbstractParser
{
    protected $options;

    protected $processors = array();

    public function __construct(array $options = array())
    {
        $resolver = new OptionsResolver;
        $resolver->setOptional([
            'delimiter',
            'enclosure',
            'escape',
            'noHeadline',
            'trim',
            'strict',
        ]);
        $resolver->setDefaults([
            'delimiter' => ';',
            'enclosure' => '"',
            'escape' => '\\',
            'noHeadline' => false, // Remove the first row of the CSV (headers)
            'trim' => false, // Trim all blank chars of each cell of each row
            'strict' => false, // Throw a StrictViolationException if a row doesn't provide the number of cells excpected
        ]);
        $this->setDefaultOptions($resolver);
        $this->options = $resolver->resolve($options);

        // Set helper processors
        if ($this->options['noHeadline']) {
            $this->addProcessor('NemesisCSV\Parser\Processor\Processors::noHeadline');
        }

        if ($this->options['strict']) {
            $processor = new Processor\StrictProcessor;
            $this->addProcessor([$processor, 'strict']);
        }

        if ($this->options['trim']) {
            $this->addProcessor('NemesisCSV\Parser\Processor\Processors::trim');
        }
    }

    protected function setDefaultOptions(OptionsResolverInterface $resolver)
    {
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function addProcessor($processor)
    {
        if ((is_string($processor) && ! is_callable($processor, true)) || ! is_callable($processor)) {
            throw new \InvalidArgumentException(sprintf('Processor must be a callable, %s given', gettype($processor)));
        }

        $this->processors[] = $processor;

        return $this;
    }

    protected function processRow(array $row, $rowNumber)
    {
        foreach($this->processors as $processor)
        {
            $row = call_user_func_array($processor, [$row, $rowNumber]);

            if (! is_array($row)) {
                return null;
            }
        }

        return $row;
    }

    abstract public function parse($resource = null);

    abstract public function dump($resource = null);
}
