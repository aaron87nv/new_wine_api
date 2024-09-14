<?php

namespace App\Controller;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class ApiLoginController extends AbstractController
{
    /**
     * @Route("/api/login", name="api_login", methods={"POST"})
     *
     * @OA\Post(
     *     path="/api/login",
     *     summary="User Login",
     *     description="Authenticates a user and returns a token if credentials are valid.",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             type="object",
     *             required={"username", "password"},
     *
     *             @OA\Property(
     *                 property="username",
     *                 type="string",
     *                 description="The username of the user."
     *             ),
     *             @OA\Property(
     *                 property="password",
     *                 type="string",
     *                 description="The password of the user."
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful authentication.",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="user",
     *                 type="string",
     *                 description="The identifier of the authenticated user."
     *             ),
     *             @OA\Property(
     *                 property="token",
     *                 type="string",
     *                 description="The API token for authenticated requests."
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Missing credentials or invalid authentication.",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="missing credentials"
     *             )
     *         )
     *     ),
     *     tags={"User"}
     * )
     */
    #[Route('/api/login', name: 'api_login', methods: 'POST')]
    public function index(#[CurrentUser] ?User $user, JWTTokenManagerInterface $JWTManager): Response
    {
        if (null === $user) {
            return $this->json([
                'message' => 'missing credentials',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Generate a JWT token for the authenticated user
        $token = $JWTManager->create($user);

        return $this->json([
            'user' => $user->getUserIdentifier(),
            'token' => $token,
        ]);
    }
}
