<script setup>
const props = defineProps({
  claim: Object,
  additionalContacts: Object,
  auditLogs: Object,
  claimHistory: Object,
  documents: Object,
  dropdowns: Object,
  canEdit: Boolean,
  canDelete: Boolean,
});

const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const notification = useToast();

const activeTab = ref('details');
const tabs = [
  { key: 'details', label: 'Details', icon: 'information-circle' },
  { key: 'documents', label: 'Documents', icon: 'document-text' },
  { key: 'contacts', label: 'Additional Contacts', icon: 'users' },
  { key: 'audit', label: 'Audit Logs', icon: 'clock' },
  { key: 'history', label: 'Claims History', icon: 'history' },
];

// Assignment form for managers
const assignmentForm = useForm({
  manager_id: props.claim.assigned_to || '',
  manager_type: 'primary',
});

// Status update form
const statusForm = useForm({
  status: props.claim.status || '',
});

const managersOptions = computed(() => {
  return (
    props.dropdowns.managers?.map(manager => ({
      value: manager.id,
      label: manager.name,
    })) || []
  );
});

const statusOptions = [
  { value: 'pending', label: 'Pending' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'completed', label: 'Completed' },
  { value: 'cancelled', label: 'Cancelled' },
];

function assignManager() {
  if (!assignmentForm.manager_id) {
    notification.error({
      title: 'Please select a manager',
      position: 'top',
    });
    return;
  }

  assignmentForm.post(`/claims/${props.claim.id}/assign-manager`, {
    preserveScroll: true,
    onSuccess: () => {
      notification.success({
        title: 'Manager assigned successfully',
        position: 'top',
      });
    },
    onError: errors => {
      notification.error({
        title: 'Error assigning manager',
        position: 'top',
      });
    },
  });
}

function updateStatus() {
  if (!statusForm.status) {
    notification.error({
      title: 'Please select a status',
      position: 'top',
    });
    return;
  }

  statusForm.post(`/claims/${props.claim.id}/update-status`, {
    preserveScroll: true,
    onSuccess: () => {
      notification.success({
        title: 'Status updated successfully',
        position: 'top',
      });
    },
    onError: errors => {
      notification.error({
        title: 'Error updating status',
        position: 'top',
      });
    },
  });
}

function deleteClaim() {
  if (confirm('Are you sure you want to delete this claim?')) {
    router.delete(`/claims/${props.claim.id}`, {
      onSuccess: () => {
        notification.success({
          title: 'Claim deleted successfully',
          position: 'top',
        });
        router.visit('/claims');
      },
      onError: errors => {
        notification.error({
          title: 'Error deleting claim',
          position: 'top',
        });
      },
    });
  }
}

