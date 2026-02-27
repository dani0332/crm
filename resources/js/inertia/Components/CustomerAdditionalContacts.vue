<script setup>
const props = defineProps({
  quoteType: {
    type: String,
    required: true,
  },
  customerId: {
    required: true,
  },
  quoteId: {
    required: true,
  },
  contacts: {
    type: Array,
    default: () => [],
  },
  quoteEmail: {
    type: String,
  },
  quoteMobile: {
    type: String,
  },
  canDelete: {
    type: Boolean,
    required: false,
    default: true,
  },
  quoteStatusId: {
    type: Number,
    required: false,
    default: null,
  },
});

const { isRequired, isEmail, isMobileNo } = useRules();

const notification = useNotifications('toast');
const page = usePage();
const permissionsEnum = page.props.permissionsEnum;
const can = permission => useCan(permission);
const quoteStatusEnum = page.props.quoteStatusEnum;

const contactLoader = ref(false);
const EmailCheckLoader = ref(false);
const keepExistingPrimaryEmailLoader = ref(null);

const additionalContactTable = [
  { text: 'Type', value: 'key' },
  { text: 'Value', value: 'value' },
  { text: 'Created At', value: 'created_at' },
  { text: 'Action', value: 'action' },
];

const modals = reactive({
  addContact: false,
  contactPrimaryConfirm: false,
  contactDeleteConfirm: false,
  customerAlreadyPrimaryConfirm: false,
});

const addAdditionalContact = () => {
  additionalContact.additional_contact_type = null;
  additionalContact.additional_contact_val = null;
  modals.addContact = true;
};

const additionalContact = useForm({
  id: null,
  additional_contact_type: null,
  additional_contact_val: null,
  quote_id: props.quoteId,
  customer_id: props.customerId,
  quote_type: props.quoteType,
});

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

const isPrimaryEmailLocked = computed(() => {
  return [
    quoteStatusEnum.POLICY_BOOKING_QUEUED,
    quoteStatusEnum.POLICY_BOOKING_FAILED,
  ].includes(props.quoteStatusId);
});

const confirmData = reactive({
  contactPrimary: null,
});
const additionalContactPrimary = data => {
  modals.contactPrimaryConfirm = true;
  confirmData.contactPrimary = data;
};

const customerAlreadyPrimaryCheck = async () => {
  let data = {
    isInertial: true,
    key: confirmData.contactPrimary.key,
    value: confirmData.contactPrimary.value,
  };

  EmailCheckLoader.value = true;

  axios
    .post('/customer-primary-email-check', data)
    .then(res => {
      if (res.data.response === true) {
        modals.contactPrimaryConfirm = false;
        EmailCheckLoader.value = false;
        modals.customerAlreadyPrimaryConfirm = true;
      } else {
        additionalContactPrimaryConfirmed();
      }
    })
    .catch(err => {
      console.log(err);
    });
};

function additionalContactPrimaryConfirmed(keepExistingPrimaryEmail = true) {
  const isEmail = confirmData.contactPrimary.key === 'email';

  // Determine the API endpoint based on quote type
  let endpoint;
  let requestData;
  keepExistingPrimaryEmailLoader.value = keepExistingPrimaryEmail;

  if (props.quoteType === 'Claim') {
    // For claims, use the claims-specific endpoint
    endpoint = route('claims.make-additional-contact-primary', props.quoteId);
    requestData = {
      key: confirmData.contactPrimary.key,
      value: confirmData.contactPrimary.value,
    };
  } else {
    // For other quote types, use the existing customer endpoint
    endpoint = `/customer-additional-contact/${
      isEmail ? confirmData.contactPrimary.id : 0
    }/make-primary`;
    requestData = {
      isInertia: true,
      quote_id: props.quoteId,
      quote_type: props.quoteType,
      key: confirmData.contactPrimary.key,
      value: confirmData.contactPrimary.value,
      quote_customer_id: props.customerId,
      quote_primary_email_address: props.quoteEmail,
      quote_primary_mobile_no: props.quoteMobile,
      keep_existing_primary_email: keepExistingPrimaryEmail ? 1 : 0,
    };
  }

  router.post(endpoint, requestData, {
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
      EmailCheckLoader.value = false;
      keepExistingPrimaryEmailLoader.value = null;
      modals.contactPrimaryConfirm = false;
      modals.customerAlreadyPrimaryConfirm = false;
    },
    onError: err => {
      const firstError = Object.values(err)[0];
      notification.error({
        title: firstError,
        position: 'top',
      });
    },
  });
}

const confirmDeleteData = reactive({
  contact: null,
});

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
      onFinish: () => {
        contactLoader.value = false;
        modals.contactDeleteConfirm = false;
      },
      onError: err => {
        const firstError = Object.values(err)[0];
        notification.error({
          title: firstError,
          position: 'top',
        });
      },
    },
  );
};

