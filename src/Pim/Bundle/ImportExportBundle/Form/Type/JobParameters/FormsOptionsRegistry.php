<?php

namespace Pim\Bundle\ImportExportBundle\Form\Type\JobParameters;

use Akeneo\Component\Batch\Job\JobInterface;

/**
 * Provides options to build the JobParameters forms
 * For instance, how to render the filepath parameter in an export context
 *
 * @author    Nicolas Dupont <nicolas@akeneo.com>
 * @copyright 2016 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class FormsOptionsRegistry
{
    /** @var FormsOptionsInterface[] */
    protected $formsOptions = [];

    /** @var boolean */
    protected $isStrict;

    /**
     * @param FormsOptionsInterface $options
     * @param boolean               $isStrict
     */
    public function register(FormsOptionsInterface $options, $isStrict = true)
    {
        $this->formsOptions[] = $options;
        $this->isStrict = $isStrict;
    }

    /**
     * @param JobInterface $job
     *
     * @return FormsOptionsInterface
     *
     * @throws UndefinedFormOptionsException
     */
    public function getFormsOptions(JobInterface $job)
    {
        foreach ($this->formsOptions as $options) {
            if ($options->supports($job)) {
                return $options;
            }
        }

        if ($this->isStrict) {
            throw new UndefinedFormOptionsException(
                sprintf('No Form options have been defined for the Job "%s"', $job->getName())
            );
        }

        return $this->getFormsOptionsFromStepElements($job);
    }

    /**
     * Partially ensure the Backward Compatibility with Akeneo PIM <= v1.5
     *
     * @param JobInterface $job
     *
     * @return FormsOptionsInterface
     *
     * @deprecated will be removed in 1.7, please use a FormsOptionsInterface to define your form fields options
     */
    private function getFormsOptionsFromStepElements(JobInterface $job)
    {
        $options = [];
        if (method_exists($job, 'getSteps')) {
            foreach ($job->getSteps() as $step) {
                if (method_exists($step, 'getConfigurableStepElements')) {
                    foreach ($step->getConfigurableStepElements() as $stepElement) {
                        if (method_exists($stepElement, 'getConfigurationFields')) {
                            $options = array_merge($options, $stepElement->getConfigurationFields());
                        }
                    }
                }
            }
        }

        return new SimpleFormsOptions($options);
    }
}
