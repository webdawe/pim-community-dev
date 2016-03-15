<?php


namespace Pim\Component\Catalog\Completeness;


use Pim\Component\Catalog\Model\ChannelInterface;
use Pim\Component\Catalog\Model\LocaleInterface;
use Pim\Component\Catalog\Model\ProductInterface;

class Completeness
{
    protected $product;
    protected $nbMissing;
    protected $nbRequired;
    protected $locale;
    protected $channel;

    public function __construct(ProductInterface $product, ChannelInterface $channel, LocaleInterface $locale, $nbRequired)
    {
        $this->product = $product;
        $this->channel = $channel;
        $this->locale = $locale;
        $this->nbRequired = $nbRequired;
        $this->nbMissing = $nbRequired;
    }

    /**
     * @return ProductInterface
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @return mixed
     */
    public function getNbMissing()
    {
        return $this->nbMissing;
    }

    /**
     * @return mixed
     */
    public function getNbRequired()
    {
        return $this->nbRequired;
    }

    /**
     * @return LocaleInterface
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @return ChannelInterface
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @return int
     */
    public function getRatio()
    {
        return round($this->nbRequired / ($this->nbRequired - $this->nbMissing));
    }

    public function incNbMissing()
    {
        $this->nbMissing++;
    }
}
