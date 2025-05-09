<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakePipelineCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:pipeline {name : The name of the pipeline class}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new pipeline class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Pipeline';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');

        // Handle nested directories in the name
        $className = $this->getClassName($name);

        // Ensure the name has Pipeline suffix
        if (! Str::endsWith($className, 'Pipeline')) {
            $className = $className.'Pipeline';
        }

        // Get directory and namespace parts
        [$directory, $namespace] = $this->getPiplineClassDirectory($name);

        // Create directory if it doesn't exist
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filePath = $directory.'/'.$className.'.php';

        // Check if file already exists
        if (File::exists($filePath)) {
            $this->error("Pipeline {$className} already exists!");

            return 1;
        }

        // Get the stub file content
        $stub = File::get($this->getStubPath());

        // Replace the placeholders
        $stub = str_replace(
            ['{{ namespace }}', '{{ class }}'],
            [$namespace, $className],
            $stub
        );

        // Create the file
        File::put($filePath, $stub);

        $this->info("Pipeline {$namespace}\\{$className} created successfully!");

        return 0;
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStubPath()
    {
        $customPath = base_path('stubs/pipeline.stub');

        return File::exists($customPath)
            ? $customPath
            : __DIR__.'/stubs/pipeline.stub';
    }

    /**
     * Get the class name from the input name.
     */
    protected function getClassName(string $name): string
    {
        return class_basename($name);
    }

    /**
     * Get the directory and namespace for the pipeline class.
     *
     * @return array [directory, namespace]
     */
    protected function getPiplineClassDirectory(string $name): array
    {
        $baseDirectory = app_path('Pipelines');
        $baseNamespace = 'App\\Pipelines';

        // Handle namespace directories
        $parts = explode('/', $name);
        $className = array_pop($parts);

        if (empty($parts)) {
            return [$baseDirectory, $baseNamespace];
        }

        // Convert directory separators to namespace format
        $subNamespace = implode('\\', $parts);
        $subDirectory = implode('/', $parts);

        return [
            $baseDirectory.'/'.$subDirectory,
            $baseNamespace.'\\'.$subNamespace,
        ];
    }
}
