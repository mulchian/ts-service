<?php


namespace touchdownstars\contract;


use JsonSerializable;
use Lombok\Getter;
use Lombok\Helper;
use Lombok\Setter;

/**
 * Class Contract
 * @package touchdownstars\contract
 *
 * @method int|null getId()
 * @method void setId(int $id)
 * @method int getSalary()
 * @method void setSalary(int $salary)
 * @method int getSigningBonus()
 * @method void setSigningBonus(int $signingBonus)
 * @method int getEndOfContract()
 * @method void setEndOfContract(int $endOfContract)
 */
#[Setter, Getter]
class Contract extends Helper implements JsonSerializable
{
    private ?int $id = null;
    private int $salary;
    private int $signingBonus;
    private int $endOfContract;

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'salary' => $this->getSalary(),
            'signingBonus' => $this->getSigningBonus(),
            'endOfContract' => $this->getEndOfContract()
        ];
    }
}