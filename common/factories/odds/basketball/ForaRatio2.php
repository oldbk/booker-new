<?php

namespace common\factories\ratio\basketball;
use common\factories\ratio\_default\_interface\iRatio;
use common\factories\ratio\_default\BaseForaRatio2;

/**
 * Class ForaRatio1
 * @package common\factories\ratio
 *
 * @method \BasketballEvent getEvent()
 */
class ForaRatio2 extends BaseForaRatio2 implements iRatio
{
    protected function getForaVal2()
    {
        return $this->getEvent()->getNewRatio()->getForaVal2();
    }
}