<?php

namespace App\Controller;

use App\Services\GiltzaProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GiltzaController extends AbstractController
{
    private $options;

    public function __construct(
        UrlGeneratorInterface $urlGenerator, 
        HttpClientInterface $client, 
        private string $clientId, 
        private string $clientSecret,
        private string $redirectUri,
        private string $urlAuthorize,
        private string $urlAccessToken,
        private string $urlResourceOwnerDetails, 
    )
    {
        $this->client = $client;
        $this->provider = new GiltzaProvider([
            'clientId' => $this->clientId,    // The client ID assigned to you by the provider
            'clientSecret' => $this->clientSecret,    // The client password assigned to you by the provider
            'redirectUri' => $urlGenerator->generate($this->redirectUri, [], UrlGeneratorInterface::ABSOLUTE_URL),
            'urlAuthorize' => $this->urlAuthorize,
            'urlAccessToken' => $this->urlAccessToken,
            'urlResourceOwnerDetails' => $this->urlResourceOwnerDetails,
        ]);
    }

    #[Route(path: '/', name: 'app_home')]
    public function home(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->redirectToRoute('app_giltza');
    }

    #[Route(path: '/giltza/{_locale}', name: 'app_giltza', requirements: ['_locale' => 'es|eu|en'])]
    public function giltza(Request $request, string $_locale = 'es'): Response
    {
        // If we don't have an authorization code then get one
        if (!isset($_GET['code'])) {
            $this->options = [
                'response_type' => 'code',
                'scope' => 'urn:izenpe:identity:global',
                'ui_locales' => $_locale,
                'prompt' => 'login',
    //            'acr_values' => 'urn:safelayer:tws:policies:authentication:flow:bakq',
            ];

            $authorizationUrl = $this->provider->getAuthorizationUrl($this->options);
            $_SESSION['oauth2state'] = $this->provider->getState();
            header('Location: '.$authorizationUrl);
            exit;
        } elseif (empty($_GET['state']) || (isset($_SESSION['oauth2state']) && $_GET['state'] !== $_SESSION['oauth2state'])) {
            if (isset($_SESSION['oauth2state'])) {
                unset($_SESSION['oauth2state']);
            }
            exit('Invalid State');
        } else {
            try {
                $accessToken = $this->provider->getAccessToken('authorization_code', [
                    'code' => $_GET['code'],
                ]);
                $resourceOwner = $this->provider->getResourceOwner($accessToken);
                $authenticatedEequest = $this->provider->getAuthenticatedRequest(
                    'GET',
                    $this->urlResourceOwnerDetails,
                    $accessToken
                );
                if (!$accessToken->hasExpired()) {
                    $response = $this->provider->getParsedResponse($authenticatedEequest);
                    $request->getSession()->set(
                        'giltzaUser',
                        $response
                    );

                    return $this->redirectToRoute('app_kutxa');
                } else {
                    return $this->redirectToRoute('app_giltza');
                }
            } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
                exit($e->getMessage());
            }
        }
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(Request $request): Response
    {
        $request->getSession()->invalidate();

        return $this->json('logout');
    }
}
