<?php

use Latte\Engine;
use Nextras\Latte\Macros\RedefineMacro;
use Tester\Assert;
use Tester\Environment;


require_once __DIR__ . '/../vendor/autoload.php';
Environment::setup();


function render($file)
{
	$latte = new Latte\Engine;
	$latte->setTempDirectory(__DIR__ . '/temp');
	$latte->onCompile[] = function (Engine $engine) {
		RedefineMacro::install($engine->getCompiler());
	};
	$result = $latte->renderToString(__DIR__ . '/' . $file);
	return trim($result);
}



Assert::same('import.latte', render('case1/main.latte'));
Assert::same('main.latte', render('case2/main.latte'));
Assert::same('import.latte', render('case3/main.latte'));
Assert::same('import.latte', render('case4/main.latte'));
