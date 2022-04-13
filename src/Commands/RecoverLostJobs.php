<?php

namespace Aloware\FairQueue\Commands;

use Aloware\FairQueue\Interfaces\RepositoryInterface;
use Illuminate\Console\Command;

class RecoverLostJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fair-queue:recover-lost-jobs {age=300}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command tries to recover jobs which have been on in-progress mode for a long time';

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
        $age = intval($this->argument('age'));

        $this->info(sprintf('> recovering dusted jobs older than %d seconds', $age));

        $count = $this->repository->recoverLost($age);

        $this->info(sprintf('> %d jobs recovered', $count));

        return 0;
    }
}
