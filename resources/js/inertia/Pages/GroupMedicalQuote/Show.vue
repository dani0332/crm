<script setup>

defineProps({
  quote: Object,
  genderOptions: Object,
  assignedGMType: String,
  allowedDuplicateLOB: Array,
  isDuplicateAllowed: Boolean,
  quoteDetails: Object,
  customerAdditionalContacts: Array,
  enums: Object,
  permissions: Object,
});

const page = usePage();

const notification = useNotifications('toast');

const { copy, copied } = useClipboard();

const { isRequired } = useRules();

const dateToYMD = date => {
  if (date) {
    const d = new Date(date);
    const year = d.getFullYear();
    const month = `0${d.getMonth() + 1}`.slice(-2);
    const day = `0${d.getDate()}`.slice(-2);
    return `${year}-${month}-${day}`;
  }
  return '';
};

const genderText = gender =>
  computed(() => {
    return page.props.genderOptions[gender];
  });

const modals = reactive({
  duplicate: false,
  member: false,
  memberConfirm: false,
  doc: false,
  docConfirm: false,
  plan: false,
  createPlan: false,
  activity: false,
  activityConfirm: false,
  addContact: false,
  contactDeleteConfirm: false,
  contactPrimaryConfirm: false,
});

const leadDuplicateForm = useForm({
  lob_team: [],
});

const openDuplicate = () => {
  modals.duplicate = true;
  leadDuplicateForm.reset();
};

const onCreateDuplicate = isValid => {
  if (!isValid) return;
  let data = {
    modelType: 'business',
    parentType: 'business',
    entityId: page.props.quote.id,
    entityCode: page.props.quote.code,
    entityUId: page.props.quote.uuid,
    lob_team: leadDuplicateForm.lob_team,
  };
  axios
    .post('/quotes/createDuplicate', data)
    .then(res => {
      modals.duplicate = false;
      notification.success('Lead duplicated successfully');
      router.visit('/medical/amt');
    })
    .catch(err => {
      notification.error('Something went wrong');
    });
};

// Lead Status

const leadStatusOptions = computed(() => {
  return page.props.leadStatuses.map(status => ({
    value: status.id,
    label: status.text,
  }));
});

const leadStatusForm = useForm({
  modelType: 'Business',
  leadId: page.props.quote.id,
  quote_uuid: page.props.quote.uuid,
  assigned_to_user_id: page.props.quote.advisor_id,
  leadStatus: page.props.quote.quote_status_id || null,
  notes: page.props.quoteDetails.notes || null,
  trans_code: page.props.quote.transapp_code || null,
  lostReason: page.props.quote.lost_reason || null,
});

const onLeadStatus = () => {

  let data = {
    modelType: 'Business',
    leadId: leadStatusForm.leadId,
    quote_uuid: leadStatusForm.quote_uuid,
    assigned_to_user_id: leadStatusForm.assigned_to_user_id,
    leadStatus: leadStatusForm.leadStatus,
    notes: leadStatusForm.notes,
    trans_code: leadStatusForm.trans_code,
    lostReason: leadStatusForm.lostReason,
  };

  axios
    .post(`/quotes/Business/${page.props.quote.id}/update-lead-status`, data)
    .then(res => {
      console.log(res);
      notification.success({
        title: 'Lead Status Updated',
        position: 'top',
      });
    })
    .catch(err => {
      console.log(err);
    });
};

// Lead History

const historyData = ref(null),
  historyLoading = ref(false);

const onLoadHistoryData = async () => {
  historyLoading.value = true;
  const res = await fetch(
    `/quotes/getLeadHistory?modelType=business&recordId=${page.props.quote.id}`,
  );
  const finalRes = await res.json();
  historyData.value = finalRes;
  historyLoading.value = false;
};

const historyDataTable = [
  { text: 'Modified At', value: 'ModifiedAt' },
  { text: 'Modified By', value: 'ModifiedBy' },
  { text: 'Notes', value: 'NewNotes' },
  { text: 'Lead Status', value: 'NewStatus' },
];

// additional contact

const additionalContactTable = [
  { text: 'Type', value: 'key' },
  { text: 'Value', value: 'value' },
  { text: 'Created At', value: 'created_at' },
  { text: 'Action', value: 'action' },
];

const additionalContact = useForm({
  id: null,
  additional_contact_type: null,
  additional_contact_val: null,
  quote_id: page.props.quote.id,
  customer_id: page.props.quote.customer_id,
  quote_type: 'business',
});

