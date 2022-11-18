<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2022 Alexander Bigga <alexander@bigga.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

declare(strict_types=1);

namespace Bobosch\OdsOsm\Domain\Model;

/**
 * Event (Default) for the calendarize function.
 *
 * @DatabaseTable
 */
class Event extends \HDNET\Calendarize\Domain\Model\Event
{
    /**
     * Title.
     *
     * @var float
     */
    protected $txOdsosmLon = '';

    /**
     * Slug.
     *
     * @var float
     */
    protected $txOdsosmLat = '';

    /**
     * @return float
     */
    public function getTxOdsosmLon(): ?float
    {
        return $this->txOdsosmLon;
    }

    /**
     * @param float $slug
     */
    public function SetTxOdsosmLon(string $txOdsosmLon): void
    {
        $this->txOdsosmLon = $txOdsosmLon;
    }

    /**
     * @return float
     */
    public function getTxOdsosmLat(): ?float
    {
        return $this->txOdsosmLat;
    }

    /**
     * @param float $slug
     */
    public function SetTxOdsosmLat(string $txOdsosmLat): void
    {
        $this->txOdsosmLat = $txOdsosmLat;
    }
}
