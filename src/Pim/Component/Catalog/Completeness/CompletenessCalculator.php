<?php

namespace Pim\Component\Catalog\Completeness;

use Pim\Component\Catalog\Completeness\Checker\ProductValueCompleteCheckerInterface;
use Pim\Component\Catalog\Model\AttributeRequirementInterface;
use Pim\Component\Catalog\Model\ChannelInterface;
use Pim\Component\Catalog\Model\LocaleInterface;
use Pim\Component\Catalog\Model\ProductInterface;
use Pim\Component\Catalog\Model\ProductValueInterface;

class CompletenessCalculator
{
    /** @var ProductValueCompleteCheckerInterface */
    protected $checker;

    /** @var Completeness[] indexed by channel-locale */
    private $completenesses;

    /** @var AttributeRequirementInterface[] indexed by channel-attribute */
    private $requirements;

    /** @var array indexed by channel */
    private $nbRequirements;

    public function __construct(ProductValueCompleteCheckerInterface $checker)
    {
        $this->checker = $checker;
        $this->completenesses = [];
        $this->requirements = [];
        $this->nbRequirements = [];
    }

    /**
     * @param ProductInterface $product
     *
     * @return float
     */
    public function calculate(ProductInterface $product)
    {
        if (empty($this->requirements)) {
            $this->computeFamilyRequirements($product);
        }

        /** @var ProductValueInterface $value */
        foreach ($product->getValues() as $value) {
            $channel = $value->getScope();
            $locale = $value->getLocale();

            if (!$this->checker->isComplete($value, $channel, $locale)) {
                if (null !== $channel && null !== $locale) {
                    $this->incMissingCompleteness($product, $channel, $locale);
                } elseif (null !== $channel) {
                    foreach ($channel->getLocales() as $locale) {
                        $this->incMissingCompleteness($product, $channel, $locale);
                    }
                } else {
                    foreach ($allChannels as $channel) {
                        foreach ($channel->getLocales() as $locale) {
                            $this->incMissingCompleteness($product, $channel, $locale);
                        }
                    }
                }
            }
        }
    }

//    /**
//     * @param ProductInterface $product
//     *
//     * @return float
//     */
//    public function calculate(ProductInterface $product)
//    {
//        $this->computeFamilyRequirements($product);
//
//        foreach ($this->requirements as $requirement) {
//            $attribute = $requirement->getAttribute();
//            if ($attribute->isLocalizable()) {
//
//
//            }
//
//
//            $completeness = $this->getCompleteness($product, )
//
//            $requirement->
//
//            $value = $product->getValue($attributeCode);
//            if (null === $value || !$this->checker->isComplete($value)) {
//                $nbMissing += 1;
//            } else {
//
//            }
//        }
//
//        return $nbCompleted * 100 / count($requirements);
//    }

    /**
     * @param ProductInterface $product
     * @param LocaleInterface  $locale
     * @param ChannelInterface $channel
     */
    private function incMissingCompleteness(
        ProductInterface $product,
        LocaleInterface $locale,
        ChannelInterface $channel
    ) {
        $c = $this->getCompleteness($product, $channel, $locale);
        $c->incNbMissing();
    }

    /**
     * @param ProductInterface $product
     *
     * @return AttributeRequirementInterface[] indexed by attribute code
     */
    private function computeFamilyRequirements(ProductInterface $product)
    {
        foreach ($product->getFamily()->getAttributeRequirements() as $requirement) {
            $channelCode = $requirement->getChannelCode();
            if (!isset($this->nbRequirements[$channelCode])) {
                $this->nbRequirements[$channelCode] = 0;
            }

            if ($requirement->isRequired()) {
                $key = sprintf('%s-%s', $channelCode, $requirement->getAttributeCode());
                $this->requirements[$key] = $requirement;
                $this->nbRequirements[$channelCode]++;
            }
        }
    }

    /**
     * @param ProductInterface $product
     * @param LocaleInterface  $locale
     * @param ChannelInterface $channel
     *
     * @return Completeness
     */
    private function getCompleteness(ProductInterface $product, ChannelInterface $channel, LocaleInterface $locale)
    {
        $channelCode = $channel->getCode();
        $key = sprintf('%s-%s', $channelCode, $locale->getCode());
        if (!isset($this->completenesses[$key])) {
            $this->completenesses[$key] = new Completeness(
                $product,
                $channel,
                $locale,
                $this->nbRequirements[$channelCode]
            );
        }

        return $this->completenesses[$key];
    }
}
