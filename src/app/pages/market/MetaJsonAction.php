<?php
namespace app\pages\market;

use Slim\Exception\HttpNotFoundException;
use Slim\Views\Twig;
use app\domain\market\Market;
use Slim\Psr7\Request;

class MetaJsonAction
{
    
    private Twig $view;
    
    public function __construct(Twig $view)
    {
        $this->view = $view;
    }
    
    public function __invoke(Request $request, $response, $args) {
        $key = $args['key'] ?? '';
        $product = Market::getProductByKey($key);
        if ($product == null) {
            throw new HttpNotFoundException($request);
        }
        
        $version = $request->getQueryParams()['version'] ?? 'version-get-param-missing';

        $content = $product->getMetaJson();
        $content = str_replace('${version}', $version, $content);
        
        $response->getBody()->write($content);
        $response = $response->withHeader('Content-Type', 'application/json');
        return $response;
    }
}
