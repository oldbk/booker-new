<?php

namespace common\factories\ratio\hokkey;
use common\factories\ratio\_default\_interface\iRatio;
use common\factories\ratio\_default\BaseItotalLess2;


/**
 * Class ForaRatio1
 * @package common\factories\ratio
 *
 * @method \HokkeyEvent getEvent()
 */
class ItotalLess2 extends BaseItotalLess2 implements iRatio
{
    protected function getItotalVal2()
    {
        return $this->getEvent()->getNewRatio()->getItotalVal2();
    }
}