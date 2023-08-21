<script setup>
const props = defineProps({
  dropdownSource: Object,
  genderOptions: Object,
  fields: Object,
    quote:Object,
    travelers: Array,
});

const genderSelect = computed(() => {
  return Object.keys(props.genderOptions).map(status => ({
    value: status,
    label: props.genderOptions[status],
  }));
});

    const formFields = computed(() => {
  return Object.keys(props.fields).map(key => ({
    value: key,
    label: props.fields[key].label,
  }));
});

/*
const quoteForm = useForm({
  ...formFields.value.reduce((acc, field) => {
    acc[field.value] = '';
    return acc;
  }, {}),
}); */

const quoteForm = useForm({
    first_name: props.quote?.first_name || null,
    last_name: props.quote?.last_name || null,
    email: props.quote?.email || null,
    direction_code:props.quote?.direction_code || null,
    has_arrived_uae:props.quote?.has_arrived_uae?.toString() || null,
    has_arrived_destination: props.quote?.has_arrived_destination?.toString() || null,
    coverage_code:props.quote?.coverage_code || null,
    uuid:props.quote?.uuid || null,
    mobile_no: props.quote?.mobile_no || null,
    nationality_id: props.quote?.nationality_id || null,
    start_date: props.quote?.start_date || null,
    end_date: props.quote?.end_date || null,
    region_cover_for_id:props.quote?.region_cover_for_id?.toString() || null,
    premium: props.quote?.premium || null,
    policy_number: props.quote?.policy_number || null,
    iam_possesion_type_id: props.quote?.iam_possesion_type_id || null,
    ilivein_accommodation_type_id: props.quote?.ilivein_accommodation_type_id || null,
    members:[{ value: 'male', label: 'Male',primary:true }]


});

const rules = {
  isEmail: v =>
    /^\w+([.-]?\w+)*@\w+([.-]?\w+)*(\.\w{2,3})+$/.test(v) ||
    'E-mail must be valid',
  isRequired: v => !!v || 'This field is required',
  allowEmpty: v => true || 'This field is required',
};
const subTeamOptions = [
    { value: 'travelUaeInbound', label: 'To the UAE (Inbound)' },
    { value: 'travelUaeOutbound', label: 'Outside UAE (OutBound)' }
];
const alreadylived = [
    { value: '1', label: 'Yes' },
    { value: '0', label: 'No' }
];
const genderList = [
    { value: 'M', label: 'Male' },
    { value: 'F', label: 'Female' }
];
const inboundCoverageCode = [
    { value: 'singleTrip', label: 'Single Trip' },
    { value: 'multiTrip', label: 'Multi Trip' }
];
const outboundCoverageCode = [
    { value: 'singleTrip', label: 'Single Trip' },
    { value: 'annualTrip', label: 'Annual Trip' }
];
const outboundRegions = [
    { value: '1', label: 'Worldwide (excl. US/Canada)' },
    { value: '2', label: 'Worldwide (incl. US/Canada)' },
    { value: '4', label: 'Schengen Countries' }
];

function addTravler(){
    quoteForm.members.push({ dob: '', gender: '' });
}
function removeMember(index) {
    quoteForm.members.splice(index, 1);
}

function onSubmit(isValid) {
  if (isValid) {
    quoteForm.post(`/quotes/travel`, {
      onError: errors => {},
      onStart: () => {
        quoteForm.clearErrors();
      },
    });
  }
}

function addUpdatedTraveller(){
    if(props.travelers) {
        let listing = Object.keys(props.travelers).map((key, item) => {
            if(props.quote.primary_member_id == props.travelers[key].id){
                console.log('inside primary');
                return {dob: props.travelers[key].dob, gender: props.travelers[key].gender,primary: true}
            }
            return {dob: props.travelers[key].dob, gender: props.travelers[key].gender}
        });
        quoteForm.members = listing;
    }
}

onMounted(() => {
    addUpdatedTraveller();
});
</script>

