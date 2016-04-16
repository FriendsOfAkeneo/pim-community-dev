<?php

namespace Akeneo\Component\Batch\Job\JobParameters;

use Akeneo\Component\Batch\Job\JobInterface;

/**
 * Provides default parameters to build a JobParameters depending of the Job
 *
 * @author    Nicolas Dupont <nicolas@akeneo.com>
 * @copyright 2016 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class DefaultParametersRegistry
{
    /** @var DefaultParametersInterface[] */
    protected $defaultParameters = [];

    /** @var boolean */
    protected $isStrict;

    /**
     * @param DefaultParametersInterface $parameters
     * @param boolean                    $isStrict
     */
    public function register(DefaultParametersInterface $parameters, $isStrict = true)
    {
        $this->defaultParameters[] = $parameters;
        $this->isStrict = $isStrict;
    }

    /**
     * @param JobInterface $job
     *
     * @return DefaultParametersInterface
     *
     * @throws UndefinedDefaultsException
     */
    public function getDefaultParameters(JobInterface $job)
    {
        foreach ($this->defaultParameters as $default) {
            if ($default->supports($job)) {
                return $default;
            }
        }

        if ($this->isStrict) {
            throw new UndefinedDefaultsException(
                sprintf('No defaults parameters have been defined for the Job "%s"', $job->getName())
            );
        }

        return $this->getDefaultParametersFromStepElements($job);
    }

    /**
     * Partially ensure the Backward Compatibility with Akeneo PIM <= v1.5
     *
     * @param JobInterface $job
     *
     * @return DefaultParametersInterface
     *
     * @deprecated will be removed in 1.7, please use a DefaultParametersInterface to define your default params
     */
    private function getDefaultParametersFromStepElements(JobInterface $job)
    {
        $defaults = [];
        if (method_exists($job, 'getSteps')) {
            foreach ($job->getSteps() as $step) {
                if (method_exists($step, 'getConfigurableStepElements')) {
                    foreach ($step->getConfigurableStepElements() as $stepElement) {
                        if (method_exists($stepElement, 'getConfigurationFields')) {
                            foreach (array_keys($stepElement->getConfigurationFields()) as $field) {
                                $defaults[$field] = null;
                            }
                        }
                    }
                }
            }
        }

        return new SimpleDefaultParameters($defaults);
    }
}
