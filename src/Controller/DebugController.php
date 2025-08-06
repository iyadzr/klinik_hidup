<?php

namespace App\Controller;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/debug')]
class DebugController extends AbstractController
{
    #[Route('/jwt-info', name: 'debug_jwt_info', methods: ['GET'])]
    public function jwtInfo(Request $request, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $authHeader = $request->headers->get('Authorization');
        
        $debug = [
            'auth_header_received' => $authHeader ? 'YES' : 'NO',
            'auth_header_format' => $authHeader ? substr($authHeader, 0, 50) . '...' : null,
            'user_authenticated' => $this->getUser() ? 'YES' : 'NO',
            'user_data' => $this->getUser() ? [
                'username' => $this->getUser()->getUsername(),
                'roles' => $this->getUser()->getRoles()
            ] : null,
            'jwt_config' => [
                'token_ttl' => $this->getParameter('lexik_jwt_authentication.token_ttl'),
                'algorithm' => 'RS256'
            ]
        ];
        
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
            try {
                // Try to decode the token manually
                $parts = explode('.', $token);
                if (count($parts) === 3) {
                    $header = json_decode(base64_decode($parts[0]), true);
                    $payload = json_decode(base64_decode($parts[1]), true);
                    
                    $debug['token_parts'] = [
                        'header' => $header,
                        'payload' => $payload,
                        'signature_length' => strlen($parts[2])
                    ];
                    
                    $debug['token_valid'] = 'DECODED_OK';
                    
                    // Check expiration
                    if (isset($payload['exp'])) {
                        $debug['token_expires'] = date('Y-m-d H:i:s', $payload['exp']);
                        $debug['token_expired'] = $payload['exp'] < time() ? 'YES' : 'NO';
                    }
                }
            } catch (\Exception $e) {
                $debug['token_decode_error'] = $e->getMessage();
            }
        }
        
        return $this->json($debug);
    }
}