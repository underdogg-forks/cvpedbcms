<?php namespace cms\Console\Commands;

use Pingpong\Modules\Module;
use Pingpong\Modules\Publishing\AssetPublisher;
use Pingpong\Modules\Publishing\LangPublisher;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use cms\Infrastructure\Abstractions\Console\CommandAbstract;

/**
 * Class ModulesPublishCommand
 * @package cms\Console\Commands
 */
class ModulesPublishCommand extends CommandAbstract
{

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'cms:module:publish';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Publish a module\'s assets to the application';

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		parent::fire();

		if ($name = $this->argument('module'))
		{
			return $this->publish($name);
		}

		$this->publishAll();
	}

	/**
	 * Publish assets from all modules.
	 */
	public function publishAll()
	{
		foreach ($this->laravel['modules']->enabled() as $module)
		{
			$this->publish($module);
		}
	}

	/**
	 * Publish assets from the specified module.
	 *
	 * @param string $name
	 */
	public function publish($name)
	{
		if ($name instanceof Module)
		{
			$module = $name;
		}
		else
		{
			$module = $this->laravel['modules']->findOrFail($name);
		}

		with(new AssetPublisher($module))
			->setRepository($this->laravel['modules'])
			->setConsole($this)
			->publish();

		$this->line("<info>Published</info>: {$module->getStudlyName()}");
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return [
			['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
		];
	}

}