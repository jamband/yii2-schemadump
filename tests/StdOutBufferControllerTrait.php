<?php

/*
 * This file is part of yii2-schemadump.
 *
 * (c) Tomoki Morita <tmsongbooks215@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace jamband\schemadump\tests;

trait StdOutBufferControllerTrait
{
    private $stdOutBuffer = '';

    /**
     * @param string $string
     */
    public function stdout($string)
    {
        $this->stdOutBuffer .= $string;
    }

    /**
     * @return string
     */
    public function flushStdOutBuffer()
    {
        $result = $this->stdOutBuffer;
        $this->stdOutBuffer = '';
        return $result;
    }
}
