<?php

namespace common\factories\ratio\football;
use common\factories\ratio\_default\_interface\iRatio;
use common\factories\ratio\_default\BaseTotalLess;

/**
 * Class TotalLess
 * @package common\factories\ratio
 *
 * @method \FootballEvent getEvent()
 */
class TotalLess extends BaseTotalLess implements iRatio
{
    protected function getTotalVal()
    {
        return $this->total_val ? $this->total_val : $this->getEvent()->getNewRatio()->getTotalVal();
    }
}