<script setup>
import ClaimDetails from './Components/ClaimDetails.vue';
import ClaimStatus from './Components/ClaimStatus.vue';
import ClaimDocuments from './Components/ClaimDocuments.vue';
import CustomerDetails from './Components/CustomerDetails.vue';

const props = defineProps({
  claim: Object,
  dropdowns: Object,
  additionalContacts: Object,
  documents: Object,
});

const modelClass = 'App\\Models\\ClaimRequest';
const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const sectionExpanded = ref(true);

function formatDateTime(date) {
  console.log('formatDateTime -> date -> ', date);
  if (!date) return '-';
  return new Date(date).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

// Handle component updates
const handleClaimUpdate = response => {
  console.log('Claim updated:', response);
  // You can add any additional logic here when claim is updated
};

const handleDocumentUploaded = document => {
  console.log('Document uploaded:', document);
  // You can add any additional logic here when document is uploaded
};

const handleDocumentDeleted = documentName => {
  console.log('Document deleted:', documentName);
  // You can add any additional logic here when document is deleted
};
</script>

<template>
  <div>
    <Head :title="`Claim ${claim.code}`" />
    <StickyHeader>
      <template v-slot:header>
        <h2 class="text-xl font-semibold">Claim Details - {{ claim.uuid }}</h2>
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
            :href="`/claim/${claim.uuid}/edit`"
          >
            <x-button size="sm" color="emerald" tag="div">Edit</x-button>
          </Link>
        </div>
      </div>
    </StickyHeader>

    <!-- Claim Details Component -->
    <ClaimDetails
      :claim="claim"
      :dropdowns="dropdowns"
      @update="handleClaimUpdate"
    />

    <!-- Customer Details Component -->
    <CustomerDetails :claim="claim" />

    <!-- Claim Status Component -->
    <ClaimStatus
      :claim="claim"
      :dropdowns="dropdowns"
      @update="handleStatusUpdate"
    />

    <!-- Claim Documents Component -->
    <ClaimDocuments
      :claim="claim"
      :documents="documents"
      @update="handleClaimUpdate"
      @documentUploaded="handleDocumentUploaded"
      @documentDeleted="handleDocumentDeleted"
    />

    <!-- Audit Logs -->
    <AuditLogs
      :type="modelClass"
      :id="$page.props.claim.id"
      :quoteCode="$page.props.claim.code"
      :expanded="sectionExpanded"
    />
  </div>
</template>
