<?php

namespace Phel;

use Exception;
use Phel\Exceptions\AnalyzerException;
use Phel\Lang\Phel;
use Phel\Lang\PhelArray;
use Phel\Lang\Symbol;
use Phel\Lang\Table;
use Phel\Lang\Tuple;

class Destructure {

    public function run(Tuple $x): array {
        $bindings = [];
        
        for ($i = 0; $i < count($x); $i+=2) {
            $this->destructure($bindings, $x[$i], $x[$i+1]);
        }

        return $bindings;
    }

    /**
     * @param array $bindings
     * @param Phel|scalar|null $binding
     * @param mixed $value
     */
    private function destructure(array &$bindings, $binding, $value): void {
        if ($binding instanceof Symbol) {
            $this->processSymbol($bindings, $binding, $value);
        } else if ($binding instanceof Tuple) {
            $this->processTuple($bindings, $binding, $value);
        } else if ($binding instanceof Table) {
            $this->processTable($bindings, $binding, $value);
        } else if ($binding instanceof PhelArray) {
            $this->processArray($bindings, $binding, $value);
        } else {
            if (is_object($binding)) {
                $type = get_class($binding);
            } else {
                $type = gettype($binding);
            }

            if ($binding instanceof Phel) {
                throw new AnalyzerException(
                    "Can not destructure " .  $type,
                    $binding->getStartLocation(),
                    $binding->getEndLocation()
                );
            } else {
                // TODO: How can we get start and end location here?
                throw new Exception("Can not destructure " .  $type);
            }
        }
    }

    /**
     * @param array $bindings
     * @param Table $b
     * @param mixed $value
     */
    private function processTable(array &$bindings, Table $b, $value): void {
        $tableSymbol = Symbol::gen()->copyLocationFrom($b);
        $bindings[] = [$tableSymbol, $value];

        foreach ($b as $key => $bindTo) {
            $accessSym = Symbol::gen()->copyLocationFrom($b);
            $accessValue = Tuple::create(
                (new Symbol('php/aget'))->copyLocationFrom($b),
                $tableSymbol, 
                $key
            )->copyLocationFrom($b);
            $bindings[] = [$accessSym, $accessValue];

            $this->destructure($bindings, $bindTo, $accessSym);
        }
    }

    /**
     * @param array $bindings
     * @param PhelArray $b
     * @param mixed $value
     */
    private function processArray(array &$bindings, PhelArray $b, $value): void {
        $arrSymbol = Symbol::gen()->copyLocationFrom($b);
        $bindings[] = [$arrSymbol, $value];

        for ($i = 0; $i < count($b); $i+=2) {
            $index = $b[$i];
            $bindTo = $b[$i+1];

            $accessSym = Symbol::gen()->copyLocationFrom($b);
            $accessValue = Tuple::create(
                (new Symbol('php/aget'))->copyLocationFrom($b),
                $arrSymbol, 
                $index
            )->copyLocationFrom($b);
            $bindings[] = [$accessSym, $accessValue];

            $this->destructure($bindings, $bindTo, $accessSym);
        }
    }

    /**
     * @param array $bindings
     * @param Symbol $b
     * @param mixed $value
     */
    private function processSymbol(array &$bindings, Symbol $binding, $value): void {
        if ($binding->getName() === "_") {
            $s = Symbol::gen()->copyLocationFrom($binding);
            $bindings[] = [$s, $value];
        } else {
            $bindings[] = [$binding, $value];
        }
    }

    /**
     * @param array $bindings
     * @param Tuple $b
     * @param mixed $value
     */
    private function processTuple(array &$bindings, Tuple $b, $value): void {
        $arrSymbol = Symbol::gen()->copyLocationFrom($b);

        $bindings[] = [$arrSymbol, $value];
        $lastListSym = $arrSymbol;
        $state = 'start';

        for ($i = 0; $i < count($b); $i++) {
            $current = $b[$i];
            switch ($state) {
                case 'start':
                    if ($current instanceof Symbol && $current->getName() == '&') {
                        $state = 'rest';
                    } else {
                        $accessSym = Symbol::gen()->copyLocationFrom($current);
                        $accessValue = Tuple::create(
                            (new Symbol('php/aget'))->copyLocationFrom($current),
                            $lastListSym, 
                            0
                        )->copyLocationFrom($current);
                        $bindings[] = [$accessSym, $accessValue];

                        $nextSym = Symbol::gen()->copyLocationFrom($current);
                        $nextValue = Tuple::create(
                            (new Symbol('next'))->copyLocationFrom($current),
                            $lastListSym
                        )->copyLocationFrom($current);
                        $bindings[] = [$nextSym, $nextValue];
                        $lastListSym = $nextSym;
        
                        $this->destructure($bindings, $current, $accessSym);
                    }
                    break;
                case 'rest':
                    $state = 'done';
                    $accessSym = Symbol::gen()->copyLocationFrom($current);
                    $bindings[] = [$accessSym, $lastListSym];
                    $this->destructure($bindings, $current, $accessSym);
                    break;
                case 'done':
                    throw new AnalyzerException(
                        'Unsupported binding form, only one symbol can follow the & parameter',
                        $b->getStartLocation(),
                        $b->getEndLocation()
                    );
            }
            
        }
    }
}