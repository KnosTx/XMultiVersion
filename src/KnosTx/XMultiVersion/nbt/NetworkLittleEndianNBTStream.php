<?php

declare(strict_types=1);

namespace KnosTx\XMultiVersion\nbt;

use pocketmine\utils\Binary;
use function count;
use function strlen;

class NetworkLittleEndianNBTStream extends LittleEndianNBTStream{

	public function getInt() : int{
		return Binary::readVarInt($this->buffer, $this->offset);
	}

	public function putInt(int $v) : void{
		$this->put(Binary::writeVarInt($v));
	}

	public function getLong() : int{
		return Binary::readVarLong($this->buffer, $this->offset);
	}

	public function putLong(int $v) : void{
		$this->put(Binary::writeVarLong($v));
	}

	public function getString() : string{
		return $this->get(self::checkReadStringLength(Binary::readUnsignedVarInt($this->buffer, $this->offset)));
	}

	public function putString(string $v) : void{
		$this->put(Binary::writeUnsignedVarInt(self::checkWriteStringLength(strlen($v))) . $v);
	}

	public function getIntArray() : array{
		$len = $this->getInt(); //varint
		$ret = [];
		for($i = 0; $i < $len; ++$i){
			$ret[] = $this->getInt(); //varint
		}

		return $ret;
	}

	public function putIntArray(array $array) : void{
		$this->putInt(count($array)); //varint
		foreach($array as $v){
			$this->putInt($v); //varint
		}
	}
}
