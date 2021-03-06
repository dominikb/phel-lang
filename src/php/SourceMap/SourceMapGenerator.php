<?php

namespace Phel\SourceMap;

class SourceMapGenerator {

    /**
     * @var VLQ
     */
    private $vlq;

    public function __construct()
    {
        $this->vlq = new VLQ();
    }

    public function encode(array $mappings) {
        $previousGeneratedLine = 0;
        $previousGeneratedColumn = 0;
        $previousOriginalLine = 0;
        $previousOriginalColumn = 0;
        $result = '';

        $totalMappings = count($mappings);
        for ($i = 0; $i < $totalMappings; $i++) {
            $mapping = $mappings[$i];
            $next = "";

            if ($mapping['generated']['line'] !== $previousGeneratedLine) {
                $previousGeneratedColumn = 0;
    
                while ($mapping['generated']['line'] !== $previousGeneratedLine) {
                    $next .= ';';
                    $previousGeneratedLine++;
                }
            } else if ($i > 0) {
                if (!$this->compareByGeneratedPositionsInflated($mapping, $mappings[$i - 1])) {
                    continue;
                }
                $next .= ",";
            }

            $next .= $this->vlq->encodeIntegers([
                $mapping['generated']['column'] - $previousGeneratedColumn,
                0,
                $mapping['original']['line'] - $previousOriginalLine,
                $mapping['original']['column'] - $previousOriginalColumn
            ]);

            $previousGeneratedColumn = $mapping['generated']['column'];
            $previousOriginalLine = $mapping['original']['line'];
            $previousOriginalColumn = $mapping['original']['column'];

            $result .= $next;
        }

        return $result;
    }

    private function compareByGeneratedPositionsInflated($mappingA, $mappingB) {
        $cmp = $mappingA['generated']['line'] - $mappingB['generated']['line'];
        if ($cmp !== 0) {
          return $cmp;
        }
      
        $cmp = $mappingA['generated']['column'] - $mappingB['generated']['column'];
        if ($cmp !== 0) {
          return $cmp;
        }
      
        $cmp = $mappingA['original']['line'] - $mappingB['original']['line'];
        if ($cmp !== 0) {
          return $cmp;
        }
      
        return $mappingA['original']['column'] - $mappingB['original']['column'];
    }
}