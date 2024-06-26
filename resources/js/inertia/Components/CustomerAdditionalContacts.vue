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
});

const { isRequired, isEmail, isMobileNo } = useRules();

const notification = useNotifications('toast');

const contactLoader = ref(false);
const EmailCheckLoader = ref(false);

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
        modals.customerAlreadyPrimaryConfirm = true;
      } else {
        additionalContactPrimaryConfirmed();
      }
    })
    .catch(err => {
      console.log(err);
    });
};

function additionalContactPrimaryConfirmed() {
  const isEmail = confirmData.contactPrimary.key === 'email';
  router.post(
    `/customer-additional-contact/${
      isEmail ? confirmData.contactPrimary.id : 0
    }/make-primary`,
    {
      isInertia: true,
      quote_id: props.quoteId,
      quote_type: props.quoteType,
      key: confirmData.contactPrimary.key,
      value: confirmData.contactPrimary.value,
      quote_customer_id: props.customerId,
      quote_primary_email_address: props.quoteEmail,
      quote_primary_mobile_no: props.quoteMobile,
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
        EmailCheckLoader.value = false;
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
    },
  );
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
</script>

<template>
  <x-collapse show-icon class="p-4 rounded shadow mb-6 bg-white">
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
        show-close
        backdrop
      >
        <template #header> Alert: Primary Contact Update </template>
        <p>
          You are about to set this "email" as the primary contact for this
          lead. This action will add this lead to the list of other existing
          leads associated with the same email.
        </p>
        <br />
        <p>Are you sure you want to continue?</p>
        <template #actions>
          <div class="text-right space-x-4">
            <x-button
              size="sm"
              ghost
              @click.prevent="modals.customerAlreadyPrimaryConfirm = false"
            >
              Cancel
            </x-button>
            <x-button
              size="sm"
              color="emerald"
              @click.prevent="additionalContactPrimaryConfirmed"
              :loading="contactLoader"
            >
              Continue
            </x-button>
          </div>
        </template>
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
    </template>
  </x-collapse>
</template>