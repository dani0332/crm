<script setup>
const page = usePage();
const notification = useNotifications('toast');

defineProps({
  quote: Object,
});

const modals = reactive({
  addContact: false,
  contactDeleteConfirm: false,
  contactPrimaryConfirm: false,
});

const confirmDeleteData = reactive({
  contact: null,
});

const confirmData = reactive({
  contactPrimary: null,
});

const contactLoader = ref(false);

function addContactModal() {
  contactForm.key = '';
  contactForm.value = '';
  modals.addContact = true;
}

const { isRequired, isEmail, isMobileNo } = useRules();

// additional contact
const additionalContactTable = [
  { text: 'Type', value: 'key' },
  { text: 'Value', value: 'value' },
  { text: 'Created At', value: 'created_at' },
  { text: 'Action', value: 'action' },
];

const contactForm = useForm({
  id: null,
  key: '',
  value: '',
  quote_id: page.props.quote.id,
  customer_id: page.props.quote.customer_id,
});

const additionalContactPrimary = data => {
  modals.contactPrimaryConfirm = true;
  confirmData.contactPrimary = data;
};

const additionalContactDelete = id => {
  modals.contactDeleteConfirm = true;
  confirmDeleteData.contact = id;
};

const onAdditionalContactSubmit = isValid => {
  if (!isValid) return;

  contactForm.clearErrors();
  contactForm.post(
    `/customers/` + page.props.quote.customer_id + `/additional-contacts`,
    {
      preserveScroll: true,
      onSuccess: () => {
        modals.addContact = false;
        notification.success({
          title: 'Additional Contact Added',
          position: 'top',
        });
      },
      onError: err => {
        notification.error({ title: err.error, position: 'top' });
      },
      onFinish: () => {},
    },
  );
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
const additionalContactPrimaryConfirmed = () => {
  const url = `/personal-quotes/${page.props.quote.id}/change-primary-contact`;

  router.patch(
    url,
    {
      isInertia: true,
      quote_id: page.props.quote.id,
      key: confirmData.contactPrimary.key,
      value: confirmData.contactPrimary.value,
      quote_type: 'personal',
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
</script>
<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div class="flex flex-wrap gap-3 justify-between items-center mb-4">
      <h3 class="font-semibold text-primary-800 text-lg">
        Customer Additional Contacts
        <x-tag size="sm">{{
          quote.customer.additional_contact_info?.length || 0
        }}</x-tag>
      </h3>
      <x-button size="sm" color="orange" @click="addContactModal">
        Add Additional Contacts
      </x-button>
    </div>

    <DataTable
      table-class-name="compact"
      :headers="additionalContactTable"
      :items="quote.customer.additional_contact_info || []"
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
            v-model="contactForm.key"
            label="Type"
            :options="[
              { value: 'email', label: 'Email' },
              { value: 'mobile_no', label: 'Mobile Number' },
            ]"
            :rules="[isRequired]"
            placeholder="Select Type"
            class="w-full"
            :error="contactForm.errors.key"
          />

          <x-input
            v-if="contactForm.key === 'email'"
            v-model="contactForm.value"
            label="Value"
            :rules="[isRequired, isEmail]"
            :error="contactForm.errors.value"
            class="w-full"
          />

          <x-input
            v-if="contactForm.key === 'mobile_no'"
            type="text"
            v-model="contactForm.value"
            label="Value"
            :rules="[isRequired, isMobileNo]"
            :error="contactForm.errors.value"
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
            :loading="contactForm.processing"
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
</template>
