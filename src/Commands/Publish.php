<?php

namespace Aloware\FairQueue\Commands;

use Aloware\FairQueue\Exceptions\SampleNotFoundException;
use Aloware\FairQueue\Interfaces\RepositoryInterface;
use Illuminate\Console\Command;
use Symfony\Component\Console\Formatter\NullOutputFormatter;

class Publish extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fair-queue:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish assets to public directory.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Publishing assets...');

        $this->call('vendor:publish', [
            '--tag'   => 'public',
            '--force' => true
        ]);


        $this->info('Publish config file...');

        $this->call('vendor:publish', [
            '--tag' => 'fairqueue-config'
        ]);

        return 0;
    }
}
