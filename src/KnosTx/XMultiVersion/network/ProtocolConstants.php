<?php

declare(strict_types=1);

namespace KnosTx\XMultiVersion\network;

use pocketmine\network\mcpe\protocol\ProtocolInfo;

class ProtocolConstants{

    public const BEDROCK_1_20_80 = 671;
    public const BEDROCK_1_21_0 = 685;
    public const BEDROCK_1_21_20 = 712;
    public const BEDROCK_1_21_30 = 729;

    public const MINECRAFT_VERSION = [
        self::BEDROCK_1_20_80 => "1.20.80",
        self::BEDROCK_1_21_0 => "1.21.0",
        self::BEDROCK_1_21_20 => "1.21.20",
        self::BEDROCK_1_21_30 => "1.21.30",
        ProtocolInfo::CURRENT_PROTOCOL => ProtocolInfo::MINECRAFT_VERSION_NETWORK
    ];

    public const SUPPORTED_PROTOCOLS = [
        self::BEDROCK_1_20_80,
        self::BEDROCK_1_21_0,
        self::BEDROCK_1_21_20,
        self::BEDROCK_1_21_30,
        ProtocolInfo::CURRENT_PROTOCOL
    ];
}