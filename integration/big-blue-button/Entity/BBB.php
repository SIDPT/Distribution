<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\BigBlueButtonBundle\Entity;

use Claroline\AppBundle\Entity\Identifier\Uuid;
use Claroline\CoreBundle\Entity\Resource\AbstractResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Claroline\BigBlueButtonBundle\Repository\BBBRepository")
 * @ORM\Table(name="claro_bigbluebuttonbundle_bbb")
 */
class BBB extends AbstractResource
{
    use Uuid;

    /**
     * @ORM\Column(name="welcome_message", nullable=true)
     *
     * @var string
     */
    private $welcomeMessage;

    /**
     * @ORM\Column(name="end_message", type="text", nullable=true)
     *
     * @var string
     */
    private $endMessage;

    /**
     * @ORM\Column(name="new_tab", type="boolean")
     *
     * @var bool
     */
    private $newTab = true;

    /**
     * @ORM\Column(name="moderator_required", type="boolean")
     *
     * @var bool
     */
    private $moderatorRequired = false;

    /**
     * @ORM\Column(name="record", type="boolean")
     *
     * @var bool
     */
    private $record = false;

    /**
     * @ORM\Column(type="float", nullable=true)
     *
     * @var float
     */
    private $ratio = 56.25;

    /**
     * @ORM\Column(name="activated", type="boolean")
     *
     * @var bool
     */
    private $activated = false;

    /**
     * Forces the server on which the room will be running.
     *
     * @ORM\Column(nullable=true)
     *
     * @var string
     */
    private $server = null;

    /**
     * Defines on which server the room is currently running.
     *
     * @ORM\Column(nullable=true)
     *
     * @var string
     */
    private $runningOn = null;

    /**
     * Allows users to change their username before entering the room.
     *
     * @ORM\Column(type="boolean")
     *
     * @var bool
     */
    private $customUsernames = false;

    /**
     * BBB constructor.
     */
    public function __construct()
    {
        $this->refreshUuid();
    }

    public function getWelcomeMessage()
    {
        return $this->welcomeMessage;
    }

    public function setWelcomeMessage($welcomeMessage)
    {
        $this->welcomeMessage = $welcomeMessage;
    }

    public function getEndMessage()
    {
        return $this->endMessage;
    }

    public function setEndMessage($endMessage)
    {
        $this->endMessage = $endMessage;
    }

    public function isNewTab()
    {
        return $this->newTab;
    }

    public function setNewTab($newTab)
    {
        $this->newTab = $newTab;
    }

    public function isModeratorRequired()
    {
        return $this->moderatorRequired;
    }

    public function setModeratorRequired($moderatorRequired)
    {
        $this->moderatorRequired = $moderatorRequired;
    }

    public function isRecord()
    {
        return $this->record;
    }

    public function setRecord($record)
    {
        $this->record = $record;
    }

    public function getRatio()
    {
        return $this->ratio;
    }

    public function setRatio($ratio)
    {
        $this->ratio = $ratio;
    }

    public function isActivated()
    {
        return $this->activated;
    }

    public function setActivated($activated)
    {
        $this->activated = $activated;
    }

    /**
     * @return string|null
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @param string|null $server
     */
    public function setServer(string $server = null)
    {
        $this->server = $server;
    }

    /**
     * @return string|null
     */
    public function getRunningOn()
    {
        return $this->runningOn;
    }

    /**
     * @param string|null $server
     */
    public function setRunningOn(string $server = null)
    {
        $this->runningOn = $server;
    }

    /**
     * @return bool
     */
    public function hasCustomUsernames()
    {
        return $this->customUsernames;
    }

    /**
     * @param bool $customUsernames
     */
    public function setCustomUsernames(bool $customUsernames)
    {
        $this->customUsernames = $customUsernames;
    }
}
