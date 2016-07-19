<?php

namespace Pim\Component\Connector\Writer\File;

use Akeneo\Component\Batch\Item\ItemWriterInterface;
use Akeneo\Component\Batch\Job\JobParameters;

/**
 * Write media data on the local filesystem
 *
 * @author    Marie Bochu <marie.bochu@akeneo.com>
 * @copyright 2016 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MediaWriter extends AbstractFileWriter implements ItemWriterInterface, ArchivableWriterInterface
{
    /** @var array */
    protected $writtenFiles = [];

    /**
     * {@inheritdoc}
     */
    public function initialize()
    {
        $exportDirectory = dirname($this->getPath());
        if (!is_dir($exportDirectory)) {
            $this->localFs->mkdir($exportDirectory);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $media)
    {
        $parameters = $this->stepExecution->getJobParameters();
        if ($parameters->has('with_media') && !$parameters->get('with_media')) {
            return;
        }

        foreach ($media as $medium) {
            foreach ($medium as $item) {
                if (null !== $item['copyPath'] && file_exists($item['copyPath'])) {
                    $exportPath = $this->generate($item['originalMedium'], $item['originalMedium']['code'], $item['originalMedium']['identifier']);
                    $this->writtenFiles[$item['copyPath']] = $exportPath;
                }
            }
        }
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

        return $target . DIRECTORY_SEPARATOR . substr(strrchr($value['value'], '_'), 1);
    }

    /**
     * {@inheritdoc}
     */
    public function getWrittenFiles()
    {
        return $this->writtenFiles;
    }
}
