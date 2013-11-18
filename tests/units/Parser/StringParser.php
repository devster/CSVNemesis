<?php

namespace NemesisCSV\Parser\Tests\Units;

use NemesisCSV\Parser\StringParser as Parser;
use NemesisCSV\Parser\Exception\StopProcessingException;
use mageekguy\atoum;

class StringParser extends atoum\test
{
    public function beforeTestMethod()
    {
        $this->csv = <<<EOF
"John";"Doe";26
Paul;   Martin ;42
Hello; world ;

Test;Test
EOF;
    }

    public function testStringToParse()
    {
        $parser = new Parser;

        $this
            ->exception(function() use ($parser) {
                $parser->parse();
            })
                ->isInstanceOf('\InvalidArgumentException')
            ->exception(function() use ($parser) {
                $parser->dump();
            })
                ->isInstanceOf('\InvalidArgumentException')
        ;

    }

    public function testParse()
    {
        $parser = new Parser(['string' => $this->csv]);

        $stack = array();

        $parser->addProcessor(function($row) use (&$stack) {
            $stack[] = $row;

            return $row;
        });

        // basic test
        $this

            ->variable($result = $parser->parse())
                ->isNull()
            ->array($line = $stack[0])
                ->size
                    ->isEqualTo(3)
            ->string($line[0])
                ->isEqualTo('John')
            ->string($line[1])
                ->isEqualTo('Doe')
            ->string($line[2])
                ->isEqualTo(26)
        ;

        // test with StopProcessingException
        $parser = new Parser;
        $stack = array();
        $parser
            ->addProcessor(function($row, $rowNumber) {
                if ($rowNumber === 0) {
                    throw new StopProcessingException();
                }

                return $row;
            })
            ->addProcessor(function($row, $rowNumber) use (&$stack) {
                $stack[] = $row;

                return $row;
            })
        ;
        $parser->parse($this->csv);

        $this
            ->array($stack)
                ->size
                    ->isEqualTo(4)

            ->string($stack[0][0])
                ->isEqualTo('Paul')
        ;
    }

    public function testDump()
    {
        $parser = new Parser(['string' => $this->csv]);

        // basic test
        $this

            ->array($dump = $parser->dump())
                ->size
                    ->isEqualTo(5)
            ->array($line = $dump[0])
                ->size
                    ->isEqualTo(3)
            ->string($line[0])
                ->isEqualTo('John')
            ->string($line[1])
                ->isEqualTo('Doe')
            ->string($line[2])
                ->isEqualTo(26)
        ;

        // test with processor
        $parser->addProcessor(function($row, $rowNumber) {
            array_unshift($row, $rowNumber);

            return $row;
        });

        $this
            ->array($dump = $parser->dump())
                ->size
                    ->isEqualTo(5)
            ->array($line = $dump[0])
                ->size
                    ->isEqualTo(4)
            ->integer($line[0])
                ->isEqualTo(0)
        ;

        // test with StopProcessingException
        $parser->addProcessor(function($row, $rowNumber) {
            if ($rowNumber === 0) {
                throw new StopProcessingException();
            }

            return $row;
        });

        $this
            ->array($dump = $parser->dump())
                ->size
                    ->isEqualTo(4)
            ->string($dump[0][1])
                ->isEqualTo('Paul')
        ;
    }
}
