<script setup>
const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  log: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['update:modelValue', 'close']);

const { isRequired } = useRules();
const dateFormat = date =>
  date ? useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value : '-';

const getEventTagColor = event => {
  if (!event) return 'secondary';

  const eventColors = {
    accessed: 'info',
    created: 'success',
    updated: 'primary',
    deleted: 'error',
  };

  return eventColors[event.toLowerCase()] || 'secondary';
};

const formatProperties = properties => {
  if (!properties) return null;

  // Handle string properties (JSON string)
  if (typeof properties === 'string') {
    try {
      properties = JSON.parse(properties);
    } catch (error) {
      console.warn('Failed to parse properties JSON:', error.message);
      return null;
    }
  }

  // Check if properties have old and attributes structure
  if (properties.old && properties.attributes) {
    const formatted = [];
    const allKeys = new Set([
      ...Object.keys(properties.old || {}),
      ...Object.keys(properties.attributes || {}),
    ]);

    allKeys.forEach(key => {
      formatted.push({
        key,
        old: properties.old[key] ?? null,
        new: properties.attributes[key] ?? null,
      });
    });

    return formatted;
  }

  return null;
};

const formattedProperties = computed(() => {
  if (!props.log?.properties) return null;
  return formatProperties(props.log.properties);
});

const filterNullValues = obj => {
  if (obj === null || obj === undefined) {
    return undefined;
  }

  if (Array.isArray(obj)) {
    return obj
      .map(item => filterNullValues(item))
      .filter(item => item !== undefined);
  }

  if (typeof obj === 'object') {
    const filtered = {};
    for (const [key, value] of Object.entries(obj)) {
      const filteredValue = filterNullValues(value);
      if (filteredValue !== undefined && filteredValue !== null) {
        filtered[key] = filteredValue;
      }
    }
    return Object.keys(filtered).length > 0 ? filtered : undefined;
  }

  return obj;
};

const filteredProperties = computed(() => {
  if (!props.log?.properties) return null;

  let properties = props.log.properties;

  // Handle string properties (JSON string)
  if (typeof properties === 'string') {
    try {
      properties = JSON.parse(properties);
    } catch (error) {
      console.warn('Failed to parse properties JSON:', error.message);
      return properties;
    }
  }

  // Filter out null values recursively
  const filtered = filterNullValues(properties);
  return filtered !== undefined ? filtered : null;
});

const closeModal = () => {
  emit('update:modelValue', false);
  emit('close');
};
</script>

