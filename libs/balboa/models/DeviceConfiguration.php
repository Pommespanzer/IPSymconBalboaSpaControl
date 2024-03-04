<?php

class DeviceConfiguration implements \JsonSerializable
{

    /**
     * @var array
     */
    private $byteData;

    /**
     * @param array $byteData
     */
    public function __construct(array $byteData)
    {
        $this->byteData = $byteData;
    }

    /**
     * @param int|null $index
     *
     * @return array|mixed
     */
    public function getByteData(int $index = null)
    {
        if ($index !== null && $index >= 0) {
            if (!isset($this->byteData[$index])) {
                throw new RuntimeException('Index not found');
            }

            return $this->byteData[$index];
        }

        return $this->byteData;
    }

    /**
     * @return bool
     */
    public function hasPump0(): bool
    {
        return (($this->getByteData(8) & 128) !== 0);
    }

    /**
     * @return bool
     */
    public function hasPump1(): bool
    {
        return (($this->getByteData(5) & 3) !== 0);
    }

    /**
     * @return bool
     */
    public function hasPump2(): bool
    {
        return (($this->getByteData(5) & 12) !== 0);
    }

    /**
     * @return bool
     */
    public function hasPump3(): bool
    {
        return (($this->getByteData(5) & 48) !== 0);
    }

    /**
     * @return bool
     */
    public function hasPump4(): bool
    {
        return (($this->getByteData(5) & 192) !== 0);
    }

    /**
     * @return bool
     */
    public function hasPump5(): bool
    {
        return (($this->getByteData(6) & 3) !== 0);
    }

    /**
     * @return bool
     */
    public function hasPump6(): bool
    {
        return (($this->getByteData(6) & 192) !== 0);
    }

    /**
     * @return bool
     */
    public function hasLight1(): bool
    {
        return (($this->getByteData(7) & 3) !== 0);
    }

    /**
     * @return bool
     */
    public function hasLight2(): bool
    {
        return (($this->getByteData(7) & 192) !== 0);
    }

    /**
     * @return bool
     */
    public function hasAux1(): bool
    {
        return (($this->getByteData(9) & 1) !== 0);
    }

    /**
     * @return bool
     */
    public function hasAux2(): bool
    {
        return (($this->getByteData(9) & 2) !== 0);
    }

    /**
     * @return bool
     */
    public function hasBlower(): bool
    {
        return (($this->getByteData(8) & 15) !== 0);
    }

    /**
     * @return bool
     */
    public function hasMister(): bool
    {
        return (($this->getByteData(9) & 16) !== 0);
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

}