<?php

namespace Aloware\FairQueue\Commands;

use Aloware\FairQueue\Interfaces\RepositoryInterface;
use Illuminate\Console\Command;

class RemoveExtraHorizonSignals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fair-queue:remove-extra-horizon-signals';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove extra Horizon signals';

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
        $this->info('> removing Horizon extra signals');

        $count = $this->repository->removeExtraHorizonSignals();

        $this->info(sprintf('> %d extra Horizon signals removed', $count));

        return 0;
    }
}
