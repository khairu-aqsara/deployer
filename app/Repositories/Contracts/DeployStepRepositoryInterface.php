<?php declare(strict_types=1);

namespace REBELinBLUE\Deployer\Repositories\Contracts;

use Illuminate\Database\Eloquent\Model;

interface DeployStepRepositoryInterface
{
    /**
     * @param array $fields
     *
     * @return Model
     */
    public function create(array $fields): Model;
}
