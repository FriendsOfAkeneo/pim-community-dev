<?php

namespace Pim\Bundle\ImportExportBundle\Form\Type\JobParameters;

use Akeneo\Component\Batch\Job\JobInterface;
use Akeneo\Component\Localization\Localizer\LocalizerInterface;
use Pim\Component\Catalog\Repository\ChannelRepositoryInterface;

/**
 * FormsOptions for variant group CSV import
 *
 * @author    Nicolas Dupont <nicolas@akeneo.com>
 * @copyright 2016 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class VariantGroupCsvImport implements FormsOptionsInterface
{
    /** @var SimpleCsvImport */
    protected $simpleCsvImport;

    /** @var string */
    protected $decimalSeparator = LocalizerInterface::DEFAULT_DECIMAL_SEPARATOR;

    /** @var array */
    protected $decimalSeparators;

    /** @var string */
    protected $dateFormat = LocalizerInterface::DEFAULT_DATE_FORMAT;

    /** @var array */
    protected $dateFormats;

    /** @var array */
    protected $supportedJobNames;

    /**
     * @param SimpleCsvImport            $simpleCsvImport
     * @param array                      $supportedJobNames
     * @param array                      $decimalSeparators
     * @param array                      $dateFormats
     */
    public function __construct(
        SimpleCsvImport $simpleCsvImport,
        array $supportedJobNames,
        array $decimalSeparators,
        array $dateFormats
    ) {
        $this->simpleCsvImport   = $simpleCsvImport;
        $this->supportedJobNames = $supportedJobNames;
        $this->decimalSeparators = $decimalSeparators;
        $this->dateFormats       = $dateFormats;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions()
    {
        $formOptions = [
            'decimalSeparator' => [
                'type'    => 'choice',
                'options' => [
                    'choices'  => $this->decimalSeparators,
                    'required' => true,
                    'select2'  => true,
                    'label'    => 'pim_base_connector.export.decimalSeparator.label',
                    'help'     => 'pim_base_connector.export.decimalSeparator.help'
                ]
            ],
            'dateFormat' => [
                'type'    => 'choice',
                'options' => [
                    'choices'  => $this->dateFormats,
                    'required' => true,
                    'select2'  => true,
                    'label'    => 'pim_base_connector.export.dateFormat.label',
                    'help'     => 'pim_base_connector.export.dateFormat.help',
                ]
            ],
            'copyValues' => [
                'type'    => 'switch',
                'options' => [
                    'label' => 'pim_connector.import.copyValuesToProducts.label',
                    'help'  => 'pim_connector.import.copyValuesToProducts.help'
                ]
            ]
        ];
        $formOptions = array_merge($this->simpleCsvImport->getOptions(), $formOptions);

        return $formOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(JobInterface $job)
    {
        return in_array($job->getName(), $this->supportedJobNames);
    }
}
