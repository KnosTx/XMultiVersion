<?php

declare(strict_types=1);

namespace KnosTx\XMultiVersion\network\convert;

use KnosTx\XMultiVersion\Loader;
use KnosTx\XMultiVersion\network\ProtocolConstants;
use pocketmine\block\BlockTypeIds;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\R12ToCurrentBlockMapEntry;
use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\utils\AssumptionFailedError;
use function file_get_contents;
use function json_decode;

class XMultiVersionRuntimeBlockMapping{

    /** @var int[][] */
    private static $legacyToRuntimeMap = [];
    /** @var int[][] */
    private static $runtimeToLegacyMap = [];
    /** @var CompoundTag[][]|null */
    private static $bedrockKnownStates = [];

    const PROTOCOL = [
        ProtocolConstants::BEDROCK_1_20_80 => "_1_20_80",
        ProtocolConstants::BEDROCK_1_21_0 => "_1_21_0",
        ProtocolConstants::BEDROCK_1_21_20 => "_1_21_20",
        ProtocolConstants::BEDROCK_1_21_30 => "_1_21_30"
    ];

    private function __construct(){
        //NOOP
    }

    public static function init() : void{
        foreach(self::PROTOCOL as $protocol => $fileName){
            if(Loader::getInstance()->isProtocolDisabled($protocol)) {
                continue;
            }
            $canonicalBlockStatesFile = file_get_contents(Loader::$resourcesPath . "vanilla/canonical_block_states".$fileName.".nbt");
            if($canonicalBlockStatesFile === false){
                throw new AssumptionFailedError("Missing required resource file");
            }
            $stream = new NetworkBinaryStream($canonicalBlockStatesFile);
            $list = [];
            while(!$stream->feof()){
                $list[] = $stream->getNbtCompoundRoot();
            }
            self::$bedrockKnownStates[$protocol] = $list;

            self::setupLegacyMappings($protocol);
        }
    }

    private static function setupLegacyMappings(int $protocol) : void{
        $legacyIdMap = json_decode(file_get_contents(Loader::$resourcesPath . "vanilla/block_id_map.json"), true);

        /** @var R12ToCurrentBlockMapEntry[] $legacyStateMap */
        $legacyStateMap = [];
        switch($protocol) {
            case ProtocolConstants::BEDROCK_1_20_80:
                $suffix = self::PROTOCOL[ProtocolConstants::BEDROCK_1_21_0];
                break;
            case ProtocolConstants::BEDROCK_1_21_20:
            case ProtocolConstants::BEDROCK_1_21_30:
                $suffix = self::PROTOCOL[ProtocolConstants::BEDROCK_1_21_30];
                break;
            default:
                $suffix = self::PROTOCOL[$protocol];
                break;
        }
        $path = Loader::$resourcesPath . "vanilla/r12_to_current_block_map".$suffix.".bin";
        $legacyStateMapReader = new NetworkBinaryStream(file_get_contents($path));
        $nbtReader = new NetworkLittleEndianNBTStream();
        while(!$legacyStateMapReader->feof()){
            $id = $legacyStateMapReader->getString();
            $meta = $legacyStateMapReader->getLShort();

            $offset = $legacyStateMapReader->getOffset();
            $state = $nbtReader->read($legacyStateMapReader->getBuffer(), false, $offset);
            $legacyStateMapReader->setOffset($offset);
            if(!($state instanceof CompoundTag)){
                throw new \RuntimeException("Blockstate should be a TAG_Compound");
            }
            $legacyStateMap[] = new R12ToCurrentBlockMapEntry($id, $meta, $state);
        }

        /**
         * @var int[][] $idToStatesMap string id -> int[] list of candidate state indices
         */
        $idToStatesMap = [];
        foreach(self::$bedrockKnownStates[$protocol] as $k => $state){
            $idToStatesMap[$state->getString("name")][] = $k;
        }
        foreach($legacyStateMap as $pair){
            $id = $legacyIdMap[$pair->getId()] ?? null;
            if($id === null){
                throw new \RuntimeException("No legacy ID matches " . $pair->getId());
            }
            $data = $pair->getMeta();
            if($data > 15){
                //we can't handle metadata with more than 4 bits
                continue;
            }
            $mappedState = $pair->getBlockState();

            //TODO HACK: idiotic NBT compare behaviour on 3.x compares keys which are stored by values
            $mappedState->setName("");
            $mappedName = $mappedState->getString("name");
            if(!isset($idToStatesMap[$mappedName])){
                throw new \RuntimeException("Mapped new state does not appear in network table");
            }
            foreach($idToStatesMap[$mappedName] as $k){
                $networkState = self::$bedrockKnownStates[$protocol][$k];
                if($mappedState->equals($networkState)){
                    self::registerMapping($k, $id, $data, $protocol);
                    continue 2;
                }
            }
            throw new \RuntimeException("Mapped new state does not appear in network table");
        }
    }

    private static function lazyInit() : void{
        if(self::$bedrockKnownStates === null){
            self::init();
        }
    }

    public static function toStaticRuntimeId(int $id, int $meta = 0, int $protocol = ProtocolInfo::CURRENT_PROTOCOL) : int{
        if($protocol === ProtocolConstants::BEDROCK_1_21_40) {
            $protocol = ProtocolConstants::BEDROCK_1_21_30;
        }
        self::lazyInit();
        /*
         * try id+meta first
         * if not found, try id+0 (strip meta)
         * if still not found, return update! block
         */
        return self::$legacyToRuntimeMap[$protocol][($id << 4) | $meta] ?? self::$legacyToRuntimeMap[$protocol][$id << 4] ?? self::$legacyToRuntimeMap[$protocol][BlockTypeIds::INFO_UPDATE << 4];
    }

    /**
     * @return int[] [id, meta]
     */
    public static function fromStaticRuntimeId(int $runtimeId, int $protocol) : array{
        if($protocol === ProtocolConstants::BEDROCK_1_21_40) {
            $protocol = ProtocolConstants::BEDROCK_1_21_30;
        }
        self::lazyInit();
        $v = self::$runtimeToLegacyMap[$protocol][$runtimeId] ?? null;
        if($v === null) {
            return [0, 0];
        }
        return [$v >> 4, $v & 0xf];
    }

    private static function registerMapping(int $staticRuntimeId, int $legacyId, int $legacyMeta, $protocol) : void{
        self::$legacyToRuntimeMap[$protocol][($legacyId << 4) | $legacyMeta] = $staticRuntimeId;
        self::$runtimeToLegacyMap[$protocol][$staticRuntimeId] = ($legacyId << 4) | $legacyMeta;
    }

    /**
     * @return CompoundTag[]
     */
    public static function getBedrockKnownStates() : array{
        self::lazyInit();
        return self::$bedrockKnownStates;
    }
}