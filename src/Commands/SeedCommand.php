<?php

namespace Seedster\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Database\ConnectionResolverInterface as Resolver;

class SeedCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'db:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the database with records';

    /**
     * The connection resolver instance.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * Create a new database seed command instance.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @return void
     */
    public function __construct(Resolver $resolver)
    {
        parent::__construct();

        $this->resolver = $resolver;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return 1;
        }

        $this->resolver->setDefaultConnection($this->getDatabase());

        list($command, $seeders) = [$this, $this->seeders()];

        $classInput = $this->input->getOption('class');
        if ('Database\Seeders\DatabaseSeeder' == $classInput) {
            $seeders->push($this->input->getOption('class'));
        } else {
            $seeders = collect([$this->input->getOption('class')]);
        }
        $seeders->each(function ($seeder) use ($command) {
            Model::unguarded(function () use ($seeder) {
                $this->getSeeder($seeder)->__invoke();
            });
        });

        $this->info('Database seeding completed successfully.');

        return 0;
    }

    /**
     * Get a seeder instance from the container.
     *
     * @param  string  $class
     * @return \Illuminate\Database\Seeder
     */
    protected function getSeeder($class)
    {
        $class = $this->laravel->make($class);

        return $class->setContainer($this->laravel)->setCommand($this);
    }

    /**
     * Get the name of the database connection to use.
     *
     * @return string
     */
    protected function getDatabase()
    {
        $database = $this->input->getOption('database');

        return $database ?: $this->laravel['config']['database.default'];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['class', null, InputOption::VALUE_OPTIONAL, 'The class name of the root seeder', 'Database\\Seeders\\DatabaseSeeder'],
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to seed'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        ];
    }

    /**
     * Retrieve all the registered seeders.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function seeders()
    {
        return app('seed.handler')->seeders();
    }
}
