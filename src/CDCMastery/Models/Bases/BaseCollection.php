<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/24/2017
 * Time: 4:07 PM
 */

namespace CDCMastery\Models\Bases;


class BaseCollection
{
    /**
     * @var Base[]
     */
    private $bases;

    /**
     * @return Base[]
     */
    public function getBases(): array
    {
        return $this->bases;
    }

    /**
     * @param Base[] $bases
     */
    public function setBases(array $bases)
    {
        $this->bases = $bases;
    }
}