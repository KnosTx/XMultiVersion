<?php

declare(strict_types=1);

namespace KnosTx\XMultiVersion\task;

use KnosTx\XMultiVersion\Loader;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use function chr;
use function zlib_encode;
use const ZLIB_ENCODING_GZIP;

class CompressTask extends AsyncTask{

    /** @var string */
    private $payload;

    /** @var bool */
    private $fail = false;

    /** @var int */
    private $World;

    public function __construct(BatchPacket $packet, callable $callback) {
        $packet->reset();
        $this->payload = $packet->payload;
        $this->storeLocal([$packet, $callback]);
        $this->world = Server::getInstance()->networkCompressionWorld;
    }

    public function onRun(){
        try{
            $this->setResult(zlib_encode($this->payload, ZLIB_ENCODING_RAW, $this->world));
        } catch(\Exception $e) {
            $this->fail = true;
        }
    }

    public function onCompletion(Server $server){
        if($this->fail) {
            Loader::getInstance()->getLogger()->error("Failed to compress batch packet");
            return;
        }
        [$packet, $callback] = $this->fetchLocal();
        /** @var BatchPacket $packet */
        $packet->isEncoded = true;
        $packet->buffer .= chr($packet->pid());
        $packet->buffer .= $this->getResult();
        $callback($packet);
    }
}