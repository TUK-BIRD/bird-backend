<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeServiceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:service {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new service class';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $name = $this->argument('name');
        $className = Str::studly($name);
        $path = app_path("Services/{$className}.php");

        // Services 디렉토리 생성
        File::ensureDirectoryExists(app_path('Services'));

        // 템플릿 생성
        $stub = $this->getStub();
        $content = str_replace('{{ class }}', $className, $stub);

        File::put($path, $content);

        $this->info("Service created: {$path}");
    }

    private function getStub()
    {
        return '
<?php

namespace App\Services;

use Illuminate\Support\ServiceProvider;

class {{ class }}
{
    public function __construct()
    {
    }

}';
    }
}
