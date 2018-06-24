<?php declare(strict_types=1);

namespace REBELinBLUE\Deployer\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\DispatchesJobs;
use REBELinBLUE\Deployer\Jobs\TestServerConnection;
use REBELinBLUE\Deployer\Repositories\Contracts\ServerRepositoryInterface;
use REBELinBLUE\Deployer\Server;

/**
 * The server repository.
 */
class EloquentServerRepository extends EloquentRepository implements ServerRepositoryInterface
{
    use DispatchesJobs;

    /**
     * EloquentServerRepository constructor.
     *
     * @param Server $model
     */
    public function __construct(Server $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(): Collection
    {
        return $this->model
                    ->orderBy('name')
                    ->get();
    }

    /**
     * Creates a new instance of the model.
     *
     * @param array $fields
     *
     * @return Model
     */
    public function create(array $fields): Model
    {
        // Get the current highest server order
        $max = $this->model->where('project_id', $fields['project_id'])
                           ->orderBy('order', 'DESC')
                           ->first();

        $order = 0;
        if (isset($max)) {
            $order = $max->order + 1;
        }

        $fields['order'] = $order;

        $add_commands = false;
        if (isset($fields['add_commands'])) {
            $add_commands = $fields['add_commands'];
            unset($fields['add_commands']);
        }

        $server = $this->model->create($fields);

        // Add the server to the existing commands
        if ($add_commands) {
            foreach ($server->project->commands as $command) {
                $command->servers()->attach($server->id);
            }
        }

        return $server;
    }

    /**
     * @param int $server_id
     */
    public function queueForTesting(int $server_id)
    {
        $server = $this->getById($server_id);

        if (!$server->isTesting()) {
            $server->status = Server::TESTING;
            $server->save();

            $this->dispatch(new TestServerConnection($server));
        }
    }

    /**
     * @param string $name
     *
     * @return Model
     */
    public function queryByName(string $name): Model
    {
        return $this->model
                    ->where('name', 'LIKE', "%{$name}%")
                    ->get();
    }
}
