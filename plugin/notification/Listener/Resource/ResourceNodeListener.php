<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Icap\NotificationBundle\Listener\Resource;

use Claroline\AppBundle\API\Options;
use Claroline\CoreBundle\Event\Resource\DecorateResourceNodeEvent;
use Icap\NotificationBundle\Manager\NotificationManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ResourceNodeListener
{
    /** @var NotificationManager */
    private $notificationManager;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /**
     * ResourceNodeListener constructor.
     *
     * @param NotificationManager   $notificationManager
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        NotificationManager $notificationManager,
        TokenStorageInterface $tokenStorage
    ) {
        $this->notificationManager = $notificationManager;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Add notifications option to serialized resource node when requested through API.
     *
     * @param DecorateResourceNodeEvent $event
     */
    public function onSerialize(DecorateResourceNodeEvent $event)
    {
        $options = $event->getOptions();

        if (!in_array(Options::SKIP_RESOURCE_NOTIFICATION, $options)) {
            $node = $event->getResourceNode();
            $user = $this->tokenStorage->getToken()->getUser();
            $followResource = 'anon.' !== $user ?
                $this->notificationManager->getFollowerResource(
                    $user->getId(),
                    $node->getId(),
                    $node->getClass()
                ) :
                false;

            $event->add('notifications', ['enabled' => !empty($followResource)]);
        }
    }
}
