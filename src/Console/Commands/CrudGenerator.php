<?php

namespace Orand\aocrudgenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;

class CrudGenerator extends Command
{
    protected $files;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crud:generator {name : Class (singular) for example User}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create CRUD operations';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        $this->files = $files;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->argument('name');
        $arr = [];
        $this->model($name);
        $this->controller($name);
        $this->migration($name);
        File::append(base_path('routes/api.php'), "\n\nRoute::group(['prefix' => '" . strtolower($name) . "'], function() {\n\tcreateCrudRoute('" . $name . "Controller');\n});");
        return $arr;
    }

    protected function getStub($type)
    {
        return file_get_contents(resource_path("stubs/Base$type.stub"));
    }

    protected function model($name)
    {
        $path = base_path() . "/app/Models/{$name}.php";
        if ($this->fileExists($path)) {
            $this->error('Model already exists!');
            return ['create' => false , 'message' => 'Controller already exists!'];
        }
        $modelTemplate = str_replace(
            ['{{modelName}}', '{{table}}'],
            [$name, strtolower(str_plural($name))],
            $this->getStub('Model')
        );

        file_put_contents(app_path("/Models/{$name}.php"), $modelTemplate);
        $this->info('Model create successfully!');
        return ['create' => true , 'message' => 'Model create successfully!'];
    }

    protected function controller($name)
    {
        $path = base_path() . "/app/Http/Controllers/{$name}Controller.php";
        if ($this->fileExists($path)) {
            $this->error('Controller already exists!');
            return ['create' => false , 'message' => 'Controller already exists!'];
        }
        $controllerTemplate = str_replace(
            [
                '{{modelName}}',
                '{{modelNamePluralLowerCase}}',
                '{{modelNameSingularLowerCase}}'
            ],
            [
                $name,
                strtolower(str_plural($name)),
                strtolower($name)
            ],
            $this->getStub('Controller')
        );

        file_put_contents(app_path("/Http/Controllers/{$name}Controller.php"), $controllerTemplate);

        $this->info('Controller create successfully!');
        return ['create' => true , 'message' => 'Controller create successfully!'];
    }

    /**
     * @param $name
     */
    protected function migration($name)
    {
        if(Schema::hasTable(strtolower(str_plural($name)))) {
            $this->error('Migration already exists!');
            return;
        }

        Artisan::call('make:migration', [
            'name' => 'create_' . strtolower(str_plural($name)) . '_table'
        ]);
        $this->info('Migration create successfully!');
    }

    protected function fileExists($path) {
        return file_exists($path);
    }

}