function formatDate(date) {
  if (!date) return '';
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

// Check if claim is vehicle-related
const isVehicleRelated = computed(() => {
  return (
    props.claim.quote_type?.title?.toLowerCase() === 'car' ||
    props.claim.quote_type?.title?.toLowerCase() === 'bike'
  );
});
</script>

<template>
  <div>
    <Head :title="`Claim ${claim.ref_id}`" />
    <div class="flex justify-between items-center flex-wrap gap-2 mb-5">
      <h2 class="text-xl font-semibold">Claim Details - {{ claim.ref_id }}</h2>
      <div class="flex gap-2">
        <Link
          v-if="can(permissionsEnum.CLAIM_LIST)"
          href="/claims"
          preserve-scroll
        >
          <x-button size="sm" color="primary" tag="div">Claims List</x-button>
        </Link>
        <Link
          v-if="can(permissionsEnum.CLAIM_UPDATE)"
          :href="`/claims/${claim.id}/edit`"
        >
          <x-button size="sm" color="emerald" tag="div">Edit</x-button>
        </Link>
        <x-button
          v-if="can(permissionsEnum.CLAIM_DELETE)"
          size="sm"
          color="error"
          @click="deleteClaim"
        >
          Delete
        </x-button>
      </div>
    </div>

    <!-- Status and Assignment Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
      <!-- Status Update -->
      <div class="bg-white p-4 rounded shadow">
        <h3 class="font-semibold mb-3">Update Status</h3>
        <div class="flex gap-2">
          <x-select
            v-model="statusForm.status"
            placeholder="Select Status"
            :options="statusOptions"
            class="flex-1"
          />
          <x-button
            size="sm"
            color="primary"
            @click="updateStatus"
            :loading="statusForm.processing"
          >
            Update
          </x-button>
        </div>
      </div>

      <!-- Manager Assignment -->
      <div class="bg-white p-4 rounded shadow">
        <h3 class="font-semibold mb-3">Assign Manager</h3>
        <div class="flex gap-2">
          <x-select
            v-model="assignmentForm.manager_id"
            placeholder="Select Manager"
            :options="managersOptions"
            filterable
            filterPlaceholder="Filter Managers...."
            class="flex-1"
          />
          <x-button
            size="sm"
            color="success"
            @click="assignManager"
            :loading="assignmentForm.processing"
          >
            Assign
          </x-button>
        </div>
      </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="mb-6">
      <nav class="flex space-x-8 border-b border-gray-200">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          @click="activeTab = tab.key"
          :class="[
            'py-2 px-1 border-b-2 font-medium text-sm',
            activeTab === tab.key
              ? 'border-blue-500 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
          ]"
        >
          {{ tab.label }}
        </button>
      </nav>
    </div>

    <!-- Tab Content -->
    <div class="bg-white rounded shadow">
      <!-- Details Tab -->
      <div v-if="activeTab === 'details'" class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <!-- Personal Information -->
          <div>
            <h3 class="text-lg font-semibold mb-4">Personal Information</h3>
            <dl class="space-y-3">
              <div class="grid grid-cols-2">
                <dt class="font-medium text-gray-600">First Name</dt>
                <dd class="text-gray-900">{{ claim.first_name }}</dd>
              </div>
              <div class="grid grid-cols-2">
                <dt class="font-medium text-gray-600">Last Name</dt>
                <dd class="text-gray-900">{{ claim.last_name }}</dd>
              </div>
              <div class="grid grid-cols-2">
                <dt class="font-medium text-gray-600">Email</dt>
                <dd class="text-gray-900">{{ claim.email }}</dd>
              </div>
              <div class="grid grid-cols-2">
                <dt class="font-medium text-gray-600">Mobile Number</dt>
                <dd class="text-gray-900">{{ claim.mobile_no }}</dd>
              </div>
            </dl>
          </div>

          <!-- Claim Information -->
          <div>
            <h3 class="text-lg font-semibold mb-4">Claim Information</h3>
            <dl class="space-y-3">
              <div class="grid grid-cols-2">
                <dt class="font-medium text-gray-600">Ref ID</dt>
                <dd class="text-gray-900 font-mono">{{ claim.ref_id }}</dd>
              </div>
              <div class="grid grid-cols-2">
                <dt class="font-medium text-gray-600">Line of Business</dt>
                <dd class="text-gray-900">{{ claim.quote_type?.title }}</dd>
              </div>
              <div class="grid grid-cols-2">
                <dt class="font-medium text-gray-600">Claim Type</dt>
                <dd class="text-gray-900">
                  {{ claim.claim_type_lookup?.text }}
                </dd>
              </div>
              <div class="grid grid-cols-2">
                <dt class="font-medium text-gray-600">Policy Number</dt>
                <dd class="text-gray-900">{{ claim.policy_number }}</dd>
              </div>
              <div class="grid grid-cols-2">
                <dt class="font-medium text-gray-600">Insurer Claim Number</dt>
                <dd class="text-gray-900">{{ claim.insurer_claim_number }}</dd>
              </div>
              <div class="grid grid-cols-2">
                <dt class="font-medium text-gray-600">Status</dt>
                <dd>
                  <x-tag
                    size="sm"
                    :color="
                      claim.status === 'completed'
                        ? 'success'
                        : claim.status === 'in_progress'
                          ? 'warning'
                          : claim.status === 'cancelled'
                            ? 'error'
                            : 'info'
                    "
                  >
                    {{ claim.status.replace('_', ' ').toUpperCase() }}
                  </x-tag>
                </dd>
              </div>
              <div class="grid grid-cols-2">
                <dt class="font-medium text-gray-600">Assigned To</dt>
                <dd class="text-gray-900">
                  {{ claim.assigned_manager?.name }}
                </dd>
              </div>
              <div class="grid grid-cols-2">
                <dt class="font-medium text-gray-600">Incident Date</dt>
                <dd class="text-gray-900">
                  {{ formatDate(claim.incident_date) }}
                </dd>
              </div>
              <div class="grid grid-cols-2">
                <dt class="font-medium text-gray-600">Report Date</dt>
                <dd class="text-gray-900">
                  {{ formatDate(claim.report_date) }}
                </dd>
              </div>
            </dl>
          </div>
        </div>

        <!-- Vehicle Information (if applicable) -->
        <div v-if="isVehicleRelated" class="mt-8">
          <h3 class="text-lg font-semibold mb-4">Vehicle Information</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <dl class="space-y-3">
              <div class="grid grid-cols-2">
                <dt class="font-medium text-gray-600">Plate Number</dt>
                <dd class="text-gray-900">{{ claim.plate_number }}</dd>
              </div>
              <div class="grid grid-cols-2">
                <dt class="font-medium text-gray-600">Vehicle Make</dt>
                <dd class="text-gray-900">{{ claim.vehicle_make }}</dd>
              </div>
              <div class="grid grid-cols-2">
                <dt class="font-medium text-gray-600">Vehicle Model</dt>
                <dd class="text-gray-900">{{ claim.vehicle_model }}</dd>
              </div>
              <div class="grid grid-cols-2">
                <dt class="font-medium text-gray-600">Vehicle Year</dt>
                <dd class="text-gray-900">{{ claim.vehicle_year }}</dd>
              </div>
            </dl>
          </div>
        </div>

        <!-- Description -->
        <div class="mt-8">
          <h3 class="text-lg font-semibold mb-4">Description</h3>
          <div class="bg-gray-50 p-4 rounded">
            <p class="text-gray-700 whitespace-pre-wrap">
              {{ claim.description || 'No description provided' }}
            </p>
          </div>
        </div>

        <!-- Timestamps -->
        <div class="mt-8 pt-6 border-t border-gray-200">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <dt class="font-medium text-gray-600">Created At</dt>
              <dd class="text-gray-900">{{ formatDate(claim.created_at) }}</dd>
            </div>
            <div>
              <dt class="font-medium text-gray-600">Updated At</dt>
              <dd class="text-gray-900">{{ formatDate(claim.updated_at) }}</dd>
            </div>
            <div>
              <dt class="font-medium text-gray-600">Created By</dt>
              <dd class="text-gray-900">{{ claim.created_by?.name }}</dd>
            </div>
          </div>
        </div>
      </div>

      <!-- Documents Tab -->
      <div v-if="activeTab === 'documents'" class="p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">Documents</h3>
          <x-button size="sm" color="primary"> Upload Document </x-button>
        </div>

        <div v-if="documents && documents.length > 0" class="space-y-4">
          <div
            v-for="document in documents"
            :key="document.id"
            class="border border-gray-200 rounded p-4 flex justify-between items-center"
          >
            <div>
              <p class="font-medium">{{ document.name }}</p>
              <p class="text-sm text-gray-500">
                {{ document.type }} - {{ formatDate(document.created_at) }}
              </p>
            </div>
            <div class="flex gap-2">
              <x-button size="sm" color="primary">Download</x-button>
              <x-button size="sm" color="error">Delete</x-button>
            </div>
          </div>
        </div>

        <div v-else class="text-center py-8 text-gray-500">
          <p>No documents uploaded yet.</p>
        </div>
      </div>

      <!-- Additional Contacts Tab -->
      <div v-if="activeTab === 'contacts'" class="p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">Additional Contacts</h3>
          <x-button size="sm" color="primary"> Add Contact </x-button>
        </div>

        <div
          v-if="additionalContacts && additionalContacts.length > 0"
          class="space-y-4"
        >
          <div
            v-for="contact in additionalContacts"
            :key="contact.id"
            class="border border-gray-200 rounded p-4"
          >
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <p class="font-medium">{{ contact.name }}</p>
                <p class="text-sm text-gray-500">{{ contact.relationship }}</p>
              </div>
              <div>
                <p class="text-sm">{{ contact.email }}</p>
                <p class="text-sm">{{ contact.phone }}</p>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="text-center py-8 text-gray-500">
          <p>No additional contacts added yet.</p>
        </div>
      </div>

      <!-- Audit Logs Tab -->
      <div v-if="activeTab === 'audit'" class="p-6">
        <h3 class="text-lg font-semibold mb-4">Audit Logs</h3>
        <AuditLogs :id="claim.id" :quoteType="'Claim'" />
      </div>

      <!-- Claims History Tab -->
      <div v-if="activeTab === 'history'" class="p-6">
        <h3 class="text-lg font-semibold mb-4">Claims History</h3>

        <div v-if="claimHistory && claimHistory.length > 0" class="space-y-4">
          <div
            v-for="history in claimHistory"
            :key="history.id"
            class="border border-gray-200 rounded p-4"
          >
            <div class="flex justify-between items-start mb-2">
              <div>
                <p class="font-medium">{{ history.action }}</p>
                <p class="text-sm text-gray-500">
                  {{ formatDate(history.created_at) }}
                </p>
              </div>
              <div class="text-right">
                <p class="text-sm font-medium">{{ history.user?.name }}</p>
                <p class="text-sm text-gray-500">{{ history.user?.email }}</p>
              </div>
            </div>
            <p class="text-gray-700">{{ history.description }}</p>
          </div>
        </div>

        <div v-else class="text-center py-8 text-gray-500">
          <p>No history records found.</p>
        </div>
      </div>
    </div>
  </div>
</template>
