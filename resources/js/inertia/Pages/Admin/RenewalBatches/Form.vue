<script setup>
import { computed } from 'vue';

const props = defineProps({
  teams: Array,
  volumeSegmentAdvisorsId: Array,
  valueSegmentAdvisorsId: Array,
  lastBatchSlabs: Object,
  carAdvisors: Array,
  slabs: Array,
  carSoldDeadline: Date(),
  uncontactableDeadline: Date(),
  quoteStatus: Object,
  carSoldDeadline: String,
  renewalBatch: Object,
});

const page = usePage();
const permissionEnum = page.props.permissionsEnum;
const notification = useToast();

const { isRequired } = useRules();
const isSagmentVolumeEmpty = ref(false);
const isSagmentValueEmpty = ref(false);

const isModalOpen = ref(false);

const isEdit = computed(() => {
  return route().current().includes('edit');
});

const tableHeader = computed(() => {
  let data = props.slabs.map(x => {
    return {
      text: x.title,
      value: x.title.split(' ').join('').toLowerCase(),
    };
  });

  return [{ text: 'Teams', value: 'name' }, ...data];
});

const generateSlabArray = () => {
  let slabs = {};
  props.teams.forEach(team => {
    props.slabs.forEach(slab => {
      if (team.slabs_count > 0) {
        if (!slabs[slab.id]) {
          slabs[slab.id] = {};
        }
        if (props.lastBatchSlabs[slab.id][team.id]) {
          let minValue = props.lastBatchSlabs[slab.id][team.id]['pivot']['min'];
          let maxValue = props.lastBatchSlabs[slab.id][team.id]['pivot']['max'];
          if (minValue && maxValue) {
            slabs[slab.id][team.id] = {
              Min: +minValue,
              Max: +maxValue,
            };
          }
        } else {
          batchForm.optional_slabs.push(slab.id);
          batchForm.optional_teams.push(team.id);
        }
      }
    });
  });
  return slabs;
};

const dynamicKey = `deadline_date[${props.quoteStatus.CarSold.toString()}]`;
const batchForm = useForm({
  id: props?.renewalBatch?.id ?? null,
  name: props?.renewalBatch?.name ?? null,
  start_date: props?.renewalBatch?.start_date ?? null,
  end_date: props?.renewalBatch?.end_date ?? null,
  dead_date: props.carSoldDeadline ?? null,
  batchMonth: +props?.renewalBatch?.month
    ? { month: props?.renewalBatch.month - 1, year: props?.renewalBatch?.year }
    : null,
  slab: props.lastBatchSlabs,
  segment_volume: props.volumeSegmentAdvisorsId ?? [],
  segment_value: props.valueSegmentAdvisorsId ?? [],
  optional_slabs: [],
  optional_teams: [],
  quote_status_id: [props.quoteStatus.CarSold],
  deadline_date: [],
  month: '',
  year: props?.renewalBatch?.year ?? null,
});
const generateDeadlineDate = () => {
  let data = {
    [props.quoteStatus.CarSold]: batchForm.dead_date,
  };
  return Object.entries(data)
    .filter(([_, value]) => value !== undefined)
    .reduce((acc, [key, value]) => ({ ...acc, [key]: value }), {});
};

const setBatchMonth = () => {
  return batchForm.batchMonth.month.toString()
    ? batchForm.batchMonth.month + 1
    : batchForm.batchMonth;
};

const setBatchYear = () => {
  return batchForm.batchMonth.year;
};

function onSubmit(isValid) {
  let valid = validateSlabs();

  if (!isValid || !valid) return;

  batchForm.clearErrors();
  batchForm.month = setBatchMonth();
  batchForm.year = setBatchYear();
  batchForm.transform(data => ({
    ...data,
    [dynamicKey]: data.dead_date,
    slab: generateSlabArray(),
    deadline_date: generateDeadlineDate(),
  }));

  const method = isEdit.value ? 'put' : 'post';
  const url = isEdit.value
    ? route('renewal-batches-update', props.renewalBatch?.id)
    : route('renewal-batches-store');

  const options = {
    onError: errors => {
      Object.keys(errors).forEach(function (key) {
        notification.error({
          title: errors[key],
          position: 'top',
        });
      });
    },
  };

  batchForm.submit(method, url, options);
}

