<?php

declare(strict_types=1);

namespace App\Http\Controllers\V2\Admin;

use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Contracts\WorkloadRepository;
use MongoDB\Laravel\Connection as MongoConnection;

class SystemHealthController extends Controller
{
    public function __construct(
        private readonly JobRepository $jobRepository,
        private readonly WorkloadRepository $workloadRepository,
        private readonly SupervisorRepository $supervisorRepository,
        private readonly MasterSupervisorRepository $masterSupervisorRepository,
    ) {
        $this->middleware(['role:'.RolesEnum::Engineering], ['only' => ['index', 'databases', 'redis', 'queues']]);
    }

    public function index(Request $request): Response
    {
        // Render page quickly with minimal props; sections will be fetched progressively via AJAX
        return Inertia::render('Admin/SystemHealth/Index', [
            'app' => $this->getAppInfo(),
            'links' => $this->getLinks(),
        ]);
    }

    public function databases(Request $request): JsonResponse
    {
        return response()->json([
            'mysql' => $this->checkMysql('mysql'),
            'mysql_read' => $this->checkMysql('mysql_read', true),
            'mongodb' => $this->checkMongo('mongodb'),
            'alfredchatmongo' => $this->checkMongo('alfredchatmongo'),
        ]);
    }

    public function redis(Request $request): JsonResponse
    {
        return response()->json($this->checkRedis());
    }

    public function queues(Request $request): JsonResponse
    {
        return response()->json($this->checkQueues());
    }

    private function getAppInfo(): array
    {
        return [
            'env' => App::environment(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'queue_connection' => config('queue.default'),
            'redis_client' => config('database.redis.client'),
            'timezone' => config('app.timezone'),
        ];
    }

    private function getLinks(): array
    {
        return [
            'horizon' => URL::to('/'.trim((string) config('horizon.path'), '/')),
        ];
    }

    private function checkMysql(string $connection, bool $isReplica = false): array
    {
        $startedAt = microtime(true);
        $ok = false;
        $error = null;
        $version = null;
        $database = null;
        $readOnly = null;
        $serverId = null;
        $timezone = null;
        $now = null;
        $replication = null;

        try {
            $conn = DB::connection($connection);
            $conn->select('SELECT 1');

            $meta = $conn->selectOne('select @@global.read_only as ro, @@server_id as sid, @@system_time_zone as tz, now() as now');
            if ($meta) {
                $readOnly = isset($meta->ro) ? (bool) $meta->ro : null;
                $serverId = isset($meta->sid) ? (string) $meta->sid : null;
                $timezone = isset($meta->tz) ? (string) $meta->tz : null;
                $now = isset($meta->now) ? (string) $meta->now : null;
            }

            $row = $conn->selectOne('select version() as v, database() as d');
            if ($row) {
                $version = $row->v ?? null;
                $database = $row->d ?? null;
            }

            $ok = true;

            if ($isReplica) {
                $replication = $this->getMysqlReplicationStatus($connection);
            }
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        } finally {
            $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);
        }

        return [
            'connection' => $connection,
            'ok' => $ok,
            'latency_ms' => $latencyMs,
            'version' => $version,
            'database' => $database,
            'read_only' => $readOnly,
            'server_id' => $serverId,
            'timezone' => $timezone,
            'now' => $now,
            'replication' => $replication,
            'error' => $error,
        ];
    }

    private function getMysqlReplicationStatus(string $connection): ?array
    {
        try {
            $conn = DB::connection($connection);

            // MySQL < 8.0.22
            $row = $conn->selectOne('SHOW SLAVE STATUS');
            if ($row) {
                $data = (array) $row;

                return [
                    'using' => 'SHOW SLAVE STATUS',
                    'io_running' => $data['Slave_IO_Running'] ?? null,
                    'sql_running' => $data['Slave_SQL_Running'] ?? null,
                    'seconds_behind' => isset($data['Seconds_Behind_Master']) ? (int) $data['Seconds_Behind_Master'] : null,
                    'relay_master_log_file' => $data['Relay_Master_Log_File'] ?? null,
                    'exec_master_log_pos' => $data['Exec_Master_Log_Pos'] ?? null,
                    'ok' => ($data['Slave_IO_Running'] ?? '') === 'Yes' && ($data['Slave_SQL_Running'] ?? '') === 'Yes',
                ];
            }
        } catch (\Throwable) {
            // ignore and try replica syntax
        }

        try {
            $conn = DB::connection($connection);

            // MySQL >= 8.0.22
            $row = $conn->selectOne('SHOW REPLICA STATUS');
            if ($row) {
                $data = (array) $row;

                return [
                    'using' => 'SHOW REPLICA STATUS',
                    'io_running' => $data['Replica_IO_Running'] ?? null,
                    'sql_running' => $data['Replica_SQL_Running'] ?? null,
                    'seconds_behind' => isset($data['Seconds_Behind_Source']) ? (int) $data['Seconds_Behind_Source'] : null,
                    'relay_source_log_file' => $data['Relay_Source_Log_File'] ?? null,
                    'exec_source_log_pos' => $data['Exec_Source_Log_Pos'] ?? null,
                    'ok' => ($data['Replica_IO_Running'] ?? '') === 'Yes' && ($data['Replica_SQL_Running'] ?? '') === 'Yes',
                ];
            }
        } catch (\Throwable) {
            // both failed; return null info
        }

        return null;
    }

