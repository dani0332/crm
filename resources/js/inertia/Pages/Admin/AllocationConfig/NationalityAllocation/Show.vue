<script setup>
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
  configuration: Object,
});

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY hh:mm:ss').value;

// For audit logs
const auditLogs = ref([]);
const isLoadingAuditLogs = ref(false);
const showAuditLogs = ref(false);

const fetchAuditLogs = async () => {
  try {
    isLoadingAuditLogs.value = true;
    showAuditLogs.value = true;

    const response = await axios.get(
      route(
        'admin.nationality-allocation-config.audit-logs',
        props.configuration.id,
      ),
    );
    auditLogs.value = response.data;
  } catch (error) {
    console.error('Error fetching audit logs:', error);
  } finally {
    isLoadingAuditLogs.value = false;
  }
};

// Improve formatAuditChanges function to handle labels
const formatAuditChanges = log => {
  const changes = [];

  // Process normal attribute changes
  if (log.old_values && log.new_values) {
    for (const key in log.new_values) {
      // Skip special fields that will be handled separately
      if (
        [
          'relation',
          'attached',
          'detached',
          'nationality_label',
          'quote_type_label',
        ].includes(key)
      )
        continue;

      const oldValue = log.old_values[key];
      const newValue = log.new_values[key];

      if (oldValue !== newValue) {
        // Format specific field values
        let oldDisplayValue = oldValue;
        let newDisplayValue = newValue;
        let fieldLabel = key
          .replace(/_/g, ' ')
          .replace(/\b\w/g, l => l.toUpperCase());

        // Handle foreign keys with labels
        if (key === 'nationality_id' && log.old_values.nationality_label) {
          oldDisplayValue = log.old_values.nationality_label;
          newDisplayValue = log.new_values.nationality_label;
          fieldLabel = 'Nationality';
        } else if (key === 'quote_type_id' && log.old_values.quote_type_label) {
          oldDisplayValue = log.old_values.quote_type_label;
          newDisplayValue = log.new_values.quote_type_label;
          fieldLabel = 'Quote Type';
        }

        // Handle boolean fields
        else if (
          typeof oldValue === 'boolean' ||
          typeof newValue === 'boolean'
        ) {
          oldDisplayValue = oldValue ? 'Enabled' : 'Disabled';
          newDisplayValue = newValue ? 'Enabled' : 'Disabled';
        }

        // Handle date fields
        else if (key === 'activated_at') {
          oldDisplayValue = oldValue ? dateFormat(oldValue) : 'Not Activated';
          newDisplayValue = newValue ? dateFormat(newValue) : 'Not Activated';
        }

        changes.push({
          field: fieldLabel,
          oldValue: oldDisplayValue,
          newValue: newDisplayValue,
        });
      }
    }
  }

  // Process relation changes (e.g., assigned users)
  if (log.relation_changes) {
    if (
      log.relation_changes.attached &&
      log.relation_changes.attached.length > 0
    ) {
      const userNames = log.relation_changes.attached
        .map(user => user.name)
        .join(', ');
      changes.push({
        field: 'Users',
        action: 'Added',
        value: userNames,
      });
    }

    if (
      log.relation_changes.detached &&
      log.relation_changes.detached.length > 0
    ) {
      const userNames = log.relation_changes.detached
        .map(user => user.name)
        .join(', ');
      changes.push({
        field: 'Users',
        action: 'Removed',
        value: userNames,
      });
    }
  }

  return changes;
};
</script>

