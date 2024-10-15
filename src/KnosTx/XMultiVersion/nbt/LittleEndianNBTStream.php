<?php

namespace KnosTx\XMultiVersion\nbt;

use pocketmine\utils\Binary;
use function array_values;
use function assert;
use function count;
use function pack;
use function unpack;

class LittleEndianNBTStream extends NBTStream{

	public function getShort() : int{
		return Binary::readLShort($this->get(2));
	}

	public function getSignedShort() : int{
		return Binary::readSignedLShort($this->get(2));
	}

	public function putShort(int $v) : void{
		$this->put(Binary::writeLShort($v));
	}

	public function getInt() : int{
		return Binary::readLInt($this->get(4));
	}

	public function putInt(int $v) : void{
		$this->put(Binary::writeLInt($v));
	}

	public function getLong() : int{
		return Binary::readLLong($this->get(8));
	}

	public function putLong(int $v) : void{
		$this->put(Binary::writeLLong($v));
	}

	public function getFloat() : float{
		return Binary::readLFloat($this->get(4));
	}

	public function putFloat(float $v) : void{
		$this->put(Binary::writeLFloat($v));
	}

	public function getDouble() : float{
		return Binary::readLDouble($this->get(8));
	}

	public function putDouble(float $v) : void{
		$this->put(Binary::writeLDouble($v));
	}

	public function getIntArray() : array{
		$len = $this->getInt();
		$unpacked = unpack("V*", $this->get($len * 4));
		assert($unpacked !== false, "The formatting string is valid, and we gave a multiple of 4 bytes");
		return array_values($unpacked);
	}

	public function putIntArray(array $array) : void{
		$this->putInt(count($array));
		$this->put(pack("V*", ...$array));
	}
}
