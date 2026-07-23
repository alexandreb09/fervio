<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\GameProposalRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/users')]
class UserController extends AbstractController
{
    #[Route('', name: 'api_users_list', methods: ['GET'])]
    public function list(Request $request, UserRepository $repo, NormalizerInterface $normalizer): JsonResponse
    {
        $users = $repo->findByFilters(
            $request->query->get('city'),
            $request->query->get('minRanking'),
            $request->query->get('maxRanking'),
            $request->query->get('gender'),
            $request->query->get('department'),
            $request->query->get('sort')
        );

        return $this->json($normalizer->normalize($users, null, ['groups' => ['user:list']]));
    }

    #[Route('/cities', name: 'api_users_cities', methods: ['GET'])]
    public function cities(UserRepository $userRepo, GameProposalRepository $proposalRepo): JsonResponse
    {
        $static = [
            'Aix-en-Provence', 'Ajaccio', 'Amiens', 'Angers', 'Annecy', 'Antibes',
            'Arles', 'Avignon', 'Bayonne', 'Besançon', 'Bordeaux', 'Boulogne-Billancourt',
            'Brest', 'Caen', 'Cannes', 'Clermont-Ferrand', 'Dijon', 'Dunkerque',
            'Grenoble', 'La Rochelle', 'Le Havre', 'Le Mans', 'Lens', 'Lille',
            'Limoges', 'Lyon', 'Marseille', 'Metz', 'Montpellier', 'Mulhouse',
            'Nancy', 'Nantes', 'Nice', 'Nîmes', 'Orléans', 'Paris',
            'Pau', 'Perpignan', 'Reims', 'Rennes', 'Rouen', 'Saint-Étienne',
            'Strasbourg', 'Toulon', 'Toulouse', 'Tours', 'Valenciennes', 'Versailles',
        ];

        $cities = array_values(array_unique(array_filter(array_merge(
            $static,
            $userRepo->findDistinctCities(),
            $proposalRepo->findDistinctCities()
        ))));
        sort($cities);

        return $this->json($cities);
    }

    #[Route('/me', name: 'api_users_me_delete', methods: ['DELETE'])]
    public function deleteMe(EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // Clean join tables without ON DELETE CASCADE
        $conn = $em->getConnection();
        $conn->executeStatement('DELETE FROM user_partners WHERE user_id = :id OR partner_id = :id', ['id' => $user->getId()]);

        // Delete avatar file
        $avatar = $user->getAvatar();
        if ($avatar && str_starts_with($avatar, '/uploads/')) {
            $path = $this->getParameter('kernel.project_dir') . '/public' . $avatar;
            if (file_exists($path)) {
                unlink($path);
            }
        }

        $em->remove($user);
        $em->flush();

        return $this->json(null, 204);
    }

    #[Route('/me', name: 'api_users_me_update', methods: ['PUT', 'PATCH'])]
    public function updateMe(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        NormalizerInterface $normalizer,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        return $this->applyUpdate($user, $request, $em, $validator, $normalizer, $hasher);
    }

    #[Route('/{publicId}', name: 'api_users_show', methods: ['GET'], requirements: ['publicId' => '\d+'])]
    public function show(int $publicId, UserRepository $repo, NormalizerInterface $normalizer): JsonResponse
    {
        $user = $repo->findOneBy(['publicId' => $publicId]);
        if (!$user || $user->isSuspended()) {
            return $this->json(['error' => 'Utilisateur introuvable'], 404);
        }
        return $this->json($normalizer->normalize($user, null, ['groups' => ['user:read']]));
    }

    #[Route('/{id}', name: 'api_users_update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(
        User $user,
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        NormalizerInterface $normalizer,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        if ($currentUser->getId() !== $user->getId()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        return $this->applyUpdate($user, $request, $em, $validator, $normalizer, $hasher);
    }

    #[Route('/{id}/avatar', name: 'api_users_avatar', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function uploadAvatar(
        User $user,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        if ($currentUser->getId() !== $user->getId()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $file = $request->files->get('avatar');
        if (!$file) {
            return $this->json(['error' => 'Aucun fichier envoyé'], 400);
        }

        $allowedMime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($file->getMimeType(), $allowedMime)) {
            return $this->json(['error' => 'Format non supporté'], 400);
        }

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $filename = uniqid('avatar_') . '.' . $file->guessExtension();
        $file->move($uploadDir, $filename);

        $user->setAvatar('/uploads/avatars/' . $filename);
        $em->flush();

        return $this->json(['avatar' => $user->getAvatar()]);
    }

    private function applyUpdate(
        User $user,
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        NormalizerInterface $normalizer,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? [];

        if (isset($data['firstName']))                         $user->setFirstName($data['firstName']);
        if (array_key_exists('lastName', $data))               $user->setLastName(!empty($data['lastName']) ? $data['lastName'] : null);
        if (!empty($data['email']))                            $user->setEmail($data['email']);
        if (array_key_exists('city', $data))                   $user->setCity($data['city']);
        if (array_key_exists('postalCode', $data))             $user->setPostalCode(!empty($data['postalCode']) ? $data['postalCode'] : null);
        if (array_key_exists('fftRanking', $data))             $user->setFftRanking($data['fftRanking'] ?: null);
        if (array_key_exists('gender', $data))                 $user->setGender($data['gender']);
        if (array_key_exists('description', $data))            $user->setDescription($data['description']);
        if (array_key_exists('handedness', $data))             $user->setHandedness($data['handedness']);
        if (array_key_exists('hasCourt', $data))               $user->setHasCourt($data['hasCourt']);
        if (array_key_exists('preferredSurface', $data))       $user->setPreferredSurface($data['preferredSurface']);
        if (array_key_exists('acceptMessages', $data))            $user->setAcceptMessages((bool) $data['acceptMessages']);
        if (array_key_exists('notifyMessages', $data))            $user->setNotifyMessages((bool) $data['notifyMessages']);
        if (array_key_exists('notifyProposalReplies', $data))     $user->setNotifyProposalReplies((bool) $data['notifyProposalReplies']);
        if (array_key_exists('acceptPrivateProposals', $data))    $user->setAcceptPrivateProposals((bool) $data['acceptPrivateProposals']);
        if (array_key_exists('birthYear', $data)) {
            $year = $data['birthYear'] ? (int) $data['birthYear'] : null;
            $user->setBirthdate($year ? new \DateTime("{$year}-07-01") : null);
        }
        if (!empty($data['password']))                         $user->setPassword($hasher->hashPassword($user, $data['password']));

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errMessages = [];
            foreach ($errors as $error) {
                $errMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errMessages], 422);
        }

        $em->flush();

        return $this->json($normalizer->normalize($user, null, ['groups' => ['user:read', 'user:private']]));
    }
}
