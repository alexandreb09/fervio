<?php

namespace App\EventSubscriber;

use App\Entity\GameProposal;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

#[AsDoctrineListener(event: Events::prePersist)]
class PublicIdSubscriber
{
    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        $em = $args->getObjectManager();

        if ($entity instanceof User) {
            $repo = $em->getRepository(User::class);
            while ($repo->findOneBy(['publicId' => $entity->getPublicId()])) {
                $entity->setPublicId(random_int(10000, 1000000));
            }
            return;
        }

        if ($entity instanceof GameProposal) {
            $repo = $em->getRepository(GameProposal::class);
            while ($repo->findOneBy(['publicId' => $entity->getPublicId()])) {
                $entity->setPublicId(random_int(10000, 1000000));
            }
        }
    }
}
