<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();

        // Handle JSON API requests
        if ($request->isMethod('POST') && 'json' === $request->getContentTypeFormat()) {
            $data = json_decode($request->getContent(), true);

            if (isset($data['username']) && isset($data['password'])) {
                $user->setUsername($data['username']);
                $user->setPassword($userPasswordHasher->hashPassword($user, $data['password']));

                $entityManager->persist($user);
                $entityManager->flush();

                return $this->json([
                    'message' => 'User registered successfully',
                    'userId' => $user->getId(),
                ], Response::HTTP_CREATED);
            }

            return $this->json(['error' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_login');
        }

        // Render the form for GET requests or if the form is not valid
        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
