<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * another great project.
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\SyliusElasticsearchPlugin\Transformer\Product;

use Sylius\Component\Core\Model\ProductInterface;
use Symfony\Component\Routing\RouterInterface;

final class SlugTransformer implements TransformerInterface
{
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function transform(ProductInterface $product): ?string
    {
        if (null === $product->getCode()) {
            return null;
        }

        $productTranslation = $product->getTranslation('fr_FR');
        return $productTranslation->getSlug();

        //return $this->router->generate('api_products_shop_get_item', ['code' => $product->getCode()]);
    }
}