const validateSlabs = () => {
  let isValid = true;

  props.teams.forEach((team, index) => {
    for (let slabIndex = 0; slabIndex < props.teams.length - 1; slabIndex++) {
      const currentSlab = props.lastBatchSlabs[slabIndex]?.[team.id]?.pivot;
      const nextSlab = props.lastBatchSlabs[slabIndex + 1]?.[team.id]?.pivot;

      if (currentSlab && nextSlab) {
        if (currentSlab.min > nextSlab.min) {
          notification.error({
            title: `Incorrect slab for ${team.name}`,
            message: 'Check the other teams slab for a min values',
            position: 'top',
          });
          isValid = false;
          break;
        }
        if (currentSlab.max > nextSlab.max) {
          notification.error({
            title: `Incorrect slab for ${team.name}`,
            message: 'Check the other teams slab for a max values',
            position: 'top',
          });
          isValid = false;
          break;
        }
      }
    }
  });

  return isValid;
};

const setInitialState = () => {
  props.teams.forEach((team, index) => {
    for (let key in props.lastBatchSlabs) {
      if (!props.lastBatchSlabs[key][team.id]) {
        props.lastBatchSlabs[key][team.id] = {};
        props.lastBatchSlabs[key][team.id]['pivot'] = {};
        props.lastBatchSlabs[key][team.id]['pivot']['min'] = null;
        props.lastBatchSlabs[key][team.id]['pivot']['max'] = null;
      }
    }
  });
};

