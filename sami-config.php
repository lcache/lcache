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

// List the branches to build documentation for here.  We will use the
// multi-version documentation structure from the beginning, even when
// we have but one branch, so that our documentation links do not change
// when we add a second branch.
$versions = GitVersionCollection::create($dir)
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
