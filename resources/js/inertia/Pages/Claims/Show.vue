<script setup>
import ClaimDetails from './Components/ClaimDetails.vue';
import ClaimStatus from './Components/ClaimStatus.vue';
import ClaimSubStatusAndCustomerUpdate from './Components/ClaimSubStatusAndCustomerUpdate.vue';
import ClaimDocuments from './Components/ClaimDocuments.vue';
import CustomerDetails from './Components/CustomerDetails.vue';
import ClaimLeadHistory from './Components/ClaimLeadHistory.vue';
import ClaimSubStatusLogs from './Components/ClaimSubStatusLogs.vue';
import NextFollowUpUpdate from './Components/NextFollowUpUpdate.vue';
import NextFollowUpLogs from './Components/NextFollowUpLogs.vue';
import ComplaintStatus from './Components/ComplaintStatus.vue';
import ComplaintStatusLogs from './Components/ComplaintStatusLogs.vue';
import CustomerAdditionalContacts from '../../Components/CustomerAdditionalContacts.vue';

const props = defineProps({
  claim: Object,
  dropdowns: Object,
  additionalContacts: Object,
  documents: Object,
  complaintStatuses: Object,
  claimDocumentTypes: Object,
  requiredFieldsFilled: Boolean,
  storageUrl: String,
  cdnPath: String,
});

const modelClass = 'App\\Models\\ClaimRequest';
const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const sectionExpanded = ref(true);
 

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
      :expanded="sectionExpanded"
    />

    <!-- Customer Details Component -->
    <CustomerDetails :claim="claim"  />

    <!-- Customer Additional Contacts Component -->
    <CustomerAdditionalContacts
      quoteType="Claim"
      :customerId="claim.customer_id"
      :quoteId="claim.uuid"
      :contacts="additionalContacts"
      :quoteEmail="claim.email"
      :quoteMobile="claim.mobile_no" 
    />

    <!-- Claim Status Component -->
    <ClaimStatus
      :claim="claim"
      :required-fields-filled="requiredFieldsFilled"
      :dropdowns="dropdowns"
      @update="handleStatusUpdate" 
    />
    <!-- Claim Status Component -->
    <ClaimSubStatusAndCustomerUpdate
      :claim="claim"
      :required-fields-filled="requiredFieldsFilled"
      :dropdowns="dropdowns"
      @update="handleStatusUpdate" 
    />

    <!-- Next Follow-Up Update Component -->
    <NextFollowUpUpdate
      :claim="claim" 
      @update="handleClaimUpdate"
    />


    <!-- Complaint Status Component -->
    <ComplaintStatus
      :claim="claim" 
      :complaint-statuses="complaintStatuses"
      @update="handleClaimUpdate"
    />


    <!-- Claim Documents Component -->
    <ClaimDocuments
      :claim="claim"
      :documents="documents"
      @update="handleClaimUpdate"
      @documentUploaded="handleDocumentUploaded"
      @documentDeleted="handleDocumentDeleted"
      :storage-url="storageUrl"
      :document-types="claimDocumentTypes"
      :cdnPath="cdnPath"
    />

    
    <!-- Next Follow-Up Logs Component -->
    <NextFollowUpLogs
      :claim="claim" 
    />
    <!-- Complaint Status Logs Component -->
    <ComplaintStatusLogs
      :claim="claim" 
    />

    <!-- Claim Lead History Component -->
    <ClaimLeadHistory
      :claim="claim"
    />

    <!-- Claim Sub-status Logs Component -->
    <ClaimSubStatusLogs
      :claim="claim"
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
