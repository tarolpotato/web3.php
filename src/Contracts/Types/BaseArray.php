<?php

/**
 * This file is part of web3.php package.
 * 
 * (c) Kuan-Cheng,Lai <alk03073135@gmail.com>
 * 
 * @author Peter Lai <alk03073135@gmail.com>
 * @license MIT
 */

namespace Web3\Contracts\Types;

use InvalidArgumentException;
use Web3\Utils;
use Web3\Contracts\SolidityType;
use Web3\Contracts\Types\IType;
use Web3\Formatters\IntegerFormatter;

class BaseArray extends SolidityType implements IType
{
    /**
     * construct
     * 
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * isType
     * 
     * @param string $name
     * @return bool
     */
    public function isType($name)
    {
        return (preg_match('/(\[([0-9]*)\])/', $name) === 1);
    }

    /**
     * isDynamicType
     * 
     * @return bool
     */
    public function isDynamicType()
    {
        return true;
    }

    /**
     * encodeElements
     * 
     * @param mixed $value
     * @param string $name
     * @return array
     */
    public function encodeElements($value, $name)
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException('Encode value must be array');
        }
        $results = [];
        $itemsAreDynamic = false;
        foreach ($value as $key => $val) {
            $results[] = $name['coders']['solidityType']->encode($val, $name['coders']);
            if ($name['coders']['dynamic']) {
                $itemsAreDynamic = true;
            }
        }
        if (!$itemsAreDynamic) {
            return $results;
        }
        $headLength = 32 * count($value);
        $tailOffsets = [0];
        foreach ($results as $key => $val) {
            if ($key === count($results) - 1) {
                break;
            }
            $tailOffsets[] = (int) mb_strlen($val) / 2;
        }
        $headChunks = [];
        $totalOffset = 0;
        foreach ($tailOffsets as $offset) {
            $totalOffset += $offset;
            $headChunks[] = IntegerFormatter::format($headLength + $totalOffset);
        }
        return array_merge($headChunks, $results);
    }

    /**
     * inputFormat
     * 
     * @param mixed $value
     * @param string $name
     * @return string
     */
    public function inputFormat($value, $name)
    {
        return implode('', $this->encodeElements($value, $name));
    }

    /**
     * outputFormat
     * 
     * @param mixed $value
     * @param string $name
     * @return string
     */
    public function outputFormat($value, $name)
    {
        $checkZero = str_replace('0', '', $value);

        if (empty($checkZero)) {
            return '0';
        }
        if (preg_match('/^bytes([0-9]*)/', $name, $match) === 1) {
            $size = intval($match[1]);
            $length = 2 * $size;
            $value = mb_substr($value, 0, $length);
        }
        return '0x' . $value;
    }
}