<template>
  <div>
    <Head title="Create Travel" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Create Travel</h2>
      <div>
        <Link href="/quotes/travel">
          <x-button size="sm" color="#ff5e00" tag="div"> Travel List </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 gap-4">
          <x-field label="Traveling Where?" required>
              <x-select
                  v-model="quoteForm.direction_code"
                  :options="subTeamOptions"
                  class="w-full"
                  :rules="[rules.isRequired]"

              />
          </x-field>
          <x-field v-if="quoteForm.direction_code == 'travelUaeInbound'" :label="'Have you already arrived in UAE?'" required>
              <x-select
                  v-model="quoteForm.has_arrived_uae"
                  :options="alreadylived"
                  class="w-full"
                  :rules="[rules.isRequired]"
              />
          </x-field>
          <x-field v-else :label="'Have you already arrived at your destination?'" required>
              <x-select
                  v-model="quoteForm.has_arrived_destination"
                  :options="alreadylived"
                  class="w-full"
                  :rules="[rules.isRequired]"
              />
          </x-field>
      </div>
        <div class="grid sm:grid-cols-2 gap-4" v-if="quoteForm.has_arrived_uae=='0' || quoteForm.has_arrived_destination == '0'">
            <x-field label="Travel Coverage" required>
                <x-select
                    v-model="quoteForm.coverage_code"
                    :options="quoteForm.direction_code == 'travelUaeInbound'?inboundCoverageCode:outboundCoverageCode"
                    class="w-full"
                    :rules="[rules.isRequired]"
                />
            </x-field>
            <x-field label="Which regions do you need cover for?*" v-if="quoteForm.has_arrived_destination=='0' && quoteForm.direction_code =='travelUaeOutbound'" required>
                <x-select
                    v-model="quoteForm.region_cover_for_id"
                    :options="outboundRegions"
                    :rules="[rules.isRequired]"
                    class="w-full"
                />
            </x-field>
          <x-field label="Travel Start Date" required >
          <DatePicker
              v-model="quoteForm.start_date"
              name="created_at_start"
          />
          </x-field>
          <x-field v-if="quoteForm.coverage_code == 'singleTrip'" label="Travel End Date" required >
              <DatePicker
                  v-model="quoteForm.end_date"
                  name="end_date"
                  :rules="[rules.isRequired]"
              />
          </x-field>
        </div>
        <div class="grid sm:grid-cols-2 gap-4">

        <x-field label="First Name" required>

              <x-input
                  v-model="quoteForm.first_name"
                  :rules="[rules.isRequired]"
                  class="w-full"
              />
          </x-field>
          <x-field label="Last Name" required>
              <x-input
                  v-model="quoteForm.last_name"
                  :rules="[rules.isRequired]"
                  class="w-full"
              />
          </x-field>

          <x-field label="Nationality" required>
              <ComboBox
                  v-model="quoteForm.nationality_id"
                  :options="
                    fields.nationality_id.options.map(option => ({
                      value: option.id,
                      label: option.text,
                    }))
                  "
                  :single="true"
                  class="w-full"
                  :rules="[rules.isRequired]"
                  :hasError="quoteForm.errors[index]"
              />
          </x-field>
          <x-field label="Email" required>
              <x-input
                  v-model="quoteForm.email"
                  class="w-full"
                  :rules="[rules.isEmail]"
              />
          </x-field>
          <x-field label="Phone number" required>
              <x-input
                  v-model="quoteForm.mobile_no"
                  class="w-full"
                  :rules="[rules.isRequired]"
              />
          </x-field>
        </div>

        <div v-if="quoteForm.has_arrived_uae=='0' || quoteForm.has_arrived_destination=='0'" class="grid sm:grid-cols-3 gap-4" v-for="(travel,index) in quoteForm.members">
            <h2>{{travel.primary == true?'Primary Traveler':'Additional Traveler '+index }}</h2>
            <h2></h2>
            <h2></h2>
          <x-field label="Date of Birth" required >
              <DatePicker
                  v-model="travel.dob"
                  name="created_at_start"
                  :rules="[rules.isRequired]"
              />
          </x-field>
          <x-field label="Gender" required>
              <x-select
                  v-model="travel.gender"
                  placeholder="Gender"
                  :options="genderList"
                  :rules="[rules.isRequired]"
                  class="w-full"
              />
          </x-field>
            <div class="flex items-center justify-end">
            <x-button
                v-if="travel.primary != true"
                size="sm"
                outlined
                color="error"
                icon="xc"
                @click="removeMember(index)"
            />
            </div>

       <!-- -->
      </div>
        <x-button
            v-if="quoteForm.has_arrived_uae=='0' || quoteForm.has_arrived_destination == '0'"
            size="md"
            color="emerald"
            type="button"
            @click="addTravler()"
        >
            Add Traveler
        </x-button>
      <x-divider class="my-4" />
      <div class="flex justify-end gap-3 mb-4">
        <x-button
          size="md"
          color="emerald"
          type="submit"
          :loading="quoteForm.processing"
        >
          Create
        </x-button>
      </div>
    </x-form>
  </div>
</template>
