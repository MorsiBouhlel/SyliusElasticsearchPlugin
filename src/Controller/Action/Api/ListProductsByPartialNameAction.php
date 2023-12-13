<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * another great project.
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\SyliusElasticsearchPlugin\Controller\Action\Api;

use App\Serializer\ProductNormalizer;
use BitBag\SyliusElasticsearchPlugin\Controller\Response\DTO\Item;
use BitBag\SyliusElasticsearchPlugin\Controller\Response\ItemsResponse;
use BitBag\SyliusElasticsearchPlugin\Finder\NamedProductsFinderInterface;
use BitBag\SyliusElasticsearchPlugin\Transformer\Product\TransformerInterface;
use FOS\RestBundle\View\View;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfigurationFactoryInterface;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Bundle\ResourceBundle\Controller\ViewHandlerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Resource\Metadata\MetadataInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;

final class ListProductsByPartialNameAction extends ResourceController
{
    private NamedProductsFinderInterface $namedProductsFinder;

    private TransformerInterface $productSlugTransformer;

    private TransformerInterface $productChannelPriceTransformer;

    private TransformerInterface $productImageTransformer;

    protected RequestConfigurationFactoryInterface $requestConfigurationFactory;

    protected MetadataInterface $metadata;

    protected ?ViewHandlerInterface $viewHandler;
    
    public function __construct(
        NamedProductsFinderInterface $namedProductsFinder,
        TransformerInterface $productSlugResolver,
        TransformerInterface $productChannelPriceResolver,
        TransformerInterface $productImageResolver,
        RequestConfigurationFactoryInterface $requestConfigurationFactory,
        MetadataInterface $metadata,
        ViewHandlerInterface $viewHandler,
        ProductNormalizer $normalizer,
    ) {
        $this->namedProductsFinder = $namedProductsFinder;
        $this->productSlugTransformer = $productSlugResolver;
        $this->productChannelPriceTransformer = $productChannelPriceResolver;
        $this->productImageTransformer = $productImageResolver;
        $this->requestConfigurationFactory = $requestConfigurationFactory;
        $this->metadata = $metadata;
        $this->viewHandler = $viewHandler;
        $this->normalizer = $normalizer;
    }

    public function __invoke(Request $request): Response
    {
        $itemsResponse = ItemsResponse::createEmpty();


        if (null === $request->query->get('query')) {
            return new JsonResponse($itemsResponse->toArray());
        }

        
        $nProducts = [];
        if ($request->query->get('type') && $request->query->get('type') == 'full') {
            
            $products = $this->namedProductsFinder->findAllByNamePart($request->query->get('query'), 2000);
            
            foreach ($products as $product) {
                $context['groups'] = 'shop:product:read';
                $nProducts [] = $this->normalizer->normalize($product, 'json', $context);
            }

            $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);

            $request->setRequestFormat('json');

            return $this->viewHandler->handle($configuration, View::create($nProducts));
        }

        $products = $this->namedProductsFinder->findByNamePart($request->query->get('query'));
        
        /** @var ProductInterface $product */
        foreach ($products as $product) {
            if (null === $productMainTaxon = $product->getMainTaxon()) {
                continue;
            }

            $itemsResponse->addItem(new Item(
                $productMainTaxon->getName(),
                $product->getCode(),
                $product->getName(),
                $product->getShortDescription(),
                $this->productSlugTransformer->transform($product),
                $this->productChannelPriceTransformer->transform($product),
                $this->productImageTransformer->transform($product)
            ));
        }

        return new JsonResponse($itemsResponse->toArray());
    }
}
