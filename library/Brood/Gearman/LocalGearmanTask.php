<?php

namespace Brood\Gearman;

class LocalGearmanTask extends \GearmanTask
{
    protected $data;
    protected $functionName;
    protected $jobHandle;
    protected $taskDenominator;
    protected $taskNumerator;
    protected $unique;

    /**
     * Set the task data
     *
     * setData() is a LocalGearmanTask extension; the data is set by
     * gearman in the regular GearmanTask.
     *
     * @param string $data
     * @return LocalGearmanTask
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    public function data()
    {
        return $this->data;
    }

    public function dataSize()
    {
        return strlen($this->data);
    }

    /**
     * Set the function name
     *
     * setFunctionName() is a LocalGearmanTask extension; the function name is
     * set by gearman in the regular GearmanTask.
     *
     * @param string $functionName
     * @return LocalGearmanTask
     */
    public function setFunctionName($functionName)
    {
        $this->functionName = $functionName;
        return $this;
    }

    public function functionName()
    {
        return $this->functionName;
    }

    public function isKnown()
    {
        // as of 0.8.0, this appears to always return false
        return false;
    }

    public function isRunning()
    {
        // as of 0.8.0, this appears to always return false
        return false;
    }

    /**
     * Set the job handle
     *
     * setJobHandle() is a LocalGearmanTask extension; the job handle is
     * set by gearman in the regular GearmanTask.
     *
     * @param string $jobHandle
     * @return LocalGearmanTask
     */
    public function setJobHandle($jobHandle)
    {
        $this->jobHandle = $jobHandle;
        return $this;
    }

    public function jobHandle()
    {
        return $this->jobHandle;
    }

    public function returnCode()
    {
        // as of 0.8.0, this appears to always return GEARMAN_SUCCESS
        return \GEARMAN_SUCCESS;
    }

    /**
     * Set the task denominator
     *
     * setTaskDenominator() is a LocalGearmanTask extension; the task
     * denominator is set by gearman in the regular GearmanTask.
     *
     * @param int $taskDenominator
     * @return LocalGearmanTask
     */
    public function setTaskDenominator($taskDenominator)
    {
        $this->taskDenominator = $taskDenominator;
        return $this;
    }

    public function taskDenominator()
    {
        return $this->taskDenominator;
    }

    /**
     * Set the task numerator
     *
     * setTaskNumerator() is a LocalGearmanTask extension; the task
     * numerator is set by gearman in the regular GearmanTask.
     *
     * @param int $taskNumerator
     * @return LocalGearmanTask
     */
    public function setTaskNumerator($taskNumerator)
    {
        $this->taskNumerator = $taskNumerator;
        return $this;
    }

    public function taskNumerator()
    {
        return $this->taskNumerator;
    }

    public function unique()
    {
        if ($this->unique === null) {
            $unique = '';
            while (strlen($unique) < 32) {
                $unique .= preg_replace('/[^a-f0-9]/', '', uniqid());
            }
            $this->unique = join('-', array(
                substr($unique, 0, 8),
                substr($unique, 8, 4),
                substr($unique, 12, 4),
                substr($unique, 16, 4),
                substr($unique, 20, 12),
            ));
        }

        return $this->unique;
    }
}
