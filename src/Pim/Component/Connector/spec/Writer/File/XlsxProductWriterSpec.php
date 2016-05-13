<?php

namespace spec\Pim\Component\Connector\Writer\File;

use Akeneo\Component\Batch\Model\StepExecution;
use Akeneo\Component\Buffer\BufferInterface;
use PhpSpec\Exception\Example\FailureException;
use PhpSpec\ObjectBehavior;
use Pim\Component\Connector\Writer\File\FilePathResolverInterface;
use Pim\Component\Connector\Writer\File\FlatItemBuffer;
use Pim\Component\Connector\Writer\File\BulkFileExporter;
use Prophecy\Argument;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;

class XlsxProductWriterSpec extends ObjectBehavior
{
    function let(FilePathResolverInterface $filePathResolver, FlatItemBuffer $flatRowBuffer, BulkFileExporter $mediaCopier)
    {
        $this->beConstructedWith($filePathResolver, $flatRowBuffer, $mediaCopier, 10000);

        $filePathResolver->resolve(Argument::any(), Argument::type('array'))
            ->willReturn('/tmp/export/export.xlsx');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Pim\Component\Connector\Writer\File\XlsxProductWriter');
    }

    function it_is_a_configurable_step()
    {
        $this->shouldHaveType('Akeneo\Component\Batch\Item\AbstractConfigurableStepElement');
    }

    function it_is_a_writer()
    {
        $this->shouldImplement('Akeneo\Component\Batch\Item\ItemWriterInterface');
    }

    function it_prepares_the_export($flatRowBuffer, $mediaCopier, StepExecution $stepExecution)
    {
        $this->setStepExecution($stepExecution);

        $this->setWithHeader(true);
        $items = $this->getItemToExport();

        $flatRowBuffer->write([
            [
                'id' => 123,
                'family' => 12,
            ],
            [
                'id' => 165,
                'family' => 45,
            ],
        ], true)->shouldBeCalled();

        $mediaCopier->exportAll([
            [
                'filePath' => 'wrong/path',
                'exportPath' => 'export',
                'storageAlias' => 'storageAlias',
            ],
            [
                'filePath' => 'img/product1.jpg',
                'exportPath' => 'export',
                'storageAlias' => 'storageAlias',
            ],
        ], '/tmp/export')->shouldBeCalled();

        $mediaCopier->getErrors()->willReturn([
            [
                'medium' => [
                    [
                        'filePath' => 'wrong/path',
                        'exportPath' => 'export',
                        'storageAlias' => 'storageAlias',
                    ]
                ],
                'message' => 'Error message',
            ]
        ]);
        $mediaCopier->getCopiedMedia()->willReturn([
            [
                'copyPath'       => '/tmp/export',
                'originalMedium' => [
                    'filePath'     => 'img/product1.jpg',
                    'exportPath'   => 'export',
                    'storageAlias' => 'storageAlias',
                ]
            ]
        ]);

        $stepExecution->addWarning(Argument::cetera())->shouldBeCalled();

        $this->write($items);

        $this->getWrittenFiles()->shouldBeEqualTo([
            '/tmp/export' => 'export'
        ]);
    }

    function it_writes_the_xlsx_file($flatRowBuffer, BufferInterface $buffer)
    {
        $this->setLinesPerFile(10000);
        $flatRowBuffer->count()->willReturn(10000);

        $flatRowBuffer->getHeaders()->willReturn(['id', 'family']);
        $flatRowBuffer->getBuffer()->willReturn($buffer);

        $this->flush();
    }

    function it_has_configuration()
    {
        $this->setLinesPerFile(10);
        $this->getConfigurationFields()->shouldReturnConfiguration();
    }

    public function getMatchers()
    {
        return [
            'returnConfiguration' => function ($config) {
                $expectedFilePath = [
                    'options' => [
                        'label' => 'pim_connector.export.filePath.label',
                        'help'  => 'pim_connector.export.filePath.help'
                    ]
                ];
                $expectedWithHeader = [
                    'type'    => 'switch',
                    'options' => [
                        'label' => 'pim_connector.export.withHeader.label',
                        'help'  => 'pim_connector.export.withHeader.help'
                    ]
                ];
                $expectedLinesPerFile = [
                    'type'    => 'integer',
                    'options' => [
                        'label' => 'pim_connector.export.lines_per_files.label',
                        'help'  => 'pim_connector.export.lines_per_files.help',
                        'data'  => 10
                    ]
                ];
                $constraints = $config['linesPerFile']['options']['constraints'];
                unset($config['linesPerFile']['options']['constraints']);

                if ($expectedLinesPerFile !== $config['linesPerFile']) {
                    throw new FailureException('LinesPerFile configuration doesn\'t match expecting one');
                }

                if (!$constraints[0] instanceof NotBlank) {
                    throw new FailureException('Expecting to get a NotBlank constraint for linesPerFile');
                }

                if (!$constraints[1] instanceof GreaterThan || 1 !== $constraints[1]->value) {
                    throw new FailureException('Expecting to get a GreaterThan 1 constraint for linesPerFile');
                }

                if ($expectedFilePath !== $config['filePath']) {
                    throw new FailureException('FilePath configuration doesn\'t match expecting one');
                }
                if ($expectedWithHeader !== $config['withHeader']) {
                    throw new FailureException('WithHeader configuration doesn\'t match expecting one');
                }

                return true;
            }
        ];
    }

    private function getItemToExport()
    {
        return [
            [
                'product' => [
                    'id' => 123,
                    'family' => 12,
                ],
                'media' => [
                    'filePath' => 'wrong/path',
                    'exportPath' => 'export',
                    'storageAlias' => 'storageAlias',
                ],
            ],
            [
                'product' => [
                    'id' => 165,
                    'family' => 45,
                ],
                'media' => [
                    'filePath' => 'img/product1.jpg',
                    'exportPath' => 'export',
                    'storageAlias' => 'storageAlias',
                ],
            ]
        ];
    }
}
