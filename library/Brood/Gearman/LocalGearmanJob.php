<?php

namespace Brood\Gearman;

/**
 * Gearman job that we dispatch ourselves
 *
 * Normal GearmanJobs are created by gearman. This class allows us to set up a
 * job, run dispatch it ourselves, then trigger callbacks that are expecting a
 * GearmanTask. All of this happens without actually contacting a gearman
 * server.
 */
class LocalGearmanJob extends \GearmanJob
{
    private static $serial = 0;
    protected $functionName;
    protected $workload;
    protected $return = \GEARMAN_SUCCESS;
    protected $finished = false;
    protected $completeCallback;
    protected $failCallback;
    protected $dataCallback;
    protected $statusCallback;
    protected $warningCallback;

    public function __construct()
    {
        self::$serial++;
    }

    /**
     * Set the function name
     *
     * setFunctionName() is a LocalGearmanJob extension; the function name is
     * set by gearman in the regular GearmanJob.
     *
     * @param string $functionName
     * @return LocalGearmanJob
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

    /**
     * Set the job workload
     *
     * setWorkload() is a LocalGearmanJob extension; the workload is set by
     * gearman in the regular GearmanJob.
     *
     * @param string $workload
     * @return LocalGearmanJob
     */
    public function setWorkload($workload)
    {
        $this->workload = $workload;
        return $this;
    }

    public function workload()
    {
        return $this->workload;
    }

    public function workloadSize()
    {
        return strlen($this->workload);
    }

    public function setReturn($gearman_return_t)
    {
        $this->return = $gearman_return_t;
        return true;
    }

    public function returnCode()
    {
        return $this->return;
    }

    /**
     * Trigger complete or fail callback
     *
     * finish() is a LocalGearmanJob extension; the complete or fail callback
     * is triggered by gearman in the regular GearmanJob.
     *
     * If the worker has already called sendComplete() or sendFail(), this
     * method doesn't do anything. It is only needed to implicitly trigger the
     * complete or fail callback if the worker only called setReturn() without
     * a send*() call, or if the worker didn't call anything at all (in which
     * case success is assumed and the complete callback is triggered).
     *
     * @param string $result
     * @return LocalGearmanJob
     */
    public function finish($result = null)
    {
        if ($this->finished) {
            return;
        }

        if ($this->return == \GEARMAN_WORK_FAIL) {
            $this->sendFail();
        } else {
            $this->sendComplete($result);
        }

        return $this;
    }

    /**
     * Set a callback for when the job is completed successfully
     *
     * setCompleteCallback() is a LocalGearmanJob extension; the callback is
     * normally set on the GearmanClient and is triggered by gearman.
     *
     * @param callback $callback
     * @return LocalGearmanJob
     */
    public function setCompleteCallback($callback)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('Invalid callback passed to Brood\Gearman\LocalGearmanJob::setCompleteCallback()');
        }

        $this->completeCallback = $callback;
        return $this;
    }

    public function sendComplete($result)
    {
        if ($this->triggerCallback($this->completeCallback, $result)) {
            $this->finished = true;
            return true;
        }
        return false;
    }

    /**
     * Set a callback for when the job fails
     *
     * setFailCallback() is a LocalGearmanJob extension; the callback is
     * normally set on the GearmanClient and is triggered by gearman.
     *
     * @param callback $callback
     * @return LocalGearmanJob
     */
    public function setFailCallback($callback)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('Invalid callback passed to Brood\Gearman\LocalGearmanJob::setFailCallback()');
        }

        $this->failCallback = $callback;
        return $this;
    }

    public function sendFail()
    {
        if ($this->triggerCallback($this->failCallback)) {
            $this->finished = true;
            return true;
        }
        return false;
    }

    /**
     * Set a callback for when the job sends data
     *
     * setDataCallback() is a LocalGearmanJob extension; the callback is
     * normally set on the GearmanClient and is triggered by gearman.
     *
     * @param callback $callback
     * @return LocalGearmanJob
     */
    public function setDataCallback($callback)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('Invalid callback passed to Brood\Gearman\LocalGearmanJob::setDataCallback()');
        }

        $this->dataCallback = $callback;
        return $this;
    }

    public function sendData($data)
    {
        return $this->triggerCallback($this->dataCallback, $data);
    }

    /**
     * Set a callback for when the job sends status
     *
     * setStatusCallback() is a LocalGearmanJob extension; the callback is
     * normally set on the GearmanClient and is triggered by gearman.
     *
     * @param callback $callback
     * @return LocalGearmanJob
     */
    public function setStatusCallback($callback)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('Invalid callback passed to Brood\Gearman\LocalGearmanJob::setStatusCallback()');
        }

        $this->statusCallback = $callback;
        return $this;
    }

    public function sendStatus($numerator, $denominator)
    {
        return $this->triggerCallback($this->statusCallback, array($numerator, $denominator), true);
    }

    /**
     * Set a callback for when the job sends a warning
     *
     * setWarningCallback() is a LocalGearmanJob extension; the callback is
     * normally set on the GearmanClient and is triggered by gearman.
     *
     * @param callback $callback
     * @return LocalGearmanJob
     */
    public function setWarningCallback($callback)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('Invalid callback passed to Brood\Gearman\LocalGearmanJob::setWarningCallback()');
        }

        $this->warningCallback = $callback;
        return $this;
    }

    public function sendWarning($warning)
    {
        return $this->triggerCallback($this->warningCallback, $warning);
    }

    protected function triggerCallback($callback, $data = '', $isStatus = false)
    {
        $task = new LocalGearmanTask();
        $task->setFunctionName($this->functionName);
        $task->setJobHandle('H.localgearmanjob.' . self::$serial);
        if ($isStatus) {
            $task->setTaskNumerator($data[0]);
            $task->setTaskDenominator($data[1]);
        } else {
            $task->setData($data);
        }
        call_user_func($callback, $task);
        return true;
    }

    public function sendException($exception)
    {
        // as of 0.8.0 this doesn't do anything, so we'll do the same
        return true;
    }

    public function handle()
    {
        // as of 0.8.0, parent class just returns an empty string, so we'll do the same
        return '';
    }

    public function unique()
    {
        // as of 0.8.0, parent class just returns an empty string, so we'll do the same
        return '';
    }
}
