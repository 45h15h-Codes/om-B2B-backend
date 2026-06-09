<?php

namespace App\Jobs;

use App\Models\BackgroundJob;
use App\Models\Diamond;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessDiamondUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $diamondData;
    public int $backgroundJobId;

    /**
     * Create a new job instance.
     *
     * @param  array  $diamondData
     * @param  int  $backgroundJobId
     * @return void
     */
    public function __construct(array $diamondData, int $backgroundJobId)
    {
        $this->diamondData = $diamondData;
        $this->backgroundJobId = $backgroundJobId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $backgroundJob = BackgroundJob::findOrFail($this->backgroundJobId);
        $backgroundJob->markProcessing();

        Diamond::create($this->diamondData);

        $backgroundJob->markSuccess();
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception)
    {
        $backgroundJob = BackgroundJob::find($this->backgroundJobId);

        if ($backgroundJob) {
            $backgroundJob->markFailed($exception->getMessage());
        }
    }
}
