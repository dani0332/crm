<script setup>
const props = defineProps({
  quoteStatusEnum: Object,
  quoteTypeId: String,
  lostReasons: Object,
  quoteType: String,
});
const page = usePage();
const dateFormat = date => {
  return useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value;
};

provide('quoteType', 'Travel');

const quotes = reactive({
  data: page.props.quotes || [],
  loader: false,
  searching: false,
  pages: {},
  queries: {},
});

provide('quoteStatusEnum', props.quoteStatusEnum);
provide('quoteTypeId', props.quoteTypeId);
provide('lostReasons', props.lostReasons);

const onLoadMore = id => {
  quotes.loader = true;
  quotes.pages = {
    ...quotes.pages,
    [id]: quotes.pages[id] ? Number(quotes.pages[id]) + 1 : 2,
  };
  axios
    .post(
      route('loadMoreRecords', {
        page: quotes.pages[id],
        modelType: 'Travel',
        status: id,
      }),
    )
    .then(({ data }) => {
      quotes.data = quotes.data.map(quote => {
        if (quote.id === id) {
          quote.data.leads_list = {
            ...data.leads_list,
            data: quote.data.leads_list.data.concat(data.leads_list.data),
          };
        }
        return quote;
      });
    })
    .catch(err => {
      console.log(err);
    })
    .finally(() => {
      quotes.loader = false;
    });
};

const onSearch = id => {
  quotes.searching = true;
  if (
    !quotes.queries[id] ||
    quotes.queries[id] === '' ||
    quotes.queries[id] === null
  ) {
    axios
      .post(
        route('loadMoreRecords', {
          page: 1,
          modelType: 'Travel',
          status: id,
        }),
      )
      .then(({ data }) => {
        quotes.data = quotes.data.map(quote => {
          if (quote.id === id) {
            quote.data.leads_list = data.leads_list;
          }
          return quote;
        });
      })
      .catch(err => {
        console.log(err);
      })
      .finally(() => {
        quotes.searching = false;
      });
    return;
  }
  axios
    .post(
      route('searchLead', {
        term: quotes.queries[id],
        status: id,
        modelType: 'Travel',
      }),
    )
    .then(({ data }) => {
      quotes.data = quotes.data.map(quote => {
        if (quote.id === id) {
          quote.data.leads_list = {
            ...data.leads_list,
            next_page_url: null,
            data: data.leads_list,
          };
        }
        return quote;
      });
    })
    .catch(err => {
      console.log(err);
    })
    .finally(() => {
      quotes.searching = false;
    });
};

onMounted(() => {
  console.log(quotes.data);
});
</script>

<template>
  <div>
    <Head title="Travel List ~ Card View" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Travel List</h2>
      <div class="space-x-3">
        <Link :href="route('travel.index')">
          <x-button size="sm" color="#1d83bc"> List View </x-button>
        </Link>

        <Link :href="route('travel.create')">
          <x-button size="sm" color="#ff5e00" tag="div"> Create Lead </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <div
      v-if="quotes.data.length > 0"
      class="flex w-full h-[85vh] space-x-4 overflow-auto"
    >
      <LeadsCard
        class="flex flex-col flex-shrink-0 w-64 bg-gray-200 border border-gray-300"
        v-for="quote in quotes.data"
        :key="quote.id"
        :quote="quote"
        :quotes="quotes"
        :quoteType="quoteType"
        :quoteTypeId="quoteTypeId"
        :lostReasons="props.lostReasons"
        :quoteStatusEnum="props.quoteStatusEnum"
      />
      <!-- <div
        v-for="quote in quotes.data"
        :key="quote.id"
        class="flex flex-col flex-shrink-0 w-64 bg-gray-200 border border-gray-300"
      >
        <div
          class="flex flex-col flex-shrink-0 gap-1.5 p-3 border-b border-gray-300 bg-white text-xs"
        >
          <h4 class="font-semibold text-sm">{{ quote.text }}</h4>
          <div class="flex justify-between gap-1">
            <span>Total Leads</span>
            <span>{{ quote.data.total_leads }}</span>
          </div>
          <div class="flex justify-between gap-1">
            <span>Total Premium</span>
            <span>{{ Number(quote.data.total_premium).toLocaleString() }}</span>
          </div>
          <div>
            <x-input
              v-model="quotes.queries[quote.id]"
              type="search"
              size="xs"
              class="w-full"
              placeholder="Search"
              @change.prevent="onSearch(quote.id)"
              :disabled="quotes.searching"
            />
          </div>
        </div>
        <div class="flex flex-col px-2 pb-2 overflow-auto">
          <div
            v-if="quotes.queries[quote.id] && quotes.searching"
            class="text-center p-4"
          >
            <x-spinner class="text-primary-500" />
          </div>
          <div
            v-if="quote.data.leads_list.data == 0 && quote.data.total_leads > 0"
            class="text-center text-xs text-gray-800 p-4"
          >
            <x-icon icon="box" class="text-secondary-600 mb-2" />
            <p>No Leads Found</p>
          </div>
          <a
            v-for="{
              id,
              uuid,
              code,
              first_name,
              last_name,
              premium,
              updated_at,
              company_name,
            } in quote.data.leads_list.data"
            :key="id"
            :href="route('travel.show', uuid)"
            target="_blank"
            title="View Lead"
            class="block p-3 mt-2 border border-gray-300 bg-white space-y-2 hover:transition hover:border-primary-500 rounded"
          >
            <div class="font-semibold text-sm">{{ code }}</div>
            <div class="flex items-center gap-2">
              <x-icon icon="person" size="sm" class="text-primary-400" />
              <p class="text-xs">{{ first_name }} {{ last_name }}</p>
            </div>

            <div v-if="company_name" class="flex items-center gap-2">
              <x-icon icon="company" size="sm" class="text-primary-400" />
              <p class="text-xs">{{ company_name }}</p>
            </div>

            <div class="flex items-center gap-2">
              <x-icon icon="money" size="sm" class="text-primary-400" />
              <p class="text-xs">{{ Number(premium).toLocaleString() }}</p>
            </div>

            <div class="flex items-center gap-2">
              <x-icon icon="calendar" size="sm" class="text-primary-400" />
              <p class="text-xs">{{ updated_at }}</p>
            </div>
          </a>

          <div
            class="mt-3"
            v-if="
              quote.data.total_leads > 0 &&
              quote.data.leads_list.next_page_url !== null
            "
          >
            <x-button
              size="xs"
              color="#1d83bc"
              class="w-full"
              outlined
              @click.prevent="onLoadMore(quote.id)"
              :disabled="quotes.loader"
              :loading="quotes.loader"
            >
              Load More
            </x-button>
          </div>
        </div>
      </div> -->
    </div>
  </div>
</template>
