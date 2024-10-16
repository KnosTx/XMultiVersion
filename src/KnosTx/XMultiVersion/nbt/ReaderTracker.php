<?php

/*
 *
 * This file comes from https://github.com/pmmp/NBT
 * Even though it is still there, the reason why it is included here is also the reason not too empty.
 *
 */

declare(strict_types=1);

namespace KnosTx\XMultiVersion\nbt;

class ReaderTracker{

	/** @var int */
	private $maxDepth;
	/** @var int */
	private $currentDepth = 0;

	public function __construct(int $maxDepth){
		$this->maxDepth = $maxDepth;
	}

	/**
	 * @throws \UnexpectedValueException if the recursion depth is too deep
	 */
	public function protectDepth(\Closure $execute) : void{
		if($this->maxDepth > 0 and ++$this->currentDepth > $this->maxDepth){
			throw new \UnexpectedValueException("Nesting level too deep: reached max depth of $this->maxDepth tags");
		}
		try{
			$execute();
		}finally{
			--$this->currentDepth;
		}
	}
}
