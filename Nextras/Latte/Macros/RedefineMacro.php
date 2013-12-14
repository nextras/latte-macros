<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    MIT
 * @link       https://github.com/nextras/latte-macros
 * @author     Jan Skrasek
 */

namespace Nextras\Latte\Macros;

use Nette\Latte\CompileException;
use Nette\Latte\Compiler;
use Nette\Latte\MacroNode;
use Nette\Latte\Macros\MacroSet;
use Nette\Latte\PhpWriter;
use Nette\Utils\Strings;


class RedefineMacro extends MacroSet
{
	/** @var array */
	private $namedBlocks = array();


	public static function install(Compiler $compiler)
	{
		$me = new static($compiler);
		$me->addMacro('redefine', array($me, 'macroRedefine'), array($me, 'macroRedefineEnd'));
		return $me;
	}


	/**
	 * {redefine [#]name}
	 */
	public function macroRedefine(MacroNode $node, PhpWriter $writer)
	{
		$name = $node->tokenizer->fetchWord();
		$node->data->name = $name = ltrim($name, '#');

		if (isset($this->namedBlocks[$name])) {
			throw new CompileException("Cannot redeclare static block '$name'");
		}

		$this->namedBlocks[$name] = TRUE;

		$prolog = $this->namedBlocks ? '' : "if (\$_l->extends) { ob_end_clean(); return Nette\\Latte\\Macros\\CoreMacros::includeTemplate(\$_l->extends, get_defined_vars(), \$template)->render(); }\n";
		return $prolog;
	}


	/**
	 * {/redefine}
	 */
	public function macroRedefineEnd(MacroNode $node, PhpWriter $writer)
	{
		if (empty($node->data->leave)) {
			$this->namedBlocks[$node->data->name] = $tmp = rtrim(ltrim($node->content, "\n"), " \t");
			$node->content = substr_replace($node->content, $node->openingCode . "\n", strspn($node->content, "\n"), strlen($tmp));
			$node->openingCode = "<?php ?>";
		}
	}


	/**
	 * Finishes template parsing.
	 * @return array(prolog, epilog)
	 */
	public function finalize()
	{
		$prolog = array();

		if ($this->namedBlocks) {
			foreach ($this->namedBlocks as $name => $code) {
				$func = '_lb' . substr(md5($this->getCompiler()->getTemplateId() . $name), 0, 10) . '_' . preg_replace('#[^a-z0-9_]#i', '_', $name);
				$prolog[] = "//\n// block $name\n//\n"
					. "\$_block = &\$_l->blocks[" . var_export($name, TRUE) . "];"
					. "\$_block = \$_block === NULL ? array() : \$_block;array_unshift(\$_block, '$func');"
					. "if (!function_exists('$func')) { "
					. "function $func(\$_l, \$_args) { extract(\$_args)"
					. "\n?>$code<?php\n}}";
			}
			$prolog[] = "//\n// end of blocks\n//";
		}

		return array(implode("\n\n", $prolog), "");
	}

}
