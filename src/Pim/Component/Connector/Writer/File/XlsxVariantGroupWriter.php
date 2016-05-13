<?php

namespace Pim\Component\Connector\Writer\File;

use Akeneo\Component\Batch\Item\ItemWriterInterface;
use Box\Spout\Common\Type;
use Box\Spout\Writer\WriterFactory;
use Box\Spout\Writer\WriterInterface;
use Symfony\Component\Validator\Constraints\GreaterThan;

/**
 * XLSX VariantGroup writer
 *
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2016 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class XlsxVariantGroupWriter extends AbstractFileWriter implements ItemWriterInterface, ArchivableWriterInterface
{
    /** @var bool */
    protected $withHeader;

    /** @var FlatItemBuffer */
    protected $flatRowBuffer;

    /** @var array */
    protected $writtenFiles;

    /** @var int */
    protected $linesPerFiles;

    /** @var int */
    protected $defaultLinesPerFile;

    /** @var BulkFileExporter */
    protected $fileExporter;

    /**
     * @param FilePathResolverInterface $filePathResolver
     * @param FlatItemBuffer            $flatRowBuffer
     * @param BulkFileExporter          $fileExporter
     * @param int                       $defaultLinesPerFile
     */
    public function __construct(
        FilePathResolverInterface $filePathResolver,
        FlatItemBuffer $flatRowBuffer,
        BulkFileExporter $fileExporter,
        $defaultLinesPerFile
    ) {
        parent::__construct($filePathResolver);

        $this->flatRowBuffer       = $flatRowBuffer;
        $this->fileExporter        = $fileExporter;
        $this->defaultLinesPerFile = $defaultLinesPerFile;
        $this->writtenFiles        = [];
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        $exportDirectory = dirname($this->getPath());
        if (!is_dir($exportDirectory)) {
            $this->localFs->mkdir($exportDirectory);
        }

        $variantGroups = $media = [];
        foreach ($items as $item) {
            $variantGroups[] = $item['variant_group'];
            $media[]         = $item['media'];
        }

        $this->flatRowBuffer->write($variantGroups, $this->isWithHeader());
        $this->fileExporter->exportAll($media, $exportDirectory);

        foreach ($this->fileExporter->getCopiedMedia() as $copy) {
            $this->writtenFiles[$copy['copyPath']] = $copy['originalMedium']['exportPath'];
        }

        foreach ($this->fileExporter->getErrors() as $error) {
            $this->stepExecution->addWarning(
                $this->getName(),
                $error['message'],
                [],
                $error['medium']
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $pathPattern = $this->getPath();
        if ($this->areSeveralFilesNeeded()) {
            $pathPattern = $this->getCustomFilePath($this->getPath());
        }

        $headers    = $this->flatRowBuffer->getHeaders();
        $hollowItem = array_fill_keys($headers, '');

        $fileCount = 1;
        $writtenLinesCount = 0;
        foreach ($this->flatRowBuffer->getBuffer() as $count => $incompleteItem) {
            if (0 === $writtenLinesCount % $this->getLinesPerFiles()) {
                $filePath = $this->resolveFilePath($pathPattern, $fileCount);

                $writtenLinesCount = 0;
                $writer = $this->getWriter($filePath);
                $writer->addRow($headers);
            }

            $item = array_replace($hollowItem, $incompleteItem);
            $writer->addRow($item);
            $writtenLinesCount++;

            if (null !== $this->stepExecution) {
                $this->stepExecution->incrementSummaryInfo('write');
            }

            if (0 === $writtenLinesCount % $this->getLinesPerFiles() || $this->flatRowBuffer->count() === $count + 1) {
                $writer->close();
                $this->writtenFiles[$filePath] = basename($filePath);
                $writtenLinesCount = 0;
                $fileCount++;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationFields()
    {
        return [
            'filePath' => [
                'options' => [
                    'label' => 'pim_connector.export.filePath.label',
                    'help'  => 'pim_connector.export.filePath.help',
                ],
            ],
            'linesPerFiles' => [
                'type'    => 'integer',
                'options' => [
                    'label'       => 'pim_connector.export.lines_per_files.label',
                    'help'        => 'pim_connector.export.lines_per_files.help',
                    'empty_data'  => $this->defaultLinesPerFile,
                    'constraints' => [
                        new GreaterThan(1)
                    ]
                ]
            ],
            'withHeader' => [
                'type'    => 'switch',
                'options' => [
                    'label' => 'pim_connector.export.withHeader.label',
                    'help'  => 'pim_connector.export.withHeader.help',
                ],
            ],
        ];
    }

    /**
     * @return bool
     */
    public function isWithHeader()
    {
        return $this->withHeader;
    }

    /**
     * @param bool $withHeader
     */
    public function setWithHeader($withHeader)
    {
        $this->withHeader = $withHeader;
    }

    /**
     * {@inheritdoc}
     */
    public function getWrittenFiles()
    {
        return $this->writtenFiles;
    }

    /**
     * @return int
     */
    public function getLinesPerFiles()
    {
        return $this->linesPerFiles;
    }

    /**
     * @param int $linesPerFiles
     */
    public function setLinesPerFiles($linesPerFiles)
    {
        $this->linesPerFiles = $linesPerFiles;
    }

    /**
     * @return bool
     */
    protected function areSeveralFilesNeeded()
    {
        return $this->flatRowBuffer->count() > $this->getLinesPerFiles();
    }

    /**
     * Return the given file path in terms of the current file count if needed
     *
     * @param string $pathPattern
     * @param int    $currentFileCount
     *
     * @return string
     */
    protected function resolveFilePath($pathPattern, $currentFileCount)
    {
        $resolvedFilePath = $pathPattern;
        if ($this->areSeveralFilesNeeded()) {
            $resolvedFilePath = $this->filePathResolver->resolve(
                $pathPattern,
                array_merge_recursive(
                    $this->filePathResolverOptions,
                    ['parameters' => ['%fileNb%' => '_' . $currentFileCount]]
                )
            );
        }

        return $resolvedFilePath;
    }

    /**
     * Return the given file path with %fileNb% placeholder just before the extension of the file
     * ie: in -> '/path/myFile.txt' ; out -> '/path/myFile%fileNb%.txt'
     *
     * @param string $fullFilePath
     *
     * @return string
     */
    protected function getCustomFilePath($fullFilePath)
    {
        $extension = '.' . pathinfo($fullFilePath, PATHINFO_EXTENSION);
        $filePath  = strstr($fullFilePath, $extension, true);

        return $filePath . '%fileNb%' . $extension;
    }

    /**
     * @param string $filePath File path to open with the writer
     *
     * @return WriterInterface
     */
    protected function getWriter($filePath)
    {
        $writer = WriterFactory::create(Type::XLSX);
        $writer->openToFile($filePath);

        return $writer;
    }
}
