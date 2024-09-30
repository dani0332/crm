<script setup>
const notification = useNotifications('toast');

const props = defineProps({
  quote: Object,
  dropdownSource: Object,
  homePossessionTypeEnum: Object,
  model: String,
});
const page = usePage();
const hasContentOrBuilding = ref(true);

const quoteForm = useForm({
  modelType: '"Home"',
  model: props.model,
  first_name: props.quote?.first_name || null,
  last_name: props.quote?.last_name || null,
  email: props.quote?.email || null,
  mobile_no: props.quote?.mobile_no || null,
  premium: props.quote?.premium || null,
  policy_number: props.quote?.policy_number || null,
  ownership_status_possesion_type_id: props.quote?.home_quote?.ownership_status_possesion_type_id || null,
  type_of_property_accommodation_type_id:
    props.quote?.home_quote?.type_of_property_accommodation_type_id || null,
  address: props.quote?.home_quote?.address || null,
  has_contents: props.quote?.home_quote?.has_contents || null,
  has_building: props.quote?.home_quote?.has_building || null,
  has_personal_belongings:
    props.quote?.home_quote?.has_personal_belongings || null,
  contents_aed: props.quote?.home_quote?.contents_aed || null,
  building_aed: props.quote?.home_quote?.building_aed || null,
  personal_belongings_aed:
    props.quote?.home_quote?.personal_belongings_aed || null,
  location_area: props.quote?.home_quote?.location_area || null,
  addressObj: {
    address_type: page.props.customerAddressData?.type || null,
    villa_apartment_office_no:
      page.props.customerAddressData?.office_number || null,
    floor_no: page.props.customerAddressData?.floor_number || null,
    villa_building_name: page.props.customerAddressData?.building_name || null,
    street_name: page.props.customerAddressData?.street || null,
    area: page.props.customerAddressData?.area || null,
    city: page.props.customerAddressData?.city || null,
    landmark: page.props.customerAddressData?.landmark || null,
  },
});
const isEdit = computed(() => {
  return route().current().includes('edit');
});
const { isRequired, isEmail, isMobileNo } = useRules();

const handleConditionalFields = () => {
  if (
    quoteForm.ownership_status_possesion_type_id !== props.homePossessionTypeEnum.LANDLORD
  ) {
    quoteForm.has_building = false;
  }
  if (!Boolean(quoteForm.has_building)) {
    quoteForm.building_aed = null;
  }
  if (!Boolean(quoteForm.has_contents)) {
    quoteForm.contents_aed = null;
    quoteForm.has_personal_belongings = false;
  }
  if (!Boolean(quoteForm.has_personal_belongings)) {
    quoteForm.personal_belongings_aed = null;
  }
  if (quoteForm.has_contents || quoteForm.has_building) {
    hasContentOrBuilding.value = true;
  }
};

function successResponse() {
  notification.success({
    title: 'Quote updated successfully',
    position: 'top',
  });
  if (isEdit.value) {
    router.get(route('home-quotes-show', page.props?.quote?.uuid));
  } else {
    quoteForm.reset();
  }
}
function onSubmit(isValid) {
  if (quoteForm.location_area == null || quoteForm.location_area == '') {
    formFieldReq.location_area = true;
  } else {
    formFieldReq.location_area = false;
  }
  if (quoteForm.has_contents || quoteForm.has_building) {
    if (isValid) {
      if (isEdit.value) {
        quoteForm
          .transform(data => ({
            ...data,
            has_contents: data.has_contents ? true : false,
            has_personal_belongings: data.has_personal_belongings
              ? true
              : false,
            has_building: data.has_building ? true : false,
          }))
          .put(route('home-quotes-update', props.quote.uuid), {
            onError: errors => {
              console.log(errors);
            },
            onSuccess: () => {},
          });
      } else {
        quoteForm.post(route('home-quotes-store'), {
          onError: errors => {
            quoteForm.setError(errors);
          },
          onSuccess: () => {},
        });
      }
    }
  } else {
    hasContentOrBuilding.value = false;
  }
}

const formFieldReq = reactive({
  location_area: false,
});

const locationAreaOptions = computed(() => {
  return [
    { value: 'option1', label: 'Option 1' },
    { value: 'option2', label: 'Option 2' },
  ];
});
</script>