const addAdditionalContact = () => {
  additionalContact.additional_contact_type = null;
  additionalContact.additional_contact_val = null;
  modals.addContact = true;
};


const onAdditionalContactSubmit = isValid => {
  if (!isValid) return;
  additionalContact
    .transform(data => ({
      ...data,
      isInertia: true,
    }))
    .post(`/customer-additional-contact/add`, {
      preserveScroll: true,
      onSuccess: () => {
        notification.success({
          title: 'Additional Contact Added',
          position: 'top',
        });
      },
      onFinish: () => {
        modals.addContact = false;
      },
      onError: err => {
        const firstError = Object.values(err)[0];
        notification.error({
          title: firstError,
          position: 'top',
        });
      },
    });
};

const additionalContactDelete = id => {
  modals.contactDeleteConfirm = true;
  confirmDeleteData.contact = id;
};

const additionalContactDeleteConfirmed = () => {
  router.post(
    `/customer-additional-contact/${confirmDeleteData.contact}/delete`,
    {
      isInertia: true,
    },
    {
      preserveScroll: true,
      onBefore: () => {
        contactLoader.value = true;
      },
      onSuccess: () => {
        notification.error({
          title: 'Additional Contact Deleted',
          position: 'top',
        });
      },
      onFinish: () => {
        contactLoader.value = false;
        modals.contactDeleteConfirm = false;
      },
    },
  );
};

const additionalContactPrimary = data => {
  modals.contactPrimaryConfirm = true;
  confirmData.contactPrimary = data;
};

const additionalContactPrimaryConfirmed = () => {
  const isEmail = confirmData.contactPrimary.key === 'email';
  router.post(
    `/customer-additional-contact/${
      isEmail ? confirmData.contactPrimary.id : 0
    }/make-primary`,
    {
      isInertia: true,
      quote_id: page.props.quote.id,
      key: confirmData.contactPrimary.key,
      value: confirmData.contactPrimary.value,
      quote_type: 'business',
    },
    {
      preserveScroll: true,
      onBefore: () => {
        contactLoader.value = true;
      },
      onSuccess: () => {
        notification.success({
          title: 'Additional Contact Primary',
          position: 'top',
        });
      },
      onFinish: () => {
        contactLoader.value = false;
        modals.contactPrimaryConfirm = false;
      },
    },
  );
};

