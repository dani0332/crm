<script setup>
const page = usePage();
const notification = useNotifications('toast');

defineProps({
  quote: Object,
  quoteType: String,
});
const permissionsEnum = page.props.permissionsEnum;
const can = permission => useCan(permission);
const quoteStatusEnum = page.props.quoteStatusEnum;

const modals = reactive({
  addContact: false,
  contactDeleteConfirm: false,
  contactPrimaryConfirm: false,
  customerAlreadyPrimaryConfirm: false,
});

const confirmDeleteData = reactive({
  contact: null,
});

const confirmData = reactive({
  contactPrimary: null,
});

const contactLoader = ref(false);
const EmailCheckLoader = ref(false);
const keepExistingPrimaryEmailLoader = ref(null);

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
  quote_type: page.props.quoteType,
  customer_id: page.props.quote.customer_id,
});

const numbersOnly = evt => {
  const charCode = evt.which || evt.keyCode;

  if (charCode > 31 && (charCode < 48 || charCode > 57) && charCode !== 46) {
    evt.preventDefault();
  } else {
    return true;
  }
};

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
      onSuccess: res => {
        if (res.props.flash.success) {
          notification.success({
            title: res.props.flash.success,
            position: 'top',
          });
        } else {
          notification.success({
            title: 'Additional Contact Added',
            position: 'top',
          });
        }
      },
      onError: err => {
        notification.error({ title: err.error ?? err.value, position: 'top' });
      },
      onFinish: () => {
        modals.addContact = false;
      },
    },
  );
};

const isPrimaryEmailLocked = computed(() => {
  return [
    quoteStatusEnum.POLICY_BOOKING_QUEUED,
    quoteStatusEnum.POLICY_BOOKING_FAILED,
  ].includes(page.props.quote?.quote_status_id);
});

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

const additionalContactPrimaryConfirmed = (keepExistingPrimaryEmail = true) => {
  const url = `/personal-quotes/${page.props.quote.id}/change-primary-contact`;
  keepExistingPrimaryEmailLoader.value = keepExistingPrimaryEmail;

  router.patch(
    url,
    {
      isInertia: true,
      quote_id: page.props.quote.id,
      quote_type: page.props.quoteType,
      key: confirmData.contactPrimary.key,
      value: confirmData.contactPrimary.value,
      quote_customer_id: page.props.quote.customer_id,
      quote_primary_email_address: page.props.quote.email,
      quote_primary_mobile_no: page.props.quote.mobile_no,
      keep_existing_primary_email: keepExistingPrimaryEmail ? 1 : 0,
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
    },
  );
};

const additionalContact = computed(() => {
  if (page.props.quote?.customer?.additional_contact_info) {
    return page.props.quote?.customer?.additional_contact_info;
  }
  return [];
});

const readOnlyMode = reactive({
  isDisable: true,
});
onMounted(() => {
  readOnlyMode.isDisable = !can(permissionsEnum.All_QUOTES_VIEWONLY_ACCESS);
});
</script>
<template>
  <x-accordion show-icon>
    <x-accordion-item class="p-4 rounded shadow mb-6 bg-white">
      <h3 class="font-semibold text-primary-800 text-lg">
        Customer Additional Contacts
        <x-tag size="sm">{{
          additionalContact.length > 0 ? additionalContact.length : 0
        }}</x-tag>
      </h3>
      <template #content>
        <x-divider class="mb-4 mt-1" />
        <div class="flex flex-wrap gap-3 justify-end items-center mb-4">
          <x-button
            size="sm"
            color="orange"
            @click="addContactModal"
            v-if="readOnlyMode.isDisable === true"
          >
            Add Additional Contacts
          </x-button>
        </div>
        <DataTable
          table-class-name="compact"
          :headers="additionalContactTable"
          :items="additionalContact ?? []"
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
              type="tel"
              v-model="contactForm.value"
              @keydown="numbersOnly"
              label="Value"
              :rules="[isRequired, isMobileNo]"
              :error="contactForm.errors.value"
              class="w-full"
            />
          </div>

          <template #secondary-action>
            <x-button
              ghost
              tabindex="-1"
              size="sm"
              @click.prevent="modals.addContact = false"
            >
              Cancel
            </x-button>
          </template>
          <template #primary-action>
            <x-button
              size="sm"
              color="emerald"
              :loading="contactForm.processing"
              type="submit"
            >
              Save
            </x-button>
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
      </template>
    </x-accordion-item>
  </x-accordion>
</template>
