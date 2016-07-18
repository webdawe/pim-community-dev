<?php

namespace Pim\Component\Connector\ArrayConverter\StandardToFlat;

use Pim\Component\Catalog\Localization\Localizer\LocalizerRegistryInterface;
use Pim\Component\Catalog\Repository\AttributeRepositoryInterface;
use Pim\Component\Connector\ArrayConverter\ArrayConverterInterface;

/**
 * Convert standard format to flat format for product with localized values.
 *
 * @author    Marie Bochu <marie.bochu@akeneo.com>
 * @copyright 2016 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductLocalized implements ArrayConverterInterface
{
    /** @var ArrayConverterInterface */
    protected $converter;

    /** @var AttributeRepositoryInterface */
    protected $attributeRepository;

    /** @var LocalizerRegistryInterface */
    protected $localizerRegistry;

    /**
     * @param ArrayConverterInterface      $converter
     * @param AttributeRepositoryInterface $attributeRepository
     * @param LocalizerRegistryInterface   $localizerRegistry
     */
    public function __construct(
        ArrayConverterInterface $converter,
        AttributeRepositoryInterface $attributeRepository,
        LocalizerRegistryInterface $localizerRegistry
    ) {
        $this->converter = $converter;
        $this->attributeRepository = $attributeRepository;
        $this->localizerRegistry = $localizerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function convert(array $productStandard, array $options = [])
    {
        $attributeTypes = $this->attributeRepository->getAttributeTypeByCodes(array_keys($productStandard['values']));

        foreach ($productStandard['values'] as $code => $item) {
            if (isset($attributeTypes[$code])) {
                $localizer = $this->localizerRegistry->getLocalizer($attributeTypes[$code]);

                if (null !== $localizer) {
                    foreach ($item as $index => $attribute) {
                        $productStandard['values'][$code][$index]['data'] = $localizer->localize($attribute['data'], $options);
                    }
                }
            }
        }

        $productFlat = $this->converter->convert($productStandard, $options);

        return $productFlat;
    }
}
