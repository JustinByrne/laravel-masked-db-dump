<?php

namespace BeyondCode\LaravelMaskedDumper\Console;

use BeyondCode\LaravelMaskedDumper\LaravelMaskedDump;
use Illuminate\Console\Command;

class DumpDatabaseCommand extends Command
{
    protected $signature = 'db:masked-dump {output} {--definition=default} {--gzip}';

    protected $description = 'Create a new database dump';

    public function handle()
    {
        $definition = config('masked-dump.' . $this->option('definition'));
        $definition = is_callable($definition) ? call_user_func($definition) : $definition;
        $definition->load();

        $this->output->info('Starting Database dump');

        $dumper = new LaravelMaskedDump(
            $definition,
            $this->output,
            $this->argument('output'),
            $this->option('gzip'),
        );

        $dumper->dump();
    }
}
