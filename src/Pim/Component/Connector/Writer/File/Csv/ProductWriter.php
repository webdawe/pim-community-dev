<?php

namespace Pim\Component\Connector\Writer\File\Csv;

use Akeneo\Component\Batch\Item\ItemWriterInterface;
use Akeneo\Component\Batch\Job\JobParameters;
use Akeneo\Component\Buffer\BufferFactory;
use Akeneo\Component\FileStorage\Model\FileInfoInterface;
use Akeneo\Component\FileStorage\Repository\FileInfoRepositoryInterface;
use Pim\Component\Catalog\Repository\AttributeRepositoryInterface;
use Pim\Component\Connector\ArrayConverter\ArrayConverterInterface;
use Pim\Component\Connector\Writer\File\AbstractFileWriter;
use Pim\Component\Connector\Writer\File\ArchivableWriterInterface;
use Pim\Component\Connector\Writer\File\BulkFileExporter;
use Pim\Component\Connector\Writer\File\FilePathResolverInterface;
use Pim\Component\Connector\Writer\File\FlatItemBuffer;
use Pim\Component\Connector\Writer\File\FlatItemBufferFlusher;
use Pim\Component\Connector\Writer\File\MediaExporterPathGenerator;

/**
 * Write product data into a csv file on the local filesystem
 *
 * @author    Yohan Blain <yohan.blain@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductWriter extends AbstractFileWriter implements ItemWriterInterface, ArchivableWriterInterface
{
    /** @var ArrayConverterInterface */
    protected $arrayConverter;

    /** @var FlatItemBuffer */
    protected $flatRowBuffer = null;

    /** @var BulkFileExporter */
    protected $mediaCopier;

    /** @var FlatItemBufferFlusher */
    protected $flusher;

    /** @var BufferFactory */
    protected $bufferFactory;

    /** @var array */
    protected $mediaAttributeTypes;

    /** @var MediaExporterPathGenerator */
    protected $exporterPathGenerator;

    /** @var array */
    protected $writtenFiles = [];

    /** @var AttributeRepositoryInterface */
    protected $attributeRepository;

    /** @var FileInfoRepositoryInterface */

    protected $fileInfoRepository;
    /** @var string */
    protected $catalogStorageDir;

    /**
     * @param ArrayConverterInterface      $arrayConverter
     * @param FilePathResolverInterface    $filePathResolver
     * @param BufferFactory                $bufferFactory
     * @param BulkFileExporter             $mediaCopier
     * @param FlatItemBufferFlusher        $flusher
     * @param AttributeRepositoryInterface $attributeRepository
     * @param FileInfoRepositoryInterface  $fileInfoRepository
     * @param string                       $catalogStorageDir
     */
    public function __construct(
        ArrayConverterInterface $arrayConverter,
        FilePathResolverInterface $filePathResolver,
        BufferFactory $bufferFactory,
        BulkFileExporter $mediaCopier,
        FlatItemBufferFlusher $flusher,
        AttributeRepositoryInterface $attributeRepository,
        FileInfoRepositoryInterface $fileInfoRepository,
        $catalogStorageDir
    ) {
        parent::__construct($filePathResolver);

        $this->arrayConverter = $arrayConverter;
        $this->bufferFactory = $bufferFactory;
        $this->mediaCopier = $mediaCopier;
        $this->flusher = $flusher;
        $this->attributeRepository = $attributeRepository;
        $this->fileInfoRepository = $fileInfoRepository;
        $this->catalogStorageDir = $catalogStorageDir;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize()
    {
        if (null === $this->flatRowBuffer) {
            $this->flatRowBuffer = $this->bufferFactory->create();
        }

        $exportDirectory = dirname($this->getPath());
        if (!is_dir($exportDirectory)) {
            $this->localFs->mkdir($exportDirectory);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $products)
    {
        $parameters = $this->stepExecution->getJobParameters();

        $flatItems = [];
        foreach ($products as $product) {
            if ($parameters->has('with_media') && $parameters->get('with_media')) {
                $product = $this->manageMediaWhenLogicIsInProcessor($product, $parameters);
//                $this->manageMediaWhenLogicIsInWriter($product);
            }

            $flatItems[] = $this->arrayConverter->convert($product, [
                'decimal_separator' => $parameters->get('decimalSeparator'),
                'date_format'       => $parameters->get('dateFormat'),
            ]);
        }

        $options = [];
        $options['withHeader'] = $parameters->get('withHeader');
        $this->flatRowBuffer->write($flatItems, $options);
    }

    protected function manageMediaWhenLogicIsInWriter(array &$product)
    {
        $media = $this->getMediaProductValues($product);
        $this->mediaCopier->exportAll($media, dirname($this->getPath()));

        foreach ($this->mediaCopier->getCopiedMedia() as $copy) {
            $this->writtenFiles[$copy['copyPath']] = $copy['originalMedium']['exportPath'];
        }

        foreach ($this->mediaCopier->getErrors() as $error) {
            $this->stepExecution->addWarning(
                $error['message'],
                [],
                $error['medium']
            );
        }
    }

    /**
     * Get media attributes
     *
     * @param ProductInterface $product
     *
     * @return FileInfoInterface[]
     */
    protected function getMediaProductValues(&$product)
    {
        $attributeTypes = $this->attributeRepository->getAttributeTypeByCodes(array_keys($product['values']));

        $values = [];
        foreach ($product['values'] as $code => $data) {
            if (in_array($attributeTypes[$code], ['pim_catalog_file', 'pim_catalog_image'])) {
                foreach ($data as $index => $value) {
                    $fileInfo = $this->fileInfoRepository->findOneByIdentifier($value['data']['filePath']);
                    if (null !== $fileInfo) {
                        $exportPath = $this->generate($fileInfo, $value, $code, $product['values']['sku'][0]['data']);
                        $values[$code][]   = [
                            'filePath'     => $value['data']['filePath'],
                            'exportPath'   => $exportPath,
                            'storageAlias' => $fileInfo->getStorage()
                        ];

                        $product['values'][$code][$index]['data']['filePath'] = $exportPath;
                    }
                }
            }
        }

        return $values;
    }

    /**
     * @param $value
     * @param $code
     * @param $identifier
     *
     * @return string
     */
    protected function generate($value, $code, $identifier)
    {
        $target = sprintf('files/%s/%s', str_replace(DIRECTORY_SEPARATOR, '_', $identifier), $code);

        if (null !== $value['locale']) {
            $target .= DIRECTORY_SEPARATOR . $value['locale'];
        }
        if (null !== $value['scope']) {
            $target .= DIRECTORY_SEPARATOR . $value['scope'];
        }

        return $target . DIRECTORY_SEPARATOR . substr(strrchr($value['data']['filePath'], '_'), 1);
    }

    protected function manageMediaWhenLogicIsInProcessor(array $product, JobParameters $parameters)
    {
        $attributeTypes = $this->attributeRepository->getAttributeTypeByCodes(array_keys($product['values']));
        $directory = dirname($parameters->get('filePath'))
            . DIRECTORY_SEPARATOR
            . $this->stepExecution->getJobExecution()->getJobInstance()->getCode()
            . DIRECTORY_SEPARATOR
            . $this->stepExecution->getJobExecution()->getId()
            . DIRECTORY_SEPARATOR;

        foreach ($attributeTypes as $code => $attributeType) {
            if (in_array($attributeType, ['pim_catalog_file', 'pim_catalog_image'])) {
                foreach ($product['values'][$code] as $index => $item) {
                    if (null !== $item['data'] && file_exists($directory . $item['data']['filePath'])) {
                        $exportPath = $this->generate($item, $code, $product['values']['sku'][0]['data']);
                        $this->writtenFiles[$directory . $item['data']['filePath']] = $exportPath;
                        $product['values'][$code][$index]['data']['filePath'] = $exportPath;
                    }
                }
            }
        }

        return $product;
    }

    /**
     * Flush items into a csv file
     */
    public function flush()
    {
        $this->flusher->setStepExecution($this->stepExecution);

        $parameters = $this->stepExecution->getJobParameters();
        $writerOptions = [
            'type'           => 'csv',
            'fieldDelimiter' => $parameters->get('delimiter'),
            'fieldEnclosure' => $parameters->get('enclosure'),
            'shouldAddBOM'   => false,
        ];

        $writtenFiles = $this->flusher->flush(
            $this->flatRowBuffer,
            $writerOptions,
            $this->getPath(),
            ($parameters->has('linesPerFile') ? $parameters->get('linesPerFile') : -1),
            $this->filePathResolverOptions
        );

        foreach ($writtenFiles as $writtenFile) {
            $this->writtenFiles[$writtenFile] = basename($writtenFile);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getWrittenFiles()
    {
        return $this->writtenFiles;
    }
}
