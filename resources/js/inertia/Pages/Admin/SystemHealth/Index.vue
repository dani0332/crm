<template>
  <Head title="System Health" />
  <div class="p-6 space-y-6">
    <!-- Page Title -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">System Health</h1>
      <p class="text-sm text-gray-600 mt-1">
        Monitor system status, database connections, and queue performance
      </p>
    </div>

    <!-- Header KPIs -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="bg-white shadow rounded p-4">
        <div class="text-xs text-gray-500">Environment</div>
        <div class="text-lg font-semibold">{{ app.env }}</div>
      </div>
      <div class="bg-white shadow rounded p-4">
        <div class="text-xs text-gray-500">Laravel</div>
        <div class="text-lg font-semibold">{{ app.laravel_version }}</div>
      </div>
      <div class="bg-white shadow rounded p-4">
        <div class="text-xs text-gray-500">Queue</div>
        <div class="text-lg font-semibold">{{ app.queue_connection }}</div>
      </div>
      <div class="bg-white shadow rounded p-4">
        <div class="text-xs text-gray-500">Timezone</div>
        <div class="text-lg font-semibold">{{ app.timezone }}</div>
      </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
      <!-- Databases -->
      <section class="bg-white shadow rounded p-4">
        <h3 class="text-lg font-semibold mb-4">Databases</h3>

        <div v-if="loading.databases" class="space-y-3 text-sm text-gray-500">
          <div class="animate-pulse h-8 bg-gray-100 rounded"></div>
          <div class="animate-pulse h-8 bg-gray-100 rounded"></div>
          <div class="animate-pulse h-8 bg-gray-100 rounded"></div>
        </div>

        <div v-else class="space-y-4">
          <!-- MySQL main -->
          <div class="border rounded p-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span
                  :class="statusPill(databases.mysql.ok)"
                  class="px-2 py-0.5 rounded text-xs font-medium"
                  >{{ databases.mysql.ok ? 'OK' : 'DOWN' }}</span
                >
                <span class="font-medium">MySQL (main)</span>
              </div>
              <div class="text-sm text-gray-600">
                latency: {{ databases.mysql.latency_ms ?? '-' }} ms
              </div>
            </div>
            <div class="mt-2 grid grid-cols-2 gap-y-1 text-sm">
              <div class="text-gray-500">Version</div>
              <div>{{ databases.mysql.version || 'n/a' }}</div>
              <div class="text-gray-500">DB</div>
              <div>{{ databases.mysql.database || 'n/a' }}</div>
              <div class="text-gray-500">Server ID</div>
              <div>{{ databases.mysql.server_id || 'n/a' }}</div>
              <div class="text-gray-500">Read-only</div>
              <div>
                {{
                  databases.mysql.read_only === null
                    ? 'n/a'
                    : databases.mysql.read_only
                      ? 'Yes'
                      : 'No'
                }}
              </div>
            </div>
          </div>

          <!-- MySQL replica -->
          <div class="border rounded p-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span
                  :class="statusPill(databases.mysql_read.ok)"
                  class="px-2 py-0.5 rounded text-xs font-medium"
                  >{{ databases.mysql_read.ok ? 'OK' : 'DOWN' }}</span
                >
                <span class="font-medium">MySQL (read replica)</span>
              </div>
              <div class="text-sm text-gray-600">
                latency: {{ databases.mysql_read.latency_ms ?? '-' }} ms
              </div>
            </div>
            <div class="mt-2 grid grid-cols-2 gap-y-1 text-sm">
              <div class="text-gray-500">Version</div>
              <div>{{ databases.mysql_read.version || 'n/a' }}</div>
              <div class="text-gray-500">DB</div>
              <div>{{ databases.mysql_read.database || 'n/a' }}</div>
              <div class="text-gray-500">Server ID</div>
              <div>{{ databases.mysql_read.server_id || 'n/a' }}</div>
              <div class="text-gray-500">Read-only</div>
              <div>
                {{
                  databases.mysql_read.read_only === null
                    ? 'n/a'
                    : databases.mysql_read.read_only
                      ? 'Yes'
                      : 'No'
                }}
              </div>
            </div>
            <div
              v-if="databases.mysql_read.replication"
              class="mt-3 bg-gray-50 rounded p-3 text-sm"
            >
              <div class="font-medium mb-2">Replication</div>
              <div class="grid grid-cols-2 gap-y-1">
                <div class="text-gray-500">IO Running</div>
                <div>
                  {{ databases.mysql_read.replication.io_running || 'n/a' }}
                </div>
                <div class="text-gray-500">SQL Running</div>
                <div>
                  {{ databases.mysql_read.replication.sql_running || 'n/a' }}
                </div>
                <div class="text-gray-500">Lag (s)</div>
                <div>
                  {{ databases.mysql_read.replication.seconds_behind ?? 'n/a' }}
                </div>
              </div>
              <div
                class="mt-2 h-2 bg-gray-200 rounded overflow-hidden"
                v-if="databases.mysql_read.replication.seconds_behind !== null"
              >
                <div
                  :style="{
                    width: replicationBarWidth(
                      databases.mysql_read.replication.seconds_behind,
                    ),
                  }"
                  class="h-full"
                  :class="
                    replicationBarClass(
                      databases.mysql_read.replication.seconds_behind,
                    )
                  "
                ></div>
              </div>
            </div>
          </div>

          <!-- Mongo -->
          <div class="border rounded p-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span
                  :class="statusPill(databases.mongodb.ok)"
                  class="px-2 py-0.5 rounded text-xs font-medium"
                  >{{ databases.mongodb.ok ? 'OK' : 'DOWN' }}</span
                >
                <span class="font-medium">MongoDB</span>
              </div>
              <div class="text-sm text-gray-600">
                latency: {{ databases.mongodb.latency_ms ?? '-' }} ms
              </div>
            </div>
            <div class="mt-2 grid grid-cols-2 gap-y-1 text-sm">
              <div class="text-gray-500">Version</div>
              <div>{{ databases.mongodb.version || 'n/a' }}</div>
              <div class="text-gray-500">Connections</div>
              <div>{{ databases.mongodb.connections_current ?? 'n/a' }}</div>
            </div>
          </div>
        </div>
      </section>

      <!-- Redis & Queues -->
      <section class="bg-white shadow rounded p-4">
        <h3 class="text-lg font-semibold mb-4">Redis & Queues</h3>

        <!-- Redis -->
        <div class="border rounded p-3 mb-4">
          <div v-if="loading.redis" class="text-sm text-gray-500">
            Loading Redis...
          </div>
          <div v-else>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span
                  :class="statusPill(redis.ok)"
                  class="px-2 py-0.5 rounded text-xs font-medium"
                  >{{ redis.ok ? 'OK' : 'DOWN' }}</span
                >
                <span class="font-medium">Redis</span>
              </div>
              <div class="text-sm text-gray-600">
                latency: {{ redis.latency_ms ?? '-' }} ms
              </div>
            </div>
            <div class="mt-2 grid grid-cols-2 gap-y-1 text-sm">
              <div class="text-gray-500">Version</div>
              <div>{{ redis.server?.redis_version || 'n/a' }}</div>
              <div class="text-gray-500">Role</div>
              <div>{{ redis.computed?.role || 'n/a' }}</div>
              <div class="text-gray-500">Connected Clients</div>
              <div>{{ redis.clients?.connected_clients || 'n/a' }}</div>
              <div class="text-gray-500">Uptime (s)</div>
              <div>{{ redis.computed?.uptime_in_seconds ?? 'n/a' }}</div>
              <div class="text-gray-500">Cache Hit-Rate</div>
              <div>
                {{
                  redis.computed?.hit_rate === null
                    ? 'n/a'
                    : redis.computed.hit_rate + '%'
                }}
              </div>
            </div>
            <div class="mt-3">
              <div class="flex justify-between text-xs text-gray-500 mb-1">
                <span>Memory Usage</span>
                <span>{{
                  redis.computed?.used_memory_pct === null
                    ? 'n/a'
                    : redis.computed.used_memory_pct + '%'
                }}</span>
              </div>
              <div class="h-2 bg-gray-200 rounded overflow-hidden">
                <div
                  class="h-full bg-blue-500"
                  :style="{
                    width: (redis.computed?.used_memory_pct || 0) + '%',
                  }"
                ></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Horizon / Queues -->
        <div class="border rounded p-3">
          <div v-if="loading.queues" class="text-sm text-gray-500">
            Loading Horizon...
          </div>
          <div v-else>
            <div class="flex items-center justify-between mb-2">
              <div class="flex items-center gap-2">
                <span
                  :class="statusPill(queues.horizon.ok)"
                  class="px-2 py-0.5 rounded text-xs font-medium"
                  >{{ queues.horizon.ok ? 'OK' : 'DOWN' }}</span
                >
                <span class="font-medium">Queues (Horizon)</span>
              </div>
              <a
                v-if="links?.horizon"
                :href="links.horizon"
                target="_blank"
                class="text-xs text-blue-600 hover:underline"
                >Open Horizon</a
              >
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
              <div class="bg-gray-50 rounded p-3">
                <div class="text-xs text-gray-500">Pending</div>
                <div class="text-xl font-semibold">
                  {{ queues.horizon.pending ?? 0 }}
                </div>
              </div>
              <div class="bg-gray-50 rounded p-3">
                <div class="text-xs text-gray-500">Completed</div>
                <div class="text-xl font-semibold">
                  {{ queues.horizon.completed ?? 0 }}
                </div>
              </div>
              <div class="bg-gray-50 rounded p-3">
                <div class="text-xs text-gray-500">Failed</div>
                <div class="text-xl font-semibold">
                  {{ queues.horizon.failed ?? 0 }}
                </div>
              </div>
              <div class="bg-gray-50 rounded p-3">
                <div class="text-xs text-gray-500">Recent Failed</div>
                <div class="text-xl font-semibold">
                  {{ queues.horizon.recent_failed ?? 0 }}
                </div>
              </div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
              <div class="bg-gray-50 rounded p-3">
                <div class="text-xs text-gray-500">Supervisors</div>
                <div class="text-xl font-semibold">
                  {{ queues.horizon.supervisors_count }}
                </div>
              </div>
              <div class="bg-gray-50 rounded p-3">
                <div class="text-xs text-gray-500">Masters</div>
                <div class="text-xl font-semibold">
                  {{ queues.horizon.masters_count }}
                </div>
              </div>
              <div class="bg-gray-50 rounded p-3">
                <div class="text-xs text-gray-500">Processes</div>
                <div class="text-xl font-semibold">
                  {{ queues.horizon.total_processes }}
                </div>
              </div>
            </div>
            <div class="overflow-auto">
              <table class="min-w-full text-sm">
                <thead>
                  <tr class="text-left text-gray-500">
                    <th class="py-2 pr-4">Queue</th>
                    <th class="py-2 pr-4">Length</th>
                    <th class="py-2 pr-4">Processes</th>
                    <th class="py-2 pr-4">ETA to Clear (s)</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="q in queues.horizon.workload"
                    :key="q.name"
                    class="border-t"
                  >
                    <td class="py-2 pr-4">{{ q.name }}</td>
                    <td class="py-2 pr-4">{{ q.length }}</td>
                    <td class="py-2 pr-4">{{ q.processes }}</td>
                    <td class="py-2 pr-4">{{ q.wait }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import MainLayout from '@/inertia/Layouts/MainLayout.vue';
import { h, ref, computed, onMounted } from 'vue';

defineOptions({
  layout: (h, page) =>
    h(
      MainLayout,
      { hideHeader: true, noSidebarOffset: false },
      { default: () => page },
    ),
});

const props = defineProps({
  app: Object,
  links: Object,
});

const app = computed(() => props.app);
const links = computed(() => props.links);

const loading = ref({ databases: true, redis: true, queues: true });

const databases = ref({
  mysql: {
    ok: false,
    latency_ms: null,
    version: null,
    database: null,
    server_id: null,
    read_only: null,
  },
  mysql_read: {
    ok: false,
    latency_ms: null,
    version: null,
    database: null,
    server_id: null,
    read_only: null,
    replication: null,
  },
  mongodb: {
    ok: false,
    latency_ms: null,
    version: null,
    connections_current: null,
  },
});
const redis = ref({
  ok: false,
  latency_ms: null,
  server: null,
  clients: null,
  memory: null,
  computed: {
    uptime_in_seconds: null,
    role: null,
    used_memory_pct: null,
    hit_rate: null,
  },
});
const queues = ref({
  horizon: {
    ok: false,
    pending: 0,
    completed: 0,
    failed: 0,
    recent_failed: 0,
    workload: [],
    supervisors_count: 0,
    masters_count: 0,
    total_processes: 0,
  },
});

const statusPill = ok =>
  ok ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';

async function fetchDatabases() {
  try {
    const { data } = await axios.get(route('admin.system-health.databases'));
    databases.value = data;
  } catch (_) {
    // keep defaults
  } finally {
    loading.value.databases = false;
  }
}

async function fetchRedis() {
  try {
    const { data } = await axios.get(route('admin.system-health.redis'));
    redis.value = data;
  } catch (_) {
    // keep defaults
  } finally {
    loading.value.redis = false;
  }
}

async function fetchQueues() {
  try {
    const { data } = await axios.get(route('admin.system-health.queues'));
    queues.value = data;
  } catch (_) {
    // keep defaults
  } finally {
    loading.value.queues = false;
  }
}

onMounted(async () => {
  await Promise.allSettled([fetchDatabases(), fetchRedis(), fetchQueues()]);
});

function replicationBarWidth(seconds) {
  const pct = Math.max(0, Math.min(100, (seconds / 600) * 100));
  return pct + '%';
}
function replicationBarClass(seconds) {
  if (seconds <= 5) return 'bg-green-500';
  if (seconds <= 60) return 'bg-yellow-500';
  return 'bg-red-500';
}
</script>

<style scoped></style>
