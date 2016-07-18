<?php

namespace Pim\Component\Connector\Processor\Normalization;

use Akeneo\Component\Batch\Item\AbstractConfigurableStepElement;
use Akeneo\Component\Batch\Item\ItemProcessorInterface;
use Akeneo\Component\Batch\Job\JobParameters;
use Akeneo\Component\Batch\Model\StepExecution;
use Akeneo\Component\Batch\Step\StepExecutionAwareInterface;
use Akeneo\Component\FileStorage\Model\FileInfoInterface;
use Akeneo\Component\StorageUtils\Detacher\ObjectDetacherInterface;
use Pim\Component\Catalog\Builder\ProductBuilderInterface;
use Pim\Component\Catalog\Model\ProductInterface;
use Pim\Component\Catalog\Repository\ChannelRepositoryInterface;
use Pim\Component\Connector\Writer\File\BulkFileExporter;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Product processor to process and normalize entities to the standard format
 *
 * @author    Marie Bochu <marie.bochu@akeneo.com>
 * @copyright 2016 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductProcessor extends AbstractConfigurableStepElement implements
    ItemProcessorInterface,
    StepExecutionAwareInterface
{
    /** @var NormalizerInterface */
    protected $normalizer;

    /** @var ChannelRepositoryInterface */
    protected $channelRepository;

    /** @var ProductBuilderInterface */
    protected $productBuilder;

    /** @var ObjectDetacherInterface */
    protected $detacher;

    /** @var StepExecution */
    protected $stepExecution;

    /** @var BulkFileExporter */
    protected $mediaCopier;

    /** @var array */
    protected $mediaAttributeTypes;

    /**
     * @param NormalizerInterface        $normalizer
     * @param ChannelRepositoryInterface $channelRepository
     * @param ProductBuilderInterface    $productBuilder
     * @param ObjectDetacherInterface    $detacher
     * @param BulkFileExporter           $mediaCopier
     * @param array                      $mediaAttributeTypes
     */
    public function __construct(
        NormalizerInterface $normalizer,
        ChannelRepositoryInterface $channelRepository,
        ProductBuilderInterface $productBuilder,
        ObjectDetacherInterface $detacher,
        BulkFileExporter $mediaCopier,
        array $mediaAttributeTypes
    ) {
        $this->normalizer = $normalizer;
        $this->detacher = $detacher;
        $this->channelRepository = $channelRepository;
        $this->productBuilder = $productBuilder;
        $this->mediaCopier = $mediaCopier;
        $this->mediaAttributeTypes = $mediaAttributeTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function process($product)
    {
        $parameters = $this->stepExecution->getJobParameters();
        $channelCode = $parameters->get('channel');
        $channel = $this->channelRepository->findOneByIdentifier($channelCode);
        $this->productBuilder->addMissingProductValues(
            $product,
            [$channel],
            $channel->getLocales()->toArray()
        );

        $productStandard = $this->normalizer->normalize($product, 'json', [
            'scopeCode'   => $channel->getCode(),
            'localeCodes' => array_intersect($channel->getLocaleCodes(), $parameters->get('locales')),
        ]);

        if ($parameters->has('with_media') && $parameters->get('with_media')) {
            $this->manageMediaWhenLogicIsInProcessor($product, $parameters);
        }

        $this->detacher->detach($product);

        return $productStandard;
    }

    /**
     * {@inheritdoc}
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
    }

    /**
     * @param ProductInterface $product
     * @param JobParameters    $parameters
     */
    protected function manageMediaWhenLogicIsInProcessor(ProductInterface $product, JobParameters $parameters)
    {
        $media = $this->getMediaProductValues($product);

        $directory = dirname($parameters->get('filePath'))
            . DIRECTORY_SEPARATOR
            . $this->stepExecution->getJobExecution()->getJobInstance()->getCode()
            . DIRECTORY_SEPARATOR
            . $this->stepExecution->getJobExecution()->getId()
            . DIRECTORY_SEPARATOR;

        $this->mediaCopier->exportAll([$media], $directory);

        foreach ($this->mediaCopier->getErrors() as $error) {
            $this->stepExecution->addWarning($error['message'], [], $error['medium']);
        }
    }

    /**
     * Get media attributes
     *
     * @param ProductInterface $product
     *
     * @return FileInfoInterface[]
     */
    protected function getMediaProductValues(ProductInterface $product)
    {
        $values = [];
        foreach ($product->getValues() as $value) {
            if (in_array($value->getAttribute()->getAttributeType(), $this->mediaAttributeTypes)) {
                if (null !== $medium = $value->getMedia()) {
                    $values[] = [
                        'value'   => $medium->getKey(),
                        'storage' => $medium->getStorage()
                    ];
                }
            }
        }

        return $values;
    }
}
