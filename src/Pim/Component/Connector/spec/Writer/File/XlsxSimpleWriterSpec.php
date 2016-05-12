<?php

namespace spec\Pim\Component\Connector\Writer\File;

use Akeneo\Component\Batch\Model\StepExecution;
use Akeneo\Component\Buffer\BufferInterface;
use PhpSpec\Exception\Example\FailureException;
use PhpSpec\ObjectBehavior;
use Pim\Component\Connector\Writer\File\FilePathResolverInterface;
use Pim\Component\Connector\Writer\File\FlatItemBuffer;
use Prophecy\Argument;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\Type as ConstraintsType;

class XlsxSimpleWriterSpec extends ObjectBehavior
{
    function let(FilePathResolverInterface $filePathResolver, FlatItemBuffer $flatRowBuffer)
    {
        $this->beConstructedWith($filePathResolver, $flatRowBuffer, 10000);

        $filePathResolver
            ->resolve(Argument::any(), Argument::type('array'))
            ->willReturn('/tmp/export/export.xlsx');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Pim\Component\Connector\Writer\File\XlsxSimpleWriter');
    }

    function it_is_step_execution_aware()
    {
        $this->shouldImplement('Akeneo\Component\Batch\Step\StepExecutionAwareInterface');
    }

    function it_is_a_writer()
    {
        $this->shouldImplement('Akeneo\Component\Batch\Item\ItemWriterInterface');
    }

    function it_prepares_items_to_write($flatRowBuffer, StepExecution $stepExecution)
    {
        $this->setStepExecution($stepExecution);

        $this->setWithHeader(true);
        $groups = [
            [
                'code'        => 'promotion',
                'type'        => 'RELATED',
                'label-en_US' => 'Promotion',
                'label-de_DE' => 'Förderung'
            ],
            [
                'code'        => 'related',
                'type'        => 'RELATED',
                'label-en_US' => 'Related',
                'label-de_DE' => 'Verbunden'
            ]
        ];

        $flatRowBuffer->write([
            [
                'code'        => 'promotion',
                'type'        => 'RELATED',
                'label-en_US' => 'Promotion',
                'label-de_DE' => 'Förderung'
            ],
            [
                'code'        => 'related',
                'type'        => 'RELATED',
                'label-en_US' => 'Related',
                'label-de_DE' => 'Verbunden'
            ]
        ], true)->shouldBeCalled();

        $this->write($groups);
    }

    function it_writes_the_xlsx_file($flatRowBuffer, BufferInterface $buffer)
    {
        $this->setLinesPerFile(10000);
        $flatRowBuffer->count()->willReturn(10000);
        $flatRowBuffer->getHeaders()->willReturn(['code', 'type', 'label-en_US', 'label-de_DE']);
        $flatRowBuffer->getBuffer()->willReturn($buffer);

        $this->flush();
    }

    function it_has_configuration()
    {
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
                        'label'       => 'pim_connector.export.lines_per_files.label',
                        'help'        => 'pim_connector.export.lines_per_files.help',
                        'empty_data'  => 10000,
                    ]
                ];
                $constraints = $config['linesPerFile']['options']['constraints'];
                unset($config['linesPerFile']['options']['constraints']);

                if ($expectedLinesPerFile !== $config['linesPerFile']) {
                    throw new FailureException('LinesPerFile configuration doesn\'t match expecting one');
                }

                if (!$constraints[0] instanceof GreaterThan || 1 !== $constraints[0]->value) {
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
}
