<?php

namespace App\Console\Commands;

use App\Support\DocumentAi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ProcessDocument extends Command
{
    protected $signature = 'process-document';

    protected $description = 'Process a test pdf document.';

    /**
     * @throws \Google\ApiCore\ValidationException
     * @throws \Google\ApiCore\ApiException
     * @throws \Exception
     */
    public function handle(): int
    {
        $tables = (new DocumentAi)
            ->process(Storage::disk('public')->get('test.pdf'))
            ->extractTable();

        dd(collect($tables)->flatten(1));

        return self::SUCCESS;
    }
}
