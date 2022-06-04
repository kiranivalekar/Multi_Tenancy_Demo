<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Config\Repository;
use Illuminate\Database\DatabaseManager as BaseDatabaseManager;
use Stancl\Tenancy\Contracts\TenantWithDatabase;

class TenantOtherConnection
{
    /** @var BaseDatabaseManager */
    protected $database;

    /** @var Repository */
    protected $config;
    protected $allConnection;

    public function __construct(BaseDatabaseManager $database, Repository $config)
    {
        $this->allConnection = config('database.connections');
        $this->database = $database;
        $this->config = $config;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->connectToOtherTenantConnection(tenant());
        return $next($request);
    }

    /**
     * Connect to a tenant's database.
     */
    public function connectToOtherTenantConnection(TenantWithDatabase $tenant)
    {
        $this->purgeTenantConnection();
        $this->createTenantConnection($tenant);
    }

    /**
     * Create the tenant database connection.
     */
    public function createTenantConnection(TenantWithDatabase $tenant)
    {
        foreach ($this->allConnection as $key => $name) {
            if(!in_array($key, ['sqlite', 'mysql', 'pgsql', 'sqlsrv'])){
                $this->config['database.connections.'.$key.'.database'] = $tenant->database()->connection()['database'];
            }
        }
    }

    /**
     * Purge the tenant database connection.
     */
    public function purgeTenantConnection()
    {
        foreach ($this->allConnection as $key => $name) {
            if(!in_array($key, ['sqlite', 'mysql', 'pgsql', 'sqlsrv'])){
                if (array_key_exists($key, $this->database->getConnections())) {
                    $this->database->purge($key);
                }
            }
        }
        // unset($this->config['database.connections.read']);
    }
}
