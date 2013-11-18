<?php

namespace NemesisCSV\Parser\Tests\Units;

use NemesisCSV\Parser\FileParser as Parser;
use mageekguy\atoum;

class FileParser extends atoum\test
{
    public function beforeTestMethod()
    {
        $this->csvfile = __DIR__.'/../../test.csv';
    }

    public function testFileToParse()
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
            ->exception(function() use ($parser) {
                $parser->dump('file-not-found.csv');
            })
                ->isInstanceOf('\RuntimeException')
        ;
    }

    public function addProcessor()
    {
        $parser = new Parser;

        $parser
            ->addProcessor('NemesisCSV\Parser\Processors::trim')
            ->addProcessor(function(){})
            ->addProcessor('utf8_encode')
            ->addProcessor([$parser, 'addProcessor'])
        ;

        $this
            ->exception(function() use ($parser) {
                $parser->addProcessor('Processors::doesntExist');
            })
                ->isInstanceOf('\InvalidArgumentException')
        ;
    }

    public function testGetOptions()
    {
        $parser = new Parser(['file' => $this->csvfile]);

        $this
            ->array($options = $parser->getOptions())
            ->string($options['file'])
                ->isEqualTo($this->csvfile)
            ->boolean($options['trim'])
                ->isFalse()
        ;
    }

    public function testParse()
    {
        $parser = new Parser(['file' => $this->csvfile]);

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

        // test stop processing
        $parser = new Parser;
        $stack = array();
        $parser
            ->addProcessor(function($row, $rowNumber) {
                if ($rowNumber === 0) {
                    return null;
                }

                return $row;
            })
            ->addProcessor(function($row, $rowNumber) use (&$stack) {
                $stack[] = $row;

                return $row;
            })
        ;
        $parser->parse($this->csvfile);

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
        $parser = new Parser(['file' => $this->csvfile]);

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
        $parser->addProcessor(function(array $row, $rowNumber) {
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

        // test stop processing
        $parser->addProcessor(function($row, $rowNumber) {
            if ($rowNumber === 0) {
                return;
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

    public function testTrimOption()
    {
        $parser = new Parser();

        $this
            ->array($dump = $parser->dump($this->csvfile))
            ->string($dump[1][1])
                ->isEqualTo('   Martin ')
        ;

        $parser = new Parser(['trim' => true]);

        $this
            ->array($dump = $parser->dump($this->csvfile))
            ->string($dump[1][1])
                ->isEqualTo('Martin')
        ;
    }

    public function testNoHeadline()
    {
        $parser = new Parser;

        $this
            ->array($dump = $parser->dump($this->csvfile))
                ->size
                    ->isEqualTo(5)
        ;

        $parser = new Parser(['noHeadline' => true]);
        $this
            ->array($dump = $parser->dump($this->csvfile))
                ->size
                    ->isEqualTo(4)
            ->string($dump[0][0])
                ->isEqualTo('Paul')
        ;
    }

    public function testStrict()
    {
        $parser = new Parser;

        $parser->dump($this->csvfile);

        $parser = new Parser(['strict' => true, 'file' => $this->csvfile]);

        $this
            ->exception(function() use ($parser) {
                $parser->dump();
            })
                ->isInstanceOf('\NemesisCSV\Parser\Exception\StrictViolationException')
                ->message
                    ->contains('3 cells expected on row 3, 1 given')
        ;
    }
}
