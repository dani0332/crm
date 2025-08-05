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

const quoteTypeIds = page.props.quoteTypeIds;

const sectionExpanded = ref(true);

// Assignment form for managers
const assignmentForm = useForm({
  manager_id: props.claim.manager_id || '',
  manager_type: 'primary',
});

// Status update form
const statusForm = useForm({
  claim_status_id: props.claim.claim_status_id || '',
});

// Sub-status update form
const subStatusForm = useForm({
  claim_sub_status_id: props.claim.claim_sub_status_id || '',
});

// Source update form
const sourceForm = useForm({
  source: props.claim.source || '',
});

// WhatsApp consent form
const whatsappForm = useForm({
  whatsapp_consent: props.claim.whatsapp_consent || false,
});

const managersOptions = computed(() => {
  return (
    props.dropdowns.claimsManagers?.map(manager => ({
      value: manager.id,
      label: manager.name,
    })) || []
  );
});

const statusOptions = computed(() => {
  return Object.entries(props.dropdowns.claimStatuses).map(([id, text]) => ({
    value: parseInt(id),
    label: text,
  }));
});

const subStatusOptions = computed(() => {
  return (
    props.dropdowns.claimSubStatuses?.map(subStatus => ({
      value: subStatus.id,
      label: subStatus.text,
    })) || []
  );
});

const complaintStatusOptions = computed(() => {
  return (
    props.dropdowns.complaintStatuses?.map(status => ({
      value: status.value,
      label: status.label,
    })) || []
  );
});

// Check if the claim is vehicle-related
const isCarLOB = computed(() => {
  return quoteTypeIds.Car === props.claim.line_of_business_id;
});

// Check if the claim is health-related
const isHealthLOB = computed(() => {
  return quoteTypeIds.Health === props.claim.line_of_business_id;
});

// Check if claim is overdue
const isOverdue = computed(() => {
  if (!props.claim.next_follow_up_date) return false;
  return new Date(props.claim.next_follow_up_date) < new Date();
});

