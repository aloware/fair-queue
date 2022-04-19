<?php

namespace Aloware\FairQueue\Commands;

use Aloware\FairQueue\Interfaces\RepositoryInterface;
use Illuminate\Console\Command;

class RetryFailedJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fair-queue:retry-failed-jobs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retries all failed jobs.';

    /**
     * @var RepositoryInterface
     */
    private $repository;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(RepositoryInterface $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $count = $this->repository->retryFailedJobs();

        $this->info(sprintf('> %d jobs are back to the queue', $count));

        return 0;
    }
}