const readOnlyMode = reactive({
  isDisable: true,
});
</script>

<template>
  <x-accordion
    show-icon
    class="p-4 rounded shadow mb-6 bg-white"
    :expanded="expanded"
  >
    <x-accordion-item>
      <h3 class="font-semibold text-primary-800 text-lg">
        Customer Additional Contacts
        <x-tag size="sm">{{ contacts.length || 0 }}</x-tag>
      </h3>
      <template #content>
        <x-divider class="mb-4 mt-1" />
        <div class="flex justify-end gap-4 items-center mb-4">
          <x-button
            size="sm"
            color="orange"
            @click.prevent="addAdditionalContact"
            v-if="readOnlyMode.isDisable === true"
          >
            Add Additional Contacts
          </x-button>
        </div>
        <DataTable
          table-class-name="compact"
          :headers="additionalContactTable"
          :items="contacts || []"
          border-cell
          hide-rows-per-page
          :rows-per-page="10"
          :hide-footer="contacts?.length < 10"
        >
          <template #item-key="{ key }">
            <span v-if="key === 'email'"> Email Address </span>
            <span v-else> Mobile Number </span>
          </template>
          <template #item-action="item">
            <div class="space-x-4">
              <x-tooltip
                v-if="isPrimaryEmailLocked && item.key === 'email'"
                placement="bottom"
              >
                <x-button size="xs" color="red" outlined disabled>
                  Make Primary
                </x-button>
                <template #tooltip>
                  Primary email ID cannot be changed while the policy booking is
                  in progress.
                </template>
              </x-tooltip>
              <x-button
                v-else-if="readOnlyMode.isDisable === true"
                size="xs"
                color="emerald"
                outlined
                @click.prevent="additionalContactPrimary(item)"
              >
                Make Primary
              </x-button>
              <x-button
                size="xs"
                color="red"
                outlined
                @click.prevent="additionalContactDelete(item.id)"
                v-if="
                  readOnlyMode.isDisable === true &&
                  can(permissionsEnum.DELETE_ADDITIONAL_CONTACT)
                "
              >
                Delete
              </x-button>
            </div>
          </template>
        </DataTable>

        <x-modal
          v-model="modals.addContact"
          size="md"
          title="Add Additional Contacts"
          show-close
          backdrop
          is-form
          persistent
          @submit="onAdditionalContactSubmit"
        >
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
              v-if="additionalContact.additional_contact_type === 'mobile_no'"
              v-model="additionalContact.additional_contact_val"
              label="Value"
              :rules="[isRequired, isMobileNo]"
              class="w-full"
            />

            <x-input
              v-if="additionalContact.additional_contact_type === 'email'"
              v-model="additionalContact.additional_contact_val"
              label="Value"
              :rules="[isRequired, isEmail]"
              class="w-full"
            />
          </div>

          <template #secondary-action>
            <x-button ghost tabindex="-1" @click="modals.addContact = false">
              Cancel
            </x-button>
          </template>
          <template #primary-action>
            <x-button
              color="primary"
              type="submit"
              :loading="additionalContact.processing"
            >
              Save
            </x-button>
          </template>
        </x-modal>

        <x-modal
          v-model="modals.contactPrimaryConfirm"
          title="Primary Additional Contact"
          show-close
          backdrop
        >
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
                :loading="EmailCheckLoader"
                @click.prevent="customerAlreadyPrimaryCheck"
              >
                Confirm
              </x-button>
            </div>
          </template>
        </x-modal>

        <x-modal
          v-model="modals.customerAlreadyPrimaryConfirm"
          title="Alert: Primary Contact Update"
          show-close
          backdrop
        >
          <p>
            Do you want to keep the existing primary email ID as the additional
            contact for this lead?
          </p>
          <template #actions>
            <div class="text-right space-x-4">
              <x-button
                size="sm"
                color="primary"
                @click.prevent="additionalContactPrimaryConfirmed(true)"
                :loading="
                  keepExistingPrimaryEmailLoader === true && contactLoader
                "
                :disabled="
                  keepExistingPrimaryEmailLoader === false && contactLoader
                "
              >
                Yes
              </x-button>
              <x-button
                size="sm"
                ghost
                color="red"
                outlined
                @click.prevent="additionalContactPrimaryConfirmed(false)"
                :loading="
                  keepExistingPrimaryEmailLoader === false && contactLoader
                "
                :disabled="
                  keepExistingPrimaryEmailLoader === true && contactLoader
                "
              >
                No
              </x-button>
            </div>
          </template>
        </x-modal>

        <x-modal
          v-model="modals.contactDeleteConfirm"
          title="Delete Additional Contact"
          show-close
          backdrop
        >
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
      </template>
    </x-accordion-item>
  </x-accordion>
</template>
