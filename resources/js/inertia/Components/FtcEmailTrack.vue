<script setup>
const props = defineProps({
  type: {
    required: false,
    type: String,
  },
  id: {
    required: true,
    type: [String, Number],
  },
  quoteType: {
    required: false,
    type: String,
  },
  quoteCode: {
    required: false,
    type: String,
  },
  expanded: {
    required: false,
    type: Boolean,
    default: true,
  },
});

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY h:mm:ss a');

const ftcEmailTracks = reactive({
  loading: false,
  data: null,
  table: [
    { text: 'Id', value: 'id' },
    { text: 'Email Subject', value: 'subject' },
    { text: 'Email Address', value: 'email' },
    { text: 'Status', value: 'status' },
    { text: 'Link', value: 'link' },
    { text: 'Created At', value: 'created_at' },
    { text: 'Updated At', value: 'updated_at' },
    { text: 'Action', value: 'action' },
  ],
});

const onLoadFtcEmailTrackData = async () => {
  const updatedQuery = new URLSearchParams();
  updatedQuery.append('quoteTrackableType', props.quoteType);
  updatedQuery.append('quoteTrackableId', props.id);
  updatedQuery.append('code', props.quoteCode);
  
  ftcEmailTracks.loading = true;


  let url = props.url ?? '/ftc/email-tracks?'+ updatedQuery.toString();

  axios
    .get(url)
    .then(res => {
      ftcEmailTracks.data = res.data;
    })
    .catch(err => {
      console.log(err);
    })
    .finally(() => {
      ftcEmailTracks.loading = false;
    });
};

onMounted(() => {
  onLoadFtcEmailTrackData();
});
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="expanded">
      <template #header>
        <div>
          <h3 class="font-semibold text-primary-800 text-lg">FTC Tracking</h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <DataTable
          table-class-name="compact"
          :headers="ftcEmailTracks.table"
          :items="ftcEmailTracks.data || []"
          border-cell
          hide-rows-per-page
          :rows-per-page="15"
          :hide-footer="ftcEmailTracks?.data?.length < 15"
        >
          <template #item-created_at="{ created_at }">
            {{ dateFormat(created_at).value }}
          </template>
          <template #item-updated_at="{ updated_at }">
            {{ dateFormat(updated_at).value }}
          </template>
          <template #item-action="{ action }">
                
          </template>
        </DataTable>
      </template>
    </Collapsible>
  </div>
</template>
