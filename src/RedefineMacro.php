<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    MIT
 * @link       https://github.com/nextras/latte-macros
 */

namespace Nextras\Latte\Macros;

use Latte\Compiler;
use Latte\MacroNode;
use Latte\Macros\BlockMacros;
use Latte\PhpWriter;


class RedefineMacro extends BlockMacros
{
	/** @var array */
	private $redefinedBlocks = [];


	public static function install(Compiler $compiler)
	{
		$me = new static($compiler);
		$me->addMacro('redefine', [$me, 'macroRedefine'], [$me, 'macroRedefineEnd']);
	}


	public function initialize()
	{
		parent::initialize();
		$this->redefinedBlocks = [];
	}


	public function finalize()
	{
		$compiler = $this->getCompiler();
		$properties = $compiler->getProperties();
		$blocks = isset($properties['blocks']) ? $properties['blocks'] : [];
		$blockTypes = isset($properties['blockTypes']) ? $properties['blockTypes'] : [];

		list($prolog) = parent::finalize();

		foreach ($compiler->getProperties()['blocks'] as $key => $val) { $blocks[$key] = $val; }
		foreach ($compiler->getProperties()['blockTypes'] as $key => $val) { $blockTypes[$key] = $val; }
		$compiler->addProperty('blocks', $blocks);
		$compiler->addProperty('blockTypes', $blockTypes);

		if (!$this->redefinedBlocks) {
			return [$prolog];
		}

		$compiler = $this->getCompiler();
		$compiler->addProperty('redefinedBlocks', $this->redefinedBlocks);
		return [
			$prolog . '
			$blocksToPrepend = [];
			foreach ($this->redefinedBlocks as $redefinedBlock) {
				foreach ($this->blockQueue[$redefinedBlock] as $i => $callback) {
					if ($callback[0] === $this) {
						$blocksToPrepend[] = [$redefinedBlock, $i];
					}
				}
			}
			foreach ($blocksToPrepend as $blockToPrepend) {
				$cb = $this->blockQueue[$blockToPrepend[0]][$blockToPrepend[1]];
				unset($this->blockQueue[$blockToPrepend[0]][$blockToPrepend[1]]);
				array_unshift($this->blockQueue[$blockToPrepend[0]], $cb);
			}
			'
		];
	}


	public function macroRedefine(MacroNode $node, PhpWriter $writer)
	{
		$node->name = 'define';
		$result = parent::macroBlock($node, $writer);
		$node->name = 'redefine';
		$this->redefinedBlocks[] = $node->data->name;
		return $result;
	}


	public function macroRedefineEnd(MacroNode $node, PhpWriter $writer)
	{
		$node->name = 'define';
		$result = parent::macroBlockEnd($node, $writer);
		$node->name = 'redefine';
		return $result;
	}
}
