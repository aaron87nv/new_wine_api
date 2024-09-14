<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;


class SecurityController extends AbstractController
{
    /**
     * @Route(path="/login", name="app_login", methods={"POST"})
     *
     * @SWG\Post(
     *     summary="User Login",
     *     description="Handles user login and returns the login form with errors if authentication fails.",
     *
     *     @SWG\Parameter(
     *         name="username",
     *         in="formData",
     *         type="string",
     *         description="The username of the user.",
     *         required=true
     *     ),
     *     @SWG\Parameter(
     *         name="password",
     *         in="formData",
     *         type="string",
     *         description="The password of the user.",
     *         required=true
     *     ),
     *
     *     @SWG\Response(
     *         response=200,
     *         description="Returns the login page with the last username and any errors.",
     *
     *         @SWG\Schema(
     *             type="object",
     *
     *             @SWG\Property(property="last_username", type="string"),
     *             @SWG\Property(property="error", type="string", nullable=true)
     *         )
     *     ),
     *
     *     @SWG\Response(
     *         response=401,
     *         description="Unauthorized - Invalid username or password."
     *     )
     * )
     */
    #[Route(path: '/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        if ($request->isMethod('POST')) {
            // Here you would typically handle the login form submission
            // The form and authentication process is usually handled automatically
            // by Symfony's security component, so you don't need to manually
            // authenticate the user here.
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    /**
     * @Route(path="/logout", name="app_logout", methods={"POST"})
     *
     * @SWG\Get(
     *     summary="User Logout",
     *     description="Logs the user out. This route should not be accessed directly.",
     *
     *     @SWG\Response(
     *         response=302,
     *         description="Redirects to the login page after logout."
     *     ),
     *     @SWG\Response(
     *         response=500,
     *         description="Error - Logout was not properly configured."
     *     )
     * )
     */
    #[Route(path: '/logout', name: 'app_logout', methods: 'POST')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
