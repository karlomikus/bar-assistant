<?php

namespace Kami\Cocktail;

use Exception;

class PD
{
    public const WIRE_VARINT = 0;
    public const WIRE_FIXED64 = 1;
    public const WIRE_LENGTH_DELIMITED = 2;
    public const WIRE_FIXED32 = 5;

    public function decode($data)
    {
        $length = strlen($data);
        $result = [];
        $pos = 0;

        while ($pos < $length) {
            $key = $this->decodeVarint($data, $pos);
            $fieldNumber = $key >> 3;
            $wireType = $key & 3;

            switch ($wireType) {
                case self::WIRE_VARINT:
                    $result[$fieldNumber][] = $this->decodeVarint($data, $pos);
                    break;
                case self::WIRE_FIXED64:
                    $result[$fieldNumber][] = $this->decodeFixed64($data, $pos);
                    break;
                case self::WIRE_LENGTH_DELIMITED:
                    $value = $this->decodeLengthDelimited($data, $pos);
                    if ($this->isNestedMessage($fieldNumber)) {
                        $result[$fieldNumber][] = $this->decode($value);
                    } else {
                        $result[$fieldNumber][] = $value;
                    }
                    break;
                case self::WIRE_FIXED32:
                    $result[$fieldNumber][] = $this->decodeFixed32($data, $pos);
                    break;
                    // default:
                    //     throw new Exception("Unknown wire type: $wireType");
            }
        }

        return $result;
    }

    private function decodeVarint($data, &$pos)
    {
        $result = 0;
        $shift = 0;

        while (true) {
            $byte = ord($data[$pos]);
            $pos++;
            $result |= (($byte & 0x7F) << $shift);
            if (($byte & 0x80) == 0) {
                break;
            }
            $shift += 7;
        }

        return $result;
    }

    private function decodeFixed64($data, &$pos)
    {
        $result = unpack('P', substr($data, $pos, 8))[1];
        $pos += 8;
        return $result;
    }

    private function decodeLengthDelimited($data, &$pos)
    {
        $length = $this->decodeVarint($data, $pos);
        $result = substr($data, $pos, $length);
        $pos += $length;
        return $result;
    }

    private function decodeFixed32($data, &$pos)
    {
        $result = unpack('L', substr($data, $pos, 4))[1];
        $pos += 4;
        return $result;
    }

    private function isNestedMessage($fieldNumber)
    {
        // Define which fields are nested messages based on Protobuf schema
        $nestedFields = [
            3 // Assuming field number 3 is a repeated Ingredient message in Recipe
        ];
        return in_array($fieldNumber, $nestedFields);
    }
}
