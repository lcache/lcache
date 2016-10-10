<?php

use Sami\Sami;
use Sami\RemoteRepository\GitHubRemoteRepository;
use Sami\Version\GitVersionCollection;
use Symfony\Component\Finder\Finder;

$dir = __DIR__ . '/src';

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->in($dir)
;

// generate documentation for all v2.0.* tags, the 2.0 branch, and the master one
$versions = GitVersionCollection::create($dir)
    ->addFromTags('v0.*.0')
    ->addFromTags('v*.0.0')
    ->add('master', 'master branch')
;

return new Sami($iterator, array(
    'theme'                => 'default',
    'versions'             => $versions,
    'title'                => 'LCache API',
    'build_dir'            => __DIR__.'/docs/api/%version%',
    'cache_dir'            => __DIR__.'/.sami-cache/%version%',
    'remote_repository'    => new GitHubRemoteRepository('lcache/lcache', __DIR__),
    'default_opened_level' => 2,
));
