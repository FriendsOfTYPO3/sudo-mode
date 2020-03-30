<?php
declare(strict_types = 1);
namespace FriendsOfTYPO3\SudoMode\Http;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Http\ServerRequestInstructionInterface;

class ServerRequestInstructionResponse extends NullResponse implements \TYPO3\CMS\Core\Http\ServerRequestInstructionAwareInterface
{
    /**
     * @var ServerRequestInstructionInterface
     */
    protected $serverRequestInstruction;

    public function __construct(ServerRequestInstructionInterface $serverRequestInstruction)
    {
        parent::__construct();
        $this->serverRequestInstruction = $serverRequestInstruction;
    }

    public function getServerRequestInstruction(): ServerRequestInstructionInterface
    {
        return $this->serverRequestInstruction;
    }
}