<template>
  <Head title="Configuration Detail" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">
      Nationality Allocation Configuration Detail
    </h2>
    <div class="flex gap-2">
      <Link :href="route('admin.nationality-allocation-config.index')">
        <x-button size="sm" color="#1d83bc" tag="div">
          Configuration List
        </x-button>
      </Link>
      <Link
        :href="
          route('admin.nationality-allocation-config.edit', configuration.id)
        "
      >
        <x-button size="sm" tag="div">Edit</x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div class="text-sm">
      <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 break-words">
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">ID</dt>
          <dd>{{ configuration.id }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Quote Type</dt>
          <dd>{{ configuration.quote_type.text }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Nationality</dt>
          <dd>{{ configuration.nationality.text }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Should Skip SIC</dt>
          <dd>
            <span
              :class="[
                'px-2 py-1 text-xs font-medium rounded-full',
                configuration.should_skip_sic
                  ? 'bg-green-100 text-green-800'
                  : 'bg-red-100 text-red-800',
              ]"
            >
              {{ configuration.should_skip_sic ? 'Yes' : 'No' }}
            </span>
          </dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Status</dt>
          <dd>
            <span
              :class="[
                'px-2 py-1 text-xs font-medium rounded-full',
                configuration.activated_at
                  ? 'bg-green-100 text-green-800'
                  : 'bg-red-100 text-red-800',
              ]"
            >
              {{ configuration.activated_at ? 'Active' : 'Inactive' }}
            </span>
          </dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Created By</dt>
          <dd>{{ configuration.created_by?.name }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Updated By</dt>
          <dd>{{ configuration.updated_by?.name }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Created At</dt>
          <dd>{{ dateFormat(configuration.created_at) }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Updated At</dt>
          <dd>{{ dateFormat(configuration.updated_at) }}</dd>
        </div>
      </dl>
    </div>
  </div>

  <div class="p-4 rounded shadow mb-6 bg-white">
    <h3 class="text-lg font-semibold mb-4">
      Assigned Advisors
      <span class="text-md text-red-500 font-normal"
        >({{ configuration.users.length }})</span
      >
    </h3>
    <div v-if="configuration.users.length === 0" class="text-gray-500 italic">
      No users assigned to this configuration.
    </div>
    <div v-else class="grid md:grid-cols-3 gap-2">
      <div
        v-for="user in configuration.users"
        :key="user.id"
        class="p-2 border rounded flex items-center"
      >
        <div
          class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 font-medium mr-2"
        >
          {{ user.name.charAt(0) }}
        </div>
        <div class="flex-1 overflow-hidden">
          <div class="font-medium truncate">{{ user.name }}</div>
          <div class="text-xs text-gray-500 truncate">{{ user.email }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Audit Logs Section -->
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold">Audit History</h3>
      <x-button
        v-if="!showAuditLogs"
        size="sm"
        color="#1d83bc"
        @click="fetchAuditLogs"
        :loading="isLoadingAuditLogs"
      >
        <div class="flex items-center">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-4 w-4 mr-1"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
            />
          </svg>
          Load Audit Logs
        </div>
      </x-button>
      <x-button v-else size="sm" color="gray" @click="showAuditLogs = false">
        <div class="flex items-center">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-4 w-4 mr-1"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M5 15l7-7 7 7"
            />
          </svg>
          Hide Audit History
        </div>
      </x-button>
    </div>

    <div v-if="showAuditLogs">
      <div v-if="isLoadingAuditLogs" class="flex justify-center py-8">
        <svg
          class="animate-spin h-8 w-8 text-primary-500"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          ></circle>
          <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
          ></path>
        </svg>
      </div>

      <div
        v-else-if="auditLogs.length === 0"
        class="text-gray-500 italic py-8 text-center"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="h-12 w-12 mx-auto mb-3 text-gray-400"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
          />
        </svg>
        <p>No audit logs found for this configuration.</p>
      </div>

      <div v-else class="relative timeline-container mt-6">
        <!-- Timeline vertical line -->
        <div class="absolute left-7 top-0 bottom-0 w-0.5 bg-gray-200"></div>

        <!-- Timeline entries -->
        <div
          v-for="(log, index) in auditLogs"
          :key="index"
          class="timeline-entry mb-4 pl-16 relative"
        >
          <!-- Timeline dot with icon -->
          <div
            :class="[
              'absolute left-5 top-3 w-4 h-4 rounded-full border-3 z-10 flex items-center justify-center',
              log.event === 'created'
                ? 'border-green-400 bg-green-100'
                : log.event === 'updated'
                  ? 'border-blue-400 bg-blue-100'
                  : log.event === 'deleted'
                    ? 'border-red-400 bg-red-100'
                    : log.event === 'sync'
                      ? 'border-purple-400 bg-purple-100'
                      : 'border-gray-400 bg-gray-100',
            ]"
          ></div>

          <!-- Timeline card -->
          <div
            class="bg-white rounded-lg border shadow-sm hover:shadow-md transition-shadow duration-200"
          >
            <!-- Card header - more compact -->
            <div class="p-2 border-b flex items-center">
              <div
                class="w-7 h-7 rounded-full flex items-center justify-center text-white mr-2"
                :class="[
                  log.event === 'created'
                    ? 'bg-green-500'
                    : log.event === 'updated'
                      ? 'bg-blue-500'
                      : log.event === 'deleted'
                        ? 'bg-red-500'
                        : log.event === 'sync'
                          ? 'bg-purple-500'
                          : 'bg-gray-500',
                ]"
              >
                <svg
                  v-if="log.event === 'created'"
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-4 w-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                  />
                </svg>
                <svg
                  v-else-if="log.event === 'updated'"
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-4 w-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                  />
                </svg>
                <svg
                  v-else-if="log.event === 'deleted'"
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-4 w-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                  />
                </svg>
                <svg
                  v-else-if="log.event === 'sync'"
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-4 w-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"
                  />
                </svg>
                <template v-else>
                  {{ log.name ? log.name.charAt(0) : 'S' }}
                </template>
              </div>
              <div class="min-w-0 flex-1">
                <div class="flex items-center text-sm">
                  <span class="font-medium truncate mr-1">{{
                    log.name || 'System'
                  }}</span>
                  <span
                    :class="[
                      'ml-1 px-1.5 py-0.5 text-xs font-medium rounded-full',
                      log.event === 'created'
                        ? 'bg-green-100 text-green-800'
                        : log.event === 'updated'
                          ? 'bg-blue-100 text-blue-800'
                          : log.event === 'deleted'
                            ? 'bg-red-100 text-red-800'
                            : log.event === 'sync'
                              ? 'bg-purple-100 text-purple-800'
                              : 'bg-gray-100 text-gray-800',
                    ]"
                  >
                    {{ log.event.charAt(0).toUpperCase() + log.event.slice(1) }}
                  </span>
                </div>
                <div class="text-xs text-gray-500">
                  {{ dateFormat(log.created_at) }}
                </div>
              </div>
            </div>

            <!-- Card content - more compact -->
            <div class="p-2">
              <div
                v-if="formatAuditChanges(log).length === 0"
                class="text-gray-500 italic text-center py-1 text-xs"
              >
                No changes detected.
              </div>

              <div v-else class="space-y-2">
                <div
                  v-for="(change, changeIndex) in formatAuditChanges(log)"
                  :key="changeIndex"
                  class="mb-2 last:mb-0"
                >
                  <!-- Relation changes (e.g., users) -->
                  <div v-if="change.action" class="ml-1">
                    <div
                      class="flex items-center rounded-md border border-gray-200 overflow-hidden text-xs"
                    >
                      <!-- Field label -->
                      <div
                        class="w-28 px-2 py-1.5 bg-gray-100 font-medium border-r border-gray-200 text-gray-700 truncate"
                      >
                        {{ change.field }}
                      </div>

                      <div class="flex-1 p-1.5 bg-gray-50">
                        <div class="flex items-center">
                          <span
                            :class="[
                              'flex items-center px-1.5 py-0.5 text-xs font-medium rounded-full mr-2',
                              change.action === 'Added'
                                ? 'bg-green-100 text-green-800'
                                : 'bg-red-100 text-red-800',
                            ]"
                          >
                            <svg
                              v-if="change.action === 'Added'"
                              xmlns="http://www.w3.org/2000/svg"
                              class="h-2.5 w-2.5 mr-0.5"
                              fill="none"
                              viewBox="0 0 24 24"
                              stroke="currentColor"
                            >
                              <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                              />
                            </svg>
                            <svg
                              v-else
                              xmlns="http://www.w3.org/2000/svg"
                              class="h-2.5 w-2.5 mr-0.5"
                              fill="none"
                              viewBox="0 0 24 24"
                              stroke="currentColor"
                            >
                              <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                              />
                            </svg>
                            {{ change.action }}
                          </span>
                          <span>{{ change.value }}</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Regular changes (old/new values) - more compact -->
                  <div v-else class="ml-1">
                    <div
                      class="flex items-center rounded-md border border-gray-200 overflow-hidden text-xs"
                    >
                      <!-- Field label -->
                      <div
                        class="w-28 px-2 py-1.5 bg-gray-100 font-medium border-r border-gray-200 text-gray-700 truncate"
                      >
                        {{ change.field }}
                      </div>

                      <!-- From value -->
                      <div
                        class="flex-1 p-1.5 bg-gray-50 overflow-hidden"
                        :class="{
                          'text-gray-600': change.newValue !== change.oldValue,
                        }"
                      >
                        <div
                          v-if="
                            change.oldValue !== null &&
                            change.oldValue !== undefined
                          "
                        >
                          {{ change.oldValue }}
                        </div>
                        <div v-else class="text-gray-400 italic">Empty</div>
                      </div>

                      <!-- Change arrow icon -->
                      <div
                        class="px-1 flex items-center justify-center text-blue-500 bg-blue-50"
                      >
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          class="h-3 w-3"
                          fill="none"
                          viewBox="0 0 24 24"
                          stroke="currentColor"
                        >
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M13 7l5 5m0 0l-5 5m5-5H6"
                          />
                        </svg>
                      </div>

                      <!-- To value -->
                      <div
                        class="flex-1 p-1.5 bg-blue-50 border-l border-blue-200 overflow-hidden"
                      >
                        <div
                          v-if="
                            change.newValue !== null &&
                            change.newValue !== undefined
                          "
                        >
                          <span
                            :class="{
                              'text-blue-700 font-medium':
                                change.newValue !== change.oldValue,
                            }"
                          >
                            {{ change.newValue }}
                          </span>
                        </div>
                        <div v-else class="text-gray-400 italic">Empty</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.timeline-container {
  padding-bottom: 1rem;
}

.timeline-entry {
  transform: translateX(0);
  transition: transform 0.2s ease-in-out;
}

.timeline-entry:hover {
  transform: translateX(4px);
}

/* Custom border width class */
.border-3 {
  border-width: 3px;
}

/* Custom utilities for compact layouts */
.min-h-32 {
  min-height: 8rem;
}

@media (max-width: 640px) {
  .timeline-entry {
    padding-left: 2.5rem;
  }

  .absolute.left-7 {
    left: 1rem;
  }

  .absolute.left-5 {
    left: 0.75rem;
  }
}
</style>
