<?php
namespace Core;

Class Composer {

public static function postUpdate() {
	$path = __DIR__ . '/../../../../';
		$listado = [
			'app/Http/',
			'app/Http/Controllers/',
			'app/Http/Middleware',
			'app/Http/Nexus/',
			'app/Http/Nexus/Actions/',
			'app/Http/Nexus/Views/',
			'app/Library/',
			'app/Models/',
			'app/Scopes/',
			'app/Traits/',
			'cache/',
			'cache/data/',
			'cache/views/',
			'logs',
			'public/assets/',
			'public/assets/libs',
			'public/assets/css',
			'public/assets/js',
		];
		foreach($listado as $dir) {
			static::createIfNotExists($path . $dir);
		}
		$listado = [
			'public/assets/libs' => 'F',
			'public/assets' => 'I',
			'resources/views/helloworld' => 'F',
			'resources/views/library' => 'F',
			'resources/views/layouts' => 'I',
			'resources/views/panels' => 'I',
			'util' => 'F',
			'app/Models/Libro.php' => 'F',
			'app/Scopes/MultiTenantScope.php' => 'I',
			'app/Http/Controllers/LibraryController.php' => 'F',
			'app/Http/Controllers/HelloWorldController.php' => 'F',
			'app/Http/Controllers/Auth/LoginController.php' => 'I',
			'app/Auth.php' => 'I',
		];
		$pathGit = '/usr/bin/git';
		if(file_exists($pathGit)) {
			$tmp = $path . 'temporary/';
			exec('rm -rf ' . $tmp);
			if(!file_exists($tmp)) {
				mkdir($tmp);
			}
			$cmd = $pathGit . ' clone https://github.com/danccas/nexus.git ' . $tmp . 'nexus';
			exec($cmd);
			foreach($listado as $file => $method) {
				if($method == 'F') {
					$cmd = 'cp -r -v ' . $tmp . 'nexus/' . $file . ' ' . $path . $file;
					exec($cmd);
					echo "=> [FORCE] " . $cmd . "\n";
				} else {
					$cmd = 'cp -n -v ' . $tmp . 'nexus/' . $file . ' ' . $path . $file;
					exec($cmd);
					echo "=> [IGNORE] " . $cmd . "\n";
				}
			}
			exec('rm -rf ' . $tmp);
		}
	}
	private static function createIfNotExists($directory) {
		if(!file_exists($directory)) {
			echo "create: " . $directory . "\n";
		}
	}
}
