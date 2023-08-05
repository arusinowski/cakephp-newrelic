<?php
declare(strict_types=1);

namespace NewRelic\Traits;

use Cake\Command\Command;
use Cake\Console\CommandInterface;
use NewRelic\Lib\NewRelic;
use Cake\Http\ServerRequest;
use Exception;

/**
 * NewRelic Trait
 */
trait NewRelicTrait
{
	/**
	 * The transaction name to use
	 *
	 * @var string
	 */
	protected string $_newrelicTransactionName;

    /**
     * Set the transaction name
     *
     * If `$name` is a Command instance, the name will
     * automatically be derived based on best practices
     *
     * @param \Cake\Console\CommandInterface|\Cake\Http\ServerRequest $argument
     */
	public function setName(CommandInterface|ServerRequest $argument): void
    {
		$name = "";
		if ($argument instanceof Command) {
			$name = $this->_deriveNameFromCommand($argument);
		}
		if ($argument instanceof ServerRequest) {
			$name = $this->_deriveNameFromRequest($argument);
		}

		$this->_newrelicTransactionName = $name;
	}

	/**
	 * Get the name
	 *
	 * @return string
	 */
	public function getName(): string
    {
		return $this->_newrelicTransactionName;
	}

	/**
	 * Change the application name
	 *
	 * @param string $name
	 * @return void
	 */
	public function applicationName(string $name): void
    {
		NewRelic::applicationName($name);
	}

	/**
	 * Start a NewRelic transaction
	 *
	 * @param string|null $name
	 * @return void
	 */
	public function start(?string $name = null): void
    {
		NewRelic::start($this->_getTransactionName($name));
	}

    /**
     * Stop a transaction
     *
     * @param bool $ignore
     * @return void
     */
	public function stop(bool $ignore = false): void
    {
		NewRelic::stop($ignore);
	}

	/**
	 * Ignore current transaction
	 *
	 * @return void
	 */
	public function ignoreTransaction(): void
    {
		NewRelic::ignoreTransaction();
	}

	/**
	 * Ignore current apdex
	 *
	 * @return void
	 */
	public function ignoreApdex(): void
    {
		NewRelic::ignoreApdex();
	}

	/**
	 * Add custom parameter to transaction
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function parameter(string $key, mixed $value): void
    {
		NewRelic::parameter($key, $value);
	}

    /**
     * Add custom metric
     *
     * @param string $key
     * @param float $value
     * @return void
     * @throws \Exception
     */
	public function metric(string $key, float $value): void
    {
		NewRelic::metric($key, $value);
	}

	/**
	 * capture params
	 *
	 * @param boolean $capture
	 * @return void
	 */
	public function captureParams(bool $capture): void
    {
		NewRelic::captureParams($capture);
	}

	/**
	 * Add custom tracer method
	 *
	 * @param string $method
	 */
	public function addTracer(string $method): void
    {
		NewRelic::addTracer($method);
	}

	/**
	 * Set user attributes
	 *
	 * @param string $user
	 * @param string $account
	 * @param string $product
	 * @return void
	 */
	public function user(string $user, string $account, string $product): void
    {
		NewRelic::user($user, $account, $product);
	}

	/**
	 * Send an exception to New Relic
	 *
	 * @param \Exception $e
	 * @return void
	 */
	public function sendException(Exception $e): void
    {
		NewRelic::sendException($e);
	}

	/**
	 * Get transaction name
	 *
	 * @param string $name
	 * @return string
	 */
	protected function _getTransactionName(string $name): string
    {
		if ($name) {
			return $name;
		}

		return $this->_newrelicTransactionName;
	}

	/**
	 * Derive the transaction name
	 *
	 * @param \Cake\Command\Command $command
	 * @return string
	 */
	protected function _deriveNameFromCommand(Command $command): string
    {
        return $command->getName();
	}

	/**
	 * Compute name based on request information
	 *
	 * @param  \Cake\Http\ServerRequest $request
	 * @return string
	 */
	protected function _deriveNameFromRequest(ServerRequest $request): string
    {
		$name = [];
		if ($request->getParam('prefix')) {
			$name[] = $request->getParam('prefix');
		}

		if ($request->getParam('plugin')) {
			$name[] = $request->getParam('plugin');
		}

		$name[] = $request->getParam('controller');
		$name[] = $request->getParam('action');

		$name = join('/', $name);

		if ($request->getParam('ext')) {
			$name .= '.' . $request->getParam('ext');
		}

		return $name;
	}
}
