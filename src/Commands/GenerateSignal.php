<?php

namespace Aloware\FairQueue\Commands;

use Aloware\FairQueue\Exceptions\SampleNotFoundException;
use Aloware\FairQueue\Interfaces\RepositoryInterface;
use Illuminate\Console\Command;

class GenerateSignal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fair-queue:generate-signal {queue} {count=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Use this command to generate fake signals for the queue.';

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
        $queue = $this->argument('queue');
        $count = intval($this->argument('count'));

        try {
            $this->repository->generateFakeSignals($queue, $count);
            $this->info(sprintf('> %d signals generated on queue "%s"', $count, $queue));
        } catch (SampleNotFoundException $e) {
            $this->error(sprintf('> no sample signal found for queue "%s"', $queue));
        }

        return 0;
    }
}
