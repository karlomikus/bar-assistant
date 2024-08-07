<?php

namespace Kami\Cocktail;

use Exception;

class ProtobufDecoder
{
    private string $data;
    private int $idx;

    const WIRE_VARINT = 0;
    const WIRE_FIXED64 = 1;
    const WIRE_LENGTH_DELIMITED = 2;
    const WIRE_FIXED32 = 5;

    public function __construct(string $data)
    {
        $this->data = $data;
        $this->idx = 0;
    }

    private function hasMoreContent(): bool
    {
        return $this->idx < strlen($this->data);
    }

    private function unpack(string $format, int $increment = 0): int
    {
        $ret = unpack($format, substr($this->data, $this->idx));
        $this->idx += $increment;

        return $ret[1];
    }

    private function readByte(): int
    {
        $b = $this->unpack("C", 1);

        return $b;
    }

    /**
     * https://developers.google.com/protocol-buffers/docs/encoding
     */
    private function readVarint(): int
    {
        $value = 0;
        $shift = 0;
        do {
            $b = $this->readByte();

            // strip the msb
            $s = $b & (0xFF >> 1);
            $shifted = bcmul((string) $s, bcpow('2', (string) $shift));
            $value = bcadd($value, $shifted);
            $shift += 7;
        } while ($b >> 7 & 1);

        return intval($value);
    }

    private function read64bit(): string
    {
        $length = 4;
        $value = substr($this->data, $this->idx, $length);
        $this->idx += $length;

        return bin2hex($value);
    }

    private function readStringOrObject(): array|string
    {
        // intval for sane lengths
        $length = intval($this->readVarint());

        if ($length <= 0) {
            return "";
        }

        $value = substr($this->data, $this->idx, $length);
        $this->idx += $length;

        if (!ctype_print($value)) {
            try {
                $d = new ProtobufDecoder($value);

                return $d->decode();
            } catch (\Exception $e) {

            }
        }

        return $value;
    }

    private function getFieldNumber(string $field): string
    {
        return bcdiv($field, bcpow('2', '3'));
    }

    private function getWireType(string $field): int
    {
        return intval($field) & 3;
    }

    public function decodeObjects(): array
    {
        $objects = [];
        while ($this->hasMoreContent()) {
            $objects[] = $this->readStringOrObject();
        }

        return $objects;
    }

    public function decode(): array
    {
        $fields = [];

        while ($this->hasMoreContent()) {
            $enc = $this->readVarint();
            $field = $this->getFieldNumber((string) $enc);
            $type = $this->getWireType((string) $enc);

            $value = null;

            switch ($type) {
                case self::WIRE_VARINT:
                    $value = $this->readVarint();
                    break;
                case self::WIRE_FIXED64:
                    $value = $this->read64bit();
                    break;
                case self::WIRE_LENGTH_DELIMITED:
                    $value = $this->readStringOrObject();
                    break;
                case self::WIRE_FIXED32:
                    throw new Exception("Unsupported wire type: $type");
            }

            $fields[] = $value;
        }

        return $fields;
    }
}