    private function checkMongo(string $connection = 'mongodb'): array
    {
        $ok = false;
        $error = null;
        $startedAt = microtime(true);

        try {
            // Check if MongoDB extension is available
            if (! extension_loaded('mongodb')) {
                throw new \RuntimeException('MongoDB PHP extension not available');
            }

            // Check if connection is configured
            if (! config("database.connections.{$connection}")) {
                throw new \RuntimeException("Mongo connection '{$connection}' not configured");
            }

            // Simple connection test - just try to get the connection
            /** @var MongoConnection $mongo */
            $mongo = DB::connection($connection);

            if ($mongo instanceof MongoConnection) {
                // Simple ping test
                $mongo->ping();
                $ok = true;
            } else {
                throw new \RuntimeException('Mongo connection type mismatch');
            }
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        } finally {
            $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);
        }

        return [
            'connection' => $connection,
            'ok' => $ok,
            'latency_ms' => $latencyMs,
            'error' => $error,
        ];
    }

    private function checkRedis(): array
    {
        $ok = false;
        $error = null;
        $normalized = [
            'Server' => null,
            'Clients' => null,
            'Memory' => null,
        ];
        $computed = [
            'uptime_in_seconds' => null,
            'role' => null,
            'used_memory_pct' => null,
            'hit_rate' => null,
        ];
        $startedAt = microtime(true);

        try {
            $conn = Redis::connection('default');
            $pong = $conn->client()->ping();
            $ok = $pong ? true : false;
            $info = $conn->client()->info();

            if (isset($info['Server']) || isset($info['Clients']) || isset($info['Memory'])) {
                $normalized['Server'] = $info['Server'] ?? [];
                $normalized['Clients'] = $info['Clients'] ?? [];
                $normalized['Memory'] = $info['Memory'] ?? [];
                $computed['uptime_in_seconds'] = ($info['Server']['uptime_in_seconds'] ?? null) ? (int) $info['Server']['uptime_in_seconds'] : null;
                $computed['role'] = $info['Replication']['role'] ?? null;
                $used = isset($info['Memory']['used_memory']) ? (int) $info['Memory']['used_memory'] : null;
                $max = isset($info['Memory']['maxmemory']) ? (int) $info['Memory']['maxmemory'] : null;
                $computed['used_memory_pct'] = ($used && $max && $max > 0) ? round(($used / $max) * 100, 1) : null;
                $hits = isset($info['Stats']['keyspace_hits']) ? (int) $info['Stats']['keyspace_hits'] : null;
                $miss = isset($info['Stats']['keyspace_misses']) ? (int) $info['Stats']['keyspace_misses'] : null;
                $computed['hit_rate'] = ($hits !== null && $miss !== null && ($hits + $miss) > 0)
                    ? round(($hits / ($hits + $miss)) * 100, 1)
                    : null;
            } else {
                // phpredis flat keys
                $normalized['Server'] = [
                    'redis_version' => $info['redis_version'] ?? null,
                ];
                $normalized['Clients'] = [
                    'connected_clients' => $info['connected_clients'] ?? null,
                ];
                $normalized['Memory'] = [
                    'used_memory_human' => $info['used_memory_human'] ?? null,
                ];
                $computed['uptime_in_seconds'] = isset($info['uptime_in_seconds']) ? (int) $info['uptime_in_seconds'] : null;
                $computed['role'] = $info['role'] ?? null;
                $used = isset($info['used_memory']) ? (int) $info['used_memory'] : null;
                $max = isset($info['maxmemory']) ? (int) $info['maxmemory'] : null;
                $computed['used_memory_pct'] = ($used && $max && $max > 0) ? round(($used / $max) * 100, 1) : null;
                $hits = isset($info['keyspace_hits']) ? (int) $info['keyspace_hits'] : null;
                $miss = isset($info['keyspace_misses']) ? (int) $info['keyspace_misses'] : null;
                $computed['hit_rate'] = ($hits !== null && $miss !== null && ($hits + $miss) > 0)
                    ? round(($hits / ($hits + $miss)) * 100, 1)
                    : null;
            }
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        } finally {
            $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);
        }

        return [
            'ok' => $ok,
            'latency_ms' => $latencyMs,
            'server' => $normalized['Server'],
            'clients' => $normalized['Clients'],
            'memory' => $normalized['Memory'],
            'computed' => $computed,
            'error' => $error,
        ];
    }

    private function checkQueues(): array
    {
        $result = [
            'horizon' => [
                'ok' => false,
                'error' => null,
                'pending' => null,
                'completed' => null,
                'failed' => null,
                'recent_failed' => null,
                'workload' => [],
                'supervisors' => [],
                'masters' => [],
                'supervisors_count' => 0,
                'masters_count' => 0,
                'total_processes' => 0,
            ],
        ];

        try {
            $result['horizon']['pending'] = $this->jobRepository->countPending();
            $result['horizon']['completed'] = $this->jobRepository->countCompleted();
            $result['horizon']['failed'] = $this->jobRepository->countFailed();
            $result['horizon']['recent_failed'] = $this->jobRepository->countRecentlyFailed();
            $result['horizon']['workload'] = $this->workloadRepository->get();
            $result['horizon']['supervisors'] = $this->supervisorRepository->all();
            $result['horizon']['masters'] = $this->masterSupervisorRepository->all();
            $result['horizon']['supervisors_count'] = is_array($result['horizon']['supervisors']) ? count($result['horizon']['supervisors']) : 0;
            $result['horizon']['masters_count'] = is_array($result['horizon']['masters']) ? count($result['horizon']['masters']) : 0;
            $result['horizon']['total_processes'] = collect($result['horizon']['workload'])->sum('processes');

            $result['horizon']['ok'] = true;
        } catch (\Throwable $e) {
            $result['horizon']['error'] = $e->getMessage();
        }

        return $result;
    }
}
