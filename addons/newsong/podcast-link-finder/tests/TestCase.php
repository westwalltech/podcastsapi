<?php

namespace Newsong\PodcastLinkFinder\Tests;

use Newsong\PodcastLinkFinder\ServiceProvider;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;
}