onMounted(() => {});
</script>
<template>
  <div>
    <Head title="Group Medical Lead Detail" />
    <div class="flex justify-between items-center flex-wrap gap-2">
      <h2 class="text-xl font-semibold">Group Medical Lead Detail</h2>
      <div class="flex gap-2">
        <x-button
          v-if="isDuplicateAllowed"
          size="sm"
          color="#ff5e00"
          @click.prevent="openDuplicate"
        >
          Duplicate Lead
        </x-button>

        <Link href="/medical/amt" preserve-scroll>
          <x-button size="sm" color="primary" tag="div">
            Group Medical List
          </x-button>
        </Link>

        <Link
          v-if="permissions.canEditQuote == true"
          :href="`${quote.uuid}/edit`"
        >
          <x-button size="sm" tag="div">Edit</x-button>
        </Link>
      </div>
    </div>

    <x-modal v-model="modals.duplicate" size="lg" show-close backdrop>
      <template #header> Duplicate Lead </template>
      <x-form @submit="onCreateDuplicate" :auto-focus="false">
        <div class="grid gap-4">
          <x-select
            v-model="leadDuplicateForm.lob_team"
            label="LOBs"
            :options="
              allowedDuplicateLOB.map(lob => ({
                value: lob,
                label: lob,
              }))
            "
            :rules="[isRequired]"
            placeholder="Select LOB For Duplication"
            class="w-full"
            multiple
          />

          <x-button
            color="orange"
            type="submit"
            :loading="leadDuplicateForm.processing"
          >
            Create Duplicate
          </x-button>
        </div>
      </x-form>
    </x-modal>

    <x-divider class="my-4" />

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CDB ID</dt>
            <dd>{{ quote.code }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">FIRST NAME</dt>
            <dd>{{ quote.first_name }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">LAST NAME</dt>
            <dd>{{ quote.last_name }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">MOBILE NUMBER</dt>
            <dd>{{ quote.mobile_no }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">EMAIL</dt>
            <dd>{{ quote.email }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">COMPANY NAME</dt>
            <dd>{{ quote.company_name }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">NEXT FOLLOWUP DATE</dt>
            <dd>{{ quote.next_followup_date }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">TRANSAPP CODE</dt>
            <dd>{{ quote.transapp_code }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">SOURCE</dt>
            <dd>{{ quote.source }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">LOST REASON</dt>
            <dd>{{ quote.lost_reason }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">ADVISOR</dt>
            <dd>{{ quote.advisor_id_text }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CREATED DATE</dt>
            <dd>{{ quote.created_at }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">LAST MODIFIED DATE</dt>
            <dd>{{ quote.updated_at }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">NUMBER OF EMPLOYEES</dt>
            <dd>{{ quote.number_of_employees }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">BUSINESS INSURANCE TYPE</dt>
            <dd>Group Medical</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">BRIEF DETAILS</dt>
            <dd>{{ quote.brief_details }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">RENEWAL EXPIRY DATE</dt>
            <dd>{{ quote.renewal_expiry_date }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">RENEWAL BATCH</dt>
            <dd>{{ quote.renewal_batch }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">GENDER</dt>
            <dd>{{ genderText(quote.gender).value }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PARENT CDB ID</dt>
            <dd>{{ quote.parent_duplicate_quote_id }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">RENEWAL IMPORT CODE</dt>
            <dd>{{ quote.renewal_import_code }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">DEVICE</dt>
            <dd>{{ quote.device }}</dd>
          </div>
        </dl>
      </div>

      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">
          Last Year's Policy Details
        </h3>
        <x-divider class="mb-4 mt-1" />
      </div>

      <div class="mt-6 text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PREVIOUS POLICY NUMBER</dt>
            <dd>{{ quote.previous_quote_policy_number }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PREVIOUS POLICY PREMIUM</dt>
            <dd>{{ quote.previous_quote_policy_premium }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PREVIOUS POLICY EXPIRY DATE</dt>
            <dd>{{ quote.previous_policy_expiry_date }}</dd>
          </div>
        </dl>
      </div>
      <x-divider class="mb-4 mt-1" />

      <div class="p-4 rounded shadow mb-6 bg-primary-50/25">
        <div>
          <h3 class="font-semibold text-primary-800 text-lg">Lead Status</h3>
          <x-divider class="mb-4 mt-1" />
        </div>
        <div class="flex flex-wrap md:flex-nowrap gap-6 w-full">
          <div class="w-full md:w-1/2">
            <div class="flex flex-col gap-4">
              <x-select
                v-model="leadStatusForm.leadStatus"
                label="STATUS"
                :options="leadStatusOptions"
                :disabled="
                  quote.quote_status_id ==
                  enums.quoteStatusEnum.TransactionApproved
                "
                placeholder="Lead Status"
                class="w-full"
              />
              <x-textarea
                v-model="leadStatusForm.notes"
                type="text"
                label="NOTES"
                placeholder="Lead Notes"
                class="w-full"
                :disabled="
                  quote.quote_status_id ==
                  enums.quoteStatusEnum.TransactionApproved
                "
              />
            </div>
          </div>
          <div class="w-full md:w-2/3">
            <x-input
              v-if="
                leadStatusForm.leadStatus ==
                enums.quoteStatusEnum.TransactionApproved
              "
              :disabled="
                quote.quote_status_id ==
                enums.quoteStatusEnum.TransactionApproved
              "
              v-model="leadStatusForm.trans_code"
              label="TRANSAPP CODE"
              placeholder="TransApp Code is required"
              class="w-full"
              :error="leadStatusForm.errors.trans_code"
            />
            <x-select
              v-if="leadStatusForm.leadStatus == enums.quoteStatusEnum.Lost"
              v-model="leadStatusForm.lostReason"
              label="LOST REASON"
              :options="lostReasonsOptions"
              placeholder="Lost Reason is required"
              class="w-full"
              :error="leadStatusForm.errors.lostReason"
            />
          </div>
        </div>
        <div class="flex justify-end">
          <x-button
            class="mt-4"
            color="emerald"
            size="sm"
            :loading="leadStatusForm.processing"
            @click.prevent="onLeadStatus"
            :disabled="
              quote.quote_status_id == enums.quoteStatusEnum.TransactionApproved
            "
          >
            Change Status
          </x-button>
        </div>
      </div>

      <div class="p-4 rounded shadow mb-6 bg-white">
        <div class="flex flex-wrap gap-3 justify-between items-center mb-4">
          <h3 class="font-semibold text-primary-800 text-lg">
            Customer Additional Contacts
            <x-tag size="sm">{{
              customerAdditionalContacts.length || 0
            }}</x-tag>
          </h3>
          <x-button
            size="sm"
            color="orange"
            @click.prevent="addAdditionalContact"
          >
            Add Additional Contacts
          </x-button>
        </div>

        <DataTable
          table-class-name="compact"
          :headers="additionalContactTable"
          :items="customerAdditionalContacts || []"
          border-cell
          hide-rows-per-page
          hide-footer
        >
          <template #item-key="{ key }">
            <span v-if="key === 'email'"> Email Address </span>
            <span v-else> Mobile Number </span>
          </template>
          <template #item-action="item">
            <div class="space-x-4">
              <x-button
                size="xs"
                color="emerald"
                outlined
                @click.prevent="additionalContactPrimary(item)"
              >
                Make Primary
              </x-button>
              <x-button
                size="xs"
                color="error"
                outlined
                @click.prevent="additionalContactDelete(item.id)"
              >
                Delete
              </x-button>
            </div>
          </template>
        </DataTable>

        <x-modal v-model="modals.addContact" size="lg" show-close backdrop>
          <template #header> Add Additional Contacts </template>

          <x-form @submit="onAdditionalContactSubmit" :auto-focus="false">
            <div class="grid gap-4">
              <x-select
                v-model="additionalContact.additional_contact_type"
                label="Type"
                :options="[
                  { value: 'email', label: 'Email' },
                  { value: 'mobile_no', label: 'Mobile Number' },
                ]"
                :rules="[isRequired]"
                placeholder="Select Type"
                class="w-full"
              />

              <x-input
                v-model="additionalContact.additional_contact_val"
                label="Value"
                :rules="[isRequired]"
                class="w-full"
              />
            </div>

            <div class="text-right space-x-4 mt-12">
              <x-button size="sm" @click.prevent="modals.addContact = false">
                Cancel
              </x-button>

              <x-button
                size="sm"
                color="emerald"
                :loading="additionalContact.processing"
                type="submit"
              >
                Save
              </x-button>
            </div>
          </x-form>
        </x-modal>

        <x-modal v-model="modals.contactDeleteConfirm" show-close backdrop>
          <template #header> Delete Additional Contact </template>
          <p>Are you sure you want to delete this?</p>
          <template #actions>
            <div class="text-right space-x-4">
              <x-button
                size="sm"
                ghost
                @click.prevent="modals.contactDeleteConfirm = false"
              >
                Cancel
              </x-button>
              <x-button
                size="sm"
                color="error"
                @click.prevent="additionalContactDeleteConfirmed"
                :loading="contactLoader"
              >
                Delete
              </x-button>
            </div>
          </template>
        </x-modal>

        <x-modal v-model="modals.contactPrimaryConfirm" show-close backdrop>
          <template #header> Primary Additional Contact </template>
          <p>Are you sure you want to make this information as Primary?</p>
          <template #actions>
            <div class="text-right space-x-4">
              <x-button
                size="sm"
                ghost
                @click.prevent="modals.contactPrimaryConfirm = false"
              >
                Cancel
              </x-button>
              <x-button
                size="sm"
                color="emerald"
                @click.prevent="additionalContactPrimaryConfirmed"
                :loading="contactLoader"
              >
                Confirm
              </x-button>
            </div>
          </template>
        </x-modal>
      </div>

      <div class="p-4 rounded shadow mb-6 bg-white">
        <div>
          <h3 class="font-semibold text-primary-800 text-lg">Lead History</h3>
          <x-divider class="mb-4 mt-1" />
        </div>
        <div v-if="historyData === null" class="text-center py-3">
          <x-button
            size="sm"
            color="primary"
            outlined
            @click.prevent="onLoadHistoryData"
            :loading="historyLoading"
          >
            Load History Data
          </x-button>
        </div>
        <DataTable
          v-else
          table-class-name="compact"
          :headers="historyDataTable"
          :items="historyData || []"
          border-cell
          hide-rows-per-page
          :rows-per-page="15"
          :hide-footer="historyData.length < 15"
        />
      </div>
    </div>
  </div>
</template>
