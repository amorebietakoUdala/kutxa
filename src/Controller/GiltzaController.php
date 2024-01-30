<?php

namespace App\Controller;

use App\Services\GiltzaProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class GiltzaController extends AbstractController
{

    private $options;
    private GiltzaProvider $provider;

    public function __construct(UrlGeneratorInterface $urlGenerator, ParameterBagInterface $paramBag, private HttpClientInterface $client)
    {
        $this->client = $client;
        $this->provider = new GiltzaProvider(        [
            'clientId'                => $paramBag->get('clientId'),    // The client ID assigned to you by the provider
            'clientSecret'            => $paramBag->get('clientSecret'),    // The client password assigned to you by the provider
            'redirectUri'             => $urlGenerator->generate($paramBag->get('redirectUri'),[],UrlGeneratorInterface::ABSOLUTE_URL),
            'urlAuthorize'            => $paramBag->get('urlAuthorize'),
            'urlAccessToken'          => $paramBag->get('urlAccessToken'),
            'urlResourceOwnerDetails' => $paramBag->get('urlResourceOwnerDetails'),
        ]);
    }

    #[Route(path: '/', name: 'app_home')]
    public function home() {
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
                'ui_locales' =>  $_locale,
                'prompt' => 'login'
    //            'acr_values' => 'urn:safelayer:tws:policies:authentication:flow:bakq',
            ];
    
            $authorizationUrl = $this->provider->getAuthorizationUrl($this->options);
            $_SESSION['oauth2state'] = $this->provider->getState();
            header('Location: ' . $authorizationUrl);
            exit;

        } elseif (empty($_GET['state']) || (isset($_SESSION['oauth2state']) && $_GET['state'] !== $_SESSION['oauth2state'])) {
            if (isset($_SESSION['oauth2state'])) {
                unset($_SESSION['oauth2state']);
            }
            exit('Invalid State');
        } else {
            try {
                $accessToken = $this->provider->getAccessToken('authorization_code', [
                    'code' => $_GET['code']
                ]);
                $resourceOwner = $this->provider->getResourceOwner($accessToken);
                $authenticatedEequest = $this->provider->getAuthenticatedRequest(
                    'GET',
                    $this->getParameter('urlResourceOwnerDetails'),
                    $accessToken
                );
                if (!$accessToken->hasExpired()) {
                    $response = $this->provider->getParsedResponse($authenticatedEequest);
                    $request->getSession()->set(
                        "giltzaUser",
                        $response
                    );
                    return $this->redirectToRoute('app_register');
                } else {
                    return $this->redirectToRoute('app_giltza');
                }
            } catch (IdentityProviderException $e) {
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
