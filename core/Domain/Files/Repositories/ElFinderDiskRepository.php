<?php namespace Core\Domain\Files\Repositories;

use Illuminate\Support\Facades\Auth;
use Illuminate\Filesystem\FilesystemAdapter;
use CVEPDB\Settings\Facades\Settings;
use Core\Domain\Files\Repositories\DiskRepository as CoreDiskRepository;

/**
 * Class ElFinderDiskRepository
 * @package Core\Domain\Files\Repositories
 */
class ElFinderDiskRepository extends CoreDiskRepository
{

	/**
	 * Get elfinder.roots settings.
	 * https://github.com/Studio-42/elFinder/wiki/Multiple-Roots
	 *
	 * @param string $environment_reference
	 *
	 * @return mixed
	 */
	public function getElFinderRoots($environment_reference = null)
	{
		return Settings::get('elfinder.roots', [], $environment_reference);
	}

	/**
	 * Allow to mount an elFinder disk based on a directory path.
	 *
	 * @param string $new_directory The directory path
	 * @param string $environment_reference
	 */
	public function mountElFinderDirectory(
		$new_directory,
		$environment_reference = null
	)
	{
		$directories = Settings::get('elfinder.dir', [], $environment_reference);

		if (empty($directories))
		{
			$directories = [];
		}
		else if (is_string($directories))
		{
			$directories = [$directories];
		}

		Settings::set(
			'elfinder.dir',
			$directories + [$new_directory],
			$environment_reference
		);
	}

	/**
	 * Allow to unmount an elFinder disk based on a directory path.
	 *
	 * @param string $directory The directory path
	 * @param string $environment_reference
	 */
	public function unmountElFinderDirectory($directory, $environment_reference = null)
	{
		$directories = Settings::get('elfinder.dir', [], $environment_reference);
		unset($directories[array_search($directory, $directories)]);

		Settings::forget('elfinder.dir', $environment_reference);
		Settings::set('elfinder.dir', $directories, $environment_reference);
	}

	/**
	 * Get the list of all directories path based disks.
	 *
	 * @param null $environment_reference
	 *
	 * @return array
	 */
	public function getElFinderDirectories($environment_reference = null)
	{
		return Settings::get('elfinder.dir', [], $environment_reference);
	}

	/**
	 * Return the list of mountable directories available for elFinder.
	 * The list is required by the elFinder connector.
	 *
	 * @param null $environment_reference
	 *
	 * @return array
	 */
	public function getMountableElFinderDirectoriesList($environment_reference = null)
	{
		$roots = [];
		$dirs = $this->getElFinderDirectories($environment_reference);

		foreach ($dirs as $dir)
		{
			$roots[] = [
				'driver'        => 'LocalFileSystem',
				'path'          => public_path($dir),
				'URL'           => url($dir),
				'accessControl' => 'Module\Files\Access\DontShowFilesStartingWithDot::checkAccess'
			];
		}

		return $roots;
	}

	/**
	 * Allow to mount an elFinder disk based on file system disk configuration.
	 *
	 * @param string $disk_reference
	 * @param array  $options
	 * @param string $environment_reference
	 */
	public function mountElFinderDisk($disk_reference, $options, $environment_reference = null)
	{
		$disks = Settings::get('elfinder.disks', [], $environment_reference);

		Settings::set(
			'elfinder.disks',
			$disks + [$disk_reference => $options],
			$environment_reference
		);
	}

	/**
	 * Allow to unmount an elFinder disk based on file system disk configuration.
	 *
	 * @param string $disk_reference
	 * @param string $environment_reference
	 */
	public function unmountElFinderDisk($disk_reference, $environment_reference = null)
	{
		$disks = Settings::get('elfinder.disks', [], $environment_reference);
		unset($disks[$disk_reference]);

		Settings::forget('elfinder.disks', $environment_reference);
		Settings::set('elfinder.disks', $disks, $environment_reference);
	}

	/**
	 * Get the list of all disks based on file system disk configuration.
	 *
	 * @param string $environment_reference
	 *
	 * @return array
	 */
	public function getElFinderDisks($environment_reference = null)
	{
		return Settings::get('elfinder.disks', [], $environment_reference);
	}

	/**
	 * Return the list of mountable disks available for elFinder.
	 * The list is required by the elFinder connector.
	 *
	 * @param null $environment_reference
	 *
	 * @return array
	 */
	public function getMountableElFinderDisksList($environment_reference = null)
	{
		$roots = [];
		$disks = $this->getElFinderDisks($environment_reference);

		foreach ($disks as $key => $root)
		{
			if ($this->isElFinderDiskRestricted($root))
			{
				if ($this->isElFinderDiskCouldBeMount($root))
				{
					$roots[] = $this->elFinderMountDisk(
						$key,
						$root
					);
				}
			}
			else
			{
				if (is_string($root))
				{
					$key = $root;
					$root = [];
				}

				$roots[] = $this->elFinderMountDisk($key, $root);
			}
		}

		return $roots;
	}

	/**
	 * Check in disk settings to know if a disk is role or permission
	 * restricted.
	 *
	 * @param $root
	 *
	 * @return bool
	 */
	protected function isElFinderDiskRestricted($root)
	{
		return is_array($root)
		&& array_key_exists('access', $root)
		&& (
			array_key_exists('roles', $root['access'])
			|| array_key_exists('permissions', $root['access'])
		);
	}

	/**
	 * Check in disk settings to know if a disk could be use by the current
	 * user.
	 *
	 * @param $root
	 *
	 * @return bool
	 */
	protected function isElFinderDiskCouldBeMount($root)
	{
		return Auth::user()->hasRole($root['access']['roles'])
		|| Auth::user()->hasRole($root['access']['permissions']);
	}

	/**
	 * @param $key
	 * @param $root
	 */
	protected function elFinderMountDisk($disk_name, $options)
	{
		$access = 'Core\Domain\Files\Access\DontShowFilesStartingWithDot::checkAccess';

		if (
			is_array($options)
			&& array_key_exists('access', $options)
			&& array_key_exists('readonly', $options['access'])
			&& $options['access']['readonly']
		)
		{
			$access = 'Core\Domain\Files\Access\ReadOnly::checkAccess';
		}

		$disk = app('filesystem')->disk($disk_name);

		if ($disk instanceof FilesystemAdapter)
		{
			$defaults = [
				'driver'        => 'Flysystem',
				'filesystem'    => $disk->getDriver(),
				'alias'         => $disk_name,
				'accessControl' => $access
			];

			return array_merge($defaults, $options);
		}

		throw new \Exception('Disk : ' . $disk_name . ' unmountable!');
	}

}