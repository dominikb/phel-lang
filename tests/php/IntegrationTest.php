<?php

namespace Phel;

use Phel\Lang\Symbol;
use PHPUnit\Framework\TestCase;

class IntegrationTest extends TestCase {

    private static $globalEnv;

    public static function setUpBeforeClass(): void
    {
        Symbol::resetGen();
        $globalEnv = new GlobalEnvironment();
        $rt = Runtime::initialize($globalEnv);
        $rt->addPath('phel\\', [__DIR__ . '/../../src/phel/']);
        $rt->loadNs('phel\core');
        self::$globalEnv = $globalEnv;
    }

    /**
     * @dataProvider integrationDataProvider
     */
    public function testIntegration($filename, $phelCode, $generatedCode) {
        $this->doIntegrationTest($filename, $phelCode, $generatedCode);
    }

    protected function doIntegrationTest($filename, $phelCode, $generatedCode) {
        $globalEnv = self::$globalEnv;
        $globalEnv->setNs('user');
        Symbol::resetGen();
        $lexer = new Lexer();
        $reader = new Reader();
        $analzyer = new Analyzer($globalEnv);
        $emitter = new Emitter(false);
        $tokenStream = $lexer->lexString($phelCode);

        $compiledCode = [];
        while (true) {
            $readAst = $reader->readNext($tokenStream);

            if (!$readAst) {
                break;
            }

            $compiledCode[] = $emitter->emitAndEval($analzyer->analyze($readAst->getAst()));
        }
        $compiledCode = trim(implode("", $compiledCode));
        $this->assertEquals($generatedCode, $compiledCode, "in " . $filename);
    }

    public function integrationDataProvider() {
        $fixturesDir = realpath(__DIR__ . '/Fixtures');
        $tests = [];

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($fixturesDir), \RecursiveIteratorIterator::LEAVES_ONLY) as $file) {
            if (!preg_match('/\.test$/', $file)) {
                continue;
            }

            $test = file_get_contents($file->getRealpath());
            
            if (preg_match('/--PHEL--\s*(.*?)\s*--PHP--\s*(.*)/s', $test, $match)) {
                $phelCode = $match[1];
                $phpCode = trim($match[2]);

                $tests[] = [str_replace($fixturesDir.'/', '', $file), $phelCode, $phpCode];
            }
        }

        return $tests;
    }
}