<template>
  <div>
    <Head :title="isEdit ? 'Edit Home' : 'Create Home'" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        {{ isEdit ? 'Edit' : 'Create' }} Home
      </h2>
      <div class="space-x-4">
        <Link
          v-if="$page.props?.quote?.uuid"
          :href="route('home.show', $page.props?.quote?.uuid)"
        >
          <x-button size="sm" tag="div"> Cancel </x-button>
        </Link>
        <Link :href="route('home-quotes-list')">
          <x-button size="sm" color="#ff5e00" tag="div"> Home List </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 gap-4">
        <x-field label="FIRST NAME" required>
          <x-input
            v-model="quoteForm.first_name"
            type="text"
            :rules="[isRequired]"
            class="w-full"
            maxLength="20"
            :error="quoteForm.errors.first_name"
          />
        </x-field>
        <x-field label="LAST NAME" required>
          <x-input
            v-model="quoteForm.last_name"
            type="text"
            maxLength="50"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.last_name"
          />
        </x-field>
        <x-field label="EMAIL" required>
          <x-input
            v-model="quoteForm.email"
            type="email"
            :disabled="isEdit"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm?.errors?.email"
          />
        </x-field>
        <x-field label="MOBILE NUMBER" required>
          <x-input
            v-model="quoteForm.mobile_no"
            type="tel"
            :disabled="isEdit"
            :rules="[isRequired, isMobileNo]"
            class="w-full"
            :error="quoteForm?.errors?.mobile_no"
          />
        </x-field>
        <x-field label="PRICE">
          <x-input v-model="quoteForm.premium" type="text" class="w-full" />
        </x-field>
        <x-field label="POLICY NUMBER">
          <x-input
            v-model="quoteForm.policy_number"
            type="text"
            maxLength="100"
            class="w-full"
          />
        </x-field>
        <x-field label="OWNERSHIP STATUS" required>
          <x-select
            v-model="quoteForm.ownership_status_possesion_type_id"
            :rules="[isRequired]"
            :options="
              dropdownSource.ownership_status_possesion_type_id.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            @change="handleConditionalFields"
            class="w-full"
          />
        </x-field>
        <x-field label="TYPE OF PROPERTY" required>
          <x-select
            v-model="quoteForm.type_of_property_accommodation_type_id"
            :rules="[isRequired]"
            :options="
              dropdownSource.type_of_property_accommodation_type_id.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            class="w-full"
          />
        </x-field>
        <x-field label="ADDRESS" required>
          <x-textarea
            v-model="quoteForm.address"
            type="text"
            maxLength="2000"
            class="w-full"
          />
        </x-field>

        <div class="grid grid-cols-2 gap-2">
          <x-field label="HAS CONTENTS" required>
            <x-checkbox
              v-model="quoteForm.has_contents"
              color="primary"
              @change="handleConditionalFields"
            />
          </x-field>
          <x-field
            label="HAS PERSONAL BELONGINGS"
            v-if="quoteForm.has_contents"
          >
            <x-checkbox
              v-model="quoteForm.has_personal_belongings"
              label=""
              color="primary"
              @change="handleConditionalFields"
            />
          </x-field>
          <x-field
            label="HAS BUILDING"
            v-if="
              quoteForm.ownership_status_possesion_type_id == homePossessionTypeEnum.LANDLORD
            "
          >
            <x-checkbox
              v-model="quoteForm.has_building"
              color="primary"
              @change="handleConditionalFields"
            />
          </x-field>

          <p v-if="!hasContentOrBuilding" class="text-sm text-red-500">
            Must be selected at least one of the above
          </p>
        </div>
        <x-field label="CONTENTS AED" v-if="quoteForm.has_contents" required>
          <x-input
            v-if="quoteForm.has_contents"
            v-model="quoteForm.contents_aed"
            type="number"
            class="w-full"
            :rules="[isRequired]"
          />
        </x-field>
        <x-field
          label="PERSONAL BELONGINGS AED"
          v-if="quoteForm.has_personal_belongings"
          required
        >
          <x-input
            v-model="quoteForm.personal_belongings_aed"
            type="number"
            class="w-full"
            :rules="[isRequired]"
          />
        </x-field>
        <x-field label="BUILDING AED" v-if="quoteForm.has_building" required>
          <x-input
            v-model="quoteForm.building_aed"
            type="number"
            class="w-full"
            :rules="[isRequired]"
          />
        </x-field>
        <x-field label="Location Area" required>
          <ComboBox
            v-model="quoteForm.location_area"
            :rules="[isRequired]"
            :single="true"
            :options="locationAreaOptions"
            class="w-full"
            :hasError="formFieldReq.location_area"
            :error="quoteForm.errors.location_area"
          />
        </x-field>

        <x-field label="ADDRESS">
            <div class="flex flex-wrap -mx-2">
              <div class="w-1/2 px-2">
                <x-input
                  type="text"
                  v-model="quoteForm.addressObj.villa_apartment_office_no"
                  placeholder="Villa/Apartment/Office No."
                  class="w-full"
                />
              </div>
              <div class="w-1/2 px-2">
                <x-input
                  type="text"
                  v-model="quoteForm.addressObj.floor_no"
                  placeholder="Floor No."
                  class="w-full"
                />
              </div>
              <div class="w-1/2 px-2">
                <x-input
                  type="text"
                  v-model="quoteForm.addressObj.villa_building_name"
                  placeholder="Villa/Building Name"
                  class="w-full"
                />
              </div>
              <div class="w-1/2 px-2">
                <x-input
                  type="text"
                  v-model="quoteForm.addressObj.street_name"
                  placeholder="Street"
                  class="w-full"
                />
              </div>
              <div class="w-1/2 px-2">
                <x-input
                  type="text"
                  v-model="quoteForm.addressObj.area"
                  placeholder="Area"
                  class="w-full"
                />
              </div>
              <div class="w-1/2 px-2">
                <x-input
                  type="text"
                  v-model="quoteForm.addressObj.city"
                  placeholder="City"
                  class="w-full"
                />
              </div>
              <div class="w-1/2 px-2">
                <x-input
                  type="text"
                  v-model="quoteForm.addressObj.landmark"
                  placeholder="Landmark"
                  class="w-full"
                />
              </div>
            </div>
          </x-field>

      </div>
      <x-divider class="my-4" />
      <div class="flex justify-end gap-3 mb-4">
        <x-button
          size="md"
          color="emerald"
          type="submit"
          :loading="quoteForm.processing"
        >
          {{ isEdit ? 'Update' : 'Create' }}
        </x-button>
      </div>
    </x-form>
  </div>
</template>