<template>
  <x-modal
    :modelValue="modelValue"
    :title="`Activity Log Details${log ? ' - ID: ' + log.id : ''}`"
    size="xl"
    backdrop
    show-close
    @update:modelValue="val => emit('update:modelValue', val)"
    @close="closeModal"
  >
    <template #default>
      <div v-if="!log" class="flex flex-col items-center justify-center py-12">
        <p class="text-gray-500">No data available</p>
      </div>
      <div v-else class="space-y-4">
        <!-- Basic Information -->
        <div class="bg-gray-50 p-4 rounded-lg">
          <h3 class="text-lg font-semibold mb-3">Basic Information</h3>
          <dl class="grid md:grid-cols-2 gap-x-4 gap-y-3">
            <div>
              <dt class="font-medium text-gray-700">ID:</dt>
              <dd class="text-gray-900">{{ log.id }}</dd>
            </div>
            <div>
              <dt class="font-medium text-gray-700">User:</dt>
              <dd class="text-gray-900">
                {{ log.causer?.name || 'System' }}
                <span v-if="log.causer?.email" class="text-gray-500">
                  ({{ log.causer.email }})
                </span>
              </dd>
            </div>
            <div>
              <dt class="font-medium text-gray-700">Log Name:</dt>
              <dd class="text-gray-900">{{ log.log_name || '-' }}</dd>
            </div>
            <div>
              <dt class="font-medium text-gray-700">Feature:</dt>
              <dd class="text-gray-900">{{ log.feature || '-' }}</dd>
            </div>
            <div>
              <dt class="font-medium text-gray-700">Event:</dt>
              <dd>
                <x-tag size="sm" :color="getEventTagColor(log.event)">
                  {{ log.event ? log.event.toUpperCase() : '-' }}
                </x-tag>
              </dd>
            </div>
            <div>
              <dt class="font-medium text-gray-700">Code:</dt>
              <dd class="text-gray-900">{{ log.code || '-' }}</dd>
            </div>
            <div>
              <dt class="font-medium text-gray-700">IP Address:</dt>
              <dd class="text-gray-900">{{ log.ip_address || '-' }}</dd>
            </div>
            <div>
              <dt class="font-medium text-gray-700">User Agent:</dt>
              <dd class="text-gray-900">{{ log.user_agent || '-' }}</dd>
            </div>
            <div>
              <dt class="font-medium text-gray-700">Created At:</dt>
              <dd class="text-gray-900">
                {{ dateFormat(log.created_at) }}
              </dd>
            </div>
          </dl>
        </div>

        <!-- Description -->
        <div>
          <h3 class="text-lg font-semibold mb-2">Description</h3>
          <p class="text-gray-700 bg-gray-50 p-3 rounded">
            {{ log.description || '-' }}
          </p>
        </div>

        <!-- Subject Information -->
        <div v-if="log.subject_type" class="bg-blue-50 p-4 rounded-lg">
          <h3 class="text-lg font-semibold mb-3">Subject Information</h3>
          <dl class="grid md:grid-cols-2 gap-x-4 gap-y-3">
            <div>
              <dt class="font-medium text-gray-700">Subject Type:</dt>
              <dd class="text-gray-900">{{ log.subject_type }}</dd>
            </div>
            <div>
              <dt class="font-medium text-gray-700">Subject ID:</dt>
              <dd class="text-gray-900">{{ log.subject_id || '-' }}</dd>
            </div>
            <div v-if="log.subject" class="col-span-2">
              <dt class="font-medium text-gray-700 mb-2">Subject Data:</dt>
              <dd>
                <pre
                  class="bg-white p-3 rounded border text-xs overflow-auto max-h-40"
                  >{{ JSON.stringify(log.subject, null, 2) }}</pre
                >
              </dd>
            </div>
          </dl>
        </div>

        <!-- URL -->
        <div v-if="log.url">
          <h3 class="text-lg font-semibold mb-2">URL</h3>
          <p class="text-gray-700 bg-gray-50 p-3 rounded break-all">
            {{ log.url }}
          </p>
        </div>

        <!-- Properties -->
        <div v-if="log.properties">
          <h3 class="text-lg font-semibold mb-2">Properties</h3>
          <div
            v-if="formattedProperties"
            class="bg-gray-50 p-4 rounded border overflow-auto max-h-96"
          >
            <div class="space-y-3">
              <div
                v-for="item in formattedProperties"
                :key="item.key"
                class="border-b border-gray-200 pb-3 last:border-b-0 last:pb-0"
              >
                <div class="font-semibold text-gray-800 mb-2 capitalize">
                  {{ item.key.replace(/_/g, ' ') }}
                </div>
                <div class="grid md:grid-cols-2 gap-3">
                  <div>
                    <div class="text-xs font-medium text-gray-600 mb-1">
                      Old Value:
                    </div>
                    <div
                      class="bg-red-50 border border-red-200 rounded p-2 text-sm break-words"
                      :class="{
                        'text-gray-400 italic':
                          item.old === null || item.old === '',
                      }"
                    >
                      {{
                        item.old === null || item.old === ''
                          ? '(empty)'
                          : item.old
                      }}
                    </div>
                  </div>
                  <div>
                    <div class="text-xs font-medium text-gray-600 mb-1">
                      New Value:
                    </div>
                    <div
                      class="bg-green-50 border border-green-200 rounded p-2 text-sm break-words"
                      :class="{
                        'text-gray-400 italic':
                          item.new === null || item.new === '',
                      }"
                    >
                      {{
                        item.new === null || item.new === ''
                          ? '(empty)'
                          : item.new
                      }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div
            v-else-if="filteredProperties"
            class="bg-gray-50 p-4 rounded border overflow-auto max-h-96"
          >
            <pre class="text-xs">{{
              JSON.stringify(filteredProperties, null, 2)
            }}</pre>
          </div>
          <div
            v-else
            class="bg-gray-50 p-4 rounded border overflow-auto max-h-96"
          >
            <pre class="text-xs text-gray-400 italic">
No properties data available</pre
            >
          </div>
        </div>

        <!-- Batch UUID -->
        <div v-if="log.batch_uuid">
          <h3 class="text-lg font-semibold mb-2">Batch UUID</h3>
          <p class="text-gray-700 bg-gray-50 p-3 rounded font-mono text-sm">
            {{ log.batch_uuid }}
          </p>
        </div>
      </div>
    </template>
  </x-modal>
</template>