function assignManager() {
  if (!assignmentForm.manager_id) {
    notification.error({
      title: 'Please select a manager',
      position: 'top',
    });
    return;
  }

  assignmentForm.post(`/claim/${props.claim.id}/assign-manager`, {
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

  statusForm.post(`/claim/${props.claim.id}/update-status`, {
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

function updateSubStatus() {
  if (!subStatusForm.sub_status_id) {
    notification.error({
      title: 'Please select a sub-status',
      position: 'top',
    });
    return;
  }

  subStatusForm.post(`/claim/${props.claim.id}/update-sub-status`, {
    preserveScroll: true,
    onSuccess: () => {
      notification.success({
        title: 'Sub-status updated successfully',
        position: 'top',
      });
    },
    onError: errors => {
      notification.error({
        title: 'Error updating sub-status',
        position: 'top',
      });
    },
  });
}

function updateComplaintStatus() {
  complaintStatusForm.post(
    `/claim/${props.claim.id}/update-complaint-status`,
    {
      preserveScroll: true,
      onSuccess: () => {
        notification.success({
          title: 'Complaint status updated successfully',
          position: 'top',
        });
      },
      onError: errors => {
        notification.error({
          title: 'Error updating complaint status',
          position: 'top',
        });
      },
    },
  );
}

function scheduleFollowUp() {
  if (!followUpForm.next_follow_up_date) {
    notification.error({
      title: 'Please select a follow-up date',
      position: 'top',
    });
    return;
  }

  followUpForm.post(`/claim/${props.claim.id}/schedule-follow-up`, {
    preserveScroll: true,
    onSuccess: () => {
      notification.success({
        title: 'Follow-up scheduled successfully',
        position: 'top',
      });
    },
    onError: errors => {
      notification.error({
        title: 'Error scheduling follow-up',
        position: 'top',
      });
    },
  });
}

function formatDate(date) {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
}

function formatDateTime(date) {
  if (!date) return '-';
  return new Date(date).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

function formatCurrency(amount) {
  if (!amount) return '-';
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'AED',
  }).format(amount);
}

function deleteClaim() {
  if (confirm('Are you sure you want to delete this claim?')) {
    router.delete(`/claim/${props.claim.id}`, {
      onSuccess: () => {
        notification.success({
          title: 'Claim deleted successfully',
          position: 'top',
        });
        router.visit('/claim');
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
</script>

<template>
  <div>
    <Head :title="`Claim ${claim.ref_id}`" />
    <StickyHeader>
      <template v-slot:header>
        <h2 class="text-xl font-semibold">
          Claim Details - {{ claim.ref_id }}
        </h2>
        <x-tag
          v-if="claim.complaint_status === 'Complaint Open'"
          size="sm"
          color="error"
        >
          Complaint Open
        </x-tag>
        <x-tag v-else-if="isOverdue" size="sm" color="amber">
          Overdue Follow-up
        </x-tag>
      </template>
      <div class="flex justify-between items-center flex-wrap gap-2 mb-5">
        <div class="flex gap-2">
          <Link
            v-if="can(permissionsEnum.CLAIM_LIST)"
            href="/claim"
            preserve-scroll
          >
            <x-button size="sm" color="primary" tag="div">Claims List</x-button>
          </Link>
          <Link
            v-if="can(permissionsEnum.CLAIM_EDIT)"
            :href="`/claim/${claim.ref_id}/edit`"
          >
            <x-button size="sm" color="emerald" tag="div">Edit</x-button>
          </Link>
        </div>
      </div>
    </StickyHeader>

    <!-- Quick Actions -->
    <div class="p-4 rounded shadow mb-6 bg-white">
      <Collapsible :expanded="sectionExpanded">
        <template #header>
          <div class="flex justify-between items-center">
            <h3 class="font-semibold text-primary-800 text-lg">
              Quick Actions
            </h3>
          </div>
        </template>
        <template #body>
          <x-divider class="my-4" />
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Status Update -->
            <div class="bg-gray-50 p-4 rounded">
              <h4 class="font-semibold mb-3 text-gray-700">Update Status</h4>
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

            <!-- Sub-Status Update -->
            <div class="bg-gray-50 p-4 rounded">
              <h4 class="font-semibold mb-3 text-gray-700">
                Update Sub-Status
              </h4>
              <div class="flex gap-2">
                <x-select
                  v-model="subStatusForm.sub_status_id"
                  placeholder="Select Sub-Status"
                  :options="subStatusOptions"
                  filterable
                  filterPlaceholder="Filter Sub-Status...."
                  class="flex-1"
                />
                <x-button
                  size="sm"
                  color="warning"
                  @click="updateSubStatus"
                  :loading="subStatusForm.processing"
                >
                  Update
                </x-button>
              </div>
            </div>

            <!-- Manager Assignment -->
            <div class="bg-gray-50 p-4 rounded">
              <h4 class="font-semibold mb-3 text-gray-700">Assign Manager</h4>
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

            <!-- Complaint Status Update -->
            <div class="bg-gray-50 p-4 rounded">
              <h4 class="font-semibold mb-3 text-gray-700">Complaint Status</h4>
              <div class="flex gap-2">
                <x-select
                  v-model="complaintStatusForm.complaint_status"
                  placeholder="Select Complaint Status"
                  :options="complaintStatusOptions"
                  class="flex-1"
                />
                <x-button
                  size="sm"
                  color="info"
                  @click="updateComplaintStatus"
                  :loading="complaintStatusForm.processing"
                >
                  Update
                </x-button>
              </div>
            </div>

            <!-- Follow-up Scheduling -->
            <div class="bg-gray-50 p-4 rounded">
              <h4 class="font-semibold mb-3 text-gray-700">
                Schedule Follow-up
              </h4>
              <div class="flex gap-2">
                <DatePicker
                  v-model="followUpForm.next_follow_up_date"
                  placeholder="Select Date"
                  class="flex-1"
                />
                <x-button
                  size="sm"
                  color="purple"
                  @click="scheduleFollowUp"
                  :loading="followUpForm.processing"
                >
                  Schedule
                </x-button>
              </div>
            </div>

            <!-- Overdue Alert -->
            <div
              v-if="isOverdue"
              class="bg-red-50 p-4 rounded border-l-4 border-red-400"
            >
              <h4 class="font-semibold mb-2 text-red-700">Overdue Follow-up</h4>
              <p class="text-sm text-red-600">
                This claim requires follow-up (Due:
                {{ formatDate(claim.next_follow_up_date) }})
              </p>
            </div>
          </div>
        </template>
      </Collapsible>
    </div>

    <!-- Claim Details -->
    <div class="p-4 rounded shadow mb-6 bg-white">
      <Collapsible :expanded="sectionExpanded">
        <template #header>
          <div class="flex justify-between items-center">
            <h3 class="font-semibold text-primary-800 text-lg">
              Claim Details
            </h3>
          </div>
        </template>
        <template #body>
          <x-divider class="my-4" />
          <div class="text-sm">
            <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 break-words">
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CODE</dt>
                <dd class="font-mono">{{ claim.code }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">FIRST NAME</dt>
                <dd>{{ claim.first_name }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">LAST NAME</dt>
                <dd>{{ claim.last_name }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">EMAIL</dt>
                <dd>{{ claim.email }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">MOBILE NUMBER</dt>
                <dd>{{ claim.mobile_no }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">QUOTE TYPE</dt>
                <dd>{{ claim.quoteType?.text }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CLAIM TYPE</dt>
                <dd>{{ claim.claimType?.text }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">POLICY NUMBER</dt>
                <dd>{{ claim.policy_number || '-' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">SOURCE</dt>
                <dd>{{ claim.source || '-' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">INCIDENT</dt>
                <dd>{{ claim.incident || '-' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">INSURANCE PROVIDER</dt>
                <dd>{{ claim.insuranceProvider?.name || '-' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">WHATSAPP CONSENT</dt>
                <dd>{{ claim.whatsapp_consent ? 'Yes' : 'No' }}</dd>
              </div>
              <!-- Vehicle Details if available -->
              <template v-if="claim.claimRequestDetails?.length > 0">
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">VEHICLE MAKE</dt>
                  <dd>{{ claim.claimRequestDetails[0].car_make || '-' }}</dd>
                </div>
                <div class="grid sm:grid-cols-2">
                  <dt class="font-medium">VEHICLE MODEL</dt>
                  <dd>{{ claim.claimRequestDetails[0].car_model || '-' }}</dd>
                </div>
              </template>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">STATUS</dt>
                <dd>
                  <x-tag
                    size="sm"
                    :color="
                      claim.claim_status === 'Closed'
                        ? 'success'
                        : claim.claim_status === 'Open'
                          ? 'warning'
                          : claim.claim_status === 'Cancelled'
                            ? 'error'
                            : 'info'
                    "
                  >
                    {{ claim.claim_status }}
                  </x-tag>
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">SUB STATUS</dt>
                <dd>{{ claim.claimSubStatus?.text || '-' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">INCIDENT DATE</dt>
                <dd>{{ formatDate(claim.incident_date) }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">LEAD SOURCE</dt>
                <dd>{{ claim.lead_source || '-' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">POLICY ADVISOR</dt>
                <dd>{{ claim.policy_advisor || '-' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CREATED DATE</dt>
                <dd>{{ formatDateTime(claim.created_at) }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">LAST MODIFIED DATE</dt>
                <dd>{{ formatDateTime(claim.updated_at) }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">ASSIGNED CLAIMS MANAGER</dt>
                <dd>{{ claim.assignedClaimsManager?.name || '-' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CLAIMS MANAGER</dt>
                <dd>{{ claim.claimsManager?.name || '-' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">MANAGER ASSIGNED DATE</dt>
                <dd>{{ formatDate(claim.claims_manager_assigned_date) }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">COMPLAINT STATUS</dt>
                <dd>
                  <x-tag
                    size="sm"
                    :color="
                      claim.complaint_status === 'Complaint Open'
                        ? 'error'
                        : claim.complaint_status === 'Complaint Closed'
                          ? 'success'
                          : 'gray'
                    "
                  >
                    {{ claim.complaint_status }}
                  </x-tag>
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">NEXT FOLLOW-UP DATE</dt>
                <dd :class="{ 'text-red-600 font-semibold': isOverdue }">
                  {{ formatDate(claim.next_follow_up_date) }}
                  <span v-if="isOverdue" class="ml-2 text-red-500"
                    >(Overdue)</span
                  >
                </dd>
              </div>
              <div class="grid sm:grid-cols-2" v-if="isCarLOB">
                <dt class="font-medium">PLATE NUMBER</dt>
                <dd>{{ claim.plate_number || '-' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2" v-if="isCarLOB">
                <dt class="font-medium">VEHICLE MAKE</dt>
                <dd>{{ claim.vehicle_make || '-' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2" v-if="isCarLOB">
                <dt class="font-medium">VEHICLE MODEL</dt>
                <dd>{{ claim.vehicle_model || '-' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2" v-if="isCarLOB">
                <dt class="font-medium">VEHICLE YEAR</dt>
                <dd>{{ claim.vehicle_year || '-' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2" v-if="isHealthLOB">
                <dt class="font-medium">HEALTH CLAIM SERVICE TYPE</dt>
                <dd>{{ claim.health_claim_service_type || '-' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2" v-if="isHealthLOB">
                <dt class="font-medium">HEALTH SERVICE TYPE</dt>
                <dd>{{ claim.health_service_type || '-' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">APPROVED REPAIR AMOUNT</dt>
                <dd>{{ formatCurrency(claim.approved_repair_amount) }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">APPROVED TOTAL LOSS AMOUNT</dt>
                <dd>{{ formatCurrency(claim.approved_total_loss_amount) }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">APPROVED CASH LOSS AMOUNT</dt>
                <dd>{{ formatCurrency(claim.approved_cash_loss_amount) }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CLAIM DENIAL REASON</dt>
                <dd>{{ claim.claim_denial_reason || '-' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">ADDITIONAL NOTES</dt>
                <dd>
                  {{ claim.additional_notes || 'No additional notes provided' }}
                </dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">CREATED BY</dt>
                <dd>{{ claim.createdBy?.name || '-' }}</dd>
              </div>
              <div class="grid sm:grid-cols-2">
                <dt class="font-medium">UPDATED BY</dt>
                <dd>{{ claim.updatedBy?.name || '-' }}</dd>
              </div>
            </dl>
          </div>
        </template>
      </Collapsible>
    </div>

    <!-- Audit Logs -->
    <div class="p-4 rounded shadow mb-6 bg-white">
      <Collapsible :expanded="sectionExpanded">
        <template #header>
          <div class="flex justify-between items-center">
            <h3 class="font-semibold text-primary-800 text-lg">Audit Logs</h3>
          </div>
        </template>
        <template #body>
          <x-divider class="my-4" />
          <AuditLogs :id="claim.id" :auditType="'Claim'" />
        </template>
      </Collapsible>
    </div>
  </div>
</template>