onMounted(() => {
  setInitialState();
});
</script>
<template>
  <Head title="Renewal Batch Configs" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Renewal Batch Configs</h2>
    <Link :href="route('renewal-batches-list')">
      <x-button size="sm" color="#ff5e00" tag="div">
        Renewal Batches Lists
      </x-button>
    </Link>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <x-card class="rounded-lg">
      <div class="bg-primary rounded-t-lg p-3">
        <p class="text-xl text-white">Batch Details</p>
      </div>
      <div class="grid sm:grid-cols-2 gap-4 p-4">
        <x-input
          label="Batch Name"
          required
          v-model="batchForm.name"
          :rules="[isRequired]"
          class="w-full"
          placeholder="Batch Name"
        />

        <DatePicker
          label="Batch Month"
          required
          v-model="batchForm.batchMonth"
          :rules="[isRequired]"
          class="w-full"
          :monthPicker="true"
          placeholder="Batch Month"
          format="MMM-yyyy"
          :disableYear="true"
          teleport
        />

        <DatePicker
          label="Start Date"
          required
          v-model="batchForm.start_date"
          :rules="[isRequired]"
          class="w-full"
          placeholder="Start Date"
          teleport
        />

        <DatePicker
          label="End Date"
          required
          v-model="batchForm.end_date"
          :rules="[isRequired]"
          class="w-full"
          placeholder="End Date"
          teleport
        />
      </div>
    </x-card>
    <x-card class="rounded-lg mt-5">
      <div class="bg-primary rounded-t-lg p-3">
        <p class="text-xl text-white">Renewal Batch DeadLines</p>
      </div>
      <div class="grid sm:grid-cols-2 gap-4 p-4">
        <DatePicker
          v-model="batchForm.dead_date"
          :rules="[isRequired]"
          class="w-full"
          placeholder="Car Sold Deadline"
          teleport
          label="Car Sold Deadline"
          required
        />
      </div>
    </x-card>
    <x-card class="rounded-lg mt-5">
      <div class="bg-primary rounded-t-lg p-3">
        <p class="text-xl text-white">Teamwise Slabs</p>
      </div>
      <div class="p-4">
        <table class="w-full border-collapse border rounded">
          <thead>
            <tr class="border">
              <th scope="col" class="p-3 border">Team</th>
              <th
                class="border"
                scope="col"
                v-for="slab in slabs"
                :key="slab.title"
              >
                {{ slab.title }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr class="border" v-for="team in teams" :key="team.id">
              <th class="w-40">
                {{ team.name
                }}<span class="text-red-600 text-sm font-medium">*</span>
              </th>
              <td class="border" v-for="(slab, index) in slabs" :key="slab.id">
                <div
                  class="flex gap-2 items-center mt-3 px-2"
                  v-if="
                    index < team.slabs_count && lastBatchSlabs[slab.id][team.id]
                  "
                >
                  <x-input
                    v-model="lastBatchSlabs[slab.id][team.id]['pivot']['min']"
                    :rules="[isRequired]"
                    class="w-full mb-0"
                    placeholder="Min"
                    type="number"
                    step="0.01"
                  />
                  <x-input
                    v-model="lastBatchSlabs[slab.id][team.id]['pivot']['max']"
                    :rules="[isRequired]"
                    class="w-full mb-0"
                    placeholder="Max"
                    type="number"
                    step="0.01"
                  />
                </div>

                <div v-else class="text-center">
                  <h4>Not Applicable</h4>
                  <input
                    type="hidden"
                    name="optional_slabs[]"
                    :value="slab.id"
                  />
                  <input
                    type="hidden"
                    name="optional_teams[]"
                    :value="team.id"
                  />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </x-card>
    <x-card class="rounded-lg mt-5">
      <div class="bg-primary rounded-t-lg p-3">
        <p class="text-xl text-white">Segments</p>
      </div>
      <div class="p-4">
        <table class="w-full border-collapse border rounded-lg">
          <thead class="bg-primary text-white">
            <tr class="border bg-primary text-white">
              <th scope="col" class="border py-1 px-2">Segment Type</th>
              <th scope="col" class="border py-1 px-2">
                Segment Volume <span class="text-red-600">*</span>
              </th>
              <th scope="col" class="border py-1 px-2">
                Segment Value <span class="text-red-600">*</span>
              </th>
              <th scope="col" class="border py-1 px-2">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr class="border">
              <td class="p-3 border font-medium text-center">Advisors</td>
              <td class="p-3 border text-center">
                <div v-for="value in batchForm.segment_volume">
                  <span class="text-sm">{{
                    carAdvisors.find(item => item.id === value).name
                  }}</span>
                </div>
                <!-- <x-select
                  v-model="batchForm.segment_volume"
                  name="segment_volume"
                  placeholder="Please select Segment Volume"
                  :options="
                    carAdvisors.map(item => ({
                      value: item.id,
                      label: item.name,
                    }))
                  "
                  filterable
                  filterPlaceholder="Filter Segment Volume...."
                  multiple
                  truncate
                  required
                  :rules="[isRequired]"
                >
                  <template #content-footer>
                    <ui-select-actions
                      @select-all="
                        batchForm.segment_volume = carAdvisors.map(
                          item => item.id,
                        )
                      "
                      @clear="batchForm.segment_volume = []"
                    />
                  </template>
                </x-select> -->
              </td>
              <td class="p-3 border text-center">
                <div v-for="value in batchForm.segment_value">
                  <span class="text-sm">{{
                    carAdvisors.find(item => item.id === value).name
                  }}</span>
                </div>
                <!-- <x-select
                  v-model="batchForm.segment_value"
                  name="segment_value"
                  placeholder="Please select Segment Value"
                  :options="
                    carAdvisors.map(item => ({
                      value: item.id,
                      label: item.name,
                    }))
                  "
                  filterable
                  filterPlaceholder="Filter Segment Value...."
                  multiple
                  truncate
                  required
                  :rules="[isRequired]"
                >
                  <template #content-footer>
                    <ui-select-actions
                      @select-all="
                        batchForm.segment_value = carAdvisors.map(
                          item => item.id,
                        )
                      "
                      @clear="batchForm.segment_value = []"
                    />
                  </template>
                </x-select> -->
              </td>
              <td class="p-3 border text-center">
                <x-button
                  size="sm"
                  color="primary"
                  tag="div"
                  outlined
                  @click="isModalOpen = true"
                >
                  Edit
                </x-button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </x-card>
    <x-divider class="my-4" />
    <div class="flex justify-end gap-3 mb-4">
      <x-button
        class="mt-5"
        size="md"
        color="emerald"
        type="submit"
        :disabled="batchForm.processing"
        :loading="batchForm.processing"
        processing="Saving..."
      >
        {{ isEdit ? 'Update' : 'Create' }}
      </x-button>
    </div>
  </x-form>
  <x-modal
    v-model="isModalOpen"
    showHeader
    title="Edit Segments"
    persistent
    @close="isModalOpen = false"
    show-close
  >
    <div class="grid grid-cols-2 gap-4">
      <x-select
        label="Segment Volume"
        v-model="batchForm.segment_volume"
        name="segment_volume"
        placeholder="Please select Segment Volume"
        :options="
          carAdvisors.map(item => ({ value: item.id, label: item.name }))
        "
        filterable
        multiple
        truncate
        required
        :rules="[isRequired]"
      />
      <x-select
        label="Segment Value"
        v-model="batchForm.segment_value"
        name="segment_value"
        placeholder="Please select Segment Value"
        :options="
          carAdvisors.map(item => ({ value: item.id, label: item.name }))
        "
        filterable
        multiple
        truncate
        required
        :rules="[isRequired]"
      />
    </div>
    <div class="flex justify-end gap-3 mt-3 w-full">
      <x-button
        class="w-full"
        size="md"
        color="primary"
        @click="isModalOpen = false"
      >
        Add
      </x-button>
    </div>
  </x-modal>
</template>
