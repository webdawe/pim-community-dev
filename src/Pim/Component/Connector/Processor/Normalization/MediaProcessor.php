<?php

namespace Pim\Component\Connector\Processor\Normalization;

use Akeneo\Component\Batch\Item\AbstractConfigurableStepElement;
use Akeneo\Component\Batch\Item\ItemProcessorInterface;
use Akeneo\Component\Batch\Job\JobParameters;
use Akeneo\Component\Batch\Model\StepExecution;
use Akeneo\Component\Batch\Step\StepExecutionAwareInterface;
use Akeneo\Component\FileStorage\Model\FileInfoInterface;
use Pim\Component\Catalog\Model\ProductInterface;
use Pim\Component\Connector\Writer\File\BulkFileExporter;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Media processor to process and normalize entities to the standard format
 *
 * @author    Marie Bochu <marie.bochu@akeneo.com>
 * @copyright 2016 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MediaProcessor extends AbstractConfigurableStepElement implements
    ItemProcessorInterface,
    StepExecutionAwareInterface
{
    /** @var NormalizerInterface */
    protected $normalizer;

    /** @var StepExecution */
    protected $stepExecution;

    /** @var BulkFileExporter */
    protected $mediaCopier;

    /** @var array */
    protected $mediaAttributeTypes;

    /**
     * @param NormalizerInterface $normalizer
     * @param BulkFileExporter    $mediaCopier
     * @param array               $mediaAttributeTypes
     */
    public function __construct(
        NormalizerInterface $normalizer,
        BulkFileExporter $mediaCopier,
        array $mediaAttributeTypes
    ) {
        $this->normalizer = $normalizer;
        $this->mediaCopier = $mediaCopier;
        $this->mediaAttributeTypes = $mediaAttributeTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function process($product)
    {
        $parameters = $this->stepExecution->getJobParameters();
        if ($parameters->has('with_media') && !$parameters->get('with_media')) {
            return;
        }

        $media = $this->getMediaProductValues($product);
        if (empty($media)) {
            return;
        }

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

        return $this->mediaCopier->getCopiedMedia();
    }

    /**
     * {@inheritdoc}
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
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
        $identifier = $product->getIdentifier()->getData();

        foreach ($product->getValues() as $value) {
            if (in_array($value->getAttribute()->getAttributeType(), $this->mediaAttributeTypes)) {
                if (null !== $medium = $value->getMedia()) {
                    $values[] = [
                        'value'      => $medium->getKey(),
                        'storage'    => $medium->getStorage(),
                        'locale'     => $value->getLocale(),
                        'scope'      => $value->getScope(),
                        'code'       => $value->getAttribute()->getCode(),
                        'identifier' => $identifier
                    ];
                }
            }
        }

        return $values;
    }
}
