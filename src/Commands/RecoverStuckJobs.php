<?php

namespace Aloware\FairQueue\Commands;

use Aloware\FairQueue\Interfaces\RepositoryInterface;
use Illuminate\Console\Command;

class RecoverStuckJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fair-queue:recover-stuck-jobs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recovers stuck jobs';

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
        $this->info('> recovering stuck jobs');

        $count = $this->repository->recoverStuckJobs();

        $this->info(sprintf('> %d stuck jobs recovered', $count));

        return 0;
    }
